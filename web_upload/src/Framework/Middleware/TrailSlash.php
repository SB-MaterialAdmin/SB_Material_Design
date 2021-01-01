<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Middleware;

class TrailSlash extends AbstractMiddleware
{
	/**
	 * {@inheritdoc}
	 */
	protected function preHandle()
	{
		$uri = $this->request->getUri();
		$path = $uri->getPath();

		if ($path != '/' && substr($path, -1) === '/')
		{
			// permanently redirect paths with a trailing slash to
			// their non-trailing counterpart, if request method is
			// GET. if method any another - just handle next.
			$uri = $uri->withPath(substr($path, 0, -1));

			if ($this->request->getMethod() == 'GET')
			{
				throw $this->exception($this->response->withRedirect((string) $uri, 301));
			}
			else
			{
				$this->request = $this->request->withUri($uri);
			}
		}
	}
}
