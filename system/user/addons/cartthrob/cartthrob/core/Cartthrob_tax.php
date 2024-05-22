<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Traits\ValidateTrait;
use ExpressionEngine\Service\Validation\ValidationAware;

abstract class Cartthrob_tax extends Cartthrob_child implements ValidationAware
{
    use ValidateTrait;

    public $title = '';
    public $note = '';
    public $overview = '';
    public $html = '';
    public $settings = [];

    /**
     * @param array $params
     * @param array $defaults
     * @return $this|void
     */
    public function initialize($params = [], $defaults = [])
    {
        return $this;
    }

    /**
     * @param $key
     * @param bool $default
     * @return array|bool|mixed
     */
    public function plugin_settings($key, $default = false)
    {
        $settings = $this->core->store->config(get_class($this) . '_settings');

        if ($key === false) {
            return ($settings) ? $settings : $default;
        }

        return (isset($settings[$key])) ? $settings[$key] : $default;
    }

    /**
     * @param $price
     * @return mixed
     */
    abstract public function get_tax($price);
}
