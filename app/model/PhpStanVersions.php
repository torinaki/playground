<?php declare(strict_types = 1);

namespace App\Model;

use Milo\Github;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;


class PhpStanVersions
{
	/** @var Github\Api */
	private $githubApi;

	/** @var PhpStanInstaller */
	private $installer;

	/** @var string */
	private $dataPath;

	/** @var string[] */
	private $usersWhitelist;


	public function __construct(Github\Api $githubApi, PhpStanInstaller $installer, string $dataDir, array $usersWhitelist)
	{
		$this->githubApi = $githubApi;
		$this->installer = $installer;
		$this->dataPath = "$dataDir/versions.json";
		$this->usersWhitelist = $usersWhitelist;
	}


	/**
	 * @return string[] (shaHex => name)
	 */
	public function fetch(): array
	{
		if (!is_file($this->dataPath)) {
			return [];
		}

		$encoded = FileSystem::read($this->dataPath);
		$data = Json::decode($encoded, Json::FORCE_ARRAY);

		return $data;
	}


	public function refresh(): void
	{
		$versions = $this->fetchFromGitHub();
		$this->reinstall($versions);
		$this->persist($versions);
	}


	private function reinstall(array $versions): void
	{
		foreach ($versions as $shaHex => $refName) {
			$this->installer->install(new GitShaHex($shaHex));
		}
	}


	private function persist(array $versions): void
	{
		$encoded = Json::encode($versions, Json::PRETTY);
		FileSystem::write("{$this->dataPath}.tmp", $encoded);
		FileSystem::rename("{$this->dataPath}.tmp", $this->dataPath);
	}


	private function fetchFromGitHub(): array
	{
		$result = [];

		foreach (['heads', 'tags'] as $group) {
			$paginatedResponse = $this->githubApi->paginator('/repos/:owner/:repo/git/refs/:group', [
				'owner' => 'phpstan',
				'repo' => 'phpstan',
				'group' => $group,
				'per_page' => 100,
			]);

			foreach ($paginatedResponse as $response) {
				foreach ($this->githubApi->decode($response) as $item) {
					$result[$item->object->sha] = $this->getFriendlyRefName($item->ref);
				}
			}
		}

		$paginatedResponse = $this->githubApi->paginator('/repos/:owner/:repo/pulls', [
			'owner' => 'phpstan',
			'repo' => 'phpstan',
			'per_page' => 100,
		]);

		foreach ($paginatedResponse as $response) {
			foreach ($this->githubApi->decode($response) as $pr) {
				if (in_array($pr->head->user->login, $this->usersWhitelist, TRUE)) {
					$result[$pr->head->sha] = "#{$pr->number} ($pr->title)";
				}
			}
		}

		asort($result);
		return $result;
	}


	private function getFriendlyRefName(string $ref): string
	{
		if (Strings::startsWith($ref, 'refs/heads/')) {
			return Strings::after($ref, 'refs/heads/');

		} elseif (Strings::startsWith($ref, 'refs/tags/')) {
			return Strings::after($ref, 'refs/tags/');

		} else {
			return $ref;
		}
	}
}
