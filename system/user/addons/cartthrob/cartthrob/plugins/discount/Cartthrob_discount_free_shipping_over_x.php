<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_free_shipping_over_x extends DiscountPlugin
{
    public $title = 'free_shipping_over_x_title';
    public $settings = [
        [
            'name' => 'if_order_over',
            'short_name' => 'order_over',
            'note' => 'enter_required_minimum',
            'type' => 'text',
        ],
    ];

    /**
     * @return int
     */
    public function get_discount()
    {
        if ($this->core->cart->subtotal() >= abs(Number::sanitize($this->plugin_settings('order_over')))) {
            $this->core->cart->set_discounted_shipping(ee('cartthrob:MoneyService')->fresh());
        }

        return 0;
    }

    public function toString($data)
    {
        ee()->lang->loadfile('cartthrob_discount_to_string', 'cartthrob_order_manager');
        $langString = strtolower('discount.' . __CLASS__);
        $initial = lang($langString);
        $output = $this->createString($initial, $data);

        return $output;
    }
}
