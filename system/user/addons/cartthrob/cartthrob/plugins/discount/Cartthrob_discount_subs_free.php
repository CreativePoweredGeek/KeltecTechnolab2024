<?php

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

use CartThrob\Math\Number;
use CartThrob\Plugins\Discount\DiscountPlugin;

class Cartthrob_discount_subs_free extends DiscountPlugin
{
    public $title = 'subs_free_months';
    public $settings = [
        [
            'name' => 'months_off',
            'short_name' => 'months_off',
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
        $months_off = abs(Number::sanitize($this->plugin_settings('months_off')));
        $discount = 0;

        if ($this->plugin_settings('entry_ids') && $entry_ids = preg_split('/\s*(,|\|)\s*/',
            trim($this->plugin_settings('entry_ids')))) {
            foreach ($this->core->cart->items() as $item) {
                if ($item->product_id() && in_array($item->product_id(), $entry_ids)) {
                    // $has_subscription = $this->apply('subscriptions', 'subscriptions_initialize', element('subscription', $options), element('subscription_options', $options, array()));
                    if ($item->meta('subscription_options') && $item->meta('subscription') === true) {
                        $discount += ($item->quantity() * $item->price());
                        $subscription_options = (is_array($item->meta('subscription_options')) ? $item->meta('subscription_options') : []);
                        $subscription_options['trial_price'] = 0;
                        $subscription_options['trial_occurrences'] = $months_off;

                        // adding subscription meta. even if there's no new info, we still want the subscription meta set
                        $item->set_meta('subscription_options', $subscription_options);
                    }
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
