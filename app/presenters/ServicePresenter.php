<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;
use function phpinfo;

class ServicePresenter extends Presenter
{

	/** @var bool */
	private $devMode;

	public function __construct(bool $devMode)
	{
		parent::__construct();
		$this->devMode = $devMode;
	}

	protected function startup()
	{
		parent::startup();
		if (!$this->devMode) {
			throw new BadRequestException();
		}
	}

	public function actionPhpInfo()
	{
		phpinfo();
		$this->terminate();
	}

	public function actionOpcache()
	{
		error_reporting(0);

		require __DIR__ . '/../../vendor/carlosio/opcache-dashboard/opcache.php';
		$this->terminate();
	}

}
