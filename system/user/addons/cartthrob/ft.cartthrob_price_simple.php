<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

use ExpressionEngine\Library\CP\EntryManager\ColumnInterface;

class Cartthrob_price_simple_ft extends EE_Fieldtype implements ColumnInterface
{
    public $info = [
        'name' => 'CartThrob Price - Simple',
        'version' => CARTTHROB_VERSION,
    ];

    /**
     * @return array
     */
    public function install()
    {
        return [
            'field_prefix' => '$',
        ];
    }

    /**
     * @param $data
     * @return bool|mixed
     */
    public function validate($data)
    {
        ee()->load->library('form_validation');

        if ($data && !ee()->form_validation->numeric($data)) {
            return ee()->lang->line('numeric');
        }

        return true;
    }

    /**
     * @param $data
     * @return mixed
     */
    public function pre_process($data)
    {
        if (isset($this->row['channel_id'])) {
            loadCartThrobPath();

            ee()->load->model('cartthrob_settings_model');
            unloadCartThrobPath();

            $product_channel_fields = ee()->config->item('cartthrob:product_channel_fields');

            if (isset($product_channel_fields[$this->row['channel_id']]['global_price'])) {
                $global_price = $product_channel_fields[$this->row['channel_id']]['global_price'];

                if ($global_price !== '') {
                    $data = $product_channel_fields[$this->row['channel_id']]['global_price'];
                }
            }
        }

        return $data;
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return string
     */
    public function replace_no_tax($data, $params = '', $tagdata = '')
    {
        return $this->replace_tag($data, $params, $tagdata);
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return string
     */
    public function replace_tag($data, $params = '', $tagdata = '')
    {
        loadCartThrobPath();

        ee()->load->library('number');

        ee()->number->set_prefix($this->get_prefix());
        unloadCartThrobPath();

        return ee()->number->format($data);
    }

    /**
     * @return mixed
     */
    public function get_prefix()
    {
        if (empty($this->settings['field_prefix'])) {
            loadCartThrobPath();

            ee()->load->model('cartthrob_settings_model');
            unloadCartThrobPath();

            return ee()->config->item('cartthrob:number_format_defaults_prefix');
        } else {
            return $this->settings['field_prefix'];
        }
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return mixed
     */
    public function replace_plus_tax($data, $params = '', $tagdata = '')
    {
        loadCartThrobPath();
        ee()->load->library('number');

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $plugin->get_tax($data) + $data;
        }

        ee()->number->set_prefix($this->get_prefix());

        return ee()->number->format($data);
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return mixed
     */
    public function replace_plus_tax_numeric($data, $params = '', $tagdata = '')
    {
        ee()->load->library('number');

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $plugin->get_tax($data) + $data;
        }

        return $data;
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return mixed
     */
    public function replace_numeric($data, $params = '', $tagdata = '')
    {
        return $data;
    }

    /**
     * @param $data
     * @return string
     */
    public function display_field($data)
    {
        $prefix = $this->get_prefix();

        $field_id = $this->settings['field_id'];

        $span = '<span style="position:absolute;padding:5px 0 0 5px;">' . $prefix . '</span>';

        ee()->javascript->output('
				var span = $(\'' . $span . '\').appendTo("body").css({top:-9999});
				var indent = span.width()+4;
				span.remove();

			$("#field_id_' . $field_id . '").before(\'' . $span . '\');
			$("#field_id_' . $field_id . '").css({paddingLeft: indent});
			');

        return form_input([
            'name' => $this->field_name,
            'id' => $this->field_name,
            'class' => 'cartthrob_price_simple',
            'value' => $data,
            'maxlength' => $this->settings['field_maxl'],
        ]);
    }

    /**
     * @param $data
     * @return array|string
     */
    public function display_settings($data)
    {
        $field_maxl = (empty($data['field_maxl'])) ? 12 : $data['field_maxl'];
        $field_prefix = (empty($data['field_prefix'])) ? ee()->cartthrob->store->config('number_format_defaults_prefix') : $data['field_prefix'];

        return [
            'field_options_cartthrob_price_simple' => [
                'label' => 'field_options',
                'group' => 'cartthrob_price_simple',
                'settings' => [
                    [
                        'title' => 'field_max_length',
                        'desc' => '',
                        'fields' => [
                            'field_maxl_cps' => [
                                'type' => 'text',
                                'value' => $field_maxl,
                            ],
                        ],
                    ],
                    [
                        'title' => 'number_format_defaults_prefix',
                        'desc' => 'number_format_defaults_prefix_desc',
                        'fields' => [
                            'field_prefix_cps' => [
                                'type' => 'text',
                                'value' => $field_prefix,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param $data
     * @return array
     */
    public function settings_modify_column($data)
    {
        $fields = parent::settings_modify_column($data);

        $fields['field_id_' . $data['field_id']]['type'] = 'FLOAT';
        $fields['field_id_' . $data['field_id']]['default'] = 0;

        return $fields;
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function save_settings($data)
    {
        return [
            'field_maxl' => ee()->input->post('field_maxl_cps'),
            'field_prefix' => ee()->input->post('field_prefix_cps'),
            'field_fmt' => 'none',
        ];
    }
}
