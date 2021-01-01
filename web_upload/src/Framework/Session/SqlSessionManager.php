<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Session;


use Framework\Util\Guid;

class SqlSessionManager extends AbstractSessionManager
{
	/**
	 * Loads session.
	 * @param string $sessionId
	 * @return \Framework\Session\UserSession|null
	 */
	public function loadSession($sessionId)
	{
		/** @var \Framework\Entity\Session|bool $session */
		$session = $this->mapper()->first(['id' => $sessionId]);
		if (!$session)
		{
			return null;
		}

		return new UserSession($this, $sessionId, $session->data);
	}

	/**
	 * @param string|null $sessionId
	 * @return \Framework\Session\UserSession
	 */
	public function instantiateSession($sessionId = null)
	{
		/** @var \Framework\Entity\Session $session */
		$session = $this->mapper()->create([
			'id'	=> $sessionId ?: '',
		]);

		$session->save();
		return new UserSession($this, $session->id, $session->data);
	}

	/**
	 * Saves session.
	 * @param \Framework\Session\UserSession $session
	 */
	public function saveSession(UserSession $session)
	{
		$id = $session->getId();

		/** @var \Framework\Entity\Session $sessionEntity */
		$sessionEntity = $this->mapper()->first(['id'	=> $id]);
		if (!$sessionEntity)
		{
			$sessionEntity = $this->instantiateSession($id);
		}

		$sessionEntity->data = $session->all();
		$sessionEntity->save();
	}

	/**
	 * @return \Framework\Db\Locator
	 */
	protected function database()
	{
		return \Framework::container()->get('database');
	}

	protected function mapper()
	{
		return $this->database()->mapper('\Framework\Entity\Session');
	}
}