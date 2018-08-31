<?php
declare(strict_types = 1);

echo json_encode([
	[
		'name' => 'efs-phpstan',
		'host' => [
			'sourcePath' => '/efs/phpstan-data',
		],
	],
]);
