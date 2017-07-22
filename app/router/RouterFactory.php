<?php declare(strict_types = 1);

namespace App;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;


class RouterFactory
{
	public static function createRouter(): IRouter
	{
		return new Route('[r/<inputHash>]', 'Playground:default');
	}
}
