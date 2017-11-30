<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI;


/**
 * @property-read UI\ITemplate $template
 */
class Error4xxPresenter extends UI\Presenter
{
	public function renderDefault(BadRequestException $exception)
	{
		$httpCode = $exception->getCode();

		$files = [
			__DIR__ . "/templates/Error/$httpCode.latte",
			__DIR__ . '/templates/Error/4xx.latte',
		];

		foreach ($files as $file) {
			if (is_file($file)) {
				$this->template->setFile($file);
			}
		}

		$this->error();
	}
}
