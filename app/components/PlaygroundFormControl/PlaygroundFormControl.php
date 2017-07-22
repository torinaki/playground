<?php declare(strict_types = 1);

namespace App\Components;

use App\Model\AnalyzerInput;
use App\Model\GitShaHex;
use Nette\Application\UI;


class PlaygroundFormControl extends UI\Control
{
	/** @var PlaygroundFormFactory */
	private $formFactory;

	/** @var callable (AnalyzerInput $input, bool $persist) */
	private $onSuccess;

	/** @var callable (array $errors) */
	private $onError;


	public function __construct(PlaygroundFormFactory $formFactory, callable $onSuccess, callable $onError)
	{
		parent::__construct();
		$this->formFactory = $formFactory;
		$this->onSuccess = $onSuccess;
		$this->onError = $onError;
	}


	public function setDefaults(AnalyzerInput $input): void
	{
		$version = (string) $input->getPhpStanVersion();

		$versionInput = $this['form']['version'];
		$versionInput->setItems($versionInput->getItems() + [$version => $version]);

		$this['form']->setDefaults([
			'phpCode' => $input->getPhpCode(),
			'config' => $input->getConfig(),
			'level' => $input->getLevel(),
			'version' => $version,
		]);
	}


	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/PlaygroundFormControl.latte');
		$this->template->render();
	}


	protected function createComponentForm(): UI\Form
	{
		$form = $this->formFactory->create();

		$form->onSuccess[] = function (UI\Form $form, array $values): void {
			$input = new AnalyzerInput(
				new GitShaHex($values['version']),
				$values['phpCode'],
				$values['level'],
				$values['config']
			);

			$persist = $form['analyzeAndPersist']->isSubmittedBy();
			($this->onSuccess)($input, $persist);
		};

		$form->onError[] = function (UI\Form $form) {
			($this->onError)($form->getErrors());
		};

		return $form;
	}
}
