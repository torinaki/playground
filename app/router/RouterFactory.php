<?php declare(strict_types = 1);

namespace App;

use Nette\Application\IRouter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;


class RouterFactory
{
	public static function createRouter(): IRouter
	{
		$routeList = new RouteList();
		$routeList[] = new Route('[r/<inputHash>]', 'Playground:default');
		$routeList[] = new Route('r/<inputHash>/input', 'Api:showInput');

		return $routeList;
	}
}
