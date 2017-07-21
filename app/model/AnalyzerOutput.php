<?php declare(strict_types = 1);

namespace App\Model;


class AnalyzerOutput
{
	/** @var AnalyzerInput */
	private $input;

	/** @var string */
	private $output;


	public function __construct(AnalyzerInput $input, string $output)
	{
		$this->input = $input;
		$this->output = $output;
	}


	public function getInput(): AnalyzerInput
	{
		return $this->input;
	}


	public function getOutput(): string
	{
		return $this->output;
	}
}
