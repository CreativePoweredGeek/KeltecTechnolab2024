<?php

use CartThrob\Traits\ValidateTrait;
use ExpressionEngine\Service\Validation\ValidationAware;
use Money\Money;

if (!defined('CARTTHROB_PATH')) {
    Cartthrob_core::core_error('No direct script access allowed');
}

abstract class Cartthrob_shipping extends Cartthrob_child implements ValidationAware
{
    use ValidateTrait;

    public $title = '';
    public $note = '';
    public $overview = '';
    public $html = '';
    public $settings = [];
    public $required_fields = [];

    /**
     * @param array $params
     * @param array $defaults
     * @return $this|void
     */
    public function initialize($params = [], $defaults = [])
    {
        return $this;
    }

    /**
     * @return Money
     */
    abstract public function get_shipping(): Money;

    /**
     * @return array
     */
    public function get_thresholds()
    {
        $thresholds = [];

        foreach ($this->plugin_settings('thresholds', []) as $threshold) {
            $thresholds[$threshold['threshold']] = $threshold['rate'];
        }

        return $thresholds;
    }

    /**
     * @param $key
     * @param bool $default
     * @return array|bool|mixed
     */
    public function plugin_settings($key, $default = false)
    {
        $settings = $this->core->store->config(get_class($this) . '_settings');

        if ($key === false) {
            return ($settings) ? $settings : $default;
        }

        return (isset($settings[$key])) ? $settings[$key] : $default;
    }

    /**
     * @param $number
     * @param $thresholds
     * @return bool|mixed
     */
    public function threshold($number, $thresholds)
    {
        ksort($thresholds);

        $rate = false;

        foreach ($thresholds as $threshold => $threshold_rate) {
            if ($number > $threshold) {
                continue;
            }

            $rate = $threshold_rate;

            break;
        }

        if ($rate === false) {
            $rate = end($thresholds);
        }

        return $rate;
    }

    /**
     * @param null $shipping
     * @return string
     */
    public function cart_hash($shipping = null)
    {
        $content = $this->core->cart->items_array();
        foreach ($content as $key => &$item) {
            if (!empty($item['row_id']) && !is_numeric($item['row_id'])) {
                unset($content[$key]);
            }
        }

        // hashing the cart data, so we can check later if the cart has been updated
        $cart_hash = md5(serialize($content));
        if ($shipping) {
            $this->core->cart->set_custom_data('cart_hash', $cart_hash);
            $this->core->cart->set_custom_data(ucfirst(get_class($this)), $shipping);
        }
        $this->core->cart->save();

        return $cart_hash;
    }

    /**
     * @param $key
     * @param bool $default
     * @param bool $use_billing
     * @return array|bool|false|float|int|mixed
     */
    public function shipping_data($key, $default = false, $use_billing = false)
    {
        switch ($key) {
            case 'weight':
                return $this->core->cart->shippable_weight() ? $this->core->cart->shippable_weight() : $default;
                break;
            case 'destination_res_com':
                return ($this->plugin_settings('destination_res_com') == 'RES') ? 1 : 0;
                break;
            case 'origination_res_com':
                return ($this->plugin_settings('origination_res_com') == 'RES') ? 1 : 0;
                break;
            default:
                if ($this->core->cart->customer_info('shipping_' . $key) && !$use_billing) {
                    return $this->core->cart->customer_info('shipping_' . $key);
                } elseif ($this->core->cart->customer_info($key)) {
                    return $this->core->cart->customer_info($key);
                } elseif ($this->plugin_settings($key)) {
                    return $this->plugin_settings($key);
                } else {
                    // looking through custom data for this information.
                    if ($this->core->cart->custom_data($key)) {
                        return $this->core->cart->custom_data($key);
                    } else {
                        // deliberately set it to false, because we might want 0,"", or NULL returned.
                        if ($default !== false) {
                            return $default;
                        } else {
                            return $this->core->store->config('default_location', $key);
                        }
                    }
                }
        }
    }
}
