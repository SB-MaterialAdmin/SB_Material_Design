<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Collection;

abstract class AbstractCollection implements \ArrayAccess, \Countable, \IteratorAggregate
{
	/**
	 * @var array
	 */
	protected $_values = [];

	public function set($key, $value)
	{
		$this->_values[$key] = $value;
	}

	public function get($key)
	{
		return $this->has($key) ?
			$this->_values[$key] :
			null;
	}

	public function has($key)
	{
		return array_key_exists($key, $this->_values);
	}

	public function remove($key)
	{
		if ($this->has($key))
		{
			unset($this->_values[$key]);
		}
	}

	public function __construct(array $data = [])
	{
		$this->_values = $data;
	}

	/*************************************************************************
	 * ArrayAccess implementation
	 *************************************************************************/
	public function offsetExists($offset)
	{
		return $this->has($offset);
	}

	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->remove($offset);
	}

	/*************************************************************************
	 * Default PHP magic methods
	 *************************************************************************/
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	public function __get($name)
	{
		return $this->get($name);
	}

	public function __isset($name)
	{
		return $this->has($name);
	}

	public function __unset($name)
	{
		$this->remove($name);
	}

	/*************************************************************************
	 * Countable implementation
	 *************************************************************************/
	public function count()
	{
		return count($this->_values);
	}

	/*************************************************************************
	 * IteratorAggregate implementation
	 *************************************************************************/
	public function getIterator()
	{
		return new \ArrayIterator($this->_values);
	}
}
