<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_store extends Cartthrob_child
{
    /** @var array */
    private $products;

    /** @var array */
    private $plugins;

    /**
     * @return int
     */
    public function tax_rate()
    {
        if ($plugin = $this->plugin($this->config('tax_plugin'))) {
            return $plugin->tax_rate();
        }

        return 0;
    }

    /**
     * @param $class
     * @return Cartthrob_child|mixed|null
     */
    public function plugin($class)
    {
        if (!$class) {
            return null;
        }

        if (isset($this->plugins[$class])) {
            return $this->plugins[$class];
        }

        return $this->plugins[$class] = Cartthrob_core::create_child($this->core, $class);
    }

    /**
     * @return array|bool|mixed
     */
    public function config()
    {
        return $this->core->config(func_get_args());
    }

    /**
     * @return string
     */
    public function tax_name()
    {
        if ($plugin = $this->plugin($this->config('tax_plugin'))) {
            return $plugin->tax_name();
        }

        return '';
    }

    /**
     * @param $key
     * @param bool $value
     * @return $this
     */
    public function set_config($key, $value = false)
    {
        $this->core->set_config($key, $value);

        return $this;
    }

    /**
     * @param $override_config
     * @return $this
     */
    public function override_config($override_config)
    {
        $this->core->override_config($override_config);

        return $this;
    }

    /**
     * @param $productId
     * @return Cartthrob_product|bool
     */
    public function product($productId)
    {
        if (isset($this->products[$productId])) {
            return $this->products[$productId];
        }

        if ($product = $this->core->get_product($productId)) {
            return $this->products[$productId] = $product;
        }

        return false;
    }
}
