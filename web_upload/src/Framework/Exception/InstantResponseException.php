<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Exception;

use Psr\Http\Message\ResponseInterface as Response;
use Throwable;

class InstantResponseException extends \Exception
{
	/**
	 * @var \Psr\Http\Message\ResponseInterface
	 */
	protected $response;

	public function __construct(Response $response, $message = "", $code = 0, Throwable $previous = null)
	{
		$this->response = $response;
		parent::__construct($message, $code, $previous);
	}

	/**
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}
}