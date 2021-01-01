<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Controller;

use Framework\Entity\AbstractEntity as Entity;

abstract class AbstractCrudController extends AbstractController
{
	/**
	 * Available operations with entity.
	 */
	const OP_LIST	= 1;
	const OP_CREATE	= 2;
	const OP_READ	= 3;
	const OP_UPDATE	= 4;
	const OP_DELETE	= 5;

	public function __call($name, $arguments)
	{
		// TODO: Implement __call() method. We should automatically transform entity identifier to entity instance. This right?
		return parent::__call($name, $arguments);
	}

	public function actionIndex()
	{
		// TODO: list all entities in database.
	}

	public function actionCreate(Entity $entity = null)
	{
		// TODO: create entity in database.
	}

	public function actionRead(Entity $entity = null)
	{
		// TODO: view entity (we really need this method?).
	}

	public function actionUpdate(Entity $entity = null)
	{
		// TODO: update entity in database.
	}

	public function actionDelete(Entity $entity = null)
	{
		// TODO: delete entity from database.
	}

	/**
	 * @param int $operation
	 * @return bool
	 */
	protected function hasPermission($operation = self::OP_LIST)
	{
		if (!$this->isOperationAllowed($operation))
		{
			return false;
		}

		return $this->_hasPermission($operation);
	}

	/**
	 * @param $operation
	 * @return bool
	 */
	protected function _hasPermission($operation)
	{
		// TODO: by default, we think "user can do everything". maybe, add calling method `hasPermission()` with dynamic named permission name like `can{$entity}{$operation}`?
		return true;
	}

	/**
	 * @param $operation
	 * @return bool
	 */
	protected function isOperationAllowed($operation)
	{
		return true;
	}
}
