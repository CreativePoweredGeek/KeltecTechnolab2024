<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_percentage_off_categories extends DiscountPlugin
{
    public $title = 'percentage_off_categories';
    public $settings = [
        [
            'name' => 'percentage_off',
            'short_name' => 'percentage_off',
            'note' => 'percentage_off_note',
            'type' => 'text',
        ],
        [
            'name' => 'categories',
            'short_name' => 'categories',
            'type' => 'multiselect',
            'options' => [],
        ],
    ];

    /**
     * @param array $plugin_settings
     * @param array $defaults
     * @return Cartthrob_discount|void
     */
    public function initialize($plugin_settings = [], $defaults = [])
    {
        $this->settings[1]['options'] = $this->core->get_categories();
        parent::initialize($plugin_settings);
    }

    /**
     * @return float|int
     */
    public function get_discount()
    {
        $discount = 0;

        $valid_categories = $this->plugin_settings('categories', []);

        foreach ($this->core->cart->items() as $item) {
            $product = $this->core->store->product($item->product_id());

            if (!$product) {
                continue;
            }

            if (array_intersect($valid_categories, $product->categories())) {
                $item_discount = $item->price() * abs(Number::sanitize($this->plugin_settings('percentage_off')) / 100);

                $item->add_discount($item_discount, $this->core->lang('discount_reason_eligible_product_category'));

                $discount += $item_discount * $item->quantity();
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
