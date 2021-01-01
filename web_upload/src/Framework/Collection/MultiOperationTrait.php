<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Collection;

trait MultiOperationTrait
{
	/**
	 * Add items to collection, replacing existing items with the same data key.
	 *
	 * @param	array	$items	Key-value array of data to append to this collection.
	 */
	public function replace($array)
	{
		foreach ($array as $key => $value)
		{
			$this->set($key, $value);
		}
	}

	/**
	 * Get all items in collection.
	 *
	 * @return	array	The collection's source data
	 */
	public function all()
	{
		return $this->_values;
	}

	/**
	 * Get collection keys
	 *
	 * @return	array	The collection's source data keys
	 */
	public function keys()
	{
		return array_keys($this->_values);
	}

	/**
	 * Remove all items from collection
	 */
	public function clear()
	{
		$this->_values = [];
	}
}
