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
class Cartthrob_price_modifiers_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Price Modifiers',
        'version' => CARTTHROB_VERSION,
    ];

    public $default_row = [
        'option_value' => '',
        'option_name' => '',
        'price' => '',
    ];

    /**
     * Cartthrob_price_modifiers_ft constructor.
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
        loadCartThrobPath();
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
        if (isset($params['orderby']) && $params['orderby'] === 'price') {
            $params['orderby'] = 'price_numeric';
        }

        return parent::replace_tag($data, $params, $tagdata);
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
        ee()->load->helper('html');

        if (!$presets = ee()->config->item('cartthrob:price_modifier_presets')) {
            $presets = [];
        }

        $options = ['' => ee()->lang->line('select_preset')];

        $json_presets = [];

        foreach ($presets as $key => $preset) {
            $json_presets[] = [
                'name' => $key,
                'values' => $preset,
            ];

            $options[] = $key;
        }

        $this->additional_controls = ul(
            [
                form_dropdown('', $options, '',
                    'onchange="var event = arguments[0] || window.event; event.stopPropagation();"'),
                form_submit('', ee()->lang->line('load_preset'),
                    'onclick="$.cartthrobPriceModifiers.loadPreset($(this).parents(\'div.cartthrobMatrixControls\').prev(\'table.cartthrobMatrix\')); return false;" class="btn action"'),
                form_submit('', ee()->lang->line('delete_preset'),
                    'onclick="$.cartthrobPriceModifiers.deletePreset($(this).parents(\'div.cartthrobMatrixControls\').prev(\'table.cartthrobMatrix\')); return false;" class="btn action"'),
                form_submit('', ee()->lang->line('save_preset'),
                    'onclick="$.cartthrobPriceModifiers.savePreset($(this).parents(\'div.cartthrobMatrixControls\').prev(\'table.cartthrobMatrix\')); return false;" class="btn action"'),
            ],
            ['class' => 'cartthrobMatrixPresets']
        );

        unset($this->default_row['inventory']);
        unset($this->default_row['weight']);

        $channel_id = $this->get_channel_id();

        if (!$channel_id && isset(ee()->channel_form)) {
            $channel_id = ee()->channel_form->channel('channel_id');
        }

        if ($channel_id && $this->field_id == array_value(ee()->config->item('cartthrob:product_channel_fields'), $channel_id, 'inventory')) {
            $this->default_row['inventory'] = '';
        }

        if ($channel_id && $this->field_id == array_value(ee()->config->item('cartthrob:product_channel_fields'), $channel_id, 'weight')) {
            $this->default_row['weight'] = '';
        }

        if (empty(ee()->session->cache['cartthrob_price_modifiers']['head'])) {
            // always use action
            $url = (REQ === 'CP') ? '(EE.BASE+"/cp/addons/settings/cartthrob/save_price_modifier_presets_action").replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1")'
                : 'EE.BASE.replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1")+"&ACT="+' . ee()->functions->fetch_action_id('Cartthrob_mcp',
                    'save_price_modifier_presets_action');

            ee()->cp->add_to_foot('
                <script type="text/javascript">
                    $.cartthrobPriceModifiers = {
                        getCurrentIndex: function(e) {
                            return $(e).next("div.cartthrobMatrixControls").find("ul.cartthrobMatrixPresets select").val() || "";
                        },
                        presets: ' . json_encode($json_presets) . ',
                        savePreset: function(e) {
                            var index = this.getCurrentIndex(e);
                            var currentPreset = this.presets[index];
                            var hasCurrentPreset = typeof currentPreset !== "undefined";

                            // If preset select has a selected option, update the preset. Else create a new one.
                            if (hasCurrentPreset && confirm("' . ee()->lang->line('save_preset_confirm') . '")) {
                                this.updatePreset(e);
                            }

                            if (!hasCurrentPreset) {
                                this.createPreset(e);
                            }
                        },
                        updatePreset: function(e) {
                            var index = this.getCurrentIndex(e);
                            var currentPreset = this.presets[index];
                            currentPreset.values = $.cartthrobMatrix.serialize(e);
                            this.updatePresets();
                        },
                        createPreset: function(e) {
                            var name = prompt("' . ee()->lang->line('name_preset_prompt') . '", "");

                            this.presets.push({"name": name, "values": $.cartthrobMatrix.serialize(e)});

                            this.updateSelectDisplay();
                            // select option for this select only
                            var $targetSelect = $(e).next(".cartthrobMatrixControls").find(".cartthrobMatrixPresets select");
                            $targetSelect.find("option[value=" + (this.presets.length - 1) + "]").attr("selected", "selected");

                            this.updatePresets();
                        },
                        updateSelectDisplay: function() {
                            var newSelect = "<select>";
                            newSelect += "<option value=\'\'>' . ee()->lang->line('select_preset') . '</option>";
                            for (var i = 0; i < this.presets.length; i++) {
                                newSelect += "<option value=\'"+i+"\'>"+this.presets[i].name+"</option>";
                            }
                            newSelect += "</select>";
                            $("div.cartthrobMatrixControls ul.cartthrobMatrixPresets select").replaceWith(newSelect);
                        },
                        updatePresets: function() {
                            $.post(
                                ' . $url . ',
                                {
                                    "CSRF_TOKEN": EE.CSRF_TOKEN,
                                    "price_modifier_presets": this.presets
                                },
                                function(data){
                                    EE.CSRF_TOKEN = data.CSRF_TOKEN;
                                },
                                "json"
                            );
                        },
                        loadPreset: function(e) {
                            var index = this.getCurrentIndex(e);
                            var currentPreset = this.presets[index];
                            var hasCurrentPreset = typeof currentPreset !== "undefined";

                            if (hasCurrentPreset && confirm("' . ee()->lang->line('load_preset_confirm') . '")) {
                                $.cartthrobMatrix.unserialize(e, currentPreset.values);
                            }
                        },
                        deletePreset: function(e) {
                            var index = this.getCurrentIndex(e);
                            var currentPreset = this.presets[index];
                            var hasCurrentPreset = typeof currentPreset !== "undefined";

                            if (hasCurrentPreset && confirm("' . ee()->lang->line('delete_preset_confirm') . '")) {
                                if (this.presets.length === 1) {
                                    this.presets.pop();
                                } else {
                                    this.presets.splice(index, 1);
                                }
                                this.updatePresets();
                                this.updateSelectDisplay();
                            }
                        }
                    };
                </script>
            ');

            ee()->session->cache['cartthrob_price_modifiers']['head'] = true;
        }
        unloadCartThrobPath();

        return parent::display_field($data, $replace_tag);
    }
}
