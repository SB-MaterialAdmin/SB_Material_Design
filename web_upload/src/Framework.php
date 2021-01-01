<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

use Framework\App;
use Framework\Util\Str;

/**
 * The bootstrap class for Framework.
 */
class Framework
{
	/**
	 * Current kernel version.
	 */
	const KERNEL_VERSION = 1;

	/**
	 * Used memory limit for app working.
	 * @var	integer|null
	 */
	protected static $memoryLimit = null;

	/**
	 * @var	\Composer\Autoload\ClassLoader
	 */
	public static $autoLoader = null;

	/**
	 * @var	integer
	 */
	public static $time = 0;

	/**
	 * @var	\Framework\App
	 */
	protected static $app = null;

	/**
	 * Current root and source directory.
	 *
	 * @var	string
	 * @var	string
	 */
	protected static $rootDirectory = '.';
	protected static $sourceDirectory = '.';

	/**
	 * Starts the framework and standartized the environment.
	 */
	public static function start($rootDirectory)
	{
		self::$time = time();
		self::$rootDirectory = $rootDirectory;
		self::$sourceDirectory = __DIR__;

		self::standartizeEnvironment();
		self::startAutoloader();
		self::startSystem();
	}

	/**
	 * Sets up the PHP environment in the framework-expected way.
	 */
	public static function standartizeEnvironment()
	{
		ignore_user_abort(true);
		self::setMemoryLimit(32 * 1024 * 1024);
		error_reporting(E_ALL | E_STRICT & ~8192);

		date_default_timezone_set('UTC');
		setlocale(LC_ALL, 'C');

		// if you really need to load a phar file, you can call stream_wrapper_restore('phar')
		@stream_wrapper_unregister('phar');

		@ini_set('output_buffering', false);

		if (version_compare(PHP_VERSION, '7.1', '>='))
		{
			@ini_set('serialize_precision', -1);
		}

		// see http://bugs.php.net/bug.php?id=36514
		if (!@ini_get('output_handler'))
		{
			$level = ob_get_level();
			while ($level)
			{
				@ob_end_clean();
				$newLevel = ob_get_level();
				if ($newLevel >= $level)
				{
					break;
				}

				$level = $newLevel;
			}
		}
	}

	/**
	 * Sets up Framework autoloader.
	 */
	public static function startAutoloader()
	{
		if (self::$autoLoader)
		{
			return;
		}

		/** @var \Composer\Autoload\ClassLoader $autoLoader */
		$autoLoader = require(self::$sourceDirectory . '/vendor/autoload.php');
		$autoLoader->register();

		self::$autoLoader = $autoLoader;
	}

	/**
	 * Sets up Framework system.
	 */
	public static function startSystem()
	{
		register_shutdown_function(['Framework', 'triggerRunOnce']);
	}

	public static function triggerRunOnce($rethrow = false)
	{
		\Framework\DeferredJob::run();
	}

	/**
	 * Sets the memory limit. Will not shrink the limit.
	 *
	 * @param	integer	$limit	Limit must be given in integer 9byte) format.
	 * @return	bool			True if the limit was updated (or already met)
	 */
	public static function setMemoryLimit($limit)
	{
		$existingLimit = self::getMemoryLimit();
		if ($existingLimit < 0)
		{
			return true;
		}

		$limit = intval($limit);
		if ($limit > $existingLimit && $existingLimit)
		{
			if (@ini_set('memory_limit', $limit) === false)
			{
				return false;
			}

			self::$memoryLimit = $limit;
		}

		return true;
	}

	public static function increaseMemoryLimit($amount)
	{
		$amount = intval($amount);
		if ($amount <= 0)
		{
			return false;
		}

		$currentLimit = self::getMemoryLimit();
		if ($currentLimit < 0)
		{
			return true;
		}

		return self::setMemoryLimit($currentLimit + $amount);
	}

	/**
	 * Gets the current memory limit.
	 *
	 * @return	integer
	 */
	public static function getMemoryLimit()
	{
		if (self::$memoryLimit === null)
		{
			$curLimit = @ini_get('memory_limit');
			if ($curLimit === false)
			{
				// reading failed, so we have to treat it as unlimited - unlikely to be able to change anyway.
				$curLimitInt = -1;
			}
			else
			{
				$curLimitInt = intval($curLimit);

				switch (substr($curLimit, -1))
				{
					case 'g':
					case 'G':
						$curLimitInt *= 1024;

					case 'm':
					case 'M':
						$curLimitInt *= 1024;

					case 'k':
					case 'K':
						$curLimitInt *= 1024;
				}
			}

			self::$memoryLimit = $curLimitInt;
		}

		return self::$memoryLimit;
	}

