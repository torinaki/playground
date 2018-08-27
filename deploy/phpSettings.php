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
];
