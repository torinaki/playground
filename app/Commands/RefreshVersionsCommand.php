<?php declare(strict_types = 1);

namespace App\Commands;

use App\Model\PhpStanVersions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RefreshVersionsCommand extends Command
{

	/** @var PhpStanVersions */
	private $versions;

	public function __construct(PhpStanVersions $versions)
	{
		parent::__construct();
		$this->versions = $versions;
	}

	protected function configure()
	{
		$this->setName('versions:refresh');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->versions->refresh();
	}

}
