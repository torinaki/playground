<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Request;

class OpcachePresenter implements \Nette\Application\IPresenter
{

	/** @var bool */
	private $devMode;

	public function __construct(bool $devMode)
	{
		$this->devMode = $devMode;
	}

	public function run(Request $request)
	{
		if (!$this->devMode) {
			throw new BadRequestException();
		}

		error_reporting(0);

		require __DIR__ . '/../../vendor/carlosio/opcache-dashboard/opcache.php';
	}

}
