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
	private $localDataDir;

	/** @var string */
	private $remoteDataDir;


	public function __construct(
		CodeSanitizer $codeSanitizer,
		string $phpBin,
		string $phpStanDir,
		string $localDataDir,
		string $remoteDataDir
	)
	{
		$this->codeSanitizer = $codeSanitizer;
		$this->phpBin = $phpBin;
		$this->phpStanDir = $phpStanDir;
		$this->localDataDir = $localDataDir;
		$this->remoteDataDir = $remoteDataDir;
	}


	public function analyze(AnalyzerInput $input, bool $persist): AnalyzerOutput
	{
		$inputHash = $input->getHash();
		$remotePathPrefix = $this->getRemotePathPrefix($inputHash);
		$inputFilePath = $this->getInputFilePath($remotePathPrefix);
		$outputFilePath = $this->getOutputFilePath($remotePathPrefix);

		if (is_file($outputFilePath)) {
			return $this->fetchOutputFile($inputFilePath, $outputFilePath);
		}

		$localPathPrefix = $this->getLocalPathPrefix($inputHash);
		$includedFilePath = $this->createIncludedFile($input, $localPathPrefix);
		$configFilePath = $this->createConfigFile($input, $localPathPrefix);
		$analyzedFilePath = $this->createAnalyzedFile($input, $localPathPrefix);

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
		$process->setEnv(['COLUMNS' => '120']);
		$process->inheritEnvironmentVariables();
		$exitCode = $process->run();

		if ($exitCode > 127) {
			$processOutput = sprintf(
				"PHP process crashed with exit code %d: \n%s",
				$exitCode,
				$processOutput = $this->clearPathFromOutput($process->getOutput(), " in $includedFilePath", '')
			);
			$persist = FALSE;

		} else {
			$processOutput = $process->getOutput();
			$processOutput = $this->clearPathFromOutput($processOutput, dirname($binDir), '<PHPStan>');
			$processOutput = $this->clearPathFromOutput($processOutput, " in $includedFilePath", '');
			$processOutput = $this->clearPathFromOutput($processOutput, $localPathPrefix, '');
		}

		$output = new AnalyzerOutput($input, $processOutput);

		if ($persist) {
			$this->createInputFile($input, $inputFilePath);
			$this->createOutputFile($output, $outputFilePath);

		} else {
			FileSystem::delete($localPathPrefix);
		}

		return $output;
	}


	public function fetchInput(string $inputHash): ?AnalyzerInput
	{
		$remotePathPrefix = $this->getRemotePathPrefix($inputHash);
		$inputFilePath = $this->getInputFilePath($remotePathPrefix);

		return is_file($inputFilePath)
			? $this->fetchInputFile($inputFilePath)
			: NULL;
	}


	public function fetchOutput(string $inputHash): ?AnalyzerOutput
	{
		$remotePathPrefix = $this->getRemotePathPrefix($inputHash);
		$inputFilePath = $this->getInputFilePath($remotePathPrefix);
		$outputFilePath = $this->getOutputFilePath($remotePathPrefix);

		return is_file($outputFilePath)
			? $this->fetchOutputFile($inputFilePath, $outputFilePath)
			: NULL;
	}


	private function getLocalPathPrefix(string $inputHash): string
	{
		$inputHashPrefix = substr($inputHash, 0, 2);
		return "{$this->localDataDir}/results/{$inputHashPrefix}/{$inputHash}";
	}

	private function getRemotePathPrefix(string $inputHash): string
	{
		$inputHashPrefix = substr($inputHash, 0, 2);
		return "{$this->remoteDataDir}/results/{$inputHashPrefix}/{$inputHash}";
	}

	private function getInputFilePath(string $remotePathPrefix): string
	{
		return "$remotePathPrefix/input.json";
	}


	private function getOutputFilePath(string $remotePathPrefix): string
	{
		return "$remotePathPrefix/output.json";
	}


	private function createIncludedFile(AnalyzerInput $input, string $localPathPrefix): string
	{
		$sanitizedCode = $this->codeSanitizer->sanitize($input->getPhpCode());
		$includedFilePath = "$localPathPrefix/included.php";
		FileSystem::write($includedFilePath, $sanitizedCode);

		return realpath($includedFilePath);
	}


	private function createConfigFile(AnalyzerInput $input, string $localPathPrefix): string
	{
		$configFilePath = "$localPathPrefix/config.neon";
		FileSystem::write($configFilePath, $input->getConfig());

		return realpath($configFilePath);
	}


	private function createAnalyzedFile(AnalyzerInput $input, string $localPathPrefix): string
	{
		$analyzedFilePath = "$localPathPrefix/analyzed.php";
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

		file_put_contents($inputFilePath, $encodedInput);

		return $inputFilePath;
	}


	private function fetchInputFile(string $inputFilePath): AnalyzerInput
	{
		$encodedInput = file_get_contents($inputFilePath);
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

		file_put_contents($outputFilePath, $encodedOutput);

		return $outputFilePath;
	}


	private function fetchOutputFile(string $inputFilePath, string $outputFilePath): AnalyzerOutput
	{
		$encodedOutput = file_get_contents($outputFilePath);
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
