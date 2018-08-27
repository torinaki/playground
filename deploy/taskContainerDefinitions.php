<?php
declare(strict_types = 1);

$phpSettings = require __DIR__ . '/phpSettings.php';

echo json_encode([
	[
		'name' => 'cli',
		'memoryReservation' => 32,
		'image' => $_SERVER['FPM_DOCKER_TAG'],
		'environment' => $phpSettings,
		'entryPoint' => ['php','bin/cli.php'],
	],
]);
