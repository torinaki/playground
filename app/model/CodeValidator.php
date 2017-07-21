<?php declare(strict_types = 1);

namespace App\Model;

use Nette\Utils\Arrays;
use PhpParser;


class CodeValidator
{
	/** @var PhpParser\Parser */
	private $parser;


	public function __construct(PhpParser\Parser $parser)
	{
		$this->parser = $parser;
	}


	/**
	 * @param  string $code
	 * @return string[]
	 */
	public function validate(string $code): array
	{
		$errorHandler = new PhpParser\ErrorHandler\Collecting();
		$this->parser->parse($code, $errorHandler);

		return Arrays::map($errorHandler->getErrors(), function (PhpParser\Error $error) use ($code) {
			return $error->hasColumnInfo() ? $error->getMessageWithColumnInfo($code) : $error->getMessage();
		});
	}
}
