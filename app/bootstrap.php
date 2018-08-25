<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

return function (array $parameters = []) {
	$configurator = new Nette\Configurator();
	$configurator->addParameters($parameters);
	if (isset($_ENV['DEVELOPMENT']) && $_ENV['DEVELOPMENT']) {
		$configurator->setDebugMode(true);
	}
	$configurator->enableTracy(__DIR__ . '/../log');
	$configurator->setTimeZone('UTC');
	$configurator->setTempDirectory(__DIR__ . '/../temp');
	$configurator->addDynamicParameters([
		'env' => $_ENV,
	]);

	$configurator->createRobotLoader()
		->addDirectory(__DIR__)
		->register();

	$configurator->defaultExtensions = [
		'extensions' => Nette\DI\Extensions\ExtensionsExtension::class,
		'nette.application' => [Nette\Bridges\ApplicationDI\ApplicationExtension::class, ['%debugMode%', ['%appDir%'], '%tempDir%/cache']],
		'nette.http' => [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']],
		'nette.http.session' => [Nette\Bridges\HttpDI\SessionExtension::class, ['%debugMode%', '%consoleMode%']],
		'latte' => [Nette\Bridges\ApplicationDI\LatteExtension::class, ['%tempDir%/cache/latte', '%debugMode%']],
		'tracy' => [Tracy\Bridges\Nette\TracyExtension::class, ['%debugMode%', '%consoleMode%']],
		'inject' => Nette\DI\Extensions\InjectExtension::class,
	];

	$configurator->addConfig(__DIR__ . '/config/config.neon');
	$configurator->addConfig(__DIR__ . '/config/config.local.neon');

	$container = $configurator->createContainer();
	$container->getByType(\Aws\S3\S3Client::class)->registerStreamWrapper();

	return $container;
};

