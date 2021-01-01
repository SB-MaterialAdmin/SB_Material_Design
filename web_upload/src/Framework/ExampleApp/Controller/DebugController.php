<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\ExampleApp\Controller;

class DebugController extends \Framework\Controller\AbstractController
{
	public function actionWhoops($message = '')
	{
		$message = empty($message) ? 'Self-testing Whoops' : $message;

		throw new \Exception('Manually throwed exception: ' . $message);
	}

	public function actionIncluded()
	{
		return $this->response->withJson(get_included_files());
	}

	public function actionConfig()
	{
		return $this->response->withJson($this->container->get('config')->all());
	}

	public function actionCookie()
	{
		/** @var \Framework\Cookie\CookieManager $cookieManager */
		$cookieManager = $this->container()->get('cookieManager');

		/** @var \Framework\Cookie\Cookie $cookie */
		$cookie = $cookieManager->createIfNotExists('debugCookie');

		$value = intval($cookie->getValue());
		$newValue = $value + 1;

		$cookie->setValue($newValue)
			->setExpiryTime(\Framework::$time + 120)
			->save();

		$this->response->getBody()->write($value);
	}

	public function actionSession()
	{
		$session = $this->session();
		if (!$session->has('debug'))
		{
			$session->debug = 0;
		}

		$session->debug++;
		$this->response->getBody()->write($session['debug']);
	}
}
