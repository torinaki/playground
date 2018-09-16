<?php declare(strict_types = 1);

namespace App\Model;

use Composer;
use Milo\Github;
use Monolog\Logger;
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

	/** @var string */
	private $targetCacheDir;

	/** @var Logger */
	private $logger;


	public function __construct(
		Github\Api $githubApi,
		Logger $logger,
		string $tempDir,
		string $targetDir,
		string $targetCacheDir
	)
	{
		$this->githubApi = $githubApi;
		$this->logger = $logger;
		$this->tempDir = $tempDir;
		$this->targetDir = $targetDir;
		$this->targetCacheDir = $targetCacheDir;
	}


	public function isInstalled(GitShaHex $shaHex): bool
	{
		$targetPath = $this->getTargetPath($shaHex);
		return is_dir("$targetPath/vendor");
	}


	public function install(GitShaHex $shaHex): void
	{
		$targetPath = $this->getTargetPath($shaHex);

		if (!is_dir($targetPath)) {
			$this->logger->info(sprintf('Installing %s', (string) $shaHex));
			$zipPath = $this->getZipPath($shaHex);
			$this->downloadZipFile($shaHex, $zipPath);
			$this->extractZipFile($zipPath, $targetPath);
		}

		if (!is_dir("$targetPath/vendor")) {
			$this->installDependencies($targetPath);
		}

		$targetCachePath = sprintf('%s/%s', $this->targetCacheDir, $this->getPath($shaHex));
		if (!is_dir($targetCachePath)) {
			FileSystem::copy($targetPath, $targetCachePath);
		}
	}


	private function getZipPath(GitShaHex $shaHex): string
	{
		return "{$this->tempDir}/phpstan-{$shaHex}.zip";
	}


	private function getTargetPath(GitShaHex $shaHex): string
	{
		return sprintf('%s/%s', $this->targetDir, $this->getPath($shaHex));
	}

	private function getPath(GitShaHex $shaHex): string
	{
		return sprintf('%s/%s', substr((string) $shaHex, 0, 2), (string) $shaHex);
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
		$installer->setDumpAutoloader(true);
		$installer->setRunScripts(false);
		$installer->setOptimizeAutoloader(true);
		$installer->setClassMapAuthoritative(true);
		$installer->setPreferDist(true);
		$installer->setDevMode(false);

		$code = $installer->run();
		assert($code === 0);
	}
}
