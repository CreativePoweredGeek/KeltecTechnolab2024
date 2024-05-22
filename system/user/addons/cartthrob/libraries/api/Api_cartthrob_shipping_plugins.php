<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Api_cartthrob_shipping_plugins extends Api_cartthrob_plugins
{
    protected $shipping_plugin;

    protected $shipping_plugins;

    public function __construct()
    {
        $this->reset_shipping_plugin();

        ee()->load->library('cartthrob_shipping_plugins');
    }

    public function reset_shipping_plugin()
    {
        $this->shipping_plugin = ee()->cartthrob->store->config('shipping_plugin');
        $this->set_plugin(ee()->cartthrob->store->config('shipping_plugin'));

        return $this;
    }

    public function title()
    {
        return ($this->plugin()) ? $this->plugin()->title : '';
    }

    public function html()
    {
        return ($this->plugin()) ? $this->plugin()->html : '';
    }

    public function overview()
    {
        return ($this->plugin()) ? $this->plugin()->overview : '';
    }

    public function note()
    {
        return ($this->plugin()) ? $this->plugin()->note : '';
    }

    public function required_fields()
    {
        return ($this->plugin()) ? $this->plugin()->required_fields : [];
    }

    /**
     * Return an array of shipping options from the shipping plugin
     * @return array
     */
    public function shipping_options(): array
    {
        $shippingOptions = method_exists($this->plugin(), 'plugin_shipping_options') ? $this->plugin()->plugin_shipping_options() : [];
        $defaultOptions = [
            'rate_title' => 'default', // @TODO make this run off of lang files.
            'rate_price' => 0,
            'price' => 0,
            'rate_short_name' => 'default', // @TODO make this run off of lang files.
        ];

        foreach ($shippingOptions as $key => $value) {
            if (!is_array($value)) {
                unset($shippingOptions[$key]);
                continue;
            }

            // make sure it has the default options
            $shippingOptions[$key] = array_merge($defaultOptions, $value);
        }

        if (is_array($shippingOptions) && count($shippingOptions) > 0) {
            return $shippingOptions;
        }

        return [];
    }

    public function get_live_rates($rate = null)
    {
        return (method_exists($this->plugin(), 'get_live_rates')) ? $this->plugin()->get_live_rates($rate) : false;
    }

    public function set_shipping($cost = null)
    {
        return (method_exists($this->plugin(), 'set_shipping')) ? $this->plugin()->set_shipping($cost) : false;
    }

    public function default_shipping_option()
    {
        return (method_exists($this->plugin(), 'default_shipping_option')) ? $this->plugin()->default_shipping_option() : '';
    }
}
