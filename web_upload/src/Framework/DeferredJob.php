<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;


class DeferredJob
{
	/**
	 * @var array
	 */
	protected static $deferredJobs = [];

	/**
	 * Add a job to "late start".
	 *
	 * @param \Closure $callback
	 */
	public static function runOnce(\Closure $callback)
	{
		self::$deferredJobs[] = $callback;
	}

	/**
	 * Runs the all jobs.
	 */
	public static function run()
	{
		$jobs = self::$deferredJobs;
		if (count($jobs) < 1)
		{
			return; // nothing to do.
		}

		foreach ($jobs as $job)
		{
			try {
				$job();
			}
			catch (\Exception $e)
			{
				// suppress any exceptions (PHP 5, 7)
			}
			catch (\Throwable $e)
			{
				// suppress any errors (PHP 7)
			}
		}
	}
}