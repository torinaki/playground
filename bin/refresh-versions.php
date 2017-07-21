#!/usr/bin/env php
<?php declare(strict_types = 1);

$dic = require __DIR__ . '/../app/bootstrap.php';
$dic->getByType(App\Model\PhpStanVersions::class)->refresh();
