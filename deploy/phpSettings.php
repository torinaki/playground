<?php
declare(strict_types = 1);

return [
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
	[
		'name' => 'REMOTE_DATA_DIR',
		'value' => 's3://phpstan-playground/data',
	],
	[
		'name' => 'REMOTE_LOG_DIR',
		'value' => 's3://phpstan-playground/log',
	],
	[
		'name' => 'CLOUDWATCH_ENABLED',
		'value' => '1',
	],
	[
		'name' => 'ECS',
		'value' => '1',
	],
];
