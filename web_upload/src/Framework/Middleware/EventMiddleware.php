<?php
/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Middleware;

class EventMiddleware extends AbstractMiddleware
{
	protected function preHandle()
	{
		\Framework::app()->extension()->getEventEmitter()->emit('app_pre_run');
	}

	protected function postHandle()
	{
		\Framework::app()->extension()->getEventEmitter()->emit('app_post_run');
	}
}