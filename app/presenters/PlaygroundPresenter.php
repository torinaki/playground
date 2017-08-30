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

	/** @var NULL|AnalyzerInput */
	private $input;

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
			if ($output = $this->analyzer->fetchOutput($inputHash)) {
				$input = $output->getInput();

			} elseif ($input = $this->analyzer->fetchInput($inputHash)) {
				$output = $this->analyzer->analyze($input, TRUE);

			} else {
				$this->error();
			}

			$this->input = $input;
			$this->output = $output;
		}
	}


	public function renderDefault(): void
	{
		if ($this->input !== NULL) {
			$this['playgroundForm']->setDefaults($this->input);
		}

		if ($this->output !== NULL) {
			$this['terminalOutput']->writeRaw($this->output->getOutput());
		}
	}


	protected function createComponentPlaygroundForm(): PlaygroundFormControl
	{
		return $this->playgroundFormControlFactory->create(
			function (AnalyzerInput $input, bool $persist): void {
				$output = $this->analyzer->analyze($input, $persist);

				if ($persist) {
					$this->redirect('this', ['inputHash' => $input->getHash()]);

				} else {
					$this['terminalOutput']->writeRaw($output->getOutput());
					$this->output = NULL;
				}
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
