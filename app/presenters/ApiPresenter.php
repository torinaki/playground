<?php declare(strict_types = 1);

namespace App\Presenters;

use App\Model\PhpStanAnalyzer;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IResponse;
use Nette\Application\Request;
use Nette\Application\Responses\JsonResponse;


class ApiPresenter implements IPresenter
{
	/** @var PhpStanAnalyzer */
	private $analyzer;


	public function __construct(PhpStanAnalyzer $analyzer)
	{
		$this->analyzer = $analyzer;
	}


	public function run(Request $request): IResponse
	{
		$action = $request->getParameter('action');
		$inputHash = $request->getParameter('inputHash');

		if (!is_string($action) || !is_string($inputHash)) {
			throw new BadRequestException();
		}

		if ($action === 'showInput') {
			return $this->actionShowInput($inputHash);
		}

		throw new BadRequestException();
	}


	public function actionShowInput(string $inputHash): IResponse
	{
		$input = $this->analyzer->fetchInput($inputHash);

		if ($input === NULL) {
			throw new BadRequestException();
		}

		return new JsonResponse([
			'phpStanVersion' => (string) $input->getPhpStanVersion(),
			'phpCode' => $input->getPhpCode(),
			'level' => $input->getLevel(),
			'config' => $input->getConfig(),
		]);
	}
}
