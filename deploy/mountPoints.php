<?php
declare(strict_types = 1);

return [
	[
		'containerPath' => '/usr/deploy/phpstan',
		'sourceVolume' => 'efs-phpstan',
	],
	[
		'containerPath' => '/usr/deploy/temp/phpstan-cache',
		'sourceVolume' => 'ebs-phpstan-cache',
	],
];
