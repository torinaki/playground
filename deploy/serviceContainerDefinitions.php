<?php
declare(strict_types = 1);

$phpSettings = require __DIR__ . '/phpSettings.php';

echo json_encode([
	[
		'name' => 'fpm',
		'memoryReservation' => 32,
		'image' => $_SERVER['FPM_DOCKER_TAG'],
		'environment' => $phpSettings,
		'command' => ['php-fpm'],
		'mountPoints' => [
			[
				'containerPath' => '/usr/deploy/phpstan',
				'sourceVolume' => 'efs-phpstan',
			],
		],
	],
	[
		'name' => 'nginx',
		'memoryReservation' => 32,
		'image' => $_SERVER['NGINX_DOCKER_TAG'],
		'portMappings' => [
			[
				'containerPort' => 80,
				'hostPort' => 0,
				'protocol' => 'tcp',
			],
		],
		'links' => ['fpm:fpm'],
		'volumesFrom' => [
			[
				'sourceContainer' => 'fpm',
				'readOnly' => true,
			],
		],
	],
]);
