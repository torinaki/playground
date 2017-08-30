<?php declare(strict_types = 1);

namespace App\Model;

use Composer\Semver\Comparator;
use Composer\Semver\VersionParser;
use Milo\Github;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use stdClass;


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

	/** @var string[] */
	private $versionsBlacklist;


	public function __construct(
		Github\Api $githubApi,
		PhpStanInstaller $installer,
		string $dataDir,
		array $usersWhitelist,
		array $versionsBlacklist
	) {
		$this->githubApi = $githubApi;
		$this->installer = $installer;
		$this->dataPath = "$dataDir/versions.json";
		$this->usersWhitelist = $usersWhitelist;
		$this->versionsBlacklist = $versionsBlacklist;
	}


	/**
	 * @return string[] (groupName => (shaHex => name))
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
		foreach ($versions as $groupName => $group) {
			foreach ($group as $shaHex => $refName) {
				$this->installer->install(new GitShaHex($shaHex));
			}
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
		$tags = $this->fetchTags();
		$branches = $this->fetchBranches();
		$pullRequests = $this->fetchPullRequests();

		return [
			'Tags' => array_diff($tags, $this->versionsBlacklist),
			'Branches' => array_diff($branches, $this->versionsBlacklist),
			'Pull Requests' => array_diff($pullRequests, $this->versionsBlacklist),
		];
	}


	private function fetchTags(): array
	{
		$result = $this->request('/repos/:owner/:repo/git/refs/tags', function (stdClass $item) {
			yield "{$item->object->sha}" => Strings::after($item->ref, 'refs/tags/');
		});

		$vp = new VersionParser();
		uasort($result, function (string $a, string $b) use ($vp): int {
			$an = $vp->normalize($a);
			$bn = $vp->normalize($b);

			if ($an === $bn) {
				return 0;

			} elseif (Comparator::lessThan($an, $bn)) {
				return +1;

			} else {
				return -1;
			}
		});

		return $result;
	}


	private function fetchBranches(): array
	{
		$result = $this->request('/repos/:owner/:repo/git/refs/heads', function (stdClass $item) {
			yield "{$item->object->sha}" => Strings::after($item->ref, 'refs/heads/');
		});

		asort($result);
		return $result;
	}


	private function fetchPullRequests(): array
	{
		$result = $this->request('/repos/:owner/:repo/pulls', function (stdClass $item) {
			if (in_array($item->head->user->login, $this->usersWhitelist, TRUE)) {
				yield "{$item->head->sha}" => "#{$item->number} ({$item->title})";
			}
		});

		asort($result);
		return $result;
	}


	private function request(string $urlPath, callable $process): array
	{
		$paginatedResponse = $this->githubApi->paginator($urlPath, [
			'owner' => 'phpstan',
			'repo' => 'phpstan',
			'per_page' => 100,
		]);

		$result = [];
		foreach ($paginatedResponse as $response) {
			foreach ($this->githubApi->decode($response) as $decoded) {
				foreach ($process($decoded) as $shaHex => $label) {
					$result[$shaHex] = $label;
				}
			}
		}

		return $result;
	}
}
