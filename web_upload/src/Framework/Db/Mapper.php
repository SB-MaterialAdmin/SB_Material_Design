<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Db;


class Mapper extends \Spot\Mapper
{
	/** @var bool $_extended */
	protected $_extended = false;

	/**
	 * Get name of the Entity class mapper was instantiated with
	 *
	 * @return string $entityName
	 */
	public function entity()
	{
		if (!$this->_extended)
		{
			$this->entityName = \Framework::extendClass($this->entityName);
		}

		return $this->entityName;
	}
}