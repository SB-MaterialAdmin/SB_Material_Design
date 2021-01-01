<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Util;

class Guid
{
	/**
	 * Generate a globally unique identifier (GUID)
	 * Note: this function copied from phunction <https://sourceforge.net/projects/phunction/>
	 *
	 * @return string
	 */
	public static function generate()
	{
		if (function_exists('com_create_guid') === true)
		{
			return trim(com_create_guid(), '{}');
		}

		return sprintf(
			'%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
			mt_rand(0, 65535), mt_rand(0, 65535),
			mt_rand(0, 65535),
			mt_rand(16384, 20479),
			mt_rand(32768, 49151),
			mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535)
		);
	}
}