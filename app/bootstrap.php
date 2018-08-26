<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

return function (array $parameters = []) {
	$configurator = new Nette\Configurator();
	$configurator->addParameters($parameters);
	$debugMode = isset($_ENV['DEVELOPMENT']) && $_ENV['DEVELOPMENT'];
	$configurator->setDebugMode($debugMode);
	Tracy\Debugger::$strictMode = true;
	Tracy\Debugger::$logSeverity = E_ALL;
	Tracy\Debugger::enable(
		!($debugMode || (isset($_COOKIE['debug']) && $_COOKIE['debug'] === $_ENV['DEBUG_COOKIE'])),
		__DIR__ . '/../log'
	);
	Nette\Bridges\Framework\TracyBridge::initialize();
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

