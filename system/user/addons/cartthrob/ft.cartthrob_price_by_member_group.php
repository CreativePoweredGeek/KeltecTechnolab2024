<?php

use CartThrob\Math\Number;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 * @property Cartthrob_cart $cart
 * @property Cartthrob_store $store
 */
class Cartthrob_price_by_member_group_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Price - By Member Group',
        'version' => CARTTHROB_VERSION,
    ];

    public $default_row = [
        'member_group' => '',
        'price' => '',
    ];

    /**
     * Cartthrob_price_by_member_group_ft constructor.
     */
    public function __construct()
    {
        parent::__construct();

        unset($this->buttons['add_column']);
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
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_member_group($name, $value, $row, $index, $blank = false)
    {
        loadCartThrobPath();
        static $member_groups;

        if (is_null($member_groups)) {
            $member_groups[''] = lang('cartthrob_price_by_member_group_global');

            // ee()->load->model('member_model');

            // $query = ee()->member_model->get_member_groups(array(), array(array('group_id !=' => 2), array('group_id !=' => 3), array('group_id !=' => 4)));
            $query = ee('Model')->get('Role')->filter('role_id', '!=', '2')->orFilter('role_id', '!=',
                '3')->orFilter('role_id', '!=', '4')->all();
            foreach ($query as $row) {
                $member_groups[$row->role_id] = $row->name;
            }
        }
        unloadCartThrobPath();

        return form_dropdown($name, $member_groups, $value);
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return string
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        if (!$tagdata) {
            return $this->replace_price($data, $params, $tagdata);
        }

        return parent::replace_tag($data, $params, $tagdata);
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

        return $this->cartthrob_price($data);
    }

    /**
     * @param $data
     * @param null $item
     * @return int|null
     */
    public function cartthrob_price($data, $item = null)
    {
        if (!is_array($data)) {
            $serialized = $data;

            if (!isset(ee()->session->cache['cartthrob']['price_by_member_group']['cartthrob_price'][$serialized])) {
                ee()->session->cache['cartthrob']['price_by_member_group']['cartthrob_price'][$serialized] = _unserialize($data,
                    true);
            }

            $data = ee()->session->cache['cartthrob']['price_by_member_group']['cartthrob_price'][$serialized];
        }

        $price = null;

        $default_price = null;

        // loop through the rows and grab the price for current user's member group
        // or grab the default global price if no price is explicitly set for this member group
        foreach ($data as $row) {
            if (is_null($price) && !empty($row['member_group']) && ee()->session->userdata('role_id') == $row['member_group']) {
                $price = $row['price'];
            }

            if (is_null($default_price) && !$row['member_group']) {
                $default_price = $row['price'];
            }
        }

        if (is_null($price) && !is_null($default_price)) {
            $price = $default_price;
        }

        return (is_null($price)) ? 0 : $price;
    }

    /**
     * @param $data
     * @param array $params
     * @param string $tagdata
     * @return mixed
     */
    public function replace_plus_tax($data, $params = [], $tagdata = '')
    {
        $data = abs(Number::sanitize($this->cartthrob_price($data)));

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $data + $plugin->get_tax($data);
        }

        return ee()->number->format($data);
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return float|int
     */
    public function replace_plus_tax_numeric($data, $params = '', $tagdata = '')
    {
        $data = abs(Number::sanitize($this->cartthrob_price($data)));

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $data + $plugin->get_tax($data);
        }

        return $data;
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return float|int
     */
    public function replace_numeric($data, $params = '', $tagdata = '')
    {
        return abs(Number::sanitize($this->cartthrob_price($data)));
    }
}
