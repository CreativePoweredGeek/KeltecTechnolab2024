<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

abstract class Cartthrob_settings_ft extends EE_Fieldtype
{
    public $has_array_data = true;

    /**
     * @var array list of settings fields
     */
    protected $fields = [];

    protected $data;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display Field on Publish
     *
     * @param $data
     * @return string
     */
    public function display_field($data)
    {
        $this->data = $this->pre_process($data);

        if (empty(ee()->session->cache['Cartthrob_settings_ft']['display_field'])) {
            if (REQ != 'CP') {
                ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob_matrix_table.css" type="text/css" media="screen" />');
            }

            ee()->session->cache['Cartthrob_settings_ft']['display_field'] = true;
        }

        $settings = [];

        foreach ($this->fields as $setting) {
            $settings[] = $this->setting_metadata($setting);
        }

        loadCartThrobPath();
        $output = ee()->load->view('cartthrob_settings_display_field', ['settings' => $settings], true);
        unloadCartThrobPath();

        return $output;
    }

    public function pre_process($data)
    {
        return _unserialize($data, true);
    }

    protected function setting_metadata($setting, $plugin_type = false)
    {
        if ($plugin_type === false) {
            $plugin_type = isset($setting['plugin_type']) ? $setting['plugin_type'] : 'global';
        }

        // retrieve the current set value of the field
        $current_value = (isset($this->data[$setting['short_name']])) ? $this->data[$setting['short_name']] : null;

        // set the value to the default value if there is no set value and the default value is defined
        $current_value = ($current_value === null && isset($setting['default'])) ? $setting['default'] : $current_value;

        $setting['current_value'] = $current_value;

        $setting['plugin_type'] = $plugin_type;

        if (method_exists($this, 'setting_' . $setting['short_name'])) {
            $setting['display_field'] = $this->{'setting_' . $setting['short_name']}($setting);
        } else {
            if (method_exists($this, 'setting_' . $setting['type'])) {
                $setting['display_field'] = $this->{'setting_' . $setting['type']}($setting);
            } else {
                $setting['display_field'] = '';
            }
        }

        return $setting;
    }

    public function save($data)
    {
        return (is_array($data)) ? base64_encode(serialize($data)) : '';
    }

    public function save_settings($data)
    {
        return [
            'field_fmt' => 'none',
            'field_wide' => true,
        ];
    }

