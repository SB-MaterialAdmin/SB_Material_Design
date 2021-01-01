<?php

namespace SB\Api;

use Psr\Container\ContainerInterface as Container;
use Slim\Http\Environment;

use Slim\Http\Request;
use Slim\Http\Response;

use Framework\Util\Arr;
use SB\Api\Middleware\ServerTokenMiddleware;

class App extends \Framework\App
{
    /**
	 * {@inheritdoc}
	 */
	protected function setupExtra(Container $container, array $setupOptions)
	{
        // Disable user friendly URLs.
        $container['environment'] = function (Container $c)
        {
            $server = $_SERVER;
            $route = Arr::firstKey($_GET);
            if ($route)
            {
                $server['REQUEST_URI'] = $route;
            }

            return new Environment($server);
        };

        // Rewrite error handlers.
        $handler = function($statusCode = 404)
        {
            return function (Container $c) use ($statusCode)
            {
                return function (Request $request, Response $response) use ($statusCode) {
                    return $response->withStatus($statusCode)->withJson([
                        'success'   => false,
                    ]);
                };
            };
        };

        $container['errorHandler']      = $handler(500);
        $container['phpErrorHandler']   = $handler(500);
        $container['notFoundHandler']   = $handler();
    }

    protected function buildMiddlewares()
    {
        $this->slim->add(new ServerTokenMiddleware());
        parent::buildMiddlewares();
    }
}