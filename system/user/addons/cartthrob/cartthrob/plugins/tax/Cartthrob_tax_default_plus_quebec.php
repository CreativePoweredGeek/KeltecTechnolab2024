<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Tax\TaxPlugin;

class Cartthrob_tax_default_plus_quebec extends TaxPlugin
{
    public $title = 'tax_by_location_with_quebec';
    public $overview = 'tax_quebec_overview';
    public $settings = [
        [
            'name' => 'quebec_gst',
            'short_name' => 'tax_gst',
            'type' => 'text',
            'default' => '5',
        ],
        [
            'name' => 'quebec_qst',
            'short_name' => 'tax_qst',
            'type' => 'text',
            'default' => '8.5',
        ],
        [
            'name' => 'quebec_tax_shipping',
            'short_name' => 'tax_quebec_shipping',
            'type' => 'radio',
            'default' => 'no',
            'options' => ['no' => 'no', 'yes' => 'yes'],
        ],
        [
            'name' => 'quebec_descriptive_name',
            'short_name' => 'tax_quebec_name',
            'type' => 'text',
            'default' => 'Consumption Tax (GST & QST)',
        ],
        [
            'name' => 'quebec_effective_rate',
            'short_name' => 'tax_quebec_effective_rate',
            'type' => 'text',
            'default' => '13.925',
        ],
        [
            'name' => 'tax_by_location_settings',
            'short_name' => 'tax_settings',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'name',
                    'short_name' => 'name',
                    'type' => 'text',
                ],
                [
                    'name' => 'tax_percent',
                    'short_name' => 'rate',
                    'type' => 'text',
                ],
                [
                    'name' => 'state_country',
                    'short_name' => 'state',
                    'type' => 'select',
                    'attributes' => [
                        'class' => 'states_and_countries',
                    ],
                    'options' => [],
                ],
                [
                    'name' => 'zip_region',
                    'short_name' => 'zip',
                    'type' => 'text',
                ],
                [
                    'name' => 'tax_shipping',
                    'short_name' => 'tax_shipping',
                    'type' => 'checkbox',
                ],
            ],
        ],
    ];

    protected array $rules = [
        'tax_gst' => 'required|numeric',
        'tax_qst' => 'required|numeric',
        'tax_quebec_name' => 'required',
        'tax_quebec_effective_rate' => 'required|numeric',
    ];

    protected $tax_data;

    /**
     * @param $price
     * @return float|int|mixed
     */
    public function get_tax($price)
    {
        $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';

        if ($this->core->cart->customer_info($prefix . 'state') && 'QC' == $this->core->cart->customer_info($prefix . 'state')) {
            $gst_total = $price * abs(Number::sanitize($this->plugin_settings('tax_gst')) / 100);

            return $gst_total * abs(Number::sanitize($this->plugin_settings('tax_qst')) / 100);
        } else {
            return $price * $this->tax_rate();
        }
    }

    /**
     * @return array|bool|float|int|mixed
     */
    public function tax_rate()
    {
        $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';
        if ($this->core->cart->customer_info($prefix . 'state') && 'QC' == $this->core->cart->customer_info($prefix . 'state')) {
            return $this->plugin_settings('tax_quebec_effective_rate');
        }

        return abs(Number::sanitize($this->tax_data('rate')) / 100);
    }

    /**
     * @param bool $key
     * @return array|bool|mixed
     */
    public function tax_data($key = false)
    {
        if (is_null($this->tax_data)) {
            $this->tax_data = [];

            $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';

            foreach ($this->plugin_settings('tax_settings', []) as $tax_data) {
                // zip code first
                if ($this->core->cart->customer_info($prefix . 'zip') && $tax_data['zip'] == $this->core->cart->customer_info($prefix . 'zip')) {
                    $this->tax_data = $tax_data;
                    break;
                } elseif ($this->core->cart->customer_info($prefix . 'region') && $tax_data['zip'] == $this->core->cart->customer_info($prefix . 'region')) {
                    $this->tax_data = $tax_data;
                    break;
                } elseif ($this->core->cart->customer_info($prefix . 'state') && $tax_data['state'] == $this->core->cart->customer_info($prefix . 'state')) {
                    $this->tax_data = $tax_data;
                    break;
                } elseif ($this->core->cart->customer_info($prefix . 'country_code') && $tax_data['state'] == $this->core->cart->customer_info($prefix . 'country_code')) {
                    $this->tax_data = $tax_data;
                    break;
                }
                // elseif (array_key_exists('global', $tax_data))
                // 'global' is set in the state dropdown so it's not an array key, it's a value of $tax_data['state']
                elseif (in_array('global', $tax_data)) {
                    $this->tax_data = $tax_data;
                    break;
                }
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
        $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';
        if ($this->core->cart->customer_info($prefix . 'state') && 'QC' == $this->core->cart->customer_info($prefix . 'state')) {
            return $this->plugin_settings('tax_quebec_name');
        }

        return $this->tax_data('name');
    }

    /**
     * @return array|bool|mixed
     */
    public function tax_shipping()
    {
        $prefix = ($this->core->store->config('tax_use_shipping_address')) ? 'shipping_' : '';
        if ($this->core->cart->customer_info($prefix . 'state') && 'QC' == $this->core->cart->customer_info($prefix . 'state')) {
            return $this->plugin_settings('tax_quebec_shipping');
        }

        return (bool)$this->tax_data('tax_shipping');
    }
}
