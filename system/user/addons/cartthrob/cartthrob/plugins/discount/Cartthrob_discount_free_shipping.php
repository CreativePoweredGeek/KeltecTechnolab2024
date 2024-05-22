<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_free_shipping extends DiscountPlugin
{
    public $title = 'free_shipping';

    /**
     * @return int
     */
    public function get_discount()
    {
        $this->core->cart->set_shipping(0);

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
