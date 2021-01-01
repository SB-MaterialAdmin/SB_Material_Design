<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;

use Framework\RouteGroup\AbstractRouteGroup;
use Framework\App;

class Router extends \Slim\Router
{
	/**
	 * @var array
	 */
	protected $_registeredGroups = [];

	public function addRouteGroup(AbstractRouteGroup $routeGroup)
	{
		if (in_array($routeGroup, $this->_registeredGroups))
		{
			throw new \LogicException('Tried register already registered route group');
		}

		$this->_registeredGroups[] = $routeGroup;

		/** @var \Slim\Interfaces\RouteGroup $group */
		$group = $this->pushGroup($routeGroup->getPattern(), $routeGroup->getHandler());
		$group->setContainer(\Framework::container());
		$group(\Framework::app()->container()->get('app.slim'));
		$this->popGroup();

		return $group;
	}
}