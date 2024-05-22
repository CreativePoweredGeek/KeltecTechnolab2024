<?php

namespace CartThrob\Plugins\Tax;

use CartThrob\Plugins\Plugin;
use CartThrob\Traits\ValidateTrait;

abstract class TaxPlugin extends Plugin
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
