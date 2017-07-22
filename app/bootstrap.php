<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

return function (array $parameters = []) {
	$configurator = new Nette\Configurator();
	$configurator->addParameters($parameters);
	$configurator->enableTracy(__DIR__ . '/../log');
	$configurator->setTimeZone('UTC');
	$configurator->setTempDirectory(__DIR__ . '/../temp');

	$configurator->createRobotLoader()
		->addDirectory(__DIR__)
		->register();

	$configurator->addConfig(__DIR__ . '/config/config.neon');
	$configurator->addConfig(__DIR__ . '/config/config.local.neon');

	return $configurator->createContainer();
};

