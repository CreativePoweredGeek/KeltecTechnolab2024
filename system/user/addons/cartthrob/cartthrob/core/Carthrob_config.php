<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

/**
 * CartThrob Config Class
 */
class Cartthrob_config extends Cartthrob_child
{
    public $config = [];

    /**
     * @param array $config
     * @param array $defaults
     */
    public function initialize($config = [], $defaults = [])
    {
        // return $this->set_config($config);
        $this->set_config($config);
    }

    /**
     * @param array $config
     */
    public function set_config($config = [])
    {
        $this->config = $config;
    }
}
