<?php
/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Util;

class Str
{
	/**
	 * @param $string
	 * @param $needle
	 * @return bool
	 */
	public static function startsWith($string, $needle)
	{
		return (substr($string, 0, strlen($needle)) === $needle);
	}

	/**
	 * @param $string
	 * @param $needle
	 * @return bool
	 */
	public static function endsWith($string, $needle)
	{
		return (substr($string, -strlen($needle)) === $needle);
	}

	/**
	 * @param $str
	 * @param $args
	 * @param $startToken
	 * @param $endToken
	 * @return string
	 */
	public static function format($str, array $args = [], $startToken = '{', $endToken = '}')
	{
		while (true)
		{
			$startTok = strtok($str, $startToken);
			if ($startTok === $str)
			{
				break;
			}

			$searchableStr = strtok($endToken);
			if (!$searchableStr)
			{
				throw new \InvalidArgumentException('Not found ending token when start token is detected', 500);
			}

			$token = trim($searchableStr);
			if (!Arr::keyExists($args, $token))
			{
				throw new \InvalidArgumentException('Not found token name in arguments', 501);
			}

			$arg = $args[$token];
			if (is_object($arg) && method_exists($arg, '__toString'))
			{
				$arg = $arg->__toString();
			} else if (is_array($arg)) {
				// what?
				throw new \InvalidArgumentException(sprintf('$args[%s] must be a object/string/int/float, array given', $token));
			}

			$str = str_replace(sprintf('%s%s%s', $startToken, $searchableStr, $endToken), $args[$token], $str);
		}

		return $str;
	}

	/**
	 * @param string	$string
	 * @param string	$needle
	 * @param int		&$pos
	 */
	public static function contains($string, $needle, &$pos = null)
	{
		$pos = strpos($string, $needle);
		return ($pos !== false);
	}
}