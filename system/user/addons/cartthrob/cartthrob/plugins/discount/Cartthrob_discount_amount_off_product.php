<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_amount_off_product extends DiscountPlugin
{
    public $title = 'amount_off_product_title';
    public $settings = [
        [
            'name' => 'amount_off',
            'short_name' => 'amount_off',
            'note' => 'amount_off_note',
            'type' => 'text',
        ],
        [
            'name' => 'product_entry_id',
            'short_name' => 'entry_ids',
            'note' => 'separate_multiple_entry_ids_by_comma',
            'type' => 'text',
        ],
    ];

    /**
     * @return float|int
     */
    public function get_discount()
    {
        $amount_off = abs(Number::sanitize($this->plugin_settings('amount_off')));
        $discount = 0;

        if ($this->plugin_settings('entry_ids') && $entry_ids = preg_split('/\s*(,|\|)\s*/', trim($this->plugin_settings('entry_ids')))) {
            foreach ($this->core->cart->items() as $item) {
                if ($item->product_id() && in_array($item->product_id(), $entry_ids)) {
                    $item_discount = $item->price() < $amount_off ? $item->price() : $amount_off;
                    $item->add_discount($item_discount, $this->core->lang('discount_reason_eligible_product'));

                    $discount += ($item_discount * $item->quantity());
                }
            }
        }

        return $discount;
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
