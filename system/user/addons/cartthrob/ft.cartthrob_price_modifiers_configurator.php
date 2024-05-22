<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 * @property Cartthrob_core_ee $cartthrob;
 * @property Cartthrob_cart $cart
 * @property Cartthrob_store $store
 */
class Cartthrob_price_modifiers_configurator_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Price Modifiers Configurator',
        'version' => CARTTHROB_VERSION,
    ];

    // make sure the last element has no extra comma, or it will introduce empty stuff
    public $default_row = [
        'all_values' => '',
        'option_value' => '',
        'option_name' => '',
        'price' => '',
        'inventory' => '',
    ];

    public $primary_row = [
        'all_values' => '',
        'option_value' => '',
        'option_name' => '',
        'price' => '',
        'inventory' => '',
        'weight' => '',
    ];

    // make sure the last element has no extra comma, or it will introduce empty stuff
    public $secondary_row = [
        'option_group' => '',
        'option_group_label' => '',
        'field_type' => '',
        'options' => '',
    ];

    /**
     * Cartthrob_price_modifiers_configurator_ft constructor.
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
     * @param $configured_options
     * @param bool $check_inventory
     * @return bool|mixed
     */
    public function compare($data, $configured_options, $check_inventory = false)
    {
        ee()->load->helper('array');

        $saved_options = $this->split_options($data, true);

        foreach ($data as $key => $value) {
            if (element('field_type', $value) != 'text') {
                continue;
            }

            if (array_key_exists(element('option_group', $value), $configured_options)) {
                $configured_options[element('option_group', $value)] = 'text';
            }
        }

        $option_value = [];
        $inventory = [];
        $all_values = [];

        foreach ($saved_options as $key => $value) {
            $option_value[] = element('option_value', $value, []);
            $inventory[] = element('inventory', $value, []);
            $all_values[] = element('all_values', $value, []);
        }

        if ($all_values && is_array($all_values)) {
            foreach ($all_values as $key => $value) {
                $opt = @unserialize(base64_decode($value));
                $opt_count = count($opt);

                if (is_array($configured_options) && is_array($opt)) {
                    $temp_arr = array_intersect_assoc($configured_options, $opt);

                    if (count($temp_arr) == $opt_count) {
                        if ($check_inventory) {
                            if (array_key_exists($key, $inventory)) {
                                return $inventory[$key];
                            }
                        }

                        return element($key, $option_value);
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param $data
     * @param bool $option_values
     * @param bool $check_inventory
     * @return array
     */
    public function split_options($data, $option_values = true, $check_inventory = false)
    {
        $array_keys = $this->primary_row;
        $array_keys['field_type'] = false;

        $list_data = [];

        if ($data && is_array($data)) {
            if ($option_values) {
                foreach ($data as $key => $value) {
                    if (elements(['option_name', 'option_value'], $value)) {
                        if ($check_inventory) {
                            if (array_key_exists('inventory', $value)) {
                                if ($value['inventory'] !== false && $value['inventory'] >= '0') {
                                    $list_data[] = elements(array_keys($array_keys), $value);
                                } elseif ($value['inventory'] === false) {
                                    $list_data[] = elements(array_keys($array_keys), $value);
                                }
                            }
                        } else {
                            $list_data[] = elements(array_keys($array_keys), $value);
                        }
                    }
                }
            } else {
                foreach ($data as $key => $value) {
                    if (element('option_group', $value)) {
                        $list_data[] = elements(array_keys($this->secondary_row), $value);
                    }
                }
            }
        }

        return $list_data;
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @return replacement
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        loadCartThrobPath();
        ee()->load->helper(['html', 'array', 'data_formatting']);

        $data = $this->split_options($data, true);

        if (isset($params['orderby']) && $params['orderby'] === 'price') {
            $params['orderby'] = 'price_numeric';
        }
        unloadCartThrobPath();

        return parent::replace_tag($data, $params, $tagdata);
    }

    /**
     * this function pre-processes data before being output in the item-options dropdown
     * @param array $data
     * @return array
     */
    public function item_options($data = [])
    {
        return $data;
    }

    /**
     * @param array $data
     * @param $field_short_name
     * @return array
     */
    public function item_option_groups(array $data = [], string $field_short_name = '')
    {
        ee()->load->helper('array');

        $item_option_labels = ee()->cartthrob->cart->meta('item_option_labels');

        return $this->option_groups($data, $params = [], $tagdata = false, $field_short_name);
    }

    /**
     * @param $data
     * @param array $params
     * @param bool $tagdata
     * @param null $field_short_name
     * @return array
     */
    public function option_groups(array $data = [], array $params = [], $tagdata = false, $field_short_name = null)
    {
        ee()->load->helper('array');

        $item_option_labels = ee()->cartthrob->cart->meta('item_option_labels');
        $data = $this->split_options($data, false);
        $option_groups = [];

        foreach ($data as $key => $value) {
            $options = element('options', $value, []);
            $option_output = [];

            foreach (element('option', $options, []) as $k => $v) {
                $prices = element('price', $options);

                // skip anything without a sku... cuz we can't use it
                if ($v) {
                    // currently label is not used. not to be confused with option_name
                    if (element('option_group_label', $data[$key])) {
                        $label = $data[$key]['option_group_label'];
                    } else {
                        $label = ucwords(str_replace('_', ' ', $data[$key]['option_group']));
                    }

                    $item_option_labels['configuration:' . $field_short_name . ':' . $data[$key]['option_group']] = $label;

                    $option_output[] = [
                        'option_value' => $v,
                        'option_name' => ucwords(str_replace('_', ' ', $v)),
                        'price' => element($k, $prices),
                        'field_type' => element('field_type', $value, 'options'),
                        // 'inventory'		=> element($key, $inventory), // inventory can't really be pulled down INTO this option, because it coul apply to multiple final skus
                    ];
                }
            }
            $option_groups[$data[$key]['option_group']] = $option_output;
        }

        ee()->cartthrob->cart->set_meta('item_option_labels', $item_option_labels);

        return $option_groups;
    }

    /**
     * @param $data
     * @param bool $replace_tag
     * @return string
     */
    public function display_field($data, $replace_tag = false)
    {
        loadCartThrobPath();

        ee()->lang->loadfile('cartthrob', 'cartthrob');

        ee()->load->model('cartthrob_settings_model');

        ee()->load->helper(['html', 'array', 'data_formatting']);

        // default row's going to change from time to time, so we need a backup.
        $this->primary_row = $this->default_row;
        if (!is_array($data)) {
            $data = _unserialize($data, true);
        }

        $list_data = [];
        $subdata = [];
        $options = [];
        $prices = [];
        $saved_all_values = [];
        $saved_option_value = [];
        $saved_option_label = [];
        $saved_price = [];
        $saved_inventory = [];

        if ($data && is_array($data)) {
            foreach ($data as $key => $value) {
                if (elements(['option_name', 'option_value'], $value)) {
                    $list_data[$key] = elements(array_keys($this->primary_row), $value);
                    $saved_all_values[] = element('all_values', $value);
                    $saved_option_value[] = element('option_value', $value);
                    $saved_option_label[] = element('option_name', $value);
                    $saved_price[] = element('price', $value);
                    $saved_inventory[] = element('inventory', $value);
                }

                if (element('option_group', $value)) {
                    $subdata[$key] = elements(array_keys($this->secondary_row), $value);

                    foreach (element('option', $value['options']) as $k => $v) {
                        $p = element('price', $value['options']);
                        if ($v != '' && $v !== false && $v !== null) {
                            $options[element('option_group', $value)][] = $v;
                            $prices[element('option_group', $value)][] = element($k, $p);
                        }
                    }
                }
            }
        }

        $final_options = cartesian($options);

        // Values are used for finding the specific sku associated with the option row
        foreach ($saved_all_values as $key => $value) {
            if (!empty($final_options[$key]) && is_array($final_options[$key])) {
                $saved_all_values[$key] = base64_encode(serialize($final_options[$key]));
            }
        }

        unset($this->default_row['inventory']);
        unset($this->default_row['weight']);

        $channel_id = $this->get_channel_id();

        if (!$channel_id && isset(ee()->channel_form)) {
            $channel_id = ee()->channel_form->channel('channel_id');
        }

        if ($channel_id && $this->field_id == array_value(ee()->config->item('cartthrob:product_channel_fields'),
            $channel_id, 'inventory')) {
            $this->default_row['inventory'] = '';
        }

        if ($channel_id && $this->field_id == array_value(ee()->config->item('cartthrob:product_channel_fields'),
            $channel_id, 'weight')) {
            $this->default_row['weight'] = '';
        }

        if (empty(ee()->session->cache['cartthrob_price_modifiers']['head'])) {
            $url = (REQ === 'CP') ? '(EE.BASE+"/cp/addons/settings/cartthrob/save_price_modifier_presets_action").replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1")'
                : 'EE.BASE.replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1")+"&ACT="+' . ee()->functions->fetch_action_id('Cartthrob_mcp',
                    'save_price_modifier_presets_action');

            ee()->session->cache['cartthrob_price_modifiers']['head'] = true;
        }

        ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THIRD_THEMES . 'cartthrob/scripts/jquery.form.js"></script>');

        ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THIRD_THEMES . 'cartthrob_option_configurator/js/optionConfigurator_ajax.js"></script>');

        ee()->cp->add_to_foot('
			<script type="text/javascript">
				' . (isset($this->default_row['inventory']) ? "var show_inventory='1'" : "var show_inventory='0'") . '
				var configurator_id = "' . $this->field_id . '"
			</script>
		');

        $this->buttons_temp = $this->buttons;
        $this->buttons = []; // getting rid of the add_row buttons

        $vars = [
            'all_values' => $saved_all_values,
            'option_value' => $saved_option_value,
            'option_label' => $saved_option_label,
            'price' => $saved_price,
            'inventory' => $saved_inventory,
            'options' => $final_options,
            'show_inventory' => (isset($this->default_row['inventory']) ? 1 : 0),
            'field_id' => $this->field_id,
            'field_id_name' => 'field_id_' . $this->field_id,
        ];

        $this->additional_controls = '<br><br>' . ee()->load->view('configurator', $vars, true);

        $this->default_row = $this->secondary_row;
        $this->buttons = $this->buttons_temp;
        unloadCartThrobPath();

        return parent::display_field($subdata, $replace_tag);
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_all_values($name, $value, $row, $index, $blank = false)
    {
        $details = null;

        $all_values = [
            'readonly' => true,
            'name' => $name,
            'value' => $value,
        ];

        if (!is_array($value)) {
            $data = _unserialize($value, true);
        } else {
            $data = $value;
        }

        foreach ($data as $attr => $val) {
            $details .= "<strong>{$attr}:</strong> {$val}<br />";
        }

        return $details . "<span style='display:none'>" . form_input($all_values) . '</span>';
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_field_type($name, $value, $row, $index, $blank = false)
    {
        ee()->load->helper('form');

        $options = [
            'options' => 'options',
            'text' => 'text',
        ];

        return form_dropdown($name, $options, $value, 'class="cartthrob_configurator_field_type"');
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return mixed
     */
    public function display_field_options($name, $value, $row, $index, $blank = false)
    {
        $modifiers = [
            'option' => (!empty($value['option']) ? $value['option'] : [null]),
            'price' => (!empty($value['price']) ? $value['price'] : [null]),
        ];

        // count number of modifiers add that count as a JS variable.
        $vars = [
            'field_id' => $this->field_id,
            'name' => $name,
            'modifiers' => $modifiers,
            'count' => count($modifiers),
        ];

        loadCartThrobPath();

        // this view stores the option/price +- box content
        $view_data = ee()->load->view('price_modifiers_field_options', $vars, true);

        unloadCartThrobPath();

        return $view_data;
    }
}
