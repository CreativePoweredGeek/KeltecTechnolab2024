<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_by_weight_global_rate extends ShippingPlugin
{
    public $title = 'title_by_weight_global_rate';
    public $classname = __CLASS__;
    public $note = 'by_weight_global_rate_note';
    public $settings = [
        [
            'name' => 'rate',
            'note' => 'rate_example',
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
        $shipping = $this->core->cart->shippable_weight() * $this->plugin_settings('rate');

        return ee('cartthrob:MoneyService')->toMoney($shipping);
    }
}
