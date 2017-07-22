<?php declare(strict_types = 1);

namespace App\Components;

use App\Model\CodeValidator;
use App\Model\ConfigValidator;
use App\Model\PhpStanVersions;
use Nette\Application\UI;


class PlaygroundFormFactory
{
	/** @var CodeValidator */
	private $codeValidator;

	/** @var ConfigValidator */
	private $configValidator;

	/** @var PhpStanVersions */
	private $versions;


	public function __construct(
		CodeValidator $codeValidator,
		ConfigValidator $configValidator,
		PhpStanVersions $versions
	) {
		$this->codeValidator = $codeValidator;
		$this->configValidator = $configValidator;
		$this->versions = $versions;
	}


	public function create(): UI\Form
	{
		$form = new UI\Form();
		$form->addTextArea('phpCode')
			->setRequired()
			->setDefaultValue('<?php declare(strict_types = 1);

class HelloWorld
{
	public function sayHello(DateTimeImutable $date): void
	{
		echo \'Hello, \' . $date->format(\'j. n. Y\');
	}
}
');

		$form->addTextArea('config')
			->setRequired(FALSE)
			->setDefaultValue('parameters:
	polluteCatchScopeWithTryAssignments: false
	polluteScopeWithLoopInitialAssignments: false
	earlyTerminatingMethodCalls: []
	universalObjectCratesClasses: []
	ignoreErrors: []
');

		$form->addInteger('level')
			->setRequired()
			->addRule($form::MIN, 'Level must be non-negative integer', 0)
			->setDefaultValue(6);

		$versionItems = $this->versions->fetch();
		$masterShaHex = array_search('master', $versionItems);
		$form->addSelect('version', NULL, $versionItems)
			->setRequired()
			->setDefaultValue($masterShaHex);

		$form->addSubmit('send');

		$form->onValidate[] = function (UI\Form $form, array $values): void {
			foreach ($this->codeValidator->validate($values['phpCode']) as $phpCodeError) {
				$form->addError($phpCodeError);
			}

			foreach ($this->configValidator->validate($values['config']) as $configError) {
				$form->addError($configError);
			}
		};

		return $form;
	}
}
