<?php declare(strict_types = 1);

require __DIR__ . '/../vendor/autoload.php';

return function (array $parameters = []) {
	$configurator = new Nette\Configurator();
	$configurator->addParameters($parameters);
	$debugMode = isset($_ENV['DEVELOPMENT']) && $_ENV['DEVELOPMENT'];
	$devMode = $debugMode || (isset($_COOKIE['debug']) && $_COOKIE['debug'] === $_ENV['DEBUG_COOKIE']);
	$configurator->setDebugMode($debugMode);
	Tracy\Debugger::$strictMode = true;
	Tracy\Debugger::$logSeverity = E_ALL;
	$logDirectory = realpath(__DIR__ . '/../log');
	Tracy\Debugger::enable(!$devMode, $logDirectory);
	Nette\Bridges\Framework\TracyBridge::initialize();
	$configurator->setTimeZone('UTC');
	$configurator->setTempDirectory(__DIR__ . '/../temp');
	$configurator->addParameters([
		'logDirectory' => $logDirectory,
	]);

	if (isset($_ENV['ECS']) && $_ENV['ECS']) {
		$ecsInstanceId = file_get_contents('http://169.254.169.254/latest/meta-data/instance-id');
	} else {
		$ecsInstanceId = 'localhost';
	}
	$configurator->addDynamicParameters([
		'devMode' => $devMode,
		'env' => $_ENV,
		'cloudWatchEnabled' => (bool) $_ENV['CLOUDWATCH_ENABLED'],
		'ecsInstanceId' => $ecsInstanceId,
	]);

	$configurator->defaultExtensions = [
		'extensions' => Nette\DI\Extensions\ExtensionsExtension::class,
		'nette.application' => [Nette\Bridges\ApplicationDI\ApplicationExtension::class, ['%debugMode%', [], '%tempDir%/cache']],
		'nette.routing' => [\Nette\Bridges\ApplicationDI\RoutingExtension::class, ['%debugMode%']],
		'nette.http' => [Nette\Bridges\HttpDI\HttpExtension::class, ['%consoleMode%']],
		'nette.http.session' => [Nette\Bridges\HttpDI\SessionExtension::class, ['%debugMode%', '%consoleMode%']],
		'latte' => [Nette\Bridges\ApplicationDI\LatteExtension::class, ['%tempDir%/cache/latte', '%debugMode%']],
		'tracy' => [Tracy\Bridges\Nette\TracyExtension::class, ['%debugMode%', '%consoleMode%']],
		'inject' => Nette\DI\Extensions\InjectExtension::class,
	];

	$configurator->addConfig(__DIR__ . '/config/config.neon');
	$configurator->addConfig(__DIR__ . '/config/presenters.neon');
	$configurator->addConfig(__DIR__ . '/config/logging.neon');
	$container = $configurator->createContainer();
	$container->getByType(\Aws\S3\S3Client::class)->registerStreamWrapper();

	if ($devMode) {
		$tracyBar = $container->getByType(\Tracy\Bar::class);
		if (!$debugMode) {
			$tracyBar->addPanel(
				$container->getByType(\Nette\Bridges\ApplicationTracy\RoutingPanel::class)
			);
		}
		$tracyBar->addPanel(
			$container->getByType(\App\System\Tracy\GitCommitPanel::class)
		);
		$tracyBar->addPanel(
			$container->getByType(\App\System\Tracy\AwsInstanceIdPanel::class)
		);
	}

	return $container;
};

