<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_by_price_threshold extends ShippingPlugin
{
    public $title = 'title_price_threshold';
    public $classname = __CLASS__;
    public $note = 'price_threshold_overview';
    public $settings = [
        [
            'name' => 'set_shipping_cost_by',
            'short_name' => 'mode',
            'type' => 'radio',
            'default' => 'price',
            'options' => [
                'price' => 'rate_amount',
                'rate' => 'rate_amount_times_cart_total',
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
                    'name' => 'price_threshold',
                    'short_name' => 'threshold',
                    'note' => 'price_threshold_example',
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
        $price = $this->core->cart->shippable_subtotal();
        $rate = $this->threshold($price, $this->get_thresholds());
        $shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $rate : $rate;

        return ee('cartthrob:MoneyService')->toMoney($shipping);
    }
}
