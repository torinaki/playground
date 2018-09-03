<?php declare(strict_types = 1);

namespace App\Model;


class GitShaHex
{
	/** @var string */
	private $shaHex;


	public function __construct(string $shaHex)
	{
		assert(self::isValid($shaHex));
		$this->shaHex = $shaHex;
	}


	public function __toString(): string
	{
		return $this->shaHex;
	}


	public static function isValid(string $shaHex): bool
	{
		return ctype_xdigit($shaHex) && strlen($shaHex) === 40;
	}
}
