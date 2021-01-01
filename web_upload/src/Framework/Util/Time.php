<?php
/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Util;

class Time
{
	const Second	= 1;
	const Minute	= self::Second	* 60;
	const Hour		= self::Minute	* 60;
	const Day		= self::Hour	* 24;
	const Week		= self::Day		* 7;
	const Month		= self::Day		* 30;
	const Year		= self::Day		* 365;

	/**
	 * Returns the DateTime instance for current moment.
	 * @return \DateTime
	 */
	public static function getCurrentTime()
	{
		return new \DateTime("now");
	}

	/**
	 * Returns the DateTime instance for framework startup time.
	 * @return \DateTime
	 */
	public static function getStartupTime()
	{
		return self::getCurrentTime()->setTimestamp(\Framework::$time);
	}

	/**
	 * @param $intervalSpec
	 * @return \DateInterval
	 */
	public static function getInterval($intervalSpec)
	{
		return new \DateInterval('P' . $intervalSpec);
	}
}