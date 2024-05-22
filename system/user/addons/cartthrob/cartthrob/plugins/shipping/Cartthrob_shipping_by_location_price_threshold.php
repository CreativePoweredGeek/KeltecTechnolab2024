<?php

use CartThrob\Dependency\Money\Money;
use CartThrob\Plugins\Shipping\ShippingPlugin;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

class Cartthrob_shipping_by_location_price_threshold extends ShippingPlugin
{
    public $title = 'title_by_location_price_threshold';
    public $note = 'by_location_price_threshold_note';
    public $settings = [
        [
            'name' => 'set_shipping_cost_by',
            'short_name' => 'mode',
            'default' => 'rate',
            'type' => 'radio',
            'options' => [
                'price' => 'rate_amount',
                'rate' => 'rate_amount_times_cart_total',
            ],
        ],
        [
            'name' => 'primary_location_field',
            'short_name' => 'location_field',
            'type' => 'select',
            'default' => 'shipping_country_code',
            'options' => [
                'zip' => 'zip',
                'state' => 'state',
                'region' => 'Region',
                'country_code' => 'settings_country_code',
                'shipping_zip' => 'shipping_zip',
                'shipping_state' => 'shipping_state',
                'shipping_region' => 'shipping_region',
                'shipping_country_code' => 'settings_shipping_country_code',
            ],
        ],
        [
            'name' => 'backup_location_field',
            'short_name' => 'backup_location_field',
            'type' => 'select',
            'default' => 'country_code',
            'options' => [
                'zip' => 'zip',
                'state' => 'state',
                'region' => 'Region',
                'country_code' => 'settings_country_code',
                'shipping_zip' => 'shipping_zip',
                'shipping_state' => 'shipping_state',
                'shipping_region' => 'shipping_region',
                'shipping_country_code' => 'settings_shipping_country_code',
            ],
        ],
        [
            'name' => 'thresholds',
            'short_name' => 'thresholds',
            'type' => 'matrix',
            'settings' => [
                [
                    'name' => 'location_threshold',
                    'short_name' => 'location',
                    'type' => 'text',
                ],
                [
                    'name' => 'Rate',
                    'short_name' => 'rate',
                    'note' => 'rate_example',
                    'type' => 'text',
                ],
                [
                    'name' => 'price_threshold',
                    'short_name' => 'threshold',
                    'note' => 'price_threshold_example',
                    'type' => 'text',
                ],
            ],
        ],
    ];

    /**
     * @return Money
     */
    public function get_shipping(): Money
    {
        $location = '';
        $customerInfo = $this->core->cart->customer_info();
        $locationField = $this->plugin_settings('location_field', 'shipping_country_code');
        $backupLocationField = $this->plugin_settings('backup_location_field', 'country_code');

        if (!empty($customerInfo[$locationField])) {
            $location = $customerInfo[$locationField];
        } elseif (!empty($customerInfo[$backupLocationField])) {
            $location = $customerInfo[$backupLocationField];
        }

        $shipping = 0;
        $price = $this->core->cart->shippable_subtotal();
        $shippingIsPriced = false;
        $lastRate = '';

        foreach ($this->plugin_settings('thresholds', []) as $thresholdSetting) {
            $locationArray = preg_split('/\s*,\s*/', trim($thresholdSetting['location']));

            if (in_array($location, $locationArray)) {
                if ($price > $thresholdSetting['threshold']) {
                    $lastRate = $thresholdSetting['rate'];
                    continue;
                }

                $shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $thresholdSetting['rate'] : $thresholdSetting['rate'];
                $shippingIsPriced = true;
                break;
            } elseif (in_array('GLOBAL', $locationArray)) {
                if ($price > $thresholdSetting['threshold']) {
                    $lastRate = $thresholdSetting['rate'];
                    continue;
                }

                $shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $thresholdSetting['rate'] : $thresholdSetting['rate'];
                $shippingIsPriced = true;
                break;
            }
        }

        if (!$shippingIsPriced) {
            $shipping = ($this->plugin_settings('mode') == 'rate') ? $price * $lastRate : $lastRate;
        }

        return ee('cartthrob:MoneyService')->toMoney($shipping);
    }
}