	/**
	 * Attempts to determine the current available amount of memory.
	 * If there is no memory limit.
	 *
	 * @return	integer
	 */
	public static function getAvailableMemory()
	{
		$limit = self::getMemoryLimit();
		if ($limit < 0)
		{
			return PHP_INT_MAX;
		}

		$used = memory_get_usage();
		$available = $limit - $used;

		return max($available, 0);
	}

	/**
	 * Instantiates a new app.
	 *
	 * @param	string	$appClass
	 * @param	array	$setupOptions
	 * @return \Framework\App
	 */
	public static function setupApp($appClass, array $setupOptions = [])
	{
		/** @var \Framework\App $app */
		$app = new $appClass();
		self::setApp($app);
		$app->setup($setupOptions);

		return $app;
	}

	/**
	 * Instantiates a new app and run him.
	 *
	 * @param string $appClass
	 * @param array $setupOptions
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public static function runApp($appClass, array $setupOptions = [])
	{
		return (self::setupApp($appClass, $setupOptions))
			->run();
	}

	/**
	 * Setups a app in request context.
	 *
	 * @param	\Framework\App	$app
	 */
	public static function setApp(App $app)
	{
		if (self::$app)
		{
			throw new \LogicException(
				'A second app cannot be setup. ' .
				'Tried to set ' . get_class($app) . ' after setting ' . get_class(self::$app)
			);
		}

		self::$app = $app;
	}

	/**
	 * Returns a current app.
	 *
	 * @return	\Framework\App
	 */
	public static function app()
	{
		if (!self::$app)
		{
			return self::setupApp('Framework\ExampleApp\App');
		}

		return self::$app;
	}

	/**
	 * Returns the container.
	 *
	 * @return \Psr\Container\ContainerInterface
	 */
	public static function container()
	{
		return self::app()->container();
	}

	/**
	 * Returns the source directory.
	 *
	 * @return	string
	 */
	public static function getSourceDirectory()
	{
		return self::$sourceDirectory;
	}

	/**
	 * Returns the root directory.
	 *
	 * @return	string
	 */
	public static function getRootDirectory()
	{
		return self::$rootDirectory;
	}

	/**
	 * Returns the path for internal data.
	 *
	 * @return	string
	 */
	public static function getInternalDataDirectory()
	{
		return self::getRootDirectory() . '/internal_data';
	}

	/**
	 * Returns the metadata directory.
	 *
	 * @return	string
	 */
	public static function getMetaDirectory()
	{
		return self::getInternalDataDirectory() . '/meta';
	}

	/**
	 * @return \Framework\Extension
	 */
	public static function extension()
	{
		return self::container()->get('extension');
	}

	/**
	 * @param $class
	 * @return string
	 * @throws Exception
	 */
	public static function extendClass($class)
	{
		return self::extension()->getClassExtension()->extendClass($class);
	}

	/**
	 * @param $eventName
	 * @param array $arguments
	 * @param callable|null $continueCallBack
	 * @return bool
	 */
	public static function fireEvent($eventName, $arguments = [], callable $continueCallBack = null)
	{
		$eventEmitter = self::extension()->getEventEmitter();
		$res = null;
		if ($continueCallBack !== null)
		{
			$res = $eventEmitter->emit($eventName, $arguments, $continueCallBack);
		}
		else
		{
			$res = $eventEmitter->emit($eventName, $arguments);
		}

		return $res;
	}

	/**
	 * @param	string	$class
	 * @param	string	$type
	 */
	public static function className($class, $type)
	{
		if (Str::startsWith($class, '\\'))
		{
			$class = substr($class, 1);
		}

		if (!Str::contains($class, ':'))
		{
			return $class;
		}

		$class = explode(':', $class, 2);
		return Str::format('{ root }\\{ type }\\{ target }', [
			'root'		=> $class[0],
			'type'		=> $type,
			'target'	=> $class[1]
		]);
	}
}
