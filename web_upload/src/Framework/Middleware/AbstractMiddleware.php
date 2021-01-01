<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Middleware;

use Framework\Exception\InstantResponseException;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class AbstractMiddleware
{
	const MIDDLEWARE_PRE = 0;
	const MIDDLEWARE_POST = 1;

	/**
	 * @var \Psr\Http\Message\RequestInterface
	 */
	protected $request;

	/**
	 * @var \Psr\Http\Message\ResponseInterface
	 */
	protected $response;

	public function __invoke(Request $request, Response $response, $next)
	{
		$this->request = $request;
		$this->response = $response;

		try
		{
			$this->call(self::MIDDLEWARE_PRE);
			$this->response = $next($this->request, $this->response);
			$this->call(self::MIDDLEWARE_POST);
		}
		catch (InstantResponseException $e)
		{
			$this->response = $e->getResponse();
		}

		return $this->response;
	}

	protected final function call($callType)
	{
		$response = ($callType == self::MIDDLEWARE_PRE) ? $this->preHandle() : $this->postHandle();
		if ($response instanceof Response)
		{
			$this->response = $response;
		}
		else if ($response instanceof Request)
        {
            $this->request = $response;
        }
	}

	/**
	 * Pre handler for declared middleware.
	 *
	 * @return \Psr\Http\Message\ResponseInterface|null
	 */
	protected function preHandle()
	{
	}

	/**
	 * Post handler for declared middleware.
	 *
	 * @return \Psr\Http\Message\ResponseInterface|null
	 */
	protected function postHandle()
	{
	}

	protected function exception(Response $response)
	{
		return new InstantResponseException($response);
	}
}
