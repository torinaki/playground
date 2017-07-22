<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Neon\Neon;


class ConfigValidator
{
	/** @var string[] */
	private $topLevelKeysWhitelist;

	/** @var string[] */
	private $parameterKeysWhitelist;


	public function __construct(array $topLevelKeysWhitelist, array $parameterKeysWhitelist)
	{
		$this->topLevelKeysWhitelist = $topLevelKeysWhitelist;
		$this->parameterKeysWhitelist = $parameterKeysWhitelist;
	}


	/**
	 * @param  string $config
	 * @return string[]
	 */
	public function validate(string $config): array
	{
		$errors = [];

		try {
			$decoded = Neon::decode($config) ?? [];

			if (!is_array($decoded)) {
				$errors[] = 'Invalid config file: value must be array';
				return $errors;
			}

			$topLevelKeys = array_keys($decoded);
			$disallowedKeys = array_diff($topLevelKeys, $this->topLevelKeysWhitelist);
			foreach ($disallowedKeys as $disallowedKey) {
				$errors[] = sprintf('Invalid config file: top-level key \'%s\' is not supported.', $disallowedKey);
			}

			$parameterKeys = array_keys($decoded['parameters'] ?? []);
			$disallowedParameters = array_diff($parameterKeys, $this->parameterKeysWhitelist);
			foreach ($disallowedParameters as $disallowedParameter) {
				$errors[] = sprintf('Invalid config file: parameter \'%s\' is not supported.', $disallowedParameter);
			}

		} catch (\Nette\Neon\Exception $e) {
			$errors[] = sprintf('Config file parse error: %s', $e->getMessage());
		}

		return $errors;
	}
}
