<?php declare(strict_types = 1);

namespace App\Components;

use App\Model\AnalyzerInput;
use App\Model\GitShaHex;
use Nette\Application\UI;


/**
 * @property-read UI\ITemplate $template
 */
class PlaygroundFormControl extends UI\Control
{
	/** @var PlaygroundFormFactory */
	private $formFactory;

	/** @var NULL|AnalyzerInput */
	private $defaultInput;

	/** @var callable (AnalyzerInput $input, bool $persist) */
	private $onSuccess;

	/** @var callable (array $errors) */
	private $onError;


	public function __construct(PlaygroundFormFactory $formFactory, ?AnalyzerInput $defaultInput, callable $onSuccess, callable $onError)
	{
		parent::__construct();
		$this->formFactory = $formFactory;
		$this->defaultInput = $defaultInput;
		$this->onSuccess = $onSuccess;
		$this->onError = $onError;
	}


	public function render(): void
	{
		$this->template->setFile(__DIR__ . '/PlaygroundFormControl.latte');
		$this->template->render();
	}


	protected function createComponentForm(): UI\Form
	{
		$form = $this->formFactory->create($this->defaultInput);

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
