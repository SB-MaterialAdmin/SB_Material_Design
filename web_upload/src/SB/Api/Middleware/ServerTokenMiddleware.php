<?php

namespace SB\Api\Middleware;

use Framework\Middleware\AbstractMiddleware;
use Framework\Util\Arr;

class ServerTokenMiddleware extends AbstractMiddleware
{
    protected function preHandle()
    {
        $token = $this->request->getHeaderLine('X-ServerToken');
        $server = \Framework::container()->get('database')
            ->mapper('\SB\Entity\Server')
            ->where(['token' => $token])
            ->with('mod')
            ->first();

        if (!$server)
        {
            throw $this->exception($this->response->withJson([
                'success'   =>  false,
                'error'     =>  [
                    'code'      => 0,
                    'message'   => 'Invalid token passed',
                ],
            ], 400));
        }

        return $this->request->withAttribute('server', $server);
    }
}