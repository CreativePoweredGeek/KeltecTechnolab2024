<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_by_quantity_threshold extends ShippingPlugin
{
    public $title = 'title_by_quantity_threshold';
    public $classname = __CLASS__;
    public $note = 'costs_are_set_at';
    public $settings = [
        [
            'name' => 'calculate_costs',
            'short_name' => 'mode',
            'type' => 'radio',
            'default' => 'price',
            'options' => [
                'price' => 'use_rate_as_shipping_cost',
                'rate' => 'multiply_rate_by_quantity',
            ],
        ],
        [
            'name' => 'thresholds',
            'short_name' => 'thresholds',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'rate',
                    'short_name' => 'rate',
                    'note' => 'rate_example',
                    'type' => 'text',
                ],
                [
                    'name' => 'quantity_threshold',
                    'short_name' => 'threshold',
                    'type' => 'text',
                ],
            ],
        ],
    ];

    /**
     * @return Money
     */
    public function get_shipping(): Money
    {
        $total_items = $this->core->cart->count_all(['no_shipping' => false]);
        $rate = $this->threshold($total_items, $this->get_thresholds());
        $shipping = ($this->plugin_settings('mode') == 'rate') ? $total_items * $rate : $rate;

        return ee('cartthrob:MoneyService')->toMoney($shipping);
    }
}
