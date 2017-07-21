<?php declare(strict_types = 1);

namespace App\Presenters;

use App\Model\AnalyzerInput;
use App\Model\AnalyzerOutput;
use App\Model\CodeValidator;
use App\Model\GitShaHex;
use App\Model\PhpStanAnalyzer;
use App\Model\PhpStanVersions;
use Latte\Runtime\Html;
use Nette\Application\UI;
use Nette\Neon\Neon;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;


class HomepagePresenter extends UI\Presenter
{
	/** @var CodeValidator */
	private $codeValidator;

	/** @var PhpStanVersions */
	private $versions;

	/** @var PhpStanAnalyzer */
	private $analyzer;

	/** @var AnsiToHtmlConverter */
	private $ansiToHtmlConverter;

	/** @var NULL|AnalyzerOutput */
	private $output;


	public function __construct(CodeValidator $codeValidator, PhpStanVersions $versions, PhpStanAnalyzer $analyzer, AnsiToHtmlConverter $ansiToHtmlConverter)
	{
		parent::__construct();
		$this->codeValidator = $codeValidator;
		$this->versions = $versions;
		$this->analyzer = $analyzer;
		$this->ansiToHtmlConverter = $ansiToHtmlConverter;
	}


	public function actionDefault(?string $inputHash = NULL)
	{
		if ($inputHash !== NULL) {
			$this->output = $this->analyzer->fetchOutput($inputHash);

			if ($this->output === NULL) {
				$this->error();
			}

			$version = (string) $this->output->getInput()->getPhpStanVersion();
			$versionItems = $this->versions->fetch();
			if (!isset($versionItems[$version])) {
				$versionItems[$version] = $version;
				$this['form']['version']->setItems($versionItems);
			}

			$this['form']->setDefaults([
				'phpCode' => $this->output->getInput()->getPhpCode(),
				'config' => $this->output->getInput()->getConfig(),
				'level' => $this->output->getInput()->getLevel(),
				'version' => $version,
			]);
		}
	}


	public function renderDefault()
	{
		if ($this->output !== NULL) {
			$ansiOutput = $this->output->getOutput();
			$htmlOutput = $this->ansiToHtmlConverter->convert($ansiOutput);
			$this->template->htmlOutput = new Html($htmlOutput);
		}
	}


	protected function createComponentForm(): UI\Form
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
			->setDefaultValue("parameters:\n\t");

		$form->addInteger('level')
			->setRequired()
			->addRule($form::MIN, 'Level must be non-negative integer', 0)
			->setDefaultValue(6);

		$versionItems = $this->versions->fetch();
		$form->addSelect('version', NULL, $versionItems)
			->setRequired()
			->setDefaultValue(array_search('master', $versionItems));

		$form->addSubmit('send');

		$form->onValidate[] = function (UI\Form $form, array $values): void {
			foreach ($this->codeValidator->validate($values['phpCode']) as $phpCodeError) {
				$form->addError($phpCodeError);
			}

			try {
				Neon::decode($values['config']);

			} catch (\Nette\Neon\Exception $e) {
				$form->addError(sprintf('Invalid config file: %s', $e->getMessage()));
			}
		};

		$form->onSuccess[] = function (UI\Form $form, array $values): void {
			$input = new AnalyzerInput(
				new GitShaHex($values['version']),
				$values['phpCode'],
				$values['level'],
				$values['config']
			);

			$this->analyzer->analyze($input);
			$this->redirect('this', ['inputHash' => $input->getHash()]);
		};

		$form->onError[] = function (UI\Form $form) {
			$this->output = NULL;
			$this->template->htmlOutput = implode("\n", $form->errors);
		};

		return $form;
	}
}
