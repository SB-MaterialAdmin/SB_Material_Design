<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Session;


use Framework\Collection\SimpleCollection;

class UserSession extends SimpleCollection
{
	/** @var \Framework\Session\AbstractSessionManager $sessionManager */
	protected $sessionManager;

	/** @var string $id */
	protected $id;

	/** @var \Framework\Visitor $visitor */
	protected $visitor;

	/**
	 * UserSession constructor.
	 * @param \Framework\Session\AbstractSessionManager $sessionManager
	 * @param string $id
	 * @param array $data
	 */
	public function __construct(AbstractSessionManager $sessionManager, $id, array $data = [])
	{
		$this->sessionManager = $sessionManager;
		$this->id = $id;

		parent::__construct($data, 0);

		$session = $this;
		\Framework::app()->extension()->getEventEmitter()->on('app_post_run', function() use ($sessionManager, $session)
		{
			$sessionManager->saveSession($session);
		});
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return \Framework\Visitor
	 */
	public function getVisitor()
	{
		if (!$this->visitor)
		{
			/** User entity don't instantiated. Instantiate now! */
			$container = \Framework::container();
			if ($container->has('app.visitor.class'))
			{
				throw new \RuntimeException('Class for visitor don\'t defined.');
			}

			$visitorClass = \Framework::extendClass($container->get('app.visitor.class'));
			$this->visitor = new $visitorClass($this->get('visitor'));
		}

		return $this->visitor;
	}
}