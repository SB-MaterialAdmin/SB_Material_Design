<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Entity;


use Framework\Db\Mapper;
use Spot\EventEmitter;
use Framework\Util\Str;

abstract class AbstractEntity extends \Spot\Entity
{
	/**
	 * @var \Framework\Db\Mapper $mapper
	 */
	protected $__mapper = null;
	/**
	 * TODO: rollback() for restore values before editing
	 */

	/**
	 * @param EventEmitter $eventEmitter
	 */
	public static function events(EventEmitter $eventEmitter)
	{
		$eventEmitter->on('beforeSave', function (AbstractEntity $entity, Mapper $mapper)
		{
			$params = [
				'entity'	=> $entity,
				'mapper'	=> $mapper,
			];

			\Framework::fireEvent('entity_before_save', $params);
		});
	}

	/**
	 * Saves the entity.
	 */
	public function save()
	{
		$this->__mapper->save($this);
	}

	/**
	 * Deletes the entity.
	 */
	public function delete()
	{
		$this->__mapper->delete($this->toArray());
		$this->_isNew = true;
	}

	/**
	 * Rollbacks all entity fields.
	 */
	public function rollback()
	{
		throw new \Exception("This method isn't ready.");
	}

	public function __construct(array $data = array())
	{
		// Set mapper.
		$this->loadMapper();

		// Call parent method.
		parent::__construct($data);
	}

	/**
	 * Loads the mapper for internal usages.
	 */
	protected function loadMapper()
	{
		/** @var \Framework\Db\Locator $db */
		$db = \Framework::container()->get('database');
		$this->__mapper = $db->mapper('\\' . get_class($this));
	}

	public function __toString()
	{
		return Str::format('{ class }[{ id }]', [
			'class'	=> get_called_class(),
			'id'	=> $this->isNew() ?
				'unsaved' : 
				($this->primaryKey() ?:
					'unknown'
				)
		]);
	}
}
