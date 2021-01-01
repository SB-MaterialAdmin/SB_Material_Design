<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Exception;

class JsonException extends \Exception
{
	/**
	 * @param	int		$code
	 * @param	string	$message
	 */
	public function __construct($code, $message)
	{
		parent::__construct(Str::format('Error occured when working with JSON: [{ code }] { message }', [
			'code'		=> $code,
			'message'	=> $message
		]), $code);
	}
}