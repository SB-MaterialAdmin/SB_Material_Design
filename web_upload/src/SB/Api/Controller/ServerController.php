<?php

namespace SB\Api\Controller;

class ServerController extends AbstractController
{
    public function actionIndex()
    {
        $response = [
            'server'            =>  $this->server()->data(['sid', 'ip', 'port', 'mod']),

            'groups'            =>  $this->fetchGroups(),
            'overrides'         =>  $this->fetchOverrides(),
            'administrators'    =>  $this->fetchAdministrators(),
        ];

        return $this->apiResponse($response);
    }

    public function actionGroups()
    {
        return $this->apiResponse($this->fetchGroups());
    }

    public function actionOverrides()
    {
        return $this->apiResponse($this->fetchOverrides());
    }

    public function actionAdministrators()
    {
        return $this->apiResponse($this->fetchAdministrators());
    }

    /**
     * Returns the array with groups.
     * 
     * @return array
     */
    protected function fetchGroups()
    {
        return $this->mapper('ServerGroup')
            ->all()
            ->with('overrides')
            ->toArray();
    }

    /**
     * Returns the array with overrides.
     * 
     * @return array
     */
    protected function fetchOverrides()
    {
        return $this->mapper('Override')
            ->all()
            ->toArray();
    }

    /**
     * Returns the array with administrators.
     * 
     * @return array
     */
    protected function fetchAdministrators()
    {
        return $this->server()->getAdministrators();
    }
}