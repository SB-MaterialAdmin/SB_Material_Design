<?php

namespace SB\Api\Controller;

abstract class AbstractController extends \Framework\Controller\AbstractController
{
    /**
     * @return \SB\Entity\Server
     */
    protected function server()
    {
        return $this->request->getAttribute('server');
    }

    protected function apiResponse($response, $error = null)
    {
        if (!$response)
        {
            return $this->json([
                'success'   => false,
                'error'     => $error,
            ]);
        }

        return $this->json([
            'success'   => true,
            'response'  => $response
        ]);
    }

    /**
     * @return \Framework\Db\Mapper
     */
    protected function mapper($entity)
    {
        return $this->container()->get('database')->mapper('\\SB\\Entity\\' . $entity);
    }
}