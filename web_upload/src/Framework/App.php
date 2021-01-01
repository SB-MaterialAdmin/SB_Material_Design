<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;

use Framework\Cookie\CookieManager;
use Framework\Middleware\EventMiddleware;
use Framework\Middleware\TrailSlash;
use Psr\Container\ContainerInterface as Container;

use Zeuxisoo\Whoops\Provider\Slim\WhoopsGuard;

use Framework\Util\Arr;

use Framework\Collection\SimpleCollection;

/**
 * Class App
 * @package Framework
 */
abstract class App
{
	/**
	 * @var	\Framework\Slim
	 */
	protected $slim;

	/**
	 * Setups the all app.
	 *
	 * @param	array	$setupOptions
	 */
	public function setup(array $setupOptions)
	{
		if ($this->slim)
		{
			throw new \LogicException("App already installed.");
		}
		$time = !empty($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);

		$container = new \Slim\Container();

		$container['time'] = intval($time);
		$container['time.granular'] = $time;

		/**
		 * Config related.
		 */
		$container['config.default'] = function(Container $c) {
			return [
				'settings'		=>	[
					// Errors related.
					'debug'					=>	false,
					'whoops.editor'			=>	'vscode',
					'whoops.page_title'		=>	$c['app.name'] . ' :: Error',
					'displayErrorDetails'	=>	false,

					// Another default settings.
					'httpVersion'						=>	'1.1',
					'responseChunkSize'					=>	4096,
					'outputBuffering'					=>	'append',
					'determineRouteBeforeAppMiddleware'	=>	false,
					'addContentLengthHeader'			=>	true,
					'routerCacheFile'					=>	false,

					'database'				=> [
						'connections'		=> [],
					],
				],

				/**
				 * Settings for Cookie Manager.
				 */
				'cookie'		=>	[
					'prefix'	=>	'_fmwk',
					'path'		=>	'/',
					'domain'	=>	'',
				],

				'session'		=>	[
					'provider'	=>	'Framework\\Session\\SqlSessionManager',
					'params'	=>	[],
				],

				'view.settings'	=>	[],
			];
		};
		$container['config.file'] = \Framework::getSourceDirectory() . '/config.php';
		$container['config'] = function (Container $c)
		{
			$default = $c['config.default'];
			$file = $c['config.file'];

			if (file_exists($file))
			{
				$config = [];
				require($file);

				$config = array_replace_recursive($default, $config);
				$config['exists'] = true;
			}
			else
			{
				$config = $default;
			}

			return new SimpleCollection($config, 0);
		};

		/**
		 * Extension manager.
		 */
		$container['extension'] = function (Container $c)
		{
			return new Extension();
		};

		/**
		 * Callable resolver for controllers.
		 */
		$container['callableResolver'] = function (Container $c)
		{
			return new CallableResolver($c, $c['app.classRoot'] . '\Controller');
		};

		/**
		 * Templater.
		 */
		$container['view'] = function (Container $c)
		{
			$templatesDirectory = $c['app.templates.source'];
			$cacheDirectory = $c['app.templates.cache'];

			$view = new \Framework\View\View($templatesDirectory, $cacheDirectory, $c->get('config')->get('view.settings'));

			// Instantiate and add Slim specific functions.
			$router = $c['router'];
			$uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
			$extension = new \Framework\View\Extension($router, $uri);

			$extension->register($view);

			return $view;
		};

		/**
		 * Settings for Slim.
		 *
		 * We're get settings from config.
		 */
		$container['settings'] = function (Container $c)
		{
			return new SimpleCollection($c->get('config')->get('settings'), -1);
		};

		/**
		 * Database closure.
		 */
		$container['database'] = function (Container $c)
		{
			$settings = $c['settings']['database'];

			$config = new \Spot\Config();
			foreach ($settings['connections'] as $name => $data)
			{
				$config->addConnection($name, $data);
			}

			$locatorClass = \Framework::extendClass(Db\Locator::class);
			return new $locatorClass($config);
		};

		/**
		 * App specific params.
		 */
		$container['app.classRoot'] = function (Container $c)
		{
			$className = get_class($this);
			$slashPos = strrpos($className, '\\');

			return substr($className, 0, $slashPos);
		};
		$container['app.classIdentifier'] = function (Container $c)
		{
			return str_replace('\\', '_', $c['app.classRoot']);
		};
		$container['app.classRoot.hash'] = function (Container $c)
		{
			return md5($c['app.classRoot']);
		};
		$container['app.uniqueInstanceKey'] = function (Container $c)
		{
			$settings = $c['settings'];
			if (Arr::keyExists($settings, 'app_key'))
			{
				return sha1($settings['app_key'] . $c['app.classRoot.hash']);
			}

			throw new \RuntimeException('App key is not set!');
		};

		/**
		 * Router params.
		 */
		$container['router'] = function (Container $c)
		{
			$class = \Framework::extendClass(Router::class);
			$router = new $class();
			if (method_exists($router, 'setContainer'))
			{
				$router->setContainer($c);
			}

			return $router;
		};

		/**
		 * Application directories.
		 */
		$container['app.temp'] = function (Container $c)
		{
			$preferred = \Framework::getInternalDataDirectory() . '/temp';
			if (is_writable($preferred))
			{
				$temp = $preferred . '/' . $c['app.classRoot.hash'];
				if (!file_exists($temp) && !@mkdir($temp, 0700, true))
				{
					return $preferred;
				}
				return $temp;
			}

			$temp = sys_get_temp_dir();
			if (!is_writable($temp))
			{
				throw new \RuntimeException("Can't retrieve temporary directory: insufficiently rights on `internal_data`. We're tried switching to OS temp directory, but failed. Please, change rights on `internal_data` directory or create temporary directory with sufficient for web set of rights: {$temp}");
			}

			$temp .= '/' . $c['app.uniqueInstanceKey'];
			if (!file_exists($temp) && !@mkdir($temp, 0770))
			{
				throw new \RuntimeException("Can't retrieve temporary directory: insufficiently rights on `internal_data`. We're tried switching to OS temp directory, but failed. Please, change rights on `internal_data` directory or create temporary directory with sufficient for web set of rights: {$temp}");
			}

			return $temp;
		};
		$container['app.templates.cache'] = function (Container $c)
		{
			$temp = $c['app.temp'] . '/template_cache';
			if (!file_exists($temp) && !@mkdir($temp, 0700, true))
			{
				throw new \RuntimeException('Can\'t retrieve temporary directory for templates.');
			}

			return $temp;
		};
		$container['app.templates.source'] = function (Container $c)
		{
			$path = \Framework::getInternalDataDirectory() . '/template/' . $c['app.classIdentifier'];
			if (!file_exists($path) && ($c['developerMode'] || !@mkdir($path, 0700, true)))
			{
				throw new \RuntimeException('Template directory not created. Create manually, or enable developer mode, and refresh page.');
			}

			return $path;
		};
		$container['app.name'] = function (Container $c)
		{
			return 'Framework';
		};

		/**
		 * Developer mode. Used in some cases.
		 */
		$container['developerMode'] = function (Container $c)
		{
			$settings = $c['settings'];
			return Arr::keyExists($settings, 'developer') ? $settings['developer'] == true : false;
		};

		/**
		 * Session storage.
		 */
		$container['sessionStorage'] = function (Container $c)
		{
			$sessionParams = $c['config']['session'];
			$provider = \Framework::extendClass($sessionParams['provider']);

			return new $provider($c['cookieManager'], $sessionParams['params']);
		};

		/**
		 * Cookie manager.
		 */
		$cookies = $_COOKIE;
		$container['cookieManager'] = function (Container $c) use ($cookies)
		{
			$cookieParams = $c['config']['cookie'];
			$provider = \Framework::extendClass('Framework\\Cookie\\CookieManager');

			return new $provider($cookieParams['prefix'], $cookieParams['path'], $cookieParams['domain'], $cookies);
		};

		/**
		 * We're done here.
		 * Get extra parameters, build app.
		 */
		$this->setupExtra($container, $setupOptions);

		$this->buildApp($container);
		$this->buildWhoops();

		$this->buildMiddlewares();
		$this->buildRoutes();
	}