    /**
     * Replace tag
     *
     * @param    field contents
     * @return replacement text
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        return '';
    }

    protected function setting_text($setting)
    {
        $input_data = [
            'name' => $this->field_name . '[' . $setting['short_name'] . ']',
            'value' => $setting['current_value'],
        ];

        if (isset($setting['size'])) {
            $input_data['style'] = 'width:' . $setting['size'] . ';';
        }

        return form_input($input_data);
    }

    protected function setting_date($setting)
    {
        if (empty(ee()->session->cache['Cartthrob_settings_ft']['datepicker'])) {
            ee()->cp->add_js_script('ui', 'datepicker');

            ee()->javascript->output('
			$(".ct_datepicker").datepicker({dateFormat: $.datepicker.W3C + EE.date_obj_time, defaultDate: new Date(' . (ee()->localize->now * 1000) . ')});
			');

            ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/jquery-ui.min.css" type="text/css" media="screen" />');

            ee()->session->cache['Cartthrob_settings_ft']['datepicker'] = true;
        }

        return form_input([
            'name' => $this->field_name . '[' . $setting['short_name'] . ']',
            'value' => $setting['current_value'],
            'class' => 'ct_datepicker',
        ]);
    }

    protected function setting_textarea($setting)
    {
        return form_textarea([
            'name' => $this->field_name . '[' . $setting['short_name'] . ']',
            'value' => $setting['current_value'],
            'rows' => 2,
        ]);
    }

    protected function setting_hidden($setting)
    {
        return form_hidden($this->field_name . '[' . $setting['short_name'] . ']', $setting['current_value']);
    }

    protected function setting_select($setting)
    {
        if (array_values($setting['options']) === $setting['options']) {
            foreach ($setting['options'] as $key => $value) {
                unset($setting['options'][$key]);

                $setting['options'][$value] = $value;
            }
        }

        return form_dropdown($this->field_name . '[' . $setting['short_name'] . ']', $setting['options'],
            $setting['current_value'], @$setting['extra']);
    }

    protected function setting_multiselect($setting)
    {
        switch ($setting['short_name']) {
            case 'categories':
                ee()->load->model('category_model');

                $query = ee()->category_model->get_category_groups('', ee()->config->item('site_id'));

                $category_groups = [];

                foreach ($query->result() as $row) {
                    $category_groups[$row->group_id] = $row->group_name;
                }

                ee()->legacy_api->instantiate('channel_categories');

                $category_form_tree = ee()->api_channel_categories->category_form_tree(true);

                $setting['options'] = [];

                if ($category_form_tree) {
                    foreach ($category_form_tree as $key => $value) {
                        $optgroup = isset($category_groups[$value[0]]) ? $category_groups[$value[0]] : '';

                        if (!isset($setting['options'][$optgroup])) {
                            $setting['options'][$optgroup] = [];
                        }

                        $previous_key = $key - 1;

                        $next_key = $key + 1;

                        if ($previous_key < 0 || !isset($category_form_tree[$previous_key])) {
                            $category_options['NULL_' . $next_key] = '-------';
                        }

                        $setting['options'][$optgroup][$value[1]] = str_replace('!-!!-!!-!!-!!-!!-!', '-', $value[2]);

                        if (isset($category_form_tree[$next_key]) && $category_form_tree[$next_key][0] != $value[0]) {
                            $category_options['NULL_' . $next_key] = '-------';
                        }
                    }
                }

                break;
        }
        if (array_values($setting['options']) === $setting['options']) {
            foreach ($setting['options'] as $key => $value) {
                unset($setting['options'][$key]);

                $setting['options'][$value] = $value;
            }
        }

        $extra = $setting['extra'] ?? '';

        return form_multiselect($this->field_name . '[' . $setting['short_name'] . '][]', $setting['options'], $setting['current_value'], $extra);
    }

    protected function setting_checkbox($setting)
    {
        if (!isset($setting['options']) || !is_array($setting['options'])) {
            $display_field = form_label(form_checkbox($this->field_name . '[' . $setting['short_name'] . ']', 1,
                $setting['current_value'],
                'id="' . $this->field_name . '[' . $setting['short_name'] . ']' . '"') . NBS . ee()->lang->line('yes'),
                $this->field_name . '[' . $setting['short_name'] . ']');
        } else {
            $display_field = '';

            // if is index array
            if (array_values($setting['options']) === $setting['options']) {
                foreach ($setting['options'] as $value) {
                    $display_field .= form_label(form_checkbox($this->field_name . '[' . $setting['short_name'] . '][]',
                        $value, $setting['current_value'] == $value) . NBS . $value,
                        $this->field_name . '[' . $setting['short_name'] . '][]');
                }
            } // if associative array
            else {
                foreach ($setting['options'] as $key => $value) {
                    $display_field .= form_label(form_checkbox($this->field_name . '[' . $setting['short_name'] . '][]',
                        $key, $setting['current_value'] == $key) . NBS . $value,
                        $this->field_name . '[' . $setting['short_name'] . '][]');
                }
            }
        }

        return $display_field;
    }

    protected function setting_radio($setting)
    {
        if (!isset($setting['options']) || !is_array($setting['options'])) {
            $display_field = form_label(form_radio($this->field_name . '[' . $setting['short_name'] . ']', 1,
                $setting['current_value']) . NBS . ee()->lang->line('yes'),
                $this->field_name . '[' . $setting['short_name'] . ']');

            $display_field .= form_label(form_radio($this->field_name . '[' . $setting['short_name'] . ']', 0,
                !$setting['current_value']) . NBS . ee()->lang->line('no'),
                $this->field_name . '[' . $setting['short_name'] . ']');
        } else {
            $display_field = '';

            // if is index array
            if (array_values($setting['options']) === $setting['options']) {
                foreach ($setting['options'] as $value) {
                    $display_field .= form_label(form_radio($this->field_name . '[' . $setting['short_name'] . ']',
                        $value, $setting['current_value'] == $value) . NBS . $value,
                        $this->field_name . '[' . $setting['short_name'] . ']');
                }
            } // if associative array
            else {
                foreach ($setting['options'] as $key => $value) {
                    $display_field .= form_label(form_radio($this->field_name . '[' . $setting['short_name'] . ']',
                        $key, $setting['current_value'] == $key) . NBS . $value,
                        $this->field_name . '[' . $setting['short_name'] . ']');
                }
            }
        }

        return $display_field;
    }
}
