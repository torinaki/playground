<?php declare(strict_types = 1);

namespace App\Presenters;

use App\Components\PlaygroundFormControl;
use App\Components\PlaygroundFormControlFactory;
use App\Components\TerminalOutputControl;
use App\Components\TerminalOutputControlFactory;
use App\Model\AnalyzerInput;
use App\Model\AnalyzerOutput;
use App\Model\PhpStanAnalyzer;
use Nette\Application\UI;


class PlaygroundPresenter extends UI\Presenter
{
	/** @var PhpStanAnalyzer */
	private $analyzer;

	/** @var PlaygroundFormControlFactory */
	private $playgroundFormControlFactory;

	/** @var TerminalOutputControlFactory */
	private $terminalOutputControlFactory;

	/** @var NULL|AnalyzerOutput */
	private $output;


	public function __construct(
		PhpStanAnalyzer $analyzer,
		PlaygroundFormControlFactory $playgroundFormControlFactory,
		TerminalOutputControlFactory $terminalOutputControlFactory
	) {
		parent::__construct();
		$this->analyzer = $analyzer;
		$this->playgroundFormControlFactory = $playgroundFormControlFactory;
		$this->terminalOutputControlFactory = $terminalOutputControlFactory;
	}


	public function actionDefault(?string $inputHash = NULL): void
	{
		if ($inputHash !== NULL) {
			$this->output = $this->analyzer->fetchOutput($inputHash);

			if ($this->output === NULL) {
				$this->error();
			}

			$input = $this->output->getInput();
			$this['playgroundForm']->setDefaults($input);
		}
	}


	public function renderDefault(): void
	{
		if ($this->output !== NULL) {
			$ansiOutput = $this->output->getOutput();
			$this['terminalOutput']->writeRaw($ansiOutput);
		}
	}


	protected function createComponentPlaygroundForm(): PlaygroundFormControl
	{
		return $this->playgroundFormControlFactory->create(
			function (AnalyzerInput $input): void {
				$this->analyzer->analyze($input);
				$this->redirect('this', ['inputHash' => $input->getHash()]);
			},
			function (array $errors): void {
				$this['terminalOutput']->getStyle()->error($errors);
				$this->output = NULL;
			}
		);
	}


	protected function createComponentTerminalOutput(): TerminalOutputControl
	{
		return $this->terminalOutputControlFactory->create();
	}
}
