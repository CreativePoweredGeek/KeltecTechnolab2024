<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_amount_off_over_x extends DiscountPlugin
{
    public $title = 'amount_off_over_x_title';
    public $settings = [
        [
            'name' => 'amount_off',
            'short_name' => 'amount_off',
            'note' => 'amount_off_note',
            'type' => 'text',
        ],
        [
            'name' => 'if_order_over',
            'short_name' => 'order_over',
            'note' => 'enter_required_minimum',
            'type' => 'text',
        ],
    ];

    /**
     * @return float|int
     */
    public function get_discount()
    {
        if ($this->core->cart->subtotal() >= Number::sanitize($this->plugin_settings('order_over'))) {
            return abs(Number::sanitize($this->plugin_settings('amount_off')));
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
