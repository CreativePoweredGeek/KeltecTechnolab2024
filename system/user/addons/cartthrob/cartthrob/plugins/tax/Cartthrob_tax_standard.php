<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Tax\TaxPlugin;

class Cartthrob_tax_standard extends TaxPlugin
{
    public $title = 'tax_by_location';
    public $settings = [
        [
            'name' => 'default_tax',
            'note' => 'default_tax_note',
            'short_name' => 'default_tax',
            'type' => 'text',
            'default' => '8',
        ],
    ];
    protected $tax_data;

    protected array $rules = [
        'default_tax' => 'required|numeric',
    ];

    /**
     * @param $price
     * @return float|int|mixed
     */
    public function get_tax($price)
    {
        return $price * $this->tax_rate();
    }

    /**
     * @return float|int
     */
    public function tax_rate()
    {
        return abs(Number::sanitize($this->tax_data('percent')) / 100);
    }

    /**
     * @param string|bool $key
     * @return array|bool|mixed
     */
    public function tax_data($key = false)
    {
        if (is_null($this->tax_data)) {
            $this->tax_data = [];
            $locations = [];
            $prefix = $this->core->store->config('tax_use_shipping_address') ? 'shipping_' : '';

            if ($this->core->cart->customer_info($prefix . 'zip')) {
                $locations['zip'] = $this->core->cart->customer_info($prefix . 'zip');
            }
            if ($this->core->cart->customer_info($prefix . 'region')) {
                $locations['special'] = $this->core->cart->customer_info($prefix . 'region');
            }
            if ($this->core->cart->customer_info($prefix . 'state')) {
                $locations['state'] = $this->core->cart->customer_info($prefix . 'state');
            }
            if ($this->core->cart->customer_info($prefix . 'country_code')) {
                $locations['country'] = $this->core->cart->customer_info($prefix . 'country_code');
            }

            $tax_settings = $this->core->get_tax_rates($locations);

            $tax_calculated = false;
            foreach ($tax_settings as $tax_data) {
                if (!empty($tax_data['special'])) {
                    if ($this->core->cart->customer_info($prefix . 'region') && $tax_data['special'] == $this->core->cart->customer_info($prefix . 'region')) {
                        $this->tax_data = $tax_data;
                        $tax_calculated = true;
                        break;
                    }
                } elseif (!empty($tax_data['zip'])) {
                    if ($this->core->cart->customer_info($prefix . 'zip') && $tax_data['zip'] == $this->core->cart->customer_info($prefix . 'zip')) {
                        $this->tax_data = $tax_data;
                        $tax_calculated = true;
                        break;
                    }
                } elseif (!empty($tax_data['state']) && !empty($tax_data['country'])) {
                    if (($tax_data['state'] == $this->core->cart->customer_info($prefix . 'state') || strtolower($tax_data['state']) == 'global')
                        && ($tax_data['country'] == $this->core->cart->customer_info($prefix . 'country_code') || strtolower($tax_data['country']) == 'global')) {
                        $this->tax_data = $tax_data;
                        $tax_calculated = true;
                        break;
                    }
                } elseif (!empty($tax_data['state'])) {
                    if ($tax_data['state'] == $this->core->cart->customer_info($prefix . 'state') || strtolower($tax_data['state']) == 'global') {
                        $this->tax_data = $tax_data;
                        $tax_calculated = true;
                        break;
                    }
                } elseif (!empty($tax_data['country'])) {
                    if ($tax_data['country'] == $this->core->cart->customer_info($prefix . 'country_code') || strtolower($tax_data['country']) == 'global') {
                        $this->tax_data = $tax_data;
                        $tax_calculated = true;
                        break;
                    }
                } else {
                    $tax_data['tax_name'] = null;
                    $tax_data['shipping_is_taxable'] = null;
                    $tax_data['percent'] = $this->plugin_settings('default_tax');
                    $this->tax_data = $tax_data;
                    $tax_calculated = true;
                }
            }
            if ($tax_calculated === false) {
                $tax_data['tax_name'] = null;
                $tax_data['shipping_is_taxable'] = null;
                $tax_data['percent'] = $this->plugin_settings('default_tax');
                $this->tax_data = $tax_data;
                $tax_calculated = true;
            }
        }
        if ($key === false) {
            return $this->tax_data;
        }

        return (isset($this->tax_data[$key])) ? $this->tax_data[$key] : false;
    }

    /**
     * @return array|bool|mixed
     */
    public function tax_name()
    {
        return $this->tax_data('tax_name');
    }

    /**
     * @return bool
     */
    public function tax_shipping()
    {
        return (bool)$this->tax_data('shipping_is_taxable');
    }
}
