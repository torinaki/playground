<?php declare(strict_types = 1);

namespace App\Components;

use Nette\Application\UI;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


/**
 * @property-read UI\ITemplate $template
 */
class TerminalOutputControl extends UI\Control
{
	/** @var AnsiToHtmlConverter */
	private $ansiToHtmlConverter;

	/** @var BufferedOutput */
	private $output;


	public function __construct(AnsiToHtmlConverter $ansiToHtmlConverter)
	{
		parent::__construct();
		$this->ansiToHtmlConverter = $ansiToHtmlConverter;
		$this->output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, TRUE);
	}


	public function getOutput(): OutputInterface
	{
		return $this->output;
	}


	public function getStyle(): StyleInterface
	{
		return new SymfonyStyle(new ArrayInput([]), $this->output);
	}


	public function writeRaw(string $s): void
	{
		$this->output->write($s, FALSE, OutputInterface::OUTPUT_RAW);
	}


	public function render(): void
	{
		$ansiOutput = $this->output->fetch();
		$htmlOutput = $this->ansiToHtmlConverter->convert($ansiOutput);

		$this->template->htmlOutput = $htmlOutput;
		$this->template->setFile(__DIR__ . '/TerminalOutputControl.latte');
		$this->template->render();
	}
}
