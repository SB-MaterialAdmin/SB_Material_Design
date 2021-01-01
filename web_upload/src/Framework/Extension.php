<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework;


class Extension
{
	/** @var \Sabre\Event\EventEmitter $eventEmitter */
	protected $eventEmitter = null;

	/** @var \Framework\ClassExtension $classExtension */
	protected $classExtension = null;
	/**
	 * Extension constructor.
	 */
	public function __construct()
	{
		$this->eventEmitter = new \Sabre\Event\EventEmitter();
		$this->classExtension = new ClassExtension();
	}

	/**
	 * @return \Sabre\Event\EventEmitter
	 */
	public function getEventEmitter()
	{
		return $this->eventEmitter;
	}

	/**
	 * @return \Framework\ClassExtension
	 */
	public function getClassExtension()
	{
		return $this->classExtension;
	}
}