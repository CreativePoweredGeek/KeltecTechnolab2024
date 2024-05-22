<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// /////// REQUIREMENTS /////////////////
// LIBRARIES locales, package_installer
// MODELS field_model, channel_model, generic_model, template_model, crud_model, packages_field_model, packages_entries_model, table_model
// HELPERS  security, form, string, data_formatting, file

// imagess: ct_drag_handle.gif

if (!class_exists('Mbr_addon_builder')) {
    class Mbr_addon_builder
    {
        public $module_name = null;
        public $settings = [];
        public $module_enabled = null;
        public $extension_enabled = null;
        public $no_form = [];
        public $no_nav = [];
        public $version = 1;
        public $nav = [];

        private $remove_keys = [
            'name',
            'submit',
            'x',
            'y',
            'templates',
            'CSRF_TOKEN',
            'XID',
        ];

        public $tables = [];
        public $mod_actions = [];
        public $mcp_actions = [];
        public $fieldtypes = [];
        public $hooks = [];
        public $notification_events = [];
        public $cartthrob;
        public $store;
        public $cart;

        public $current;

        public $drag_handle;

        protected ?array $templates = null;

        /**
         * Mbr_addon_builder constructor.
         */
        public function __construct()
        {
            if ($this->get_cartthrob_settings()) {
                $this->drag_handle = URL_THIRD_THEMES . 'cartthrob/images/ct_drag_handle.gif';
            } else {
                $this->drag_handle = URL_THIRD_THEMES . $this->module_name . '/images/ct_drag_handle.gif';
            }
        }

        /**
         * @param array $params
         */
        public function initialize($params = [])
        {
            ee()->load->library('table');
            ee()->load->library('locales');

            ee()->load->helper(['security', 'form', 'string', 'data_formatting']);

            ee()->load->model(['field_model', 'channel_model', 'generic_model', 'template_model']);

            if (empty($params)) {
                $params = [];
            }

            foreach ($params as $key => $value) {
                $this->{$key} = $value;
            }

            if (!empty($params['module_name'])) {
                $this->module_name = $params['module_name'];
                unset($params['module_name']);
            } else {
                $trace = debug_backtrace();
                $caller = array_shift($trace);
                if (isset($caller['class'])) {
                    $this->module_name = $caller['class'];
                }
            }

            $this->settings = ee('cartthrob:SettingsService')->settings($this->module_name);

            if ($this->get_cartthrob_settings()) {
                loadCartThrobPath();
            }

            ee()->load->add_package_path(PATH_THIRD . $this->module_name . '/');

            ee()->lang->loadfile($this->module_name, $this->module_name);

            $this->module_enabled = true;
            $this->extension_enabled = true;

            if (empty($params['skip_module'])) {
                $this->module_enabled = (bool)ee()->db->where(
                    'module_name',
                    ucwords($this->module_name)
                )->count_all_results('modules');
            }

            if (empty($params['skip_extension'])) {
                $this->extension_enabled = (bool)ee()->db->where([
                    'class' => ucwords($this->module_name) . '_ext',
                    'enabled' => 'y',
                ])->count_all_results('extensions');
            }
        }

        /**
         * @param null $database_table
         */
        public function form_update($database_table = null)
        {
            if ($database_table) {
                $table = $database_table;
            } else {
                $table = $this->module_name . '_options';
            }
            $model = new Generic_model($table);

            if (ee()->input->post('delete')) {
                if ($database_table) {
                    if (ee()->input->post('id')) {
                        ee()->db->delete($database_table, ['id' => ee()->input->post('id')]);
                    }
                } else {
                    // even though we have a model in play, for safety's sake, I'm goig to use EE's delete method for entries.
                    if (ee()->input->post('id')) {
                        ee()->load->library('api');
                        ee()->legacy_api->instantiate('channel_entries');
                        ee()->api_channel_entries->delete_entry(ee()->input->post('id'));
                    }
                }

                ee()->session->set_flashdata($this->module_name . '_system_message',
                    sprintf('%s', lang($this->module_name . '_deleted')));
            } else {
                foreach (array_keys($_POST) as $key) {
                    if (!in_array($key,
                        $this->remove_keys) && !preg_match('/^(' . ucwords($this->module_name) . '_.*?_settings)_.*/',
                            $key)) {
                        $data[$key] = ee()->input->post($key, true);
                    }
                }

                if (isset($data['sub_settings']['data'])) {
                    $data['data'] = serialize($data['sub_settings']['data']);
                }

                if (!ee()->input->post('id')) {
                    $model->create($data);
                } else {
                    if (ee()->input->post('id') && !empty($data)) {
                        $model->update(ee()->input->post('id'), $data);
                    }
                }
                ee()->session->set_flashdata($this->module_name . '_system_message',
                    sprintf('%s', lang($this->module_name . '_updated')));
            }
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $this->module_name) . AMP . 'method=' . ee()->input->get('return',
                true));
        }

        /**
         * @param $method
         * @param $total_rows
         * @param int $per_page
         * @return mixed
         */
        public function pagination_config($method, $total_rows, $per_page = 50)
        {
            $config['base_url'] = ee('CP/URL')->make('addons/settings/' . $this->module_name) . AMP . 'method=' . $method;
            $config['total_rows'] = $total_rows;
            $config['per_page'] = $per_page;
            $config['page_query_string'] = true;
            $config['query_string_segment'] = 'rownum';
            $config['full_tag_open'] = '<p id="paginationLinks">';
            $config['full_tag_close'] = '</p>';
            $config['prev_link'] = '<img src="' . ee()->cp->cp_theme_url . 'images/pagination_prev_button.gif" width="13" height="13" alt="<" />';
            $config['next_link'] = '<img src="' . ee()->cp->cp_theme_url . 'images/pagination_next_button.gif" width="13" height="13" alt=">" />';
            $config['first_link'] = '<img src="' . ee()->cp->cp_theme_url . 'images/pagination_first_button.gif" width="13" height="13" alt="< <" />';
            $config['last_link'] = '<img src="' . ee()->cp->cp_theme_url . 'images/pagination_last_button.gif" width="13" height="13" alt="> >" />';

            return $config;
        }

        /**
         * @param $table
         * @param $limit
         * @param null $offset
         * @param null $method is the redirect module method. If it's not set, the module name will be used instead
         * @return bool
         */
        public function get_pagination($table, $limit, $offset = null, $method = null)
        {
            if (!$offset) {
                $offset = ee()->input->get_post('rownum');
            }
            ee()->load->library('pagination');
            $total = ee()->db->count_all($table);
            if ($total == 0) {
                return false;
            }

            if (!$method) {
                $method = $this->module_name;
            }
            ee()->pagination->initialize($this->pagination_config($method, $total, $limit));

            return ee()->pagination->create_links();
        }

        /**
         * @return array
         */
        public function get_cartthrob_settings()
        {
            if ((bool)ee()->db->where('module_name', 'Cartthrob')->count_all_results('modules')) {
                return ee('cartthrob:SettingsService')->settings('cartthrob');
            }

            return [];
        }

        /**
         * @return array
         */
        public function get_templates()
        {
            if (is_null($this->templates)) {
                $this->templates = [];

                $query = ee()->template_model->get_templates();

                foreach ($query->result() as $row) {
                    $this->templates[$row->group_name . '/' . $row->template_name] = $row->group_name . '/' . $row->template_name;
                }
            }

            return $this->templates;
        }

        /**
         * @param $member_id
         * @return mixed
         */
        public function get_member_info($member_id)
        {
            return ee()->db->select('*')->where('member_id', $member_id)
                ->limit(1)
                ->get('members')
                ->row_array();
        }

        /**
         * @param $content
         * @param string $tag
         * @param string $attributes
         * @return string
         */
        protected function html($content, $tag = 'p', $attributes = '')
        {
            if (is_array($attributes)) {
                $attributes = _parse_attributes($attributes);
            }

            return '<' . $tag . $attributes . '>' . $content . '</' . $tag . '>';
        }

        /**
         * Creates setting controls
         *
         * @param string $type text|textarea|radio The type of control that is being output
         * @param string $name input name of the control option
         * @param string $current_value the current value stored for this input option
         * @param array|bool $options array of options that will be output (for radio, else ignored)
         * @return string the control's HTML
         */
        public function plugin_setting($type, $name, $current_value, $options = [], $attributes = [])
        {
            $output = '';

            if (!is_array($options)) {
                $options = [];
            } else {
                $new_options = [];
                foreach ($options as $key => $value) {
                    // optgropus
                    if (is_array($value)) {
                        $key = lang($key);
                        foreach ($value as $sub => $item) {
                            $new_options[$key][$sub] = lang($item);
                        }
                    } else {
                        $new_options[$key] = lang($value);
                    }
                }
                $options = $new_options;
            }

            if (!is_array($attributes)) {
                $attributes = [];
            }
            switch ($type) {
                case 'select':
                    if (empty($options)) {
                        $attributes['value'] = $current_value;
                    }
                    $output = form_dropdown($name, $options, $current_value, _attributes_to_string($attributes));
                    break;
                case 'file':
                    ee()->load->library('file_field');
                    $trigger = '.choose_file';
                    if (isset($attributes['trigger'])) {
                        $trigger = $attributes['trigger'];
                    }
                    $config = [
                        'publish' => true,
                        'trigger' => $trigger,
                        'field_name' => $name,
                        'function (file, field) { console.log(file, field); }',
                    ];
                    ee()->file_field->browser($config);
                    $output = ee()->file_field->field($name, $current_value, $allowed_file_dirs = 'all',
                        $content_type = 'image');

                    // $output .= '<input type="file" name="choose file" class="file_upload" />';
                    break;
                case 'multiselect':
                    $output = form_multiselect($name . '[]', $options, $current_value,
                        _attributes_to_string($attributes));
                    break;
                case 'checkbox':
                    $is_checked = false;
                    if (!empty($attributes['checked'])) {
                        $checked_opt = $attributes['checked'];
                        if (strpos($checked_opt, 'not') !== false) {
                            $is_checked = false;
                        } else {
                            $is_checked = true;
                        }
                    } else {
                        if (!empty($current_value)) {
                            $is_checked = true;
                        }
                    }
                    $output = form_label(form_checkbox($name, 1, $is_checked,
                        isset($options['extra']) ? $options['extra'] : '') . '&nbsp;' . (!empty($options['label']) ? $options['label'] : ee()->lang->line('yes')),
                        $name);
                    break;
                case 'text':
                    $attributes['name'] = $name;
                    $attributes['value'] = $current_value;
                    $output = form_input($attributes);
                    break;
                case 'hidden':
                    $output = form_hidden($name, $current_value);
                    break;
                case 'textarea':
                    $attributes['name'] = $name;
                    $attributes['class'] = 'rte';
                    $attributes['value'] = $current_value;
                    $output = form_textarea($attributes);
                    break;
                case 'radio':
                    if (empty($options)) {
                        $output .= form_label(form_radio($name, 1,
                            (bool)$current_value) . '&nbsp;' . ee()->lang->line('yes'), $name,
                            ['class' => 'radio']);
                        $output .= form_label(form_radio($name, 0,
                            (bool)!$current_value) . '&nbsp;' . ee()->lang->line('no'), $name,
                            ['class' => 'radio']);
                    } else {
                        // if is index array
                        if (array_values($options) === $options) {
                            foreach ($options as $option) {
                                $output .= form_label(form_radio($name, $option,
                                    $current_value === $option) . '&nbsp;' . $option, $name,
                                    ['class' => 'radio']);
                            }
                        } // if associative array
                        else {
                            foreach ($options as $option => $option_name) {
                                $output .= form_label(form_radio($name, $option,
                                    $current_value === $option) . '&nbsp;' . lang($option_name), $name,
                                    ['class' => 'radio']);
                            }
                        }
                    }
                    break;
                default:
            }

            return $output;
        }

        public function settings_form()
        {
            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $this->module_name));
        }

        /**
         * Activates Extension
         *
         * @param null
         */
        public function activate_extension($hooks = [], $class = null)
        {
            if (!$class) {
                $class = $this->module_name . '_ext';
            }

            foreach ($hooks as $row) {
                ee()->db->insert(
                    'extensions',
                    [
                        'class' => $class,
                        'method' => $row[0],
                        'hook' => (!isset($row[1])) ? $row[0] : $row[1],
                        'settings' => (!isset($row[2])) ? '' : $row[2],
                        'priority' => (!isset($row[3])) ? 10 : $row[3],
                        'version' => $this->version,
                        'enabled' => 'y',
                    ]
                );
            }

            return true;
        }

        /**
         * Updates Extension
         *
         * @param string
         * @return void|bool False if the extension is current
         */
        public function update_extension($current = '')
        {
            if ($current == '' or $current == $this->version) {
                return false;
            }

            ee()->db->update('extensions', ['version' => $this->version], ['class' => $this->module_name]);

            return true;
        }

        /**
         * Disables Extension
         *
         * Deletes mention of this extension from the exp_extensions database table
         *
         * @param null
         */
        public function disable_extension()
        {
            ee()->db->delete('extensions', ['class' => $this->module_name]);
        }

        /**
         * @param string $has_cp_backend
         * @param string $has_publish_fields
         * @param bool $current
         * @return bool
         */
        public function mbr_install($has_cp_backend = 'y', $has_publish_fields = 'n', $current = false)
        {
            // updates
            if ($current !== false) {
                $this->current = $current;
                if ($this->current == $this->version) {
                    return false;
                }
            } else { // installs
                // install module to exp_modules
                $data = [
                    'module_name' => ucwords($this->module_name),
                    'module_version' => $this->version,
                    'has_cp_backend' => $has_cp_backend,
                    'has_publish_fields' => $has_publish_fields,
                ];

                ee()->db->insert('modules', $data);

                // //////////// FIELD TYPES

                if (!empty($this->fieldtypes)) {
                    // install the fieldtypes
                    require_once APPPATH . 'fieldtypes/EE_Fieldtype.php';

                    foreach ($this->fieldtypes as $fieldtype) {
                        $fieldTypeClasVars = get_class_vars(ucwords($fieldtype . '_ft'));

                        ee()->db->insert('fieldtypes', [
                            'name' => $fieldtype,
                            'version' => $fieldTypeClasVars['info']['version'],
                            'settings' => base64_encode(serialize([])),
                            'has_global_settings' => method_exists($fieldtype, 'display_global_settings') ? 'y' : 'n',
                        ]);
                    }
                }
            }
            // /////////////// TABLES /////////////////////////
            ee()->load->dbforge();

            // only do this if we actually have tables.
            if (!empty($this->tables)) {
                foreach ($this->tables as $key => $value) {
                    if ($key == 'generic_settings') {
                        unset($this->tables['generic_settings']);
                        $this->tables[$this->module_name . '_settings'] = $value;
                        break;
                    }
                }
                ee()->load->model('table_model');
                ee()->table_model->update_tables($this->tables);
            }

            // ///////////// NOTIFICICATIONS /////////////////////////
            if (!empty($this->notification_events)) {
                $existing_notifications = [];

                if (ee()->db->table_exists('cartthrob_notification_events')) {
                    ee()->db->select('notification_event')
                        ->like('application', ucwords($this->module_name), 'after');
                    $query = ee()->db->get('cartthrob_notification_events');

                    if ($query->result() && $query->num_rows() > 0) {
                        foreach ($query->result() as $row) {
                            $existing_notifications[] = $row->notification_event;
                        }
                    }

                    foreach ($this->notification_events as $event) {
                        if (!empty($event)) {
                            if (!in_array($event, $existing_notifications)) {
                                ee()->db->insert(
                                    'cartthrob_notification_events',
                                    [
                                        'application' => ucwords($this->module_name),
                                        'notification_event' => $event,
                                    ]
                                );
                            }
                        }
                    }
                }
            }
            // end notifications

            // ///////////// EXTENSIONS /////////////////////////

            if (!empty($this->hooks)) {
                if ($current !== false) {
                    ee()->db->update('extensions', ['version' => $this->version],
                        ['class' => ucwords($this->module_name) . '_ext']);
                }
                ee()->db->select('method')
                    ->from('extensions')
                    ->like('class', ucwords($this->module_name), 'after');

                $existing_extensions = [];

                foreach (ee()->db->get()->result() as $row) {
                    $existing_extensions[] = $row->method;
                }

                foreach ($this->hooks as $row) {
                    if (!empty($row)) {
                        if (!in_array($row[0], $existing_extensions)) {
                            ee()->db->insert(
                                'extensions',
                                [
                                    'class' => ucwords($this->module_name) . '_ext',
                                    'method' => $row[0],
                                    'hook' => (!isset($row[1])) ? $row[0] : $row[1],
                                    'settings' => (!isset($row[2])) ? '' : $row[2],
                                    'priority' => (!isset($row[3])) ? 10 : $row[3],
                                    'version' => $this->version,
                                    'enabled' => 'y',
                                ]
                            );
                        }
                    }
                }
            }
            // //////////////////////// MODULE AND MCP ACTIONS /////////////////////

            // check for Addon actions in the database
            // so we don't get duplicates
            ee()->db->select('method')
                ->from('actions')
                ->like('class', ucwords($this->module_name), 'after');

            $existing_methods = [];

            foreach (ee()->db->get()->result() as $row) {
                $existing_methods[] = $row->method;
            }

            // ////////// MODULE ACTIONS
            if (!empty($this->mod_actions)) {
                // install the module actions from $this->mod_actions
                foreach ($this->mod_actions as $method) {
                    if (!in_array($method, $existing_methods)) {
                        ee()->db->insert('actions', ['class' => ucwords($this->module_name), 'method' => $method]);
                    }
                }
            }
            // /////////// MCP ACTIONS
            if (!empty($this->mcp_actions)) {
                // install the module actions from $this->mcp_actions
                foreach ($this->mcp_actions as $method) {
                    if (!in_array($method, $existing_methods)) {
                        ee()->db->insert('actions',
                            ['class' => ucwords($this->module_name) . '_mcp', 'method' => $method]);
                    }
                }
            }

            return true;
        }

        /**
         * @param string $has_cp_backend
         * @param string $has_publish_fields
         * @return bool
         */
        public function install($has_cp_backend = 'y', $has_publish_fields = 'n')
        {
            return $this->mbr_install($has_cp_backend, $has_publish_fields);
        }

        /**
         * @param string $current
         * @return bool
         */
        public function update($current = '')
        {
            return $this->mbr_install(null, null, $current);
        }

        /**
         * @return bool
         */
        public function uninstall()
        {
            ee()->db->delete('modules', ['module_name' => ucwords($this->module_name)]);

            ee()->db->like('class', ucwords($this->module_name), 'after')->delete('actions');

            ee()->db->delete('extensions', ['class' => ucwords($this->module_name) . '_ext']);

            if (ee()->db->table_exists('cartthrob_notification_events')) {
                ee()->db->delete('cartthrob_notification_events', ['application' => ucwords($this->module_name)]);
            }

            return true;
        }

        /**
         * @param $data
         * @return string|null
         */
        public function view_settings_template($data)
        {
            /** @var array $structure Variable is contained within the $data variable */
            $structure = [];
            extract($data, EXTR_OVERWRITE);
            $content = null;
            $content = '<div class="' . $structure['class'] . '_settings" id="' . $structure['class'] . '">';

            // //////////////////////////// Main Heading ///////////////////////
            $tmpl = [
                'table_open' => '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">',
            ];

            $output_table = false;
            if (!empty($structure['caption'])) {
                $output_table = true;
                ee()->table->set_caption(lang($structure['caption']));
            }

            if (!empty($structure['description'])) {
                $output_table = true;
                ee()->table->set_heading([
                    '<strong>' . lang($structure['title']) . '</strong><p>' . lang($structure['description']) . '</p>',
                ]);
            } elseif (!empty($structure['title'])) {
                $output_table = true;
                ee()->table->set_heading([
                    '<strong>' . lang($structure['title']) . '</strong>',
                ]);
            }
            // normally this just outputs a table with nothing in it other than headers. This kills that if it's empty
            if ($output_table) {
                ee()->table->set_template($tmpl);
                $content .= ee()->table->generate();
            }
            ee()->table->clear();

            if (is_array($structure['settings'])) {
                foreach ($structure['settings'] as $row_id => $setting) {
                    if ($setting['type'] == 'matrix') {
                        // retrieve the current set value of the field
                        $current_values = (isset($settings[$setting['short_name']])) ? $settings[$setting['short_name']] : false;

                        // set the value to the default value if there is no set value and the default value is defined
                        $current_values = ($current_values === false && isset($setting['default'])) ?
                            $setting['default'] : $current_values;

                        $content .= '<div class="matrix">';
                        $content .= '<table cellpadding="0" cellspacing="0" border="0" class="mainTable padTable">';

                        $header = [''];
                        foreach ($setting['settings'] as $count => $matrix_setting) {
                            $style = '';
                            $setting['settings'][$count]['style'] = $style;
                            $line = '<strong>' . lang($matrix_setting['name']) . '</strong>';

                            isset($matrix_setting['note']) ? $line .= '<br />' . lang($matrix_setting['note']) : '';
                            $header[] = $line;
                        }
                        $header[] = '';
                        $content .= '<thead>';
                        $content .= '<tr>';
                        foreach ($header as $th) {
                            $content .= '<th>';
                            $content .= $th;
                            $content .= '</th>';
                        }
                        $content .= '</tr>';
                        $content .= '</thead>';
                        $content .= '<tbody>';

                        if ($current_values === false || !count($current_values)) {
                            $current_values = [[]];
                            foreach ($setting['settings'] as $matrix_setting) {
                                $current_values[0][$matrix_setting['short_name']] = isset($matrix_setting['default']) ? $matrix_setting['default'] : '';
                            }
                        }

                        foreach ($current_values as $count => $current_value) {
                            $content .= '<tr class="' . $setting['short_name'] . '_setting"';
                            $content .= 'rel ="' . $setting['short_name'] . '"';
                            $content .= 'id	="' . $setting['short_name'] . '_setting_' . $count . '">';

                            $content .= '<td><img border="0" ';
                            $content .= 'src="' . $this->drag_handle . '" width="10" height="17" /></td>';
                            foreach ($setting['settings'] as $matrix_setting) {
                                $content .= '<td style="' . $matrix_setting['style'] . '" rel="' . $matrix_setting['short_name'] . '"';
                                $content .= 'class="' . $matrix_setting['short_name'] . '" >';
                                $content .= $this->plugin_setting($matrix_setting['type'],
                                    $setting['short_name'] . '[' . $count . '][' . $matrix_setting['short_name'] . ']',
                                    @$current_value[$matrix_setting['short_name']], @$matrix_setting['options'],
                                    @$matrix_setting['attributes']);
                                $content .= '</td>';
                            }
                            $content .= '<td>';
                            $content .= ' <a href="#" class="remove_matrix_row">
											<b class="fas fa-trash"></b>
										</a>';
                            $content .= '</td>';
                            $content .= '</tr>';
                        }

                        $content .= '	</tbody>
						</table>
					</div>';

                        $content .= '
						<fieldset class="plugin_add_new_setting" >
							<a href="#" class="ct_add_matrix_row btn action" id="add_new_' . $setting['short_name'] . '">
								' . lang('add_another_row') . '
							</a>
						</fieldset>';

                        $content .= '
						<table style="display: none;" class="' . $structure['class'] . '">
							<tr id="' . $setting['short_name'] . '_blank"  class="' . $setting['short_name'] . '">
								<td ><img border="0" src="' . $this->drag_handle . '" width="10" height="17" /></td>';

                        foreach ($setting['settings'] as $matrix_setting) {
                            $content .= '<td style="' . $matrix_setting['style'] . '"  rel="' . $matrix_setting['short_name'] . '"  class="' . $matrix_setting['short_name'] . '">' . $this->plugin_setting($matrix_setting['type'],
                                '', (isset($matrix_setting['default'])) ? $matrix_setting['default'] : '',
                                @$matrix_setting['options'], @$matrix_setting['attributes']) . '</td>';
                        }

                        $content .= '
								<td>
									<a href="#" class="remove_matrix_row"><b class="fas fa-trash"></b></a>
								</td>
							</tr>
						</table>
						';
                    } elseif ($setting['type'] == 'header') {
                        $content .= '<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
								<thead class="">
									<tr>
										<th colspan="2">
											<strong>' . lang($setting['name']) . '</strong><br />
										</th>
									</tr>
								</thead>
							</table>';
                    } elseif ($setting['type'] == 'html') {
                        $content .= '
							<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
							<tbody>
								<tr class="even">
									<td>
										' . $setting['html'] . '
									</td>
								</tr>
							</tbody>
							</table>';
                    } else {
                        // retrieve the current set value of the field
                        $current_value = (array_key_exists($setting['short_name'],
                            $settings) ? $settings[$setting['short_name']] : false);

                        // @NOTE. if one of the CT global config contains a value... it'll always fill the PREVIOUS current value. Make sure your setting name doesn't clash with a default config value, or it will always be used as a default instead of your local default
                        // set the value to the default value if there is no set value and the default value is defined
                        $current_value = (($current_value === false && array_key_exists('default',
                            $setting)) ? $setting['default'] : $current_value);

                        $current_value = array_key_exists('current', $setting) ? $setting['current'] : $current_value;

                        if ($setting['type'] == 'hidden') {
                            $content .= $this->plugin_setting($setting['type'], $setting['short_name'], $current_value,
                                @$setting['options'], @$setting['attributes']);
                        } else {
                            $content .= '
								<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
								<tbody>
									<tr class="even">
										<td>
											<label>' . lang($setting['name']) . '</label><br><span class="subtext">' . (isset($setting['note']) ? lang($setting['note']) : null) . '</span>
											</td>
										<td style="width:50%;">
											' . $this->plugin_setting($setting['type'], $setting['short_name'],
                                $current_value, @$setting['options'], @$setting['attributes']) . '
										</td>
									</tr>
								</tbody>
								</table>';
                        }
                    }
                }
            }
            $content .= '</div>';

            return $content;
        }

        /**
         * @param $structure
         * @param null $view_file_name
         * @return mixed|null
         */
        public function get_html($structure, $view_file_name = null)
        {
            $view_path = PATH_THIRD . $this->module_name . '/views/';
            $view_html = null;

            if (file_exists($view_path . $view_file_name)) {
                $view_html = ee()->load->view($view_file_name, $structure, true);
            } else {
                if (!is_array($structure)) {
                    $view_html = $structure;
                } elseif (!empty($structure['html'])) {
                    $view_html = $structure['html'];
                } else {
                    // this will probably throw an error,but that's probably what we want
                    $view_html = ee()->load->view($view_file_name, $structure, true);
                }
            }

            return $view_html;
        }

        /**
         * @param $data
         * @return string
         */
        public function view_settings_form($data)
        {
            $no_form = false;
            $form_open = null;
            $sections = [];
            extract($data, EXTR_OVERWRITE);

            $tab = $this->module_name . '_tab';
            $content = '<!-- begin right column -->';

            $content .= '
			<div class="ct_top_nav">
				<div class="ct_nav" >';
            foreach (array_keys($this->nav) as $method) {
                if (!in_array($method, $this->no_nav)) {
                    $content .= '<span class="button"><a class="nav_button';
                    if (!ee()->uri->segment(5) || ee()->uri->segment(5) == $method) {
                        $content .= ' current';
                    }
                    $content .= '"';

                    // if there's no lang itme for this, we'll just convert the method name.
                    $nav_lang = lang('nav_head_' . $method);
                    if ($nav_lang == 'nav_head_' . $method) {
                        $nav_lang = ucwords(str_replace('_', ' ', $method));
                    }
                    $content .= ' href="' . ee('CP/URL')->make('addons/settings/' . $this->module_name . '/' . $method) . '">' . $nav_lang . '</a></span>';
                }
            }
            $content .= '			
					<div class="clear_both"></div>	
				</div>	
			</div>';

            $content .= '
			<div class="clear_left shun"></div>';

            if (ee()->session->flashdata($this->module_name . '_system_error')) {
                $content .= '<div id="ct_system_error"><h4>';
                $content .= ee()->session->flashdata($this->module_name . '_system_error');
                $content .= '</h4></div>';
            }

            if (ee()->session->flashdata($this->module_name . '_system_message')) {
                $content .= '<div id="ct_system_message"><h4>';
                $content .= ee()->session->flashdata($this->module_name . '_system_message');
                $content .= '</h4></div>';
            }

            if ($this->extension_enabled === false) {
                $content .= '<div id="ct_system_error"><h4>';
                $content .= lang('extension_not_installed');
                $content .= '</h4>';
                $content .= lang('please') . ' <a href="' . ee('CP/URL')->make('addons') . '">' . lang('enable') . '</a> ' . lang('before_proceeding') . '</div>';
            }

            if ($this->module_enabled === false) {
                $content .= '<div id="ct_system_error"><h4>';
                $content .= lang('module_not_installed');
                $content .= '</h4>';
                $content .= lang('please') . ' <a href="' . ee('CP/URL')->make('addons') . '">' . lang('install') . '</a> ' . lang('before_proceeding') . '</div>';
            }

            if (!$no_form) {
                if ($form_open) {
                    $content .= $form_open;
                } else {
                    $content .= form_open(ee('CP/URL')->make('addons/settings/' . $this->module_name . '/quick_save',
                        ['return' => ee()->uri->segment(5)]));
                }
            }

            $content .= '<div id="' . $this->module_name . '_settings_content">
				<input type="hidden" name="' . $this->module_name . '_tab" value="' . $tab . '" id="' . $this->module_name . '_tab" />';

            foreach ($sections as $section) {
                $view_html = $this->get_html($data, $section);

                $section_lang = lang($section . '_header');
                if ($section_lang == $section . '_header') {
                    $section_lang = ucwords(str_replace('_', ' ', $section));
                }

                $content .= '<h3 class="accordion" data-hash="' . $section . '">' . $section_lang . '</h3>
				<div style="padding: 5px 1px;">
					' . $view_html . '
				</div>';
            }

            if (!$no_form) {
                $content .= '<p><input type="submit" name="submit" value="' . lang('submit') . '" class="btn submit" /></p>
				</form>';
            }

            $content .= '</div>';

            return $content;
        }

        /**
         * @param $data
         * @return string
         */
        public function view_settings_form_head($data)
        {
            extract($data);

            // when an href in the plugin_add_new_setting fieldset is clicked....
            $add_new_setting_js = '$("fieldset.plugin_add_new_setting a").bind("click", function(){
            // get the name of the thing to add. The ID of the HREF is used as the name
                var name = $(this).attr("id").replace("add_new_", "");
                
            // if there is an existing TR with the classname NAME_setting, look for the ID and remove NAME_setting to get the current count
                var count = ($("tr."+name+"_setting:last").length > 0) ? Number($("tr."+name+"_setting:last").attr("id").replace(name+"_setting_","")) + 1 : 0;

            // get the plugin class name from the div that surrounds the table containing the settings 
                var plugin_classname = $("#"+name+"_blank").parent().parent().attr("class");

            // there is probably not multiple classes applied, but if there are, split them at the space
                var element = $("#"+name+"_blank").attr("class").split(" ");
            // get the short_name from the split class. 
                var setting_short_name = element[0];

            // clone the blank
                var clone = $("#"+name+"_blank").clone();
            
            // clone the ID NAME_setting_1
                clone.attr({"id":name+"_setting_"+count});
            // clone the class, NAME_setting
                clone.attr({"class":name+"_setting"});
                
            // clone the rel STRUCTURE_CLASSNAME_settings[SHORT_NAME]
                clone.attr({"rel": plugin_classname+"_settings["+setting_short_name+"]"});
            // finde each INPUT
                clone.find(":input").each(function(){
                    
                    // get the SETTING_SHORT_NAME
                    var matrix_setting_short_name = $(this).parent().attr("class");
                    if ( ! $(this).parent().attr("rel"))
                    {
                        // change the name attribute to STRUCTURE_CLASSNAME_settings[setting_short_name][count][matrix setting short name]
                        $(this).attr("name", plugin_classname+"_settings["+setting_short_name+"]["+count+"]["+matrix_setting_short_name+"]");
                        $(this).attr("rel", plugin_classname); 	
                    }
                    else
                    {
                        $(this).attr("name", name+"["+count+"]["+matrix_setting_short_name+"]");	
                    }
                    // add taht short name to the parent rel
                    $(this).parent().attr("rel", matrix_setting_short_name);

                });
                // in the clone, remove the content from the TD classes
                clone.children("td").attr("class","");
                // add to the row above. 
                $(this).parent().prev().find("tbody").append(clone);
                return false;
            });';

            $content = '';
            $content .= '

            <script type="text/javascript">

                jQuery.' . $this->module_name . 'CP = {
                    currentSection: function() {
                        if (window.location.hash && window.location.hash != "#") {
                            return window.location.hash.substring(1);
                        } else {
                            return $("#' . $this->module_name . '_settings_content h3:first").attr("data-hash");
                        }
                    },
                    ' . (isset($channel_titles) ? 'channels: ' . json_encode($channel_titles) . ',' : null) . '
                    ' . (isset($product_channel_titles) ? 'product_channels: ' . json_encode($product_channel_titles) . ',' : null) . '
                    ' . (isset($fields) ? 'fields: ' . json_encode($fields) . ',' : null) . '
                    ' . (isset($member_fields) ? 'member_fields: ' . json_encode($member_fields) . ',' : null) . '
                    ' . (isset($product_channel_fields) ? 'product_channel_fields: ' . json_encode($product_channel_fields) . ',' : null) . '
                    ' . (isset($order_channel_fields) ? 'order_channel_fields: ' . json_encode($order_channel_fields) . ',' : null) . '
                    ' . (isset($status_titles) ? 'statuses: ' . json_encode($status_titles) . ',' : null) . '
                    ' . (isset($templates) ? 'templates: ' . json_encode($templates) . ',' : null) . '
                    ' . (isset($states) ? 'states: ' . json_encode($states) . ',' : null) . '
                    ' . (isset($countries) ? 'countries: ' . json_encode($countries) . ',' : null) . '
                    ' . (isset($states_and_countries) ? 'statesAndCountries: ' . json_encode($states_and_countries) . ',' : null) . '
                    checkSelectedChannel: function (selector, section) {
                        if ($(selector).val() !="") {
                            $(section).css("display","inline");
                        } else {
                            $(section).css("display","none");
                        }
                    },
                    updateSelect: function(select, options) {
                        var val = $(select).val();
                        var attrs = {};
                        for (i=0;i<select.attributes.length;i++) {
                            if (select.attributes[i].name == "value") {
                                val = select.attributes[i].value;
                            } else {
                                attrs[select.attributes[i].name] = select.attributes[i].value;
                            }
                        }
					    $(select).replaceWith($.' . $this->module_name . 'CP.createSelect(attrs, options, val));					    
                    },
                    createSelect: function(attributes, options, selected) {
                        var select = "<select ";
                        for (i in attributes) {
                            select += i+ "=\""+attributes[i]+ "\"";
                        }
                        select += ">";
                        for (i in options) {
                            select += "<option value=\""+i+"\" ";
                            if (selected != undefined && selected == i) {
                                select += " selected=\"selected\"";
                            }
                            select += ">"+options[i]+"</option>";
                        }
                        select += "</select>";
                        return select;
                    }
                };

                jQuery(document).ready(function($){

                    $("select.states").each(function(){
                       $.' . $this->module_name . 'CP.updateSelect(this, ' . $this->module_name . 'CP.states)
                    });
                    $("select.states_blank").each(function(){
                        var states = {"" : "---"};
                        $.extend(states, $.' . $this->module_name . 'CP.states);
                        $.' . $this->module_name . 'CP.updateSelect(this, states);
                    });
                    $("select.templates").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.templates);
                    });
                    $("select.templates_blank").each(function(){
                        var templates = {"" : "---"};
                        $.extend(templates, $.' . $this->module_name . 'CP.templates);
                        $.' . $this->module_name . 'CP.updateSelect(this, templates);
                    });
                    $("select.statuses").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.statuses);
                    });
                    $("select.statuses_blank").each(function(){
                        var statuses = {"" : "---", "ANY" : "ANY"};
                        $.extend(statuses, $.' . $this->module_name . 'CP.statuses);
                        $.' . $this->module_name . 'CP.updateSelect(this, statuses);
                    });

                    $("select.countries").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.countries);
                    });
                    $("select.countries_blank").each(function(){
                        var countries = {"" : "---"};
                        $.extend(countries, $.' . $this->module_name . 'CP.countries);
                        $.' . $this->module_name . 'CP.updateSelect(this, countries);
                    });
                    $("select.states_and_countries").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.statesAndCountries);
                    });
                    $("select.all_fields").each(function(){
                        var fields = {"":"---"};
                        for (i in $.' . $this->module_name . 'CP.fields) {
                            for (j in $.' . $this->module_name . 'CP.fields[i]) {
                                fields["field_id_"+$.' . $this->module_name . 'CP.fields[i][j].field_id] = $.' . $this->module_name . 'CP.fields[i][j].field_label;
                            }
                        }
                        $.' . $this->module_name . 'CP.updateSelect(this, fields);
                    });
                    $("select.product_channel_fields").each(function(){
                        var product_channel_fields = {"":"---"};
                        for (i in $.' . $this->module_name . 'CP.product_channel_fields) {
                            for (j in $.' . $this->module_name . 'CP.product_channel_fields[i]) {
                                product_channel_fields["field_id_"+$.' . $this->module_name . 'CP.product_channel_fields[i][j].field_id] = $.' . $this->module_name . 'CP.product_channel_fields[i][j].field_label;
                            }
                        }
                        $.' . $this->module_name . 'CP.updateSelect(this, product_channel_fields);
                    });
                    $("select.order_channel_fields").each(function(){
                        var order_channel_fields = {"":"---"};
                        for (i in $.' . $this->module_name . 'CP.order_channel_fields) {
                            for (j in $.' . $this->module_name . 'CP.order_channel_fields[i]) {
                                order_channel_fields["field_id_"+$.' . $this->module_name . 'CP.order_channel_fields[i][j].field_id] = $.' . $this->module_name . 'CP.order_channel_fields[i][j].field_label;
                            }
                        }
                        $.' . $this->module_name . 'CP.updateSelect(this, order_channel_fields);
                    });
                    $("select.channels").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.channels);
                    });
                    $("select.product_channels").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.product_channels);
                    });
                    $("select.member_fields").each(function(){
                        $.' . $this->module_name . 'CP.updateSelect(this, $.' . $this->module_name . 'CP.member_fields);
                    });
                    
                    $.' . $this->module_name . 'CP.checkSelectedChannel("#select_orders", ".requires_orders_channel");
                 
                    $("#select_orders").bind("change", function(){
                        $.' . $this->module_name . 'CP.checkSelectedChannel("#select_orders", ".requires_orders_channel");
                    });
                    
                    
                    $("select.product_channels").bind("change", function(){
                        var channel_id = Number($(this).val());
                        var section = $(this).attr("id").replace("select_", "");
                        $("select.field_"+section).children().not(".blank").remove();
                        if ($(this).val() != "")
                        {
                            for (i in $.' . $this->module_name . 'CP.product_channel_fields[channel_id]);
                            {
                                $("select.field_"+section).append("<option value=\";field_id_;"+$.' . $this->module_name . 'CP.product_channel_fields[channel_id][i].field_id+";\">"+$.' . $this->module_name . 'CP.product_channel_fields[channel_id][i].field_label+"</option>");
                            }

                        }
                    });

                    $("#' . $this->module_name . '_tab").val($.' . $this->module_name . 'CP.currentSection());

                    var count = 0; 
                    ' . $add_new_setting_js . '

                    $(document).on("click", "a.remove_matrix_row", function(){
                        if (confirm("Are you sure you want to delete this row?"))
                        {
                            if ($(this).parent().get(0).tagName.toLowerCase() == "td")
                            {
                                $(this).parent().parent().remove();
                            }
                            else
                            {
                                $(this).parent().remove();
                            }
                        }
                        return false;
                    });
                    $(document).on("mouseover", "a.remove_matrix_row", function(){
                        $(this).find("img").animate({opacity:1});
                        console.log("in");
                    });
                    $(document).on("mouseout", "a.remove_matrix_row", function(){
                        console.log("out");
                        $(this).find("img").animate({opacity:.2});
                    });
                    $("a.remove_matrix_row").find("img").css({opacity:.2});


                    $(".add_matrix_row").bind("click", function(){
                        var name = $(this).attr("id").replace("_button", "");
                        var index = ($("."+name+"_row:last").length > 0) ? Number($("."+name+"_row:last").attr("id").replace(name+"_row_","")) + 1 : 0;
                        var clone = $("#"+name+"_row_blank").clone(); 
                        clone.attr("id", name+"_row_"+index).addClass(name+"_row").show();
                        clone.find(":input").bind("each", function(){
                            $(this).attr("name", $(this).attr("data-hash").replace("INDEX", index));
                        });
                        $(this).parent().before(clone);
                        return false;
                    });

                    // Return a helper with preserved width of cells
                    var fixHelper = function(e, ui) {
                        ui.children().each(function() {
                            $(this).width($(this).width());
                        });
                        return ui;
                    };

                    $("div.matrix table tbody").sortable({
                        helper: fixHelper,
                        stop: function(event, ui) { 
                            var count=0; 
                            $("div.matrix table tbody tr").each(function(){
                                $(this).find(":input").each(function(){
                                    $(this).attr("name", $(this).parents("tr").attr("rel")+"["+count+"]["+$(this).parent().attr("rel")+"]");	
                                }); 
                                count +=1; 
                            });
                        }
                    });
                });
            ';

            $content .= '</script>';

            return $content;
        }

        /**
         * @param $data
         * @return string
         */
        public function view_plugin_settings($data)
        {
            $plugins = [];
            $plugin_type = null;
            extract($data, EXTR_OVERWRITE);
            $content = '';
            foreach ($plugins as $plugin) {
                $content .= '<div class="' . $plugin_type . '_settings" id="' . $plugin['classname'] . '">

					<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
						<thead class="">
							<tr>
								<th colspan="2">
									<strong>' . lang($plugin['title']) . ' ' . lang('settings') . '</strong><br />
								</th>
							</tr>
						</thead>
						<tbody>';

                if (!empty($plugin['note'])) {
                    $content .= '
								<tr class="' . alternator('odd', 'even') . '">
									<td colspan="2">
										<div class="subtext note">' . lang('gateway_settings_note_title') . '</div>
										' . lang($plugin['note']) . '
									</td>
								</tr>';
                }

                if (!empty($plugin['overview'])) {
                    $content .= '
								<tr class="' . alternator('odd', 'even') . '">
									<td colspan="2">
				 						<div class="ct_overview">
											' . lang($plugin['overview']) . '
										</div>
									</td>
								</tr>
								';
                }

                $content .= '	
						</tbody>
					</table>';

                if (is_array($plugin['settings'])) {
                    foreach ($plugin['settings'] as $setting) {
                        if ($setting['type'] == 'matrix') {
                            // retrieve the current set value of the field
                            $current_values = (isset($settings[$plugin['classname'] . '_settings'][$setting['short_name']])) ?
                                $settings[$plugin['classname'] . '_settings'][$setting['short_name']] : false;

                            // set the value to the default value if there is no set value and the default value is defined
                            $current_values = ($current_values === false && isset($setting['default'])) ?
                                $setting['default'] : $current_values;

                            $content .= '
								<div class="matrix">
									<table cellpadding="0" cellspacing="0" border="0" class="mainTable padTable">
										<thead>
										    <tr>
												<th></th>';

                            foreach ($setting['settings'] as $count => $matrix_setting) {
                                $style = '';
                                $setting['settings'][$count]['style'] = $style;

                                $content .= '
				 									<th>
														<strong>' . lang($matrix_setting['name']) . '</strong>' . (isset($matrix_setting['note']) ? '<br />' . lang($matrix_setting['note']) : '') . '
													</th>';
                            }

                            $content .= '<th style="width:20px;"></th>
										    </tr>
										</thead>
										<tbody>';

                            if ($current_values === false || !count($current_values)) {
                                $current_values = [[]];
                                foreach ($setting['settings'] as $matrix_setting) {
                                    $current_values[0][$matrix_setting['short_name']] = isset($matrix_setting['default']) ? $matrix_setting['default'] : '';
                                }
                            }
                            foreach ($current_values as $count => $current_value) {
                                $content .= '
										<tr class="' . $plugin['classname'] . '_' . $setting['short_name'] . '_setting" 
											rel = "' . $plugin['classname'] . '_settings[' . $setting['short_name'] . ']' . '" 		
											id="' . $plugin['classname'] . '_' . $setting['short_name'] . '_setting_' . $count . '">
											<td><img border="0" src="' . $this->drag_handle . '" width="10" height="17" /></td>';
                                foreach ($setting['settings'] as $matrix_setting) {
                                    $content .= '<td  style="' . $matrix_setting['style'] . '" rel="' . $matrix_setting['short_name'] . '">' . $this->plugin_setting($matrix_setting['type'],
                                        $plugin['classname'] . '_settings[' . $setting['short_name'] . '][' . $count . '][' . $matrix_setting['short_name'] . ']',
                                        @$current_value[$matrix_setting['short_name']], @$matrix_setting['options'],
                                        @$matrix_setting['attributes']) . '</td>';
                                }

                                $content .= '

											<td>
												<a href="#" class="remove_matrix_row">
													<b class="fas fa-trash"></b>
												</a>
											</td>
										</tr>	';
                            }

                            $content .= '</tbody>
									</table>
								</div>

								<fieldset class="plugin_add_new_setting">
									<a href="#" class="ct_add_matrix_row btn action" id="add_new_' . $plugin['classname'] . '_' . $setting['short_name'] . '">
										' . lang('add_another_row') . '
									</a>
								</fieldset>

								<table style="display: none;" class="' . $plugin['classname'] . '">
									<tr id="' . $plugin['classname'] . '_' . $setting['short_name'] . '_blank" class="' . $setting['short_name'] . '">
										<td  ><img border="0" src="' . $this->drag_handle . '" width="10" height="17" /></td>';

                            foreach ($setting['settings'] as $matrix_setting) {
                                $content .= '<td  class="' . $matrix_setting['short_name'] . '" style="' . $matrix_setting['style'] . '">' . $this->plugin_setting($matrix_setting['type'],
                                    '', (isset($matrix_setting['default'])) ? $matrix_setting['default'] : '',
                                    @$matrix_setting['options'], @$matrix_setting['attributes']) . '</td>';
                            }

                            $content .= '
										<td>
											<a href="#" class="remove_matrix_row"><b class="fas fa-trash"></b></a>
										</td>
									</tr>
								</table>';
                        } elseif ($setting['type'] == 'header') {
                            $content .= '
									<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
										<thead class="">
											<tr>
												<th colspan="2">
													<strong>' . lang($setting['name']) . '</strong><br />
												</th>
											</tr>
										</thead>
									</table>';
                        } else {
                            // retrieve the current set value of the field
                            $current_value = (isset($settings[$plugin['classname'] . '_settings'][$setting['short_name']])) ? $settings[$plugin['classname'] . '_settings'][$setting['short_name']] : false;
                            // set the value to the default value if there is no set value and the default value is defined
                            $current_value = ($current_value === false && isset($setting['default'])) ? $setting['default'] : $current_value;

                            $content .= '
									<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
									<tbody>
										<tr class="even">
											<td>
												<label>' . lang($setting['name']) . '</label><br><span class="subtext">' . (isset($setting['note']) ? lang($setting['note']) : '') . '</span>
			 								</td>
											<td style="width:50%;">
												' . $this->plugin_setting($setting['type'],
                                $plugin['classname'] . '_settings[' . $setting['short_name'] . ']', $current_value,
                                @$setting['options'], @$setting['attributes']) . '
											</td>
										</tr>
									</tbody>
									</table>';
                        }
                    }
                }

                $content .= '</div>';
            }

            return $content;
        }

        /**
         * get_package
         *
         * Returns an array of variable data used for printing out package installer templates.
         *
         * @note requires package installer and packages entries model to use.
         *
         * @param string $xml_location path to location of installer file
         * @return array
         */
        public function get_package($xml_location = null)
        {
            $vars = [
                'module_name' => $this->module_name,
                'install_channels' => [],
                'install_template_groups' => [],
                'install_member_groups' => [],
                'install_channel_data' => [],
                'template_errors' => (ee()->session->flashdata('template_errors')) ? ee()->session->flashdata('template_errors') : [],
                'templates_installed' => (ee()->session->flashdata('templates_installed')) ? ee()->session->flashdata('templates_installed') : [],
            ];

            ee()->load->library('package_installer', ['xml' => PATH_THIRD . $this->module_name . '/installer/installer.xml']);

            foreach (ee()->package_installer->packages() as $index => $package) {
                switch ($package->getName()) {
                    case 'channel':
                        $vars['install_channels'][$index] = (string)$package->attributes()->channel_title;
                        if (isset($package->field_group) && isset($package->field_group->field)) {
                            foreach ($package->field_group->field as $field) {
                                $vars['fields'][$index][] = (string)$field->attributes()->field_label;
                            }
                        }

                        if (isset($package->channel_data)) {
                            $vars['install_channel_data'][$index] = (string)$package->attributes()->channel_title;
                        }

                        break;
                    case 'template_group':
                        $vars['install_template_groups'][$index] = (string)$package->attributes()->group_name;
                        if (isset($package->template)) {
                            foreach ($package->template as $template) {
                                $vars['templates'][$index][] = (string)$template->attributes()->template_name;
                            }
                        }
                        break;
                    case 'member_group':
                        $vars['install_member_groups'][$index] = (string)$package->attributes()->group_name;
                        break;
                }
            }

            return $vars;
        }
    }
}
