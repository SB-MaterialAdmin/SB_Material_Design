<?php

/**
 * This file is a part of slight and fast framework.
 * This framework uses a third-party components. Full list you can find in our example app (run example.index.php for start).
 */

namespace Framework\Service;

use Framework\App;

abstract class AbstractService
{
    /**
     * @var \Framework\App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->setup();
    }

    protected function setup()
    {
    }

    /**
     * @param   string  $class
     * @return  \Framework\Service\AbstractService
     */
    protected function service($class)
    {
        return call_user_func_array([$this->app, 'service'], func_get_args());
    }
}