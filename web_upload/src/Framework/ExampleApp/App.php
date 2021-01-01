<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\ExampleApp;

use Psr\Container\ContainerInterface as Container;

class App extends \Framework\App
{
	/**
	 * {@inheritdoc}
	 */
	protected function setupExtra(Container $container, array $setupOptions)
	{
	}
}
