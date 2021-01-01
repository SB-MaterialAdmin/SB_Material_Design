<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;

use RuntimeException;
use Interop\Container\ContainerInterface;
use Slim\Interfaces\CallableResolverInterface;

/**
 * This class resolves a string of the format 'class:method' into a closure
 * that can be dispatched.
 */
class CallableResolver implements CallableResolverInterface
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var string
	 */
	protected $controllerNamespace;

	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container, $controllerNamespace = null)
	{
		$this->container = $container;
		$this->controllerNamespace = $controllerNamespace;
	}

	/**
	 * Resolve toResolve into a closure that that the router can dispatch.
	 *
	 * If toResolve is of the format 'class:method', then try to extract 'class'
	 * from the container otherwise instantiate it and then dispatch 'method'.
	 *
	 * @param mixed $toResolve
	 *
	 * @return callable
	 *
	 * @throws RuntimeException if the callable does not exist
	 * @throws RuntimeException if the callable is not resolvable
	 */
	public function resolve($toResolve)
	{
		$resolved = $toResolve;

		if (!is_callable($toResolve) && is_string($toResolve)) {
			// check for slim callable as "class:method"
			$callablePattern = '!^([^\:]+)\:([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)$!';
			if (preg_match($callablePattern, $toResolve, $matches)) {
				$class = $matches[1];
				$method = $matches[2];

				if ($this->container->has($class)) {
					$resolved = [$this->container->get($class), $method];
				} elseif (class_exists($class)) {
					$resolved = [new $class($this->container), $method];
				} else {
					if (!$this->controllerNamespace) {
						throw new RuntimeException(sprintf('Callable %s does not exist', $class));
					}
					$class = "$this->controllerNamespace\\$class";
					if (!class_exists($class)) {
						throw new RuntimeException(sprintf('Callable %s does not exist', $class));
					}
					$resolved = [new $class($this->container), $method];
				}
			} else {
				// check if string is something in the DIC that's callable or is a class name which
				// has an __invoke() method
				$class = $toResolve;
				if ($this->container->has($class)) {
					$resolved = $this->container->get($class);
				} else {
					if (!class_exists($class)) {
						throw new RuntimeException(sprintf('Callable %s does not exist', $class));
					}

					$class = \Framework::extendClass($class);
					$resolved = new $class($this->container);
				}
			}
		}

		if (!is_callable($resolved) && !is_object($resolved)) {
			throw new RuntimeException(sprintf('%s is not resolvable', $toResolve));
		}

		return $resolved;
	}
}
