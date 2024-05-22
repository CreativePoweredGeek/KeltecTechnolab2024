<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_per_location_rates extends ShippingPlugin
{
    public $title = 'title_per_location_rates';
    public $note = 'per_location_rates_note';
    public $overview = 'per_location_rates_overview';

    public $settings = [
        [
            'name' => 'default_cost_per_item',
            'short_name' => 'default_rate',
            'type' => 'text',
        ],
        [
            'name' => 'charge_default_by',
            'short_name' => 'default_type',
            'type' => 'select',
            'default' => 'flat',
            'options' => [
                'flat' => 'by_item',
                'weight' => 'by_weight',
                'order' => 'by_order',
            ],
        ],
        [
            'name' => 'charge_by_location',
            'short_name' => 'location_field',
            'type' => 'select',
            'default' => 'billing',
            'options' => [
                'billing' => 'billing_address',
                'shipping' => 'shipping_address',
            ],
        ],
        [
            'name' => 'rates',
            'short_name' => 'rates',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'cost',
                    'short_name' => 'rate',
                    'type' => 'text',
                ],
                [
                    'name' => 'type',
                    'short_name' => 'type',
                    'type' => 'select',
                    'default' => 'flat',
                    'options' => [
                        'flat' => 'by_item',
                        'weight' => 'by_weight',
                        'order' => 'by_order',
                    ],
                ],
                [
                    'name' => 'location_zip_regions',
                    'short_name' => 'zip',
                    'type' => 'text',
                ],
                [
                    'name' => 'location_states',
                    'short_name' => 'state',
                    'type' => 'text',
                ],
                [
                    'name' => 'location_countries',
                    'short_name' => 'country',
                    'type' => 'text',
                ],
                [
                    'name' => 'product_entry_ids',
                    'short_name' => 'entry_ids',
                    'type' => 'text',
                ],
                [
                    'name' => 'product_cat_ids',
                    'short_name' => 'cat_ids',
                    'type' => 'text',
                ],
                [
                    'name' => 'product_channel_content',
                    'short_name' => 'field_value',
                    'type' => 'text',
                ],
                [
                    'name' => 'in_channel_field',
                    'short_name' => 'field_name',
                    'type' => 'select',
                    'options' => [],
                    'attributes' => [
                        'class' => 'all_fields',
                    ],
                ],
            ],
        ],
    ];

    protected array $rules = [
        'default_rate' => 'required|numeric',
    ];

    protected $default_rate = 0;

    /**
     * @param array $params
     * @param array $defaults
     * @return Cartthrob_shipping|void
     */
    public function initialize($params = [], $defaults = [])
    {
        if ($this->plugin_settings('default_rate')) {
            $this->default_rate = $this->plugin_settings('default_rate');
        }
    }

    /**
     * @return Money
     */
    public function get_shipping(): Money
    {
        $location = '';
        $customer_info = $this->core->cart->customer_info();
        if ($this->plugin_settings('location_field') == 'billing') {
            $primary_loc = '';
            $backup_loc = 'shipping_';
        } else {
            $primary_loc = 'shipping_';
            $backup_loc = '';
        }

        $country = (!empty($customer_info[$primary_loc . 'country_code']) ? $customer_info[$primary_loc . 'country_code'] : $customer_info[$backup_loc . 'country_code']);
        $state = (!empty($customer_info[$primary_loc . 'state']) ? $customer_info[$primary_loc . 'state'] : $customer_info[$backup_loc . 'state']);
        $zip = (!empty($customer_info[$primary_loc . 'zip']) ? $customer_info[$primary_loc . 'zip'] : $customer_info[$backup_loc . 'zip']);
        $shipping = 0;

        foreach ($this->core->cart->shippable_items() as $row_id => $item) {
            if (!$item->product_id()) {
                continue;
            }

            // Get all settings
            $location_shipping = 0;

            foreach ($this->plugin_settings('rates') as $rate) {
                $locations['zip'] = explode(',', $rate['zip']);
                $locations['state'] = explode(',', $rate['state']);
                $locations['country'] = explode(',', $rate['country']);

                if ($rate['type'] == 'weight') {
                    $shippingAmount = $rate['rate'] * ($item->quantity() * $item->weight());
                } elseif ($rate['type'] == 'flat') {
                    $shippingAmount = $rate['rate'] * $item->quantity();
                } else {
                    $shippingAmount = $rate['rate'];
                }

                if ($this->plugin_settings('default_type') == 'weight') {
                    $default_amount = $this->default_rate * ($item->quantity() * $item->weight());
                } elseif ($this->plugin_settings('default_type') == 'flat') {
                    $default_amount = $this->default_rate * $item->quantity();
                } else {
                    $default_amount = $this->default_rate;
                }

                // Make sure entry ids have been entered
                if (!empty($rate['entry_ids'])) {
                    // get list of entry ids
                    $entry_ids = explode(',', $rate['entry_ids']);

                    // check if item in cart is in this rate
                    if (in_array('GLOBAL', $entry_ids) || in_array($item->product_id(), $entry_ids)) {
                        $associated_cost = $this->location_shipping($locations, $zip, $state, $country, $shippingAmount);

                        if ($associated_cost !== false) {
                            $location_shipping = $associated_cost;
                            break;
                        } else {
                            continue;
                        }
                    } // if item isnt in this rate line, skip it
                    else {
                        continue;
                    }
                } // Check Categories
                elseif (!empty($rate['cat_ids'])) {
                    $cats = explode(',', $rate['cat_ids']);
                    if ($product = $this->core->store->product($item->product_id())) {
                        foreach ($product->categories() as $cat_id) {
                            if (in_array('GLOBAL', $cats) || in_array($cat_id, $cats)) {
                                $associated_cost = $this->location_shipping($locations, $zip, $state, $country, $shippingAmount);

                                if ($associated_cost !== false) {
                                    $location_shipping = $associated_cost;
                                    break;
                                } else {
                                    continue;
                                }
                            }
                        }
                    }
                } elseif (!empty($rate['field_value']) && !empty($rate['field_name']) && $rate['field_name'] != '0') {
                    $content = explode(',', $rate['field_value']);

                    $product = $this->core->store->product($item->product_id());

                    if ($product && $product->meta($rate['field_name']) == $rate['field_value']) {
                        $associated_cost = $this->location_shipping($locations, $zip, $state, $country, $shippingAmount);

                        if ($associated_cost !== false) {
                            $location_shipping = $associated_cost;
                            break;
                        } else {
                            continue;
                        }
                    } elseif (in_array('GLOBAL', $content)) {
                        $associated_cost = $this->location_shipping($locations, $zip, $state, $country, $shippingAmount);

                        if ($associated_cost !== false) {
                            $location_shipping = $associated_cost;
                            break;
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            if ($location_shipping > 0) {
                if ($rate['type'] == 'order') {
                    return ee('cartthrob:MoneyService')->toMoney($location_shipping);
                }
                $shipping += $location_shipping;
            } else {
                $shipping += $default_amount;
            }
        }// END checking cart items

        return ee('cartthrob:MoneyService')->toMoney($shipping);
    }

    // END get_shipping

    /**
     * _location_shipping
     *
     * checks location, and returns shipping cost
     * @param array $locations
     * @param $zip
     * @param $state
     * @param $country
     * @param string $shipping_amount
     * @param int $default
     * @return string
     */
    private function location_shipping($locations, $zip, $state, $country, $shipping_amount, $default = 0)
    {
        if (in_array('GLOBAL', $locations['zip']) || (!empty($zip) && in_array($zip, $locations['zip']))) {
            return $shipping_amount;
        } elseif (in_array('GLOBAL', $locations['state']) || (!empty($state) && in_array($state, $locations['state']))) {
            return $shipping_amount;
        } elseif (in_array('GLOBAL', $locations['country']) || (!empty($country) && in_array($country, $locations['country']))) {
            return $shipping_amount;
        } elseif ($default) {
            return $default;
        } else {
            return false;
        }
    }
}
