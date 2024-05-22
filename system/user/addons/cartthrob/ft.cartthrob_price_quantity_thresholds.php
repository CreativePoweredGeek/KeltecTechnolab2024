<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property EE_EE $EE
 * @property Cartthrob_cart $cart
 * @property Cartthrob_store $store
 */
class Cartthrob_price_quantity_thresholds_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Price - Quantity',
        'version' => CARTTHROB_VERSION,
    ];

    public $default_row = [
        'from_quantity' => '',
        'up_to_quantity' => '',
        'price' => '',
    ];

    /**
     * Cartthrob_price_quantity_thresholds_ft constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function pre_process($data)
    {
        $data = parent::pre_process($data);

        ee()->load->library('number');

        foreach ($data as &$row) {
            if (isset($row['price']) && $row['price'] !== '') {
                $row['price_plus_tax'] = $row['price'];

                if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
                    $row['price_plus_tax'] = $plugin->get_tax($row['price']) + $row['price'];
                }

                $row['price_numeric'] = $row['price'];
                $row['price_plus_tax_numeric'] = $row['price:plus_tax_numeric'] = $row['price_numeric:plus_tax'] = $row['price_plus_tax'];

                $row['price'] = ee()->number->format($row['price']);
                $row['price_plus_tax'] = $row['price:plus_tax'] = ee()->number->format($row['price_plus_tax']);
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return string
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        if ($tagdata) {
            return parent::replace_tag($data, $params, $tagdata);
        } else {
            if (!is_array($data)) {
                $serialized = $data;

                if (!isset(ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized])) {
                    ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized] = _unserialize($data,
                        true);
                }

                $data = ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized];
            }
            reset($data);

            while (($row = current($data)) !== false) {
                loadCartThrobPath();
                ee()->load->library('number');
                unloadCartThrobPath();

                return ee()->number->format($row['price']);
            }
        }
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return mixed
     */
    public function replace_price($data, $params = [], $tagdata = false)
    {
        loadCartThrobPath();
        ee()->load->library('number');
        unloadCartThrobPath();

        return ee()->number->format($this->cartthrob_price($data));
    }

    /**
     * @param $data
     * @param null $item
     * @return int
     */
    public function cartthrob_price($data, $item = null)
    {
        if (!is_array($data)) {
            $serialized = $data;

            if (!isset(ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized])) {
                ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized] = _unserialize($data,
                    true);
            }

            $data = ee()->session->cache['cartthrob']['price_quantity_thresholds']['cartthrob_price'][$serialized];
        }
        reset($data);

        while (($row = current($data)) !== false) {
            // if quantity is within the thresholds
            // OR if we get to the end of the array
            // the last row will set the price, no matter what
            if (next($data) === false || ($item instanceof Cartthrob_item && $item->quantity() >= $row['from_quantity'] && $item->quantity() <= $row['up_to_quantity'])) {
                return $row['price'];
            }
        }

        return 0;
    }
}
