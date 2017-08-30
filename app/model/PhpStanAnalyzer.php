<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Symfony\Component\Process\Process;


class PhpStanAnalyzer
{
	/** @var CodeSanitizer */
	private $codeSanitizer;

	/** @var string */
	private $phpBin;

	/** @var string */
	private $phpStanDir;

	/** @var string */
	private $dataDir;


	public function __construct(CodeSanitizer $codeSanitizer, string $phpBin, string $phpStanDir, string $dataDir)
	{
		$this->codeSanitizer = $codeSanitizer;
		$this->phpBin = $phpBin;
		$this->phpStanDir = $phpStanDir;
		$this->dataDir = $dataDir;
	}


	public function analyze(AnalyzerInput $input, bool $persist): AnalyzerOutput
	{
		$inputHash = $input->getHash();
		$resultDirPath = $this->getResultDirPath($inputHash);
		$inputFilePath = $this->getInputFilePath($resultDirPath);
		$outputFilePath = $this->getOutputFilePath($resultDirPath);

		if (is_file($outputFilePath)) {
			return $this->fetchOutputFile($inputFilePath, $outputFilePath);
		}

		$includedFilePath = $this->createIncludedFile($input, $resultDirPath);
		$configFilePath = $this->createConfigFile($input, $resultDirPath);
		$analyzedFilePath = $this->createAnalyzedFile($input, $resultDirPath);

		$commandLine = [
			$this->phpBin, '-ddisplay_errors=1',
			'phpstan', '--ansi', 'analyze',
			'--no-progress',
			'--level', $input->getLevel(),
			'--autoload-file', $includedFilePath,
			'--configuration', $configFilePath,
			$analyzedFilePath,
		];

		$binDir = $this->getPhpStanBinDir($input);
		$process = new Process($commandLine, $binDir);
		$process->setTimeout(10);
		$process->run();

		$output = $process->getOutput();
		$output = $this->clearPathFromOutput($output, dirname($binDir), '<PHPStan>');
		$output = $this->clearPathFromOutput($output, " in $includedFilePath", '');
		$output = $this->clearPathFromOutput($output, $resultDirPath, '');
		$output = new AnalyzerOutput($input, $output);

		if ($persist) {
			$this->createInputFile($input, $inputFilePath);
			$this->createOutputFile($output, $outputFilePath);

		} else {
			FileSystem::delete($resultDirPath);
		}

		return $output;
	}


	public function fetchInput(string $inputHash): ?AnalyzerInput
	{
		$resultDirPath = $this->getResultDirPath($inputHash);
		$inputFilePath = $this->getInputFilePath($resultDirPath);

		return is_file($inputFilePath)
			? $this->fetchInputFile($inputFilePath)
			: NULL;
	}


	public function fetchOutput(string $inputHash): ?AnalyzerOutput
	{
		$resultDirPath = $this->getResultDirPath($inputHash);
		$inputFilePath = $this->getInputFilePath($resultDirPath);
		$outputFilePath = $this->getOutputFilePath($resultDirPath);

		return is_file($outputFilePath)
			? $this->fetchOutputFile($inputFilePath, $outputFilePath)
			: NULL;
	}


	private function getResultDirPath(string $inputHash): string
	{
		$inputHashPrefix = substr($inputHash, 0, 2);
		return "{$this->dataDir}/results/{$inputHashPrefix}/{$inputHash}";
	}


	private function getInputFilePath(string $resultDirPath): string
	{
		return "$resultDirPath/input.json";
	}


	private function getOutputFilePath(string $resultDirPath): string
	{
		return "$resultDirPath/output.json";
	}


	private function createIncludedFile(AnalyzerInput $input, string $resultDirPath): string
	{
		$sanitizedCode = $this->codeSanitizer->sanitize($input->getPhpCode());
		$includedFilePath = "$resultDirPath/included.php";
		FileSystem::write($includedFilePath, $sanitizedCode);

		return realpath($includedFilePath);
	}


	private function createConfigFile(AnalyzerInput $input, string $resultDirPath): string
	{
		$configFilePath = "$resultDirPath/config.neon";
		FileSystem::write($configFilePath, $input->getConfig());

		return realpath($configFilePath);
	}


	private function createAnalyzedFile(AnalyzerInput $input, string $resultDirPath): string
	{
		$analyzedFilePath = "$resultDirPath/analyzed.php";
		FileSystem::write($analyzedFilePath, $input->getPhpCode());

		return realpath($analyzedFilePath);
	}


	private function getPhpStanBinDir(AnalyzerInput $input): string
	{
		$phpStanVersion = (string) $input->getPhpStanVersion();
		$prefix = substr($phpStanVersion, 0, 2);
		return realpath("{$this->phpStanDir}/{$prefix}/{$phpStanVersion}/bin");
	}


	private function createInputFile(AnalyzerInput $input, string $inputFilePath): string
	{
		$decodedInput = (object) [
			'phpStanVersion' => (string) $input->getPhpStanVersion(),
			'phpCode' => $input->getPhpCode(),
			'level' => $input->getLevel(),
			'config' => $input->getConfig(),
		];

		$encodedInput = Json::encode($decodedInput, Json::PRETTY);

		FileSystem::write("$inputFilePath.tmp", $encodedInput);
		FileSystem::rename("$inputFilePath.tmp", $inputFilePath);

		return $inputFilePath;
	}


	private function fetchInputFile(string $inputFilePath): AnalyzerInput
	{
		$encodedInput = FileSystem::read($inputFilePath);
		$decodedInput = Json::decode($encodedInput);

		$input = new AnalyzerInput(
			new GitShaHex($decodedInput->phpStanVersion),
			$decodedInput->phpCode,
			$decodedInput->level,
			$decodedInput->config
		);

		return $input;
	}


	private function createOutputFile(AnalyzerOutput $output, string $outputFilePath): string
	{
		$decodedOutput = (object) [
			'output' => $output->getOutput(),
		];

		$encodedOutput = Json::encode($decodedOutput, Json::PRETTY);

		FileSystem::write("$outputFilePath.tmp", $encodedOutput);
		FileSystem::rename("$outputFilePath.tmp", $outputFilePath);

		return $outputFilePath;
	}


	private function fetchOutputFile(string $inputFilePath, string $outputFilePath): AnalyzerOutput
	{
		$encodedOutput = FileSystem::read($outputFilePath);
		$decodedOutput = Json::decode($encodedOutput);

		$input = $this->fetchInputFile($inputFilePath);
		$output = new AnalyzerOutput($input, $decodedOutput->output);

		return $output;
	}


	private function clearPathFromOutput(string $output, string $path, string $replacement): string
	{
		$path = strtr($path, '\\', '/');
		$path = preg_quote($path, '/');
		$path = str_replace('\/', '[\\\\/]', $path);
		return preg_replace("#$path#i", $replacement, $output);
	}
}
