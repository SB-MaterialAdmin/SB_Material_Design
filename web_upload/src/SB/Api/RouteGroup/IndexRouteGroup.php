<?php

namespace SB\Api\RouteGroup;

use Framework\RouteGroup\AbstractIndexRouteGroup;

class IndexRouteGroup extends AbstractIndexRouteGroup
{
	protected function handle()
	{
		$this->get('/', 'HomeController:index');

		$this->subGroup(new DebugRouteGroup());
		$this->subGroup(new ServerRouteGroup());
	}
}