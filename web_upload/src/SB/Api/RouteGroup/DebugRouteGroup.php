<?php

namespace SB\Api\RouteGroup;

use Framework\RouteGroup\AbstractRouteGroup;

class DebugRouteGroup extends AbstractRouteGroup
{
	public function getPattern()
	{
		return '/debug';
	}

	protected function handle()
	{
		/** Whoops self-testing */
		$this->get('/whoops', 'DebugController:whoops');
		$this->get('/whoops/{message}', 'DebugController:whoops');

		/** Another debug routes */
		$this->get('/included_files', 'DebugController:included');
		$this->get('/config', 'DebugController:config');

		$this->get('/cookie', 'DebugController:cookie');
		$this->get('/session', 'DebugController:session');
	}
}