#!/usr/bin/env php
<?php declare(strict_types = 1);

$container = (require __DIR__ . '/../app/bootstrap.php')();
$container->getByType(\Symfony\Component\Console\Application::class)->run();
