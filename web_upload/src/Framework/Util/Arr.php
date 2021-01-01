<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Util;

use Framework\Collection\AbstractCollection;

class Arr
{
	/**
	 * Checks if the given key or index exists in the array
	 *
	 * @param	mixed	$array
	 * @param	mixed	$key
	 * @return	boolean
	 */
	public static function keyExists($array, $key)
	{
		if (is_array($array))
		{
			return array_key_exists($key, $array);
		}
		else if (is_object($array))
		{
			if ($array instanceof AbstractCollection)
			{
				return $array->has($key);
			}
			else if ($array instanceof \ArrayAccess)
			{
				return isset($array[$key]);
			}
		}

		$typeName = is_object($array) ? get_class($array) : gettype($array);
		$expected = ['array', AbstractCollection::class, \ArrayAccess::class];

		throw new \LogicException('Passed incorrect array object. Expected: ' . implode(' OR ', $expected) . '. Given: ' . $typeName);
	}

	/**
	 * Checks if the all given keys or indexes in the array.
	 *
	 * @param	mixed	$array
	 * @param	array	$keys
	 * @return	boolean
	 */
	public static function keysExists($array, array $keys)
	{
		foreach ($keys as $key)
		{
			if (!self::keyExists($array, $key))
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Merges passed arrays.
	 *
	 * @return array
	 */
	public static function merge()
	{
		return call_user_func_array('array_merge', func_get_args());
	}

	/**
	 * Gets the first key of an array
	 *
	 * @param  array	$array
	 * @return string	
	 */
	public static function firstKey($array)
	{
		if (function_exists('array_key_first'))
		{
			return array_key_first($array);
		}

		foreach ($array as $key => $unused)
		{
			return $key;
		}
		return '';
	}

	/**
	 * Gets the last key of an array
	 * 
	 * @param  array		$array
	 * @return string|null	
	 */
	public static function lastKey($array)
	{
		if (function_exists('array_key_last'))
		{
			return array_key_last($array);
		}

		// some PHP versions can't handle code like "func()[0]".
		$keys = array_keys();
		$index = count($keys)-1;

		if ($index < 0)
		{
			return null;
		}
		return $keys[$index];
	}
}
