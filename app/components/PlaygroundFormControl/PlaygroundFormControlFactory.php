<?php declare(strict_types = 1);

namespace App\Components;


interface PlaygroundFormControlFactory
{
	public function create(callable $onSuccess, callable $onError): PlaygroundFormControl;
}
