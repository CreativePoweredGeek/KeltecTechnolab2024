<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_percentage_off_over_x_packages extends DiscountPlugin
{
    public $title = 'percentage_off_over_x_packages';
    public $settings = [
        [
            'name' => 'percentage_off',
            'short_name' => 'percentage_off',
            'note' => 'percentage_off_note',
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
        $subtotal = 0;
        foreach ($this->core->cart->items() as $row_id => $item) {
            if ($item->sub_items()) {
                $subtotal += $item->price_subtotal();
            }
        }

        if ($subtotal >= abs(Number::sanitize($this->plugin_settings('order_over')))) {
            return $subtotal * abs(Number::sanitize($this->plugin_settings('percentage_off')) / 100);
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
