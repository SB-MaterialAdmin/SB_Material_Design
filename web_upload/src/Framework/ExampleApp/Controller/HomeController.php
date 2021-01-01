<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\ExampleApp\Controller;

class HomeController extends \Framework\Controller\AbstractController
{
	public function actionIndex()
	{
		return $this->render('index.tpl');
	}
}
