<?php

namespace CartThrob\Services;

class InputService
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function data(string $key, mixed $default = false): mixed
    {
        $return = $default;
        if (ee()->input->get_post($key, true) !== false) {
            $return = ee()->input->get_post($key, true);
        } elseif (ee()->cartthrob->cart->order($key) !== false) {
            $return = ee()->cartthrob->cart->order($key);
        } elseif (ee()->cartthrob->cart->customer_info($key) !== false) {
            $return = ee()->cartthrob->cart->customer_info($key);
        }

        return $return;
    }
}
