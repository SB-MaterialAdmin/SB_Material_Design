<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\ExampleApp\RouteGroup;

use Framework\RouteGroup\AbstractIndexRouteGroup;

class IndexRouteGroup extends AbstractIndexRouteGroup
{
	protected function handle()
	{
		$this->get('/', 'HomeController:index');

		$this->subGroup(new DebugRouteGroup());
	}
}