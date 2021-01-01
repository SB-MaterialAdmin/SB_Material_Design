<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\View;

use Framework\Util\Arr;

class Extension
{
	/**
	 * @var \Slim\Interface\RouterInterface
	 */
	protected $router;

	/**
	 * @var \Slim\Http\Uri
	 */
	protected $uri;

	public function __construct(\Slim\Interfaces\RouterInterface $router, \Slim\Http\Uri $uri)
	{
		$this->router = $router;
		$this->uri = $uri;
	}

	/**
	 * Registers extension in View.
	 *
	 * @param   \Framework\View\View	$view
	 */
	public function register(View $view)
	{
		$view->addFunction('path_for',		  [$this, 'getPathFor']);
		$view->addFunction('base_url',		  [$this, 'getBaseUrl']);
		$view->addFunction('is_current_path',   [$this, 'isCurrentPath']);
		$view->addFunction('current_path',	  [$this, 'getCurrentPath']);
	}

	public function getPathFor(array $params)
	{
		$name = $this->assertKeyExists($params, 'name');
		$data = Arr::keyExists($params, 'data') ? $params['data'] : [];
		$queryParams = Arr::keyExists($params, 'queryParams') ? $params['queryParams'] : [];

		return $this->router->pathFor($name, $data, $queryParams);
	}

	public function getBaseUrl()
	{
		return $this->uri->getBaseUrl();
	}

	public function isCurrentPath(array $params)
	{
		$name = $this->assertKeyExists($params, 'name');
		$data = Arr::keyExists($params, 'data') ? $params['data'] : [];

		return $this->router->pathFor($name, $data) === $this->uri->getBasePath() . '/' . ltrim($this->uri->getPath(), '/');
	}

	public function getCurrentPath(array $params)
	{
		$withQueryString = Arr::keyExists($params, 'withQueryString') ? intval($params['withQueryString']) != 0 : false;
		$path = $this->uri->getBasePath() . '/' . ltrim($this->uri->getPath(), '/');

		if ($withQueryString && '' !== $query = $this->uri->getQuery())
		{
			$path .= '?' . $query;
		}

		return $path;
	}

	/**
	 * Asserts the function called with required param.
	 *
	 * @param   array   $params
	 * @param   string  $paramName
	 * @return  mixed
	 */
	public function assertKeyExists(array $params, $paramName)
	{
		if (Arr::keyExists($params, $paramName))
		{
			return $params[$paramName];
		}

		throw new \RuntimeException('Invalid function call in template');
	}
}