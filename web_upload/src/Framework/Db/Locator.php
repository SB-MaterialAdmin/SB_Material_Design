<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Db;


class Locator extends \Spot\Locator
{
	public function mapper($entityName)
	{
		if (substr($entityName, 0, 1) === '\\')
		{
			$entityName = substr($entityName, 1);
		}
		else
		{
			$entityName = \Framework::container()->get('app.classRoot') . '\\' . $entityName;
		}

		return $this->_mapper($entityName);
	}

	/**
	 * Get mapper for specified entity
	 *
	 * @param string $entityName Name of Entity object to load mapper for
	 * @return \Spot\Mapper
	 */
	protected function _mapper($entityName)
	{
		if (!isset($this->mapper[$entityName])) {
			// Get custom mapper, if set
			$mapper = $entityName::mapper();
			// Fallback to generic mapper
			if ($mapper === false) {
				$mapper = 'Framework\Db\Mapper';
			}

			$mapper = \Framework::extendClass($mapper);
			$this->mapper[$entityName] = new $mapper($this, $entityName);
		}

		return $this->mapper[$entityName];
	}
}