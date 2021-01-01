<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\RouteGroup;


class CustomRouteGroup extends AbstractRouteGroup
{
	/** @var string $pattern */
	protected $pattern;

	/** @var callable $callable */
	protected $callable;

	/**
	 * @param string $pattern
	 * @return CustomRouteGroup
	 */
	public function setPattern(string $pattern)
	{
		$this->pattern = $pattern;
		return $this;
	}

	/**
	 * @param callable $callable
	 * @return CustomRouteGroup
	 */
	public function setCallable(callable $callable)
	{
		$this->callable = $callable;
		return $this;
	}

	/**
	 * Return the base pattern.
	 * @return string
	 */
	public function getPattern()
	{
		return $this->pattern;
	}

	protected function handle()
	{
		$handler = $this->callable;
		$handler($this);
	}
}