	/**
	 * Registers a extra items in Container.
	 *
	 * @param	\Psr\Container\ContainerInterface	$container
	 * @param	array								$setupOptions
	 */
	protected function setupExtra(Container $container, array $setupOptions)
	{
	}

	/**
	 * Registers all required middlewares.
	 */
	protected function buildMiddlewares()
	{
		$this->slim->add(new TrailSlash());
		$this->slim->add(new EventMiddleware());
	}

	protected function buildRoutes()
	{
		$slim = $this->slim;
		$container = $slim->getContainer();

		$indexClassName = $container['app.classRoot'] . '\RouteGroup\IndexRouteGroup';
		if (class_exists($indexClassName))
		{
			/** @var \Framework\RouteGroup\AbstractRouteGroup $indexRouteGroup */
			$indexRouteGroup = new $indexClassName();
			$indexRouteGroup->buildMatch();
		}
		// $this->buildRoutesExtra(); 
	}

	/**
	 * Runs the app.
	 */
	public function run()
	{
		return $this->slim->run();
	}

	/**
	 * Build the Slim app.
	 *
	 * @param	\Slim\Container	$container
	 */
	protected final function buildApp(\Slim\Container $container)
	{
		$this->slim = new Slim($container);
		$container['app.slim'] = function ()
		{
			return $this->slim;
		};
	}

	/**
	 * Build the Whoops service.
	 */
	protected final function buildWhoops()
	{
		$slim = $this->slim;
		$container = $slim->getContainer();

		$whoopsGuard = new WhoopsGuard();
		$whoopsGuard->setApp($slim);
		$whoopsGuard->setRequest($slim->getContainer()->get('request'));
		$whoopsGuard->setHandlers([]);
		$whoops = $whoopsGuard->install();

		$container['app.whoops'] = function () use ($whoops) {
			return $whoops;
		};
		$container['app.whoops.prettyPageHandler'] = function() use ($whoops) {
			$handlers = $whoops->getHandlers();
			foreach ($handlers as $handler)
			{
				if ($handler instanceof \Whoops\Handler\PrettyPageHandler)
				{
					return $handler;
				}
			}

			return null;
		};

	}

	/**
	 * Returns the application container.
	 *
	 * @return \Psr\Container\ContainerInterface
	 */
	public function container()
	{
		return $this->slim->getContainer();
	}

	/**
	 * @return \Framework\Extension
	 */
	public function extension()
	{
		return $this->container()->get('extension');
	}

	/**
	 * Returns the application request.
	 * 
	 * @return \Slim\Http\Request
	 */
	public function request()
	{
		return $this->container['request'];
	}

	/**
	 * Creates a service instance.
	 *
	 * @param string 	$name
	 * @param array		$args
	 */
	public function service($name, array $args = [])
	{
		$class = \Framework::className($name, 'Service');
		$class = $this->extension()->getClassExtension()->extendClass($class);
		if (class_exists($class))
		{
			throw new \RuntimeException(Str::format("Can't found service { name }.", ['name' => $name]));
		}

		return (new \ReflectionClass($class))->newInstanceArgs(Arr::merge([$this], $args));
	}
}
