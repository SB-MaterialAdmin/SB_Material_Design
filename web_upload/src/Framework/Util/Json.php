<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Util;

use Framework\Exception\JsonException;

class Json
{
	/**
	 * Decodes the passed JSON.
	 *
	 * @param	string	$data
	 * @param	bool	$assoc
	 * @param	int		$depth
	 * @param	int		$options
	 * @return	mixed
	 */
	public static function decode($data, $assoc = true, $depth = 512, $options = 0)
	{
		$result = @json_decode($data, $assoc, $depth, $options);

		self::checkErrors();
		return $result;
	}

	/**
	 * Decodes the passed file with JSON.
	 *
	 * @param	string	$file
	 * @param	bool	$assoc
	 * @param	int		$depth
	 * @param	int		$options
	 * @return	mixed
	 */
	public static function decodeFile($file, $assoc = true, $depth = 512, $options = 0)
	{
		if (!file_exists($file))
		{
			throw new \LogicException('File cannot be readed: no such file or permission denied.');
		}

		$data = file_get_contents($file);
		return self::decode($data, $assoc, $depth, $options);
	}

	/**
	 * Encodes the passed array as JSON.
	 *
	 * @param	array	$data
	 * @param	int		$options
	 * @param	int		$depth
	 */
	public static function encode($data, $options = 0, $depth = 512)
	{
		$result = @json_encode($data, $options, $depth);
		self::checkErrors();

		return $result;
	}

	/**
	 * Checks error existing and throws exception (if required).
	 */
	protected static function checkErrors()
	{
		$errorCode = json_last_error();
		if ($errorCode == JSON_ERROR_NONE)
		{
			return;
		}

		throw new JsonException($errorCode, json_last_error_msg());
	}
}
