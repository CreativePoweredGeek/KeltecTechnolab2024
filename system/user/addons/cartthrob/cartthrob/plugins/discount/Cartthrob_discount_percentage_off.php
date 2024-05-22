<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_percentage_off extends DiscountPlugin
{
    public $title = 'percentage_off';
    public $settings = [
        [
            'name' => 'percentage_off',
            'short_name' => 'percentage_off',
            'note' => 'percentage_off_note',
            'type' => 'text',
        ],
    ];

    /**
     * @return float|int
     */
    public function get_discount()
    {
        return $this->core->cart->subtotal() * abs(Number::sanitize($this->plugin_settings('percentage_off')) / 100);
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
