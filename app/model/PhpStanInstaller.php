<?php declare(strict_types = 1);

namespace App\Model;

use Composer;
use Milo\Github;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;


class PhpStanInstaller
{
	/** @var Github\Api */
	private $githubApi;

	/** @var string */
	private $tempDir;

	/** @var string */
	private $targetDir;


	public function __construct(Github\Api $githubApi, string $tempDir, string $targetDir)
	{
		$this->githubApi = $githubApi;
		$this->tempDir = $tempDir;
		$this->targetDir = $targetDir;
	}


	public function install(GitShaHex $shaHex): void
	{
		$targetPath = $this->getTargetPath($shaHex);

		if (!is_dir($targetPath)) {
			$zipPath = $this->getZipPath($shaHex);
			$this->downloadZipFile($shaHex, $zipPath);
			$this->extractZipFile($zipPath, $targetPath);
		}

		if (!is_dir("$targetPath/vendor")) {
			$this->installDependencies($targetPath);
		}
	}


	private function getZipPath(GitShaHex $shaHex): string
	{
		return "{$this->tempDir}/phpstan-{$shaHex}.zip";
	}


	private function getTargetPath(GitShaHex $shaHex): string
	{
		$prefix = substr((string) $shaHex, 0, 2);
		return "{$this->targetDir}/{$prefix}/{$shaHex}";
	}


	private function downloadZipFile(GitShaHex $shaHex, string $zipPath): void
	{
		$response = $this->githubApi->get('/repos/:owner/:repo/:archive_format/:ref', [
			'owner' => 'phpstan',
			'repo' => 'phpstan',
			'archive_format' => 'zipball',
			'ref' => (string) $shaHex,
		]);

		$content = $response->getContent();
		FileSystem::write("$zipPath.tmp", $content);
		FileSystem::rename("$zipPath.tmp", $zipPath);
	}


	private function extractZipFile(string $zipPath, string $targetPath): void
	{
		$zip = new \ZipArchive();
		$zip->open($zipPath);
		$zip->extractTo("{$targetPath}.tmp");
		$topDirName = $zip->getNameIndex(0);
		$zip->close();

		FileSystem::rename("{$targetPath}.tmp/{$topDirName}", $targetPath);
		FileSystem::delete("{$targetPath}.tmp");
		FileSystem::delete($zipPath);
	}


	private function installDependencies(string $extractedPath): void
	{
		set_time_limit(0);
		ini_set('memory_limit', '2G');

		// workaround for AutoloadGenerator using getcwd()
		chdir($extractedPath);

		// workaround for "The requested package slevomat/coding-standard dev-php7#d4a1a9cd4e ..." error
		$composerJsonPath = "$extractedPath/composer.json";
		$composerJson = Json::decode(FileSystem::read($composerJsonPath), Json::FORCE_ARRAY);
		unset($composerJson['require-dev']);
		FileSystem::write($composerJsonPath, Json::encode($composerJson, Json::PRETTY));

		$io = new Composer\IO\NullIO();
		$composer = (new Composer\Factory)->createComposer($io, $composerJsonPath, TRUE, $extractedPath);

		$installer = Composer\Installer::create($io, $composer);
		$installer->setDumpAutoloader(TRUE);
		$installer->setRunScripts(FALSE);
		$installer->setOptimizeAutoloader(TRUE);

		$code = $installer->run();
		assert($code === 0);
	}
}
