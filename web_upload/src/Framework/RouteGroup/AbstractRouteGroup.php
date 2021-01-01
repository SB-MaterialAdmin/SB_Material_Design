<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\RouteGroup;

use Framework\App;
use Framework\Slim;

abstract class AbstractRouteGroup
{
	/** @var \Framework\Slim */
	private $app;

	/**
	 * @param Slim $app
	 */
	public function setApp(Slim $app)
	{
		$this->app = $app;
	}

	/**
	 * Builds a group.
	 *
	 * @noreturn
	 */
	public final function buildMatch()
	{
		\Framework::container()->get('router')->addRouteGroup($this);
	}

	/**
	 * @param $method
	 * @param $args
	 */
	public function __call($method, $args)
	{
		if (in_array($method, ['add', 'get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'map', 'redirect']))
		{
			return call_user_func_array([$this->app, $method], $args);
		}

		if ($method === 'subGroup' && is_object($args[0]) && $args[0] instanceof AbstractRouteGroup)
		{
			\Framework::container()->get('router')->addRouteGroup($args[0]);
		}
	}

	/**
	 * Return the RouteGroup closure.
	 * @return \Closure
	 */
	public function getHandler()
	{
		$context = $this;

		return function (Slim $app) use($context)
		{
			$context->setApp($app);
			$context->handle();
		};
	}

	/**
	 * Return the base pattern.
	 * @return string
	 */
	public abstract function getPattern();

	/**
	 * YOU SHOULD IMPLEMENT THIS METHOD.
	 */
	protected abstract function handle();
}