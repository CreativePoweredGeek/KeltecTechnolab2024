<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;
use CartThrob\Plugins\Discount\ValidateCartInterface;

class Cartthrob_discount_percentage_off_product extends DiscountPlugin implements ValidateCartInterface
{
    public $title = 'percentage_off_single_product_title';
    public $settings = [
        [
            'name' => 'percentage_off',
            'short_name' => 'percentage_off',
            'note' => 'percentage_off_note',
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
        $percentage_off = abs(Number::sanitize($this->plugin_settings('percentage_off')));
        $discount = 0;

        if ($this->plugin_settings('entry_ids') && $entry_ids = preg_split('/\s*(,|\|)\s*/', trim($this->plugin_settings('entry_ids')))) {
            foreach ($this->core->cart->items() as $item) {
                /** @var Cartthrob_item $item */
                if ($item->product_id() && in_array($item->product_id(), $entry_ids)) {
                    $item_discount = $item->price() * ($percentage_off / 100);

                    $item->add_discount($item_discount, $this->core->lang('discount_reason_eligible_product'));

                    $discount += $item_discount * $item->quantity();
                }
            }
        }

        return $discount;
    }

    /**
     * @return bool
     */
    public function validateCart(): bool
    {
        $valid = false;

        if ($this->plugin_settings('entry_ids') && $entry_ids = preg_split('#\s*[,|]\s*#',
            trim($this->plugin_settings('entry_ids')))) {
            $valid = (count(array_intersect($this->core->cart->product_ids(), $entry_ids)) > 0);
        }

        if (!$valid) {
            $this->set_error($this->core->lang('coupon_not_valid_for_items'));
        }

        return $valid;
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
