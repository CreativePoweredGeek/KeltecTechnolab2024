<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_price extends Cartthrob_child
{
    public $title = '';
    public $settings = [];
    public $data = [];
    public $classname = '';
    public $type = '';
    public $markup = false;

    /**
     * Cartthrob_price constructor.
     */
    public function __construct()
    {
        $this->classname = get_class($this);
        $this->type = preg_replace('/^Cartthrob_/', '', $this->classname);
    }

    /**
     * @param $key
     * @return bool|mixed
     */
    public function data($key)
    {
        return (isset($this->data[$key])) ? $this->data[$key] : false;
    }

    /**
     * @param $price
     * @return mixed
     */
    public function adjust_price($price)
    {
        return $price;
    }
}
