<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Cookie;

use Framework\Util\Str;

class CookieManager extends \Framework\Collection\SimpleCollection
{
	/**
	 * @var string $cookiesPrefix
	 * Prefix for cookies.
	 */
	protected $cookiesPrefix = '';

	/**
	 * @var string $defaultPath
	 * Default path for all cookies.
	 */
	protected $defaultPath = '/';

	/**
	 * @var string $defaultDomain
	 * Default domain for all cookies.
	 */
	protected $defaultDomain = '';

	/**
	 * CookieManager constructor.
	 * @param string $cookiesPrefix
	 * @param string $defaultPath
	 * @param string $defaultDomain
	 * @param array $cookies
	 */
	public function __construct($cookiesPrefix, $defaultPath, $defaultDomain, $cookies = [])
	{
		$this->cookiesPrefix = $cookiesPrefix;
		$this->defaultPath = $defaultPath;
		$this->defaultDomain = $defaultDomain;

		$_data = [];
		foreach ($cookies as $name => $value)
		{
			$cookie = $this->create($name);
			$cookie->setValue($value);
			$cookie->markAsLoaded();
			$_data[$name] = $cookie;
		}

		parent::__construct($_data, 0);
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @throws \LogicException
	 */
	public function set($key, $value)
	{
		throw $this->generateErrorException('change cookie referer');
	}

	/**
	 * @throws \LogicException
	 */
	public function clear()
	{
		throw $this->generateErrorException('drop all cookies');
	}

	/**
	 * @param array $array
	 * @throws \LogicException
	 */
	public function replace($array)
	{
		throw $this->generateErrorException('change cookies referer');
	}

	/**
	 * @param string $text
	 * @return \LogicException
	 */
	protected function generateErrorException($text)
	{
		return new \LogicException("You can't {$text}");
	}

	/**
	 * @param string $name
	 *
	 * @return \Framework\Cookie\Cookie
	 */
	public function create($name)
	{
		return new Cookie($this, $name);
	}

	/**
	 * Creates a cookie, if not exists.
	 * @param $name
	 * @return Cookie
	 */
	public function createIfNotExists($name)
	{
		return ($this->has($name)) ? $this->get($name) : $this->create($name);
	}

	/**
	 * Sends the cookie on browser.
	 *
	 * @param \Framework\Cookie\Cookie $cookie
	 */
	public function save(Cookie $cookie)
	{
		$cookieName = $cookie->getName();
		if ($this->has($cookieName))
			$this->_values[$cookieName] = $cookie;

		$this->sendCookie($cookie);
	}

	public function get($key)
	{
		$cookiesPrefix = $this->getCookiesPrefix();
		if ($this->has($cookiesPrefix . $key))
			$key = $cookiesPrefix . $key;

		return parent::get($key);
	}

	public function has($key)
	{
		return (array_key_exists($key, $this->_values) || array_key_exists($this->getCookiesPrefix() . $key, $this->_values));
	}

	/**
	 * Deletes the cookie from browser.
	 *
	 * @param \Framework\Cookie\Cookie $cookie
	 */
	public function delete(Cookie $cookie)
	{
		$cookieName = $cookie->getName();
		if (!$this->has($cookieName))
			throw new \LogicException("This cookie isn't saved on client.");

		if (!array_key_exists($cookieName, $this->_values))
			$cookieName = $this->getCookiesPrefix() . $cookieName;

		unset($this->_values[$cookieName]);
		$this->sendCookie(
			(clone $cookie)
				->setExpiryTime(0)
				->setValue('')
		);
	}

	/**
	 * Sends cookie.
	 *
	 * @param \Framework\Cookie\Cookie $cookie
	 */
	protected function sendCookie(Cookie $cookie)
	{
		// Apply prefix.
		$cookieHeader = $cookie->getHeaderString();
		header("Set-Cookie: {$cookieHeader}");
	}

	/**
	 * @return string
	 */
	public function getCookiesPrefix()
	{
		return $this->cookiesPrefix;
	}

	/**
	 * @param string $cookiesPrefix
	 * @return CookieManager
	 */
	public function setCookiesPrefix($cookiesPrefix)
	{
		$this->cookiesPrefix = (string) $cookiesPrefix;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultPath()
	{
		return $this->defaultPath;
	}

	/**
	 * @param string $defaultPath
	 * @return CookieManager
	 */
	public function setDefaultPath($defaultPath)
	{
		$this->defaultPath = (string) $defaultPath;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getDefaultDomain()
	{
		return $this->defaultDomain;
	}

	/**
	 * @param string $defaultDomain
	 * @return CookieManager
	 */
	public function setDefaultDomain($defaultDomain)
	{
		$this->defaultDomain = (string) $defaultDomain;
		return $this;
	}
}