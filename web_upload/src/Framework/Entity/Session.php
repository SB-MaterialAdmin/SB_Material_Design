<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Entity;

use Framework\Util\Guid;
use Spot\EventEmitter;

/**
 * FIELDS
 * @property string $id
 * @property array $data
 */
class Session extends AbstractEntity
{
	protected static $table = 'core_session';

	public static function fields()
	{
		return [
			/** `id` - GUID */
			'id'	=>	[
				'type'		=> 'guid',
				'required'	=> true,
				'primary'	=> true,
			],

			'data'	=>	[
				'type'		=> 'json_array',
				'required'	=> true,
			],
		];
	}

	public static function events(EventEmitter $eventEmitter)
	{
		$eventEmitter->on('beforeSave', function (Session $entity, \Framework\Db\Mapper $mapper)
		{
			if (empty($entity->id))
			{
				if ($entity->isNew())
				{
					$entity->id = Guid::generate();
				}
				else
				{
					throw new \LogicException('Session ID cannot be empty!');
				}
			}

			if ($entity->isNew())
			{
				$entity->data = [];
			}
		});

		parent::events($eventEmitter);
	}
}