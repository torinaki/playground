<?php declare(strict_types = 1);

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SleepCommand extends Command
{

	protected function configure()
	{
		$this->setName('sleep');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		sleep(10);
	}

}
