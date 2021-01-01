<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace SB\Api\Controller;

class HomeController extends \Framework\Controller\AbstractController
{
	public function actionIndex()
	{
		return $this->json([
			'success'	=> true,
			'server'	=> $this->server()->toArray(),
		]);
	}
}
