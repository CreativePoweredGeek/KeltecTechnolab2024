<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_discount_ft extends Cartthrob_settings_ft
{
    public $info = [
        'name' => 'CartThrob Discount Settings',
        'version' => CARTTHROB_VERSION,
    ];

    public $prefix_only = false;

    public $variable_prefix = '';

    /**
     * Cartthrob_discount_ft constructor.
     */
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
        loadCartThrobPath();

        if (empty(ee()->session->cache[__CLASS__]['display_field'])) {
            $options = [];

            $this->fields = [];
            ee()->load->library('api/api_cartthrob_discount_plugins');

            foreach (ee()->api_cartthrob_discount_plugins->get_plugins() as $type => $plugin) {
                $options[$type] = lang($plugin['title']);

                foreach ($plugin['settings'] as $setting) {
                    $setting['plugin_type'] = $type;

                    $this->fields[] = $setting;
                }
            }

            foreach (ee()->api_cartthrob_discount_plugins->global_settings() as $setting) {
                $this->fields[] = $setting;
            }

            array_unshift($this->fields, [
                'type' => 'select',
                'name' => 'Type',
                'short_name' => 'type',
                'extra' => ' class="cartthrob_discount_plugin"',
                'options' => $options,
            ]);

            ee()->load->library('javascript');

            ee()->javascript->output('
				$(".cartthrob_discount_plugin").bind("change", function() {
					$(this).parents("table").eq(0).find("tbody tr").not(".global").hide().find(":input").attr("disabled", true);
					$(this).parents("table").eq(0).find("tbody tr."+$(this).val()).show().find(":input").attr("disabled", false);
				}).change();
			');

            ee()->session->cache[__CLASS__]['display_field'] = true;
        }

        unloadCartThrobPath();

        return parent::display_field($data);
    }

    /**
     * replace_tag
     *
     * converts the discount settings to something that can be output
     *
     * @param array $data
     * @param array $params
     * @param string|bool $tagdata
     * @return string
     *
     * {exp:channel:entries
     * channel='coupon_codes'
     * url_title='test'}
     *
     * {coupon_code_type}
     * {amount_off}
     * {/coupon_code_type}
     *
     * {/exp:channel:entries}
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        if (count($data) === 0 && preg_match('/' . LD . 'if ' . $this->variable_prefix . 'no_results' . RD . '(.*?)' . LD . '\/if' . RD . '/s',
            $tagdata, $match)) {
            ee()->TMPL->tagdata = str_replace($match[0], '', ee()->TMPL->tagdata);

            ee()->TMPL->no_results = $match[1];
        }

        if (!$data) {
            return ee()->TMPL->no_results();
        }

        // needs to be formatted as an array. should be, but just in case.
        if (!is_array($data)) {
            $data = [];
        }

        $row[] = $this->prefix_only ?
            array_key_prefix($data, $this->variable_prefix) :
            array_merge($data, array_key_prefix($data, $this->variable_prefix));

        return ee()->TMPL->parse_variables($tagdata, $row);
    }
}
