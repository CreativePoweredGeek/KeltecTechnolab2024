<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_free_order extends DiscountPlugin
{
    public $title = 'free_order';

    /**
     * @return float|string
     */
    public function get_discount()
    {
        return $this->core->cart->subtotal_with_tax();
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
