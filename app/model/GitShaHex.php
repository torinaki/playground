<?php declare(strict_types = 1);

namespace App\Model;


class GitShaHex
{
	/** @var string */
	private $shaHex;


	public function __construct(string $shaHex)
	{
		assert(ctype_xdigit($shaHex) && strlen($shaHex) === 40);
		$this->shaHex = $shaHex;
	}


	public function __toString(): string
	{
		return $this->shaHex;
	}
}
