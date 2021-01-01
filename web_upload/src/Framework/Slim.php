<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;

class Slim extends \Slim\App
{
	/**
	 * Calling a non-existant method on App checks to see if there's an item
	 * in the container that is callable and if so, calls it.
	 *
	 * @param   string  $method
	 * @param   array   $args
	 * @return  mixed
	 */
	public function __call($method, $args)
	{
		$container = $this->getContainer();

		if ($container->has($method))
		{
			$obj = $container->get($method);
			if (is_callable($obj))
			{
				return call_user_func_array($obj, $args);
			}

			return $obj;
		}

		throw new \RuntimeException("Property {$method} not defined in container!");
	}
}
