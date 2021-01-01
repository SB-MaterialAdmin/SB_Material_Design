<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Session;


use Framework\Cookie\CookieManager;
use Framework\Util\Time;

abstract class AbstractSessionManager
{
	/** @var \Framework\Cookie\CookieManager $cookieManager */
	protected $cookieManager;

	/** @var string $cookieName */
	protected $cookieName;

	public function __construct(CookieManager $cm, array $params = [])
	{
		$this->cookieManager = $cm;

		if ($params === [])
		{
			$params = $this->getDefaultParams();
		}
		$this->setup($params);

		if (!array_key_exists('cookieName', $params) || empty($params['cookieName']))
		{
			$params['cookieName'] = 'session';
		}

		$this->cookieName = $params['cookieName'];
	}

	/**
	 * @var \Framework\Session\UserSession $currentSession
	 */
	protected $currentSession = null;

	/**
	 * @param bool $createSessionIfNotExists
	 * @return \Framework\Session\UserSession
	 */
	public function getCurrentSession($createSessionIfNotExists = true)
	{
		if (!$this->currentSession && $createSessionIfNotExists)
		{
			$this->createSessionFromCookie(true);
		}

		return $this->currentSession;
	}

	/**
	 * @param UserSession $currentSession
	 */
	public function setCurrentSession(UserSession $currentSession)
	{
		$this->currentSession = $currentSession;
	}

	/**
	 * Loads session or creates, if not found.
	 * @param $sessionId
	 * @return \Framework\Session\UserSession
	 */
	public function loadOrCreateSession($sessionId)
	{
		return $this->loadSession($sessionId) ?: $this->instantiateSession($sessionId);
	}

	/**
	 * @param bool $setAsCurrent
	 * @return \Framework\Session\UserSession
	 */
	public function createSessionFromCookie($setAsCurrent = true)
	{
		/** @var \Framework\Cookie\CookieManager $cookieManager */
		/** @var string $cookieName */
		list($cookieManager, $cookieName) = [$this->cookieManager, $this->cookieName];

		/** @var \Framework\Session\UserSession $session */
		$session = null;
		$cookie = $cookieManager->createIfNotExists($cookieName);
		$id = $cookie->getValue();

		if (!empty($id))
		{
			$session = $this->loadSession($cookie->getValue());
		}
		else
		{
			$cookie = $cookieManager->create($cookieName);
		}

		// If session not found (on this moment), create new.
		if (!$session)
		{
			$session = $this->instantiateSession();
		}

		// Save session ID in cookie and send.
		$cookie->setValue($session->getId())
			->setExpiryTime(\Framework::$time + Time::Year)
			->save();

		if ($setAsCurrent)
		{
			$this->setCurrentSession($session);
		}

		return $session;
	}

	/**
	 * Do some additional logic.
	 * @param array $params
	 */
	public function setup(array $params)
	{
	}

	/**
	 * @return array
	 */
	public function getDefaultParams()
	{
		return [];
	}

	/**
	 * Loads session.
	 * @param string $sessionId
	 * @return \Framework\Session\UserSession|null
	 */
	public abstract function loadSession($sessionId);

	/**
	 * @param string|null $sessionId
	 * @return \Framework\Session\UserSession
	 */
	public abstract function instantiateSession($sessionId = null);

	/**
	 * Saves session.
	 * @param \Framework\Session\UserSession $session
	 */
	public abstract function saveSession(UserSession $session);
}