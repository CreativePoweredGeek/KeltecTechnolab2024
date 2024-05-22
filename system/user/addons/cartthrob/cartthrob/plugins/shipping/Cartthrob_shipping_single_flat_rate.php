<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_single_flat_rate extends ShippingPlugin
{
    public $title = 'single_flat_rate';
    public $settings = [
        [
            'name' => 'rate',
            'short_name' => 'rate',
            'type' => 'text',
        ],
    ];

    protected array $rules = [
        'rate' => 'required|numeric',
    ];

    /**
     * @return Money
     */
    public function get_shipping(): Money
    {
        return ee('cartthrob:MoneyService')->toMoney($this->plugin_settings('rate'));
    }
}
