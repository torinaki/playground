<?php
declare(strict_types = 1);

echo json_encode([
	[
		'name' => 'fpm',
		'memoryReservation' => 128,
		'image' => $_SERVER['FPM_DOCKER_TAG'],
		'environment' => [
			[
				'name' => 'AWS_ACCESS_KEY_ID',
				'value' => $_SERVER['AWS_ACCESS_KEY_ID'],
			],
			[
				'name' => 'AWS_SECRET_ACCESS_KEY',
				'value' => $_SERVER['AWS_SECRET_ACCESS_KEY'],
			],
			[
				'name' => 'DEBUG_COOKIE',
				'value' => $_SERVER['DEBUG_COOKIE'],
			],
		],
	],
	[
		'name' => 'nginx',
		'memoryReservation' => 128,
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
