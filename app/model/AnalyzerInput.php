<?php declare(strict_types = 1);

namespace App\Model;


class AnalyzerInput
{
	/** @var GitShaHex */
	private $phpStanVersion;

	/** @var string */
	private $phpCode;

	/** @var int */
	private $level;

	/** @var string */
	private $config;


	public function __construct(GitShaHex $phpStanVersion, string $phpCode, int $level, string $config)
	{
		$this->phpStanVersion = $phpStanVersion;
		$this->phpCode = $phpCode;
		$this->level = $level;
		$this->config = $config;
	}


	public function getPhpStanVersion(): GitShaHex
	{
		return $this->phpStanVersion;
	}


	public function getPhpCode(): string
	{
		return $this->phpCode;
	}


	public function getLevel(): int
	{
		return $this->level;
	}


	public function getConfig(): string
	{
		return $this->config;
	}


	public function getHash(): string
	{
		return md5("{$this->phpStanVersion};{$this->level};{$this->config};{$this->phpCode}");
	}
}
