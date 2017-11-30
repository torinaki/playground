<?php declare(strict_types = 1);

namespace App\Components;

use App\Model\AnalyzerInput;
use App\Model\CodeValidator;
use App\Model\ConfigValidator;
use App\Model\GitShaHex;
use App\Model\PhpStanInstaller;
use App\Model\PhpStanVersions;
use Nette\Application\UI;
use Nette\Forms\Controls\SelectBox;


class PlaygroundFormFactory
{
	/** @var CodeValidator */
	private $codeValidator;

	/** @var ConfigValidator */
	private $configValidator;

	/** @var PhpStanVersions */
	private $versions;

	/** @var PhpStanInstaller */
	private $installer;

	/** @var array */
	private $defaults;


	public function __construct(
		CodeValidator $codeValidator,
		ConfigValidator $configValidator,
		PhpStanVersions $versions,
		PhpStanInstaller $installer,
		array $defaults
	) {
		$this->codeValidator = $codeValidator;
		$this->configValidator = $configValidator;
		$this->versions = $versions;
		$this->installer = $installer;
		$this->defaults = $defaults;
	}


	public function create(?AnalyzerInput $defaultInput): UI\Form
	{
		$versionItems = $this->versions->fetch();

		$form = new UI\Form();

		$form->addTextArea('phpCode')
			->setRequired(FALSE);

		$form->addTextArea('config')
			->setRequired(FALSE);

		$form->addInteger('level')
			->setRequired('Please select valid PHPStan level')
			->addRule($form::MIN, 'Level must be non-negative integer', 0);

		$form->addSelect('version')
			->setItems($versionItems)
			->setRequired('Please select valid PHPStan version');

		$form->addSubmit('analyzeAndForget');

		$versionSelect = $form['version'];
		assert($versionSelect instanceof SelectBox);

		if ($defaultInput) {
			$shaHex = (string) $defaultInput->getPhpStanVersion();
			$this->extendVersionItems($versionSelect, $versionItems, $shaHex);
			$form->setDefaults([
				'phpCode' => $defaultInput->getPhpCode(),
				'config' => $defaultInput->getConfig(),
				'level' => $defaultInput->getLevel(),
				'version' => $shaHex,
			]);

		} else {
			$form->setDefaults($this->defaults);
		}

		$form->onAnchor[] = function () use ($versionSelect, $versionItems): void {
			$shaHex = (string) $versionSelect->getRawValue() ?? '';
			$this->extendVersionItems($versionSelect, $versionItems, $shaHex);
		};

		$form->onValidate[] = function (UI\Form $form, array $values): void {
			foreach ($this->codeValidator->validate($values['phpCode']) as $phpCodeError) {
				$form->addError($phpCodeError);
			}

			foreach ($this->configValidator->validate($values['config']) as $configError) {
				$form->addError($configError);
			}
		};

		$form->onSuccess[] = function (UI\Form $form): void {
			$form->addSubmit('analyzeAndPersist');
		};

		return $form;
	}


	private function extendVersionItems(SelectBox $versionSelect, array $versionItems, string $shaHex): void
	{
		if (!GitShaHex::isValid($shaHex)) {
			return;

		} elseif (isset($versionSelect->getItems()[$shaHex])) {
			return;

		} elseif (!$this->installer->isInstalled(new GitShaHex($shaHex))) {
			return;
		}

		$versionItems['Commits'] = [$shaHex => $shaHex];
		$versionSelect->setItems($versionItems);
	}
}
