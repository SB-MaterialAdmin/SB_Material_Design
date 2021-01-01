<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Controller;

use Framework\Exception\InstantResponseException;
use Framework\Util\Json;
use Orx0r\Slim\Controller\Controller;

abstract class AbstractController extends Controller
{
	public function __call($name, $arguments)
	{
		$this->preDispatchController($name, $arguments);

		// TODO: add info about current controller in Whoops.
		$result =  parent::__call($name, $arguments);

		$this->postDispatchController($name, $arguments, $result);
	}

	/**
	 * @return \Framework\App
	 */
	protected function app()
	{
		return \Framework::app();
	}

	/**
	 * @return \Psr\Container\ContainerInterface
	 */
	protected function container()
	{
		return $this->app()->container();
	}

	protected function json($data, $status = 200, $now = true)
	{
		$this->response = $this->response->withJson($data, $status);

		if ($now)
		{
			throw new InstantResponseException($this->response);
		}
		else
		{
			return $this->response;
		}
	}

	protected function header($name, $value)
	{
		$this->response = $this->response->withAddedHeader($name, $value);
		return $this;
	}

	protected function renderPage($template, $data = [], $wrapper = 'PAGE_CONTAINER.tpl')
	{
		$body = $this->container->get('view')->fetch($template, $data);

		return $this->header('X-Framework-Template', $template)
			->header('X-Framework-Wrapper', $wrapper)
			->render($this->response, $wrapper, array_merge($data, ['content' => $body]));
	}

	/**
	 * @return \Framework\Session\AbstractSessionManager
	 */
	protected function sessionManager()
	{
		return $this->container()->get('sessionStorage');
	}

	/**
	 * @return \Framework\Session\UserSession
	 */
	protected function session()
	{
		return $this->sessionManager()->getCurrentSession();
	}

	protected function preDispatchController(&$action, array &$arguments)
	{
	}

	protected function postDispatchController($action, array $arguments, &$result)
	{
	}

	/**
	 * Starts session.
	 */
	protected function startSession()
	{
		// Instantiate session from cookie.
		$this->sessionManager()->createSessionFromCookie();
	}
}
