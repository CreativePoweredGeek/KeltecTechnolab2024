<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 */
class Cartthrob_package_ft extends Cartthrob_matrix_ft
{
    public $info = [
        'name' => 'CartThrob Package',
        'version' => CARTTHROB_VERSION,
    ];

    public $default_row = [
        'entry_id' => 0,
        'title' => 0,
        'description' => '',
        'option_presets' => '',
        'allow_selection' => '',
    ];

    public $buttons = [];

    public $show_default_row = false;

    // public $hidden_columns = array();

    // public $additional_controls = '';

    // public $variable_prefix = '';

    // public $row_nomenclature = '';

    /**
     * Cartthrob_package_ft constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

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
                            'field_maxl_cpk' => [
                                'type' => 'text',
                                'value' => $field_maxl,
                            ],
                        ],
                    ],
                    [
                        'title' => 'number_format_defaults_prefix',
                        'desc' => 'number_format_defaults_prefix_desc',
                        'fields' => [
                            'field_prefix_cpk' => [
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
     * @param bool $replace_tag
     * @return string
     */
    public function display_field($data, $replace_tag = false)
    {
        loadCartThrobPath();

        if (!isset(ee()->session->cache['cartthrob_package']['head'])) {
            ee()->session->cache['cartthrob_package']['head'] = true;

            ee()->cp->add_to_head('
			<style type="text/css">
			.cartthrobPackageFilter {
				border: 1px solid #D0D7DF;
				padding: 15px;
			}
			table.cartthrobPackage th:nth-child(2), table.cartthrobPackage th:nth-child(5), table.cartthrobPackage th:nth-child(6) {
				width: 1%;
			}
			table.cartthrobPackage td:nth-child(2) {
				text-align: center;
			}
			table.cartthrobPackage td:nth-child(2), table.cartthrobPackage td:nth-child(5), table.cartthrobPackage td:nth-child(6) {
				padding-right: 10px;
			}
			table.cartthrobPackageOptionPresets {
				table-layout: auto;
				margin: 0 auto;
				border-collapse: collapse;
				border: none;
				box-shadow: none;
				border-radius: none;
			}
			table.cartthrobPackageOptionPresets select {
				margin: 0;
			}
			table.cartthrobPackageOptionPresets input[type=checkbox] {
				margin: 0 auto;
				display: block;
			}
			ul.cartthrobPackageFilteredEntries {
				background-color: white;
				list-style: none;
				margin: 10px 0 0;
				padding: 0;
				text-indent: 0;
				border: 1px solid #D0D7DF;
				height: 240px; /* 8 li */
				overflow: auto;
				overflow-x: none;
				overflow-y: scroll;
			}
			ul.cartthrobPackageFilteredEntries li {
				background-color: white;
				cursor: pointer;
				padding: 8px;
				height: 14px;
			}
			ul.cartthrobPackageFilteredEntries li:hover {
				background-color:#CCE6FF;
			}
			table.cartthrobMatrix table.cartthrobPackageOptionPresets td {
				border: 0 !important;
				padding: 0 !important;
				height: 28px;
				overflow: hidden;
				white-space: nowrap;
			}
			table.cartthrobMatrix table.cartthrobPackageOptionPresets td label {
				padding-right: 15px;
			}
			</style>
			');

            ee()->lang->loadfile('cartthrob');

            ee()->load->library('javascript');

            ee()->cp->add_js_script(['ui' => ['autocomplete']]);

            ee()->javascript->output('
			$(".cartthrobPackageFilter").parent().css("marginTop", 0);
			EE.cartthrobPackage = {
				currentFilter: {
					id: 0,
					xhr: null,
					entries: {},
				},
				getFilters: function(package, exclude_keywords){
					var filter = {};
					var selector = ":input";
					if (exclude_keywords === true) {
						selector += ":not(.keywords)";
					}
					$(package).next(".cartthrobMatrixControls").find(".cartthrobPackageFilter").children(selector).each(function(){
						filter[$(this).attr("class")] = $(this).val();
					});
					return filter;
				},
				showFilteredEntries: function(package) {
					EE.cartthrobPackage.currentFilter.id++;
					var filter = {
						filter_id: EE.cartthrobPackage.currentFilter.id
					};
					$.extend(filter, EE.cartthrobPackage.getFilters(package));
					var list = $(package).next(".cartthrobMatrixControls").find(".cartthrobPackageFilteredEntries");
					var color = list.css("color");
					list.children("li").animate({color: "#999"}, 100);
					
					try {
						EE.cartthrobPackage.currentFilter.xhr = $.getJSON(
							(EE.BASE+"/cp/addons/settings/cartthrob/package_filter").replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1"),
							filter,
							function(data, textStatus, XMLHttpRequest) {
								if (XMLHttpRequest != EE.cartthrobPackage.currentFilter.xhr) { return; }
								if (data.id != EE.cartthrobPackage.currentFilter.id) { return; }
								
								list.html("");
								$.each(data.entries, function(i, entry){
									EE.cartthrobPackage.currentFilter.entries[entry.entry_id] = entry;
									list.append($("<li />", {text: entry.title+" (id: "+entry.entry_id+")", rel: entry.entry_id, "class": "entry"}).css({color: "#999"}));
								});
								if (data.entries.length === 0) {
									list.append($("<li />", ' . json_encode(['text' => ee()->lang->line('no_products_in_search')]) . '));
								}
								list.children("li").animate({color: color}, 100);
							}
						);
					} catch (e) {
						console.log(e);
					}
				},
				loadEntry: function(entryID, package){
					var entry = EE.cartthrobPackage.currentFilter.entries[entryID];
					var row = $.cartthrobMatrix.addRow(package);
					row.find(".title").html(entry.title);
					row.find(".entry_id:not(:input)").html(entry.entry_id);
					row.find(".entry_id:input").val(entry.entry_id);
					var fieldName = row.find(".entry_id:input").attr("name").replace("entry_id", "NAME");
					var optionPresets = "<table border=\'0\' cellpadding=\'0\' cellspacing=\'0\' class=\'cartthrobPackageOptionPresets\'><tbody>";
					var allowSelection = optionPresets;
					$.each(entry.price_modifiers, function(priceModifier, data){
						var options = $.extend({}, data);
						var label = options.label;
						delete options.label;
						if ($.isEmptyObject(options)) {
							return;
						}
						allowSelection += "<tr><td><input type=\'checkbox\' value=\'1\' name=\'"+fieldName.replace("NAME", "allow_selection")+"["+priceModifier+"]\'></td></tr>";
						optionPresets += "<tr><td><label>"+label+"</label></td><td><select name=\'"+fieldName.replace("NAME", "option_presets")+"["+priceModifier+"]\'>";
						optionPresets += "<option>-----</option>";
						$.each(options, function(i, option){
							optionPresets += "<option value=\'"+option.option_value+"\'>"+option.option_name+"</option>";
						});
						optionPresets += "</select></td></tr>";
					});
					optionPresets += "</tbody></table>";
					allowSelection += "</tbody></table>";
					row.children("td:eq(4)").html(optionPresets);
					row.children("td:eq(5)").html(allowSelection);
				}
			};
			$(".cartthrobPackageFilter :input")
				.bind("change", function(event){
					EE.cartthrobPackage.showFilteredEntries($(event.target).parents(".cartthrobMatrixControls").prev("table.cartthrobPackage"));
				})
				.bind("keypress", function(event){
					if (event.keyCode === 13) {
						return false;
					}
				}
			);
			$(".cartthrobPackageFilter input.keywords").bind("keyup", function(event){
				EE.cartthrobPackage.showFilteredEntries($(event.target).parents(".cartthrobMatrixControls").prev("table.cartthrobPackage"));
			});
			$(document).on("click", ".cartthrobPackageFilteredEntries li.entry", function(event){
				EE.cartthrobPackage.loadEntry($(event.target).attr("rel"), $(event.target).parents(".cartthrobMatrixControls").prev("table.cartthrobPackage"));
			});
			
		 // call it on load
			EE.cartthrobPackage.showFilteredEntries($("table.cartthrobPackage"));
			');
        }

        $data = $this->pre_process($data);
        $price = null;
        if (array_key_exists('price', $data)) {
            $price = element('price', $data);
            unset($data['price']);
        }
        $vars['categories'] = ['' => lang('category')];

        ee()->load->model('cartthrob_settings_model');

        $channel_ids = ee()->config->item('cartthrob:product_channels');

        if (!$channel_ids) {
            $vars['channels'] = ['X' => lang('no_product_channels')];
        } else {
            $vars['channels'] = [];
            $vars['channels']['null'] = lang('channel');
            $vars['categories']['none'] = lang('none');

            ee()->load->model('channel_model');

            $channels = ee('Model')
                ->get('Channel')
                ->filter('channel_id', 'IN', $channel_ids);

            $used_cat_groups = [];

            foreach ($channels->all() as $row) {
                $vars['channels'][$row->channel_id] = $row->channel_title;

                if ($row->cat_group) {
                    ee()->load->model('category_model');

                    $cat_groups = explode('|', $row->cat_group);

                    foreach ($cat_groups as $group_id) {
                        if (in_array($group_id, $used_cat_groups)) {
                            continue;
                        }

                        $used_cat_groups[] = $group_id;

                        $categories = ee()->category_model->get_channel_categories($group_id);

                        if ($categories->num_rows() > 0) {
                            $vars['categories']['NULL_1'] = '-------';

                            foreach ($categories->result() as $row) {
                                $vars['categories'][$row->cat_id] = $row->cat_name;
                            }
                        }
                    }
                }
            }
        }

        loadCartThrobPath();

        $this->additional_controls = ee()->load->view('cartthrob_package_filter', $vars, true);

        // / Display Price Field
        $channel_id = $this->get_channel_id();

        if ($channel_id && $this->field_id == array_value(ee()->config->item('cartthrob:product_channel_fields'),
            $channel_id, 'price')) {
            $prefix = $this->get_prefix();

            $span = '<span style="position:absolute;padding:5px 0 0 5px;">' . $prefix . '</span>';

            ee()->javascript->output('
					var span = $(\'' . $span . '\').appendTo("body").css({top:-9999});
					var indent = span.width()+4;
					span.remove();

				$("#' . $this->field_id . '_price' . '").before(\'' . $span . '\');
				$("#' . $this->field_id . '_price' . '").css({paddingLeft: indent});
				');

            if (empty($this->settings['field_maxl'])) {
                $this->settings['field_maxl'] = 4;
            }
            $this->additional_controls .=
                form_label('<br><strong class="title">' . ee()->lang->line('cartthrob_package_price') . '</strong>',
                    $this->field_name . '_price') .
                form_input([
                    'name' => 'field_id_' . $this->field_id . '[price]',
                    'id' => $this->field_id . '_price',
                    // 'class' => 'cartthrob_price_simple',
                    'value' => $price,
                    'maxlength' => $this->settings['field_maxl'],
                ]);
        }
        // //
        $output = parent::display_field($data, $replace_tag);
        unloadCartThrobPath();

        return $output;
    }

    /**
     * @param $data
     * @return array|mixed
     */
    public function pre_process($data)
    {
        // unserializes it
        $data = parent::pre_process($data);
        loadCartThrobPath();

        ee()->load->library('data_filter');

        ee()->load->model('cartthrob_entries_model');

        if (array_key_exists('price', $data)) {
            $price = element('price', $data);
            unset($data['price']);
        }

        // get the entry_ids from the array
        $entry_ids = ee()->data_filter->key_values($data, 'entry_id');

        // preload all the entries pertaining to this package
        ee()->cartthrob_entries_model->loadEntriesByEntryId($entry_ids);
        unloadCartThrobPath();

        return $data;
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
     * @return float|int|string
     */
    public function cartthrob_price($data, $item = null)
    {
        loadCartThrobPath();

        ee()->load->model(['product_model', 'cartthrob_entries_model', 'cartthrob_field_model']);
        ee()->load->helper(['array']);

        unloadCartThrobPath();

        if ($item instanceof Cartthrob_item) {
            return $item->price();
        }
        if (!is_array($data)) {
            $serialized = $data;

            if (!isset(ee()->session->cache['cartthrob']['package']['cartthrob_price'][$serialized])) {
                ee()->session->cache['cartthrob']['package']['cartthrob_price'][$serialized] = _unserialize($data,
                    true);
            }

            $data = ee()->session->cache['cartthrob']['package']['cartthrob_price'][$serialized];
        }
        reset($data);
        $price = 0;

        foreach ($data as $key => $value) {
            // skip the price field.
            if (!is_numeric($key)) {
                if ($key == 'price' && $value !== '' && $value !== null && $value !== false) {
                    var_dump($value);

                    return $value;
                }
                continue;
            }

            $entry_id = $value['entry_id'];
            $price += trim(ee()->product_model->get_base_price($entry_id));
            // echo $price;

            if (isset($value['option_presets'])) {
                foreach ($value['option_presets'] as $field_name => $option_value) {
                    $item_option = ee()->product_model->get_price_modifier_value($entry_id, $field_name, $option_value);
                    if (element('price', $item_option) !== false) {
                        $price += trim(element('price', $item_option, 0));
                    }
                }
            }
        }
        // die;
        return $price;
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return mixed
     */
    public function replace_plus_tax($data, $params = '', $tagdata = '')
    {
        ee()->load->library('number');

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $plugin->get_tax($this->cartthrob_price($data)) + $this->cartthrob_price($data);
        }
        ee()->number->set_prefix($this->get_prefix());

        return ee()->number->format($data);
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
     * @param array $params
     * @param bool $tagdata
     * @return string
     */
    public function replace_tag($data, $params = [], $tagdata = false)
    {
        loadCartThrobPath();

        ee()->load->model(['product_model', 'cartthrob_entries_model']);

        // don't want the STRING price being converted into a row. we only need the price for the :price tag
        if (array_key_exists('price', $data)) {
            unset($data['price']);
        }
        foreach ($data as $row_id => $row) {
            if (isset($row['entry_id']) && $product = ee()->product_model->get_product($row['entry_id'])) {
                if (!empty($row['option_presets']) && !isset($row['allow_selection'])) {
                    $row['allow_selection'] = [];
                }
                $row['parent_id'] = $row['sub:parent_id'] = $this->content_id();
                $row['row_id'] = $row['sub:row_id'] = $this->content_id() . ':' . $row_id . ':';
                $row['identifier'] = $row['sub:identifier'] = ':' . $row_id;
                $row['child_id'] = $row['sub:child_id'] = $row_id;

                $data[$row_id] = array_merge($row,
                    ee()->cartthrob_entries_model->entry_vars($product, $tagdata, 'sub:'));
            }
        }
        unloadCartThrobPath();

        return parent::replace_tag($data, $params, $tagdata);
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return float|int|string
     */
    public function replace_plus_tax_numeric($data, $params = '', $tagdata = '')
    {
        ee()->load->library('number');

        if ($plugin = ee()->cartthrob->store->plugin(ee()->cartthrob->store->config('tax_plugin'))) {
            $data = $plugin->get_tax($this->cartthrob_price($data)) + $this->cartthrob_price($data);
        }

        return $data;
    }

    /**
     * @param $data
     * @param string $params
     * @param string $tagdata
     * @return float|int|string
     */
    public function replace_numeric($data, $params = '', $tagdata = '')
    {
        return $this->cartthrob_price($data);
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_entry_id($name, $value, $row, $index, $blank = false)
    {
        $output = '';

        // $output .= '<strong class="title">'.element('title', $product).'</strong>'.NBS.'(id: <span class="entry_id">'.$value.'</span>)';//.NBS.NBS.NBS.anchor('#', 'change &raquo;');
        $output .= '<span class="entry_id">' . $value . '</span>';

        $attributes = [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
            'class' => 'entry_id',
        ];

        if ($blank) {
            $attributes['disabled'] = 'disabled';
        }

        $output .= '<input ' . _parse_attributes($attributes) . '>';

        return $output;
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_title($name, $value, $row, $index, $blank = false)
    {
        loadCartThrobPath();

        $title = '';

        if (!empty($row['entry_id'])) {
            ee()->load->model('product_model');

            ee()->load->helper(['array', 'html']);

            $product = ee()->product_model->get_product($row['entry_id']);

            $title = element('title', $product);

            if ($product) {
                $title = anchor(ee('CP/URL')->make('publish/edit/entry/' . $row['entry_id']), $title,
                    ['target' => '_blank']);
            }
        }
        unloadCartThrobPath();

        return '<strong class="title">' . $title . '</strong>';
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_option_presets($name, $value, $row, $index, $blank = false)
    {
        loadCartThrobPath();

        if (empty($row['entry_id'])) {
            return NBS;
        }

        $ol = [];

        ee()->load->model('product_model');

        ee()->load->helper('array');

        $price_modifiers = ee()->product_model->get_all_price_modifiers($row['entry_id']);

        if (!$price_modifiers) {
            return NBS;
        }

        ee()->load->model('cartthrob_field_model');

        ee()->load->library('table');

        // i already know the table lib is loaded
        $table = new EE_Table();

        $table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="cartthrobPackageOptionPresets">']);

        foreach ($price_modifiers as $field_name => $options) {
            if (count($options) === 0) {
                continue;
            }

            $select_options = ['' => '-----'];

            foreach ($options as $option) {
                $select_options[$option['option_value']] = $option['option_name'];
            }

            $label = ee()->cartthrob_field_model->get_field_label(ee()->cartthrob_field_model->get_field_id($field_name));

            $input_name = $name . '[' . $field_name . ']';

            $attributes = ['id' => $input_name];

            if ($blank) {
                $attributes['disabled'] = 'disabled';
            }

            $table->add_row(form_label($label, $input_name),
                form_dropdown($input_name, $select_options, element($field_name, $value),
                    _parse_attributes($attributes)));
        }
        unloadCartThrobPath();

        return ($table->rows) ? $table->generate() : NBS;
    }

    /**
     * @param $name
     * @param $value
     * @param $row
     * @param $index
     * @param bool $blank
     * @return string
     */
    public function display_field_allow_selection($name, $value, $row, $index, $blank = false)
    {
        loadCartThrobPath();
        if (empty($row['entry_id'])) {
            return NBS;
        }

        $ol = [];

        ee()->load->model('product_model');
        ee()->load->helper('array');
        unloadCartThrobPath();

        $price_modifiers = ee()->product_model->get_all_price_modifiers($row['entry_id']);

        if (!$price_modifiers) {
            return NBS;
        }

        $table = new EE_Table();

        $table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="cartthrobPackageOptionPresets">']);

        foreach ($price_modifiers as $field_name => $options) {
            if (count($options) === 0) {
                continue;
            }

            $extra = ($blank) ? 'disabled="disabled"' : '';

            $table->add_row(form_checkbox($name . '[' . $field_name . ']', '1', element($field_name, $value), $extra));
        }

        return ($table->rows) ? $table->generate() : NBS;
    }
}
