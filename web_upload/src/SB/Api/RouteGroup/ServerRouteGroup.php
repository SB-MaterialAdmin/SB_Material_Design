<?php

namespace SB\Api\RouteGroup;

use Framework\RouteGroup\AbstractIndexRouteGroup;

class ServerRouteGroup extends AbstractIndexRouteGroup
{
    public function getPattern()
    {
        return '/server';
    }

	protected function handle()
	{
        $this->get('/details', 'ServerController:index');

        foreach (['admins', 'groups', 'overrides'] as $routeAction)
        {
            $this->get("/{$routeAction}", "ServerController:{$routeAction}");
        }
    }
}