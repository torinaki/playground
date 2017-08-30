<?php declare(strict_types = 1);

namespace App\Components;

use App\Model\AnalyzerInput;


interface PlaygroundFormControlFactory
{
	public function create(?AnalyzerInput $defaultInput, callable $onSuccess, callable $onError): PlaygroundFormControl;
}
