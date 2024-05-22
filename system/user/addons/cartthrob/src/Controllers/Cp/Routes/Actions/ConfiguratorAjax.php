<?php

namespace CartThrob\Controllers\Cp\Routes\Actions;

use CartThrob\Controllers\Cp\AbstractActionRoute;
use CartThrob\Controllers\Cp\AbstractRoute;

class ConfiguratorAjax extends AbstractActionRoute
{
    /**
     * @var string
     */
    protected $route_path = 'actions/configurator-ajax';

    /**
     * @param $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        ee()->load->helper(['data_formatting_helper', 'html', 'array', 'form', 'array']);

        $field_id = element('opt_field_name', $_POST);
        $field_id_name = 'field_id_' . $field_id;
        $p_opt = element($field_id_name, $_POST, []);
        $html = null;
        $options = [];
        $prices = [];
        $saved_all_values = [];
        $saved_option_value = [];
        $saved_option_label = [];
        $saved_price = [];
        $saved_inventory = [];

        foreach ($p_opt as $key => $value) {
            if (element('options', $value)) {
                $o = element('option', $value['options']);
                if ($o) {
                    foreach ($o as $k => $v) {
                        $price = element($k, $value['options']['price']);
                        if (!$price) {
                            $price = 0;
                        }
                        $options[element('option_group', $value)][] = $v;
                        $prices[element('option_group', $value)][] = $price;
                    }
                }
            }

            if (array_key_exists('option_value', $value)) {
                $price = element('price', $value);
                if (!$price) {
                    $price = 0;
                }
                $saved_all_values[] = element('all_values', $value);
                $saved_option_value[] = element('option_value', $value);
                $saved_option_label[] = element('option_name', $value);
                $saved_price[] = $price;
                $saved_inventory[] = element('inventory', $value);
            }
        }

        $final_options = cartesian($options);
        $final_prices = cartesian($prices);
        $prices = cartesian_to_price($final_prices);
        $all_values = [];
        $option_value = [];
        $option_label = [];
        $price = [];
        $inventory = [];

        foreach ($final_options as $k => $v) {
            if (!$cost = element($k, $prices)) {
                $cost = 0;
            }

            $all_values[$k] = base64_encode(serialize($v));
            $option_value[$k] = ''; // implode("-",$v); // sku
            $option_label[$k] = ''; // ucwords(str_replace("_" , " ", implode(", ",$v))); // name
            $price[$k] = $cost;
            $inventory[$k] = '';
        }

        if (count($saved_all_values) && !empty($saved_all_values) && is_array($saved_all_values)) {
            $copy = $final_options;

            foreach ($saved_all_values as $key => $value) {
                $opt = @unserialize(base64_decode($value));

                if (is_array($copy) && is_array($opt)) {
                    foreach ($copy as $k => $v) {
                        $temp_arr = array_intersect_assoc($opt, $v);

                        if (count($temp_arr) == count($opt)) {
                            $all_values[$k] = base64_encode(serialize($v));
                            $option_value[$k] = element($key, $saved_option_value);
                            $option_label[$k] = element($key, $saved_option_label);
                            // $price[$k] = element($key, $saved_price);
                            $inventory[$k] = element($key, $saved_inventory);
                            unset($copy[$k]);
                        }
                    }
                }
            }
        }

        $data = [
            'all_values' => $all_values,
            'option_value' => $option_value,
            'option_label' => $option_label,
            'price' => $price,
            'inventory' => $inventory,
            'options' => $final_options,
            'field_id' => $field_id,
            'field_id_name' => $field_id_name,
            'show_inventory' => element('show_inventory', $_POST, 0),
        ];

        $html = null;
        $html .= ee()->load->view('configurator', $data, true);

        if (!$html) {
            $html = 'could not be loaded';
        }

        ee()->output->send_ajax_response([
            'success' => $html,
            'CSRF_TOKEN' => ee()->functions->add_form_security_hash('{csrf_token}'),
        ]);

        exit;

        return $this;
    }
}
