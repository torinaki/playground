<?php declare(strict_types = 1);

namespace App\Presenters;

use Nette\Application\BadRequestException;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use function phpinfo;

class ServicePresenter extends Presenter
{

	/** @var bool */
	private $devMode;

	/** @var string */
	private $remoteLogDirectory;

	public function __construct(bool $devMode, string $remoteLogDirectory)
	{
		parent::__construct();
		$this->devMode = $devMode;
		$this->remoteLogDirectory = $remoteLogDirectory;
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

	public function actionLog(string $exception)
	{
		$contents = @file_get_contents($this->remoteLogDirectory . '/' . $exception);
		if ($contents === false) {
			$this->error();
		}
		$this->sendResponse(new TextResponse($contents));
	}

}
