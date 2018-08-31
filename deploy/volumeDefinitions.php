<?php
declare(strict_types = 1);

echo json_encode([
	[
		'name' => 'efs-phpstan',
		'host' => [
			'sourcePath' => '/efs/phpstan-data',
		],
	],
	[
		'name' => 'ebs-phpstan-cache',
		'host' => [
			'sourcePath' => '/ebs1/phpstan-cache',
		],
	],
]);
