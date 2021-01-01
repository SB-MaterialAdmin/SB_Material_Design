<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Cookie;


use Framework\Util\Time;

class Cookie
{
	/**
	 * @var \Framework\Cookie\CookieManager $cookieManager
	 */
	protected $cookieManager;

	/**
	 * @var string $name
	 * The name of the cookie which is also the key for future accesses via \Framework\Cookie\CookieManager
	 */
	protected $name;

	/**
	 * @var string $value
	 * The value of the cookie that will be stored on the client's machine
	 */
	protected $value = '';

	/**
	 * @var int $expiryTime
	 * The Unix timestamp indicating the time that the cookie will expire at, i.e. usually `time() + $seconds`
	 */
	protected $expiryTime = -1;

	/**
	 * @var string $path
	 * The path on the server that the cookie will be valid for (including all sub-directories), e.g. an empty string for the current directory or `/` for the root directory
	 */
	protected $path = null;

	/**
	 * @var string|null $domain
	 * The domain that the cookie will be valid for (including subdomains) or `null` for the current host (excluding subdomains)
	 */
	protected $domain = null;

	/**
	 * @var bool $httpOnly
	 * Indicates that the cookie should be accessible through the HTTP protocol only and not through scripting languages
	 */
	protected $httpOnly = true;

	/**
	 * @var bool $secureOnly
	 * Indicates that the cookie should be sent back by the client over secure HTTPS connections only
	 */
	protected $secureOnly = false;

	/**
	 * @var null|string $sameSiteRestriction
	 * Indicates that the cookie should not be sent along with cross-site requests (either `null`, `Lax` or `Strict`)
	 */
	protected $sameSiteRestriction = null;

	/**
	 * @var bool $_loaded
	 */
	protected $_loaded = false;

	/**
	 * Cookie constructor.
	 * @param \Framework\Cookie\CookieManager $cookieManager
	 * @param string $name
	 */
	public function __construct(CookieManager $cookieManager, $name)
	{
		if (empty($name))
		{
			throw new \LogicException('Empty cookie name');
		}

		$this->cookieManager = $cookieManager;
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param string $value
	 * @return Cookie
	 */
	public function setValue($value)
	{
		if (!is_string($value))
		{
			$value = (string) $value;
		}

		$this->value = $value;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getExpiryTime()
	{
		return $this->expiryTime;
	}

	/**
	 * @param int $expiryTime
	 * @return Cookie
	 */
	public function setExpiryTime($expiryTime)
	{
		$expiryTime = intval($expiryTime);
		if ($expiryTime < 0)
		{
			throw new \LogicException("Expiry time can't be negative");
		}

		$this->expiryTime = $expiryTime;
		return $this;
	}

	/**
	 * @return bool|int
	 */
	public function getMaxAge()
	{
		$expiryTime = $this->getExpiryTime();
		$time = Time::getCurrentTime()->getTimestamp();
		if ($expiryTime <= $time)
		{
			return false;
		}

		return ($expiryTime - $time);
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @param string $path
	 * @return Cookie
	 */
	public function setPath($path)
	{
		$this->path = $path;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getDomain()
	{
		return $this->domain;
	}

	/**
	 * @param string|null $domain
	 * @return Cookie
	 */
	public function setDomain($domain)
	{
		$this->domain = $domain;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isHttpOnly()
	{
		return $this->httpOnly;
	}

	/**
	 * @param bool $httpOnly
	 * @return Cookie
	 */
	public function setHttpOnly($httpOnly)
	{
		$this->httpOnly = $httpOnly;
		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSecureOnly()
	{
		return $this->secureOnly;
	}

	/**
	 * @param bool $secureOnly
	 * @return Cookie
	 */
	public function setSecureOnly($secureOnly)
	{
		$this->secureOnly = $secureOnly;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getSameSiteRestriction()
	{
		return $this->sameSiteRestriction;
	}

	/**
	 * @param string|null $sameSiteRestriction
	 * @return Cookie
	 */
	public function setSameSiteRestriction($sameSiteRestriction)
	{
		$this->sameSiteRestriction = $sameSiteRestriction;
		return $this;
	}

	/**
	 * Saves the cookie on browser.
	 */
	public function save()
	{
		$this->cookieManager->save($this);
	}

	/**
	 * Deletes the cookie from browser.
	 */
	public function delete()
	{
		$this->cookieManager->delete($this);
	}

	/**
	 * Checks existing cookie on browser.
	 * @return bool
	 */
	public function isExistsOnClient()
	{
		return $this->cookieManager->has($this->getName());
	}

	/**
	 * Converts the cookie instance to string for using in header()
	 *
	 * @return string
	 */
	public function getHeaderString()
	{
		$data = [];

		// Cookie name and value
		$data[] = sprintf('%s=%s', urlencode($this->getName()), urlencode($this->getValue()));

		// Expiry time.
		$expiryTime = $this->getExpiryTime();
		if ($expiryTime > -1)
		{
			$data[] = sprintf('expires=%s', gmdate('D, d-M-Y H:i:s T', $this->getExpiryTime()));
		}

		// Max-Age.
		$maxAge = $this->getMaxAge();
		if ($maxAge !== false)
		{
			$data[] = sprintf('Max-Age=%d', $maxAge);
		}

		// Path.
		$path = $this->getPath();
		if ($path !== null)
		{
			$data[] = sprintf('path=%s', $path);
		}

		// Domain.
		$domain = $this->getDomain();
		if (!empty($domain))
		{
			$data[] = sprintf('domain=%s', $domain);
		}

		// Is secure only?
		if ($this->isSecureOnly())
		{
			$data[] = 'secure';
		}

		// Is http only?
		if ($this->isHttpOnly())
		{
			$data[] = 'httponly';
		}

		// Same Site restriction.
		$sameSiteRestriction = $this->getSameSiteRestriction();
		if (in_array($sameSiteRestriction, [CookieSameSiteRestriction::LAX, CookieSameSiteRestriction::STRICT]))
		{
			$data[] = sprintf('SameSite=%s', $sameSiteRestriction);
		}

		return implode('; ', $data);
	}

	public function __toString()
	{
		return $this->getValue();
	}

	/**
	 * Marks cookie as loaded from header.
	 * @return $this
	 */
	public function markAsLoaded()
	{
		$time = Time::getStartupTime()->add(Time::getInterval('1Y'))->getTimestamp();

		// Renew cookie (if required to send).
		$this->setExpiryTime($time);
		$this->_loaded = true;

		return $this;
	}
}