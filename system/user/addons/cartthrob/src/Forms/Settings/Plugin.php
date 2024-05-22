<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Plugin extends AbstractForm
{
    /**
     * @var array
     */
    protected array $plugin_date = [];

    /**
     * @return array
     */
    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.enabled',
                'desc' => 'plugin_enabled_description',
                'fields' => [
                    'enabled' => [
                        'name' => 'enabled',
                        'type' => 'select',
                        'value' => $this->get('enabled'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        $plugin_data = $this->getPluginData();
        $settings = element('settings', $plugin_data);
        $note = element('note', $plugin_data, 'Details');
        $overview = element('overview', $plugin_data);
        $title = element('title', $plugin_data);

        $fields = [];
        $field = [
            'title' => $note,
        ];

        if ($overview) {
            $field['fields'] = [
                $note => [
                    'type' => 'html',
                    'content' => lang($overview),
                ],
            ];
        }

        $fields[] = $field;

        foreach ($settings as $setting) {
            $type = element('type', $setting);
            $method = $type . 'Field';
            if (method_exists($this, $method)) {
                $fields[] = $this->$method($setting);
            } elseif ($type == 'add_to_head') {
                if (strpos($setting['default'], '<script') !== false) {
                    ee()->cp->add_to_foot($setting['default']);
                } else {
                    ee()->cp->add_to_head($setting['default']);
                }
            } else {
                echo $type;
                echo '<br />';
            }
        }

        $form = ['activate_plugin' => $form];
        $form[$title] = $fields;

        return $form;
    }

    /**
     * @param array $plugin
     * @return $this
     */
    public function setPluginData(array $plugin): Plugin
    {
        $this->plugin_date = $plugin;

        return $this;
    }

    /**
     * @return array
     */
    protected function getPluginData(): array
    {
        return $this->plugin_date;
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function headerField(array $setting): array
    {
        $name ??= $setting['name'];

        return [
            'fields' => [
                $name => [
                    'type' => 'html',
                    'content' => '<h2>' . lang($name) . '</h2>',
                ],
            ],
        ];
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function textField(array $setting): array
    {
        $name = element('short_name', $setting);
        if (!$name) {
            $name = element('name', $setting);
        }

        $default = element('default', $setting);
        $note = element('note', $setting);
        $group = element('group', $setting);
        $field = [
            'title' => element('name', $setting),
            'desc' => $note,
            'group' => $group,
            'fields' => [
                $name => [
                    'name' => $name,
                    'type' => 'text',
                    'value' => $this->get($name, $default),
                ],
            ],
        ];

        return $field;
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function textareaField(array $setting): array
    {
        $name = element('short_name', $setting);
        if (!$name) {
            $name = element('name', $setting);
        }

        $default = element('default', $setting);
        $note = element('note', $setting);
        $group = element('group', $setting);
        $field = [
            'title' => element('name', $setting),
            'desc' => $note,
            'group' => $group,
            'fields' => [
                $name => [
                    'name' => $name,
                    'type' => 'textarea',
                    'value' => $this->get($name, $default),
                ],
            ],
        ];

        return $field;
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function selectField(array $setting): array
    {
        $name = element('short_name', $setting);
        if (!$name) {
            $name = element('name', $setting);
        }

        $default = element('default', $setting);
        $choices = element('options', $setting);
        $group = element('group', $setting);
        foreach ($choices as $key => $choice) {
            $choices[$key] = lang($choice);
        }
        $note = element('note', $setting);
        $field = [
            'title' => element('name', $setting),
            'desc' => $note,
            'group' => $group,
            'fields' => [
                $name => [
                    'name' => $name,
                    'type' => 'select',
                    'value' => $this->get($name, $default),
                    'choices' => $choices,
                ],
            ],
        ];

        return $field;
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function radioField(array $setting): array
    {
        return $this->selectField($setting);
    }

    /**
     * @param array $setting
     * @return array
     */
    protected function matrixField(array $setting): array
    {
        $name = element('short_name', $setting);
        if (!$name) {
            $name = element('name', $setting);
        }

        $default = element('default', $setting);
        $grid_cols = element('settings', $setting);

        $grid = ee('CP/GridInput', [
            'field_name' => $name,
            'reorder' => true,
        ]);

        $cols = [];
        $blank_fields = [];
        foreach ($grid_cols as $column) {
            $note = element('note', $column);
            $cols[$column['name']] = [
                'label' => $column['name'],
                'desc' => $note,
            ];

            $matrix_name = $column['name'];
            if (isset($column['short_name'])) {
                $matrix_name = $column['short_name'];
            }
            switch ($column['type']) {
                case 'select':
                    if ($matrix_name == 'state' && !$column['options']) {
                        if (!empty($column['attributes']['class']) && $column['attributes']['class'] == 'states_and_countries') {
                            $column['options'] = $this->getCountryStateOptions();
                        } elseif ($matrix_name == 'state' && !$column['options']) {
                            $column['options'] = $this->getStateOptions();
                        }
                    }

                    if ($matrix_name == 'country' && !$column['options']) {
                        $column['options'] = $this->getCountryOptions();
                    }
                    $blank_fields[] = form_dropdown($matrix_name, $column['options']);
                    break;

                case 'checkbox':
                    $extra = (!empty($column['options']['extra']) ? $column['options']['extra'] : '');
                    $blank_fields[] = form_checkbox($matrix_name, '1', false, $extra) . ' Yes';
                    break;

                case 'textarea':
                    $_value = (isset($value[$column['short_name']]) ? $value[$column['short_name']] : '');
                    $data = [
                        'name' => $name,
                        'value' => $_value,
                    ];

                    if (!empty($column['attributes']) && is_array($column['attributes'])) {
                        $data += $column['attributes'];
                    }

                    $blank_fields[] = form_textarea($data);
                    break;

                case 'text':
                default:
                    $extra = (!empty($column['attributes']['class']) ? 'class="' . $column['attributes']['class'] . '"' : '');
                    $blank_fields[] = form_input($matrix_name, '', $extra);
                    break;
            }
        }

        $grid->setColumns($cols);

        $grid->setNoResultsText(lang('no_' . $name), lang('add_' . $name));
        $grid->setBlankRow($blank_fields);

        // now set existing/default data
        $data = $this->prepareGridData($name, $grid_cols);
        $grid->setData($data);

        $grid->loadAssets();

        $field = [
            'title' => element('name', $setting),
            'desc' => '',
            'grid' => true,
            'wide' => true,
            'fields' => [
                $name => [
                    'name' => $name,
                    'type' => 'html',
                    'content' => ee()->load->view('_shared/table', $grid->viewData(), true),
                ],
            ],
        ];

        return $field;
    }

    /**
     * @param string $name
     * @param array $grid_cols
     * @return array
     */
    protected function prepareGridData(string $name, array $grid_cols): array
    {
        $data = $this->get($name);
        $return = [];
        if (is_array($data)) {
            $count = 0;
            foreach ($data as $key => $value) {
                $existing_fields = [];
                foreach ($grid_cols as $column) {
                    $name = $column['name'];
                    if (isset($column['short_name'])) {
                        $name = $column['short_name'];
                    }
                    switch ($column['type']) {
                        case 'select':
                            if (!empty($column['attributes']['class']) && $column['attributes']['class'] == 'states_and_countries') {
                                $column['options'] = $this->getCountryStateOptions();
                            } elseif ($name == 'state' && !$column['options']) {
                                $column['options'] = $this->getStateOptions();
                            }

                            if ($name == 'country' && !$column['options']) {
                                $column['options'] = $this->getCountryOptions();
                            }

                            $_value = (isset($value[$column['short_name']]) ? $value[$column['short_name']] : '');
                            $existing_fields[] = form_dropdown($name, $column['options'], $_value);
                            break;

                        case 'checkbox':
                            $extra = (!empty($column['options']['extra']) ? $column['options']['extra'] : '');
                            $checked = isset($value[$column['short_name']]);
                            $existing_fields[] = form_checkbox($name, '1', $checked, $extra) . ' Yes';
                            break;

                        case 'textarea':
                            $_value = (isset($value[$column['short_name']]) ? $value[$column['short_name']] : '');
                            $data = [
                                'name' => $name,
                                'value' => $_value,
                            ];

                            if (!empty($column['attributes']) && is_array($column['attributes'])) {
                                $data += $column['attributes'];
                            }

                            $existing_fields[] = form_textarea($data);
                            break;

                        case 'text':
                        default:
                            $_value = (isset($value[$column['short_name']]) ? $value[$column['short_name']] : '');
                            $extra = (!empty($column['attributes']['class']) ? 'class="' . $column['attributes']['class'] . '"' : '');
                            $existing_fields[] = form_input($name, $_value, $extra);
                            break;
                    }
                }

                $return[] = [
                    'attrs' => ['row_id' => $count],
                    'columns' => $existing_fields,
                ];
                $count++;
            }
        }

        return $return;
    }

    /**
     * @return bool
     */
    public function hasMatrix(): bool
    {
        $plugin_data = $this->getPluginData();
        $settings = element('settings', $plugin_data);
        foreach ($settings as $setting) {
            $type = element('type', $setting);
            if ($type == 'matrix') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array $plugin
     * @param array $data
     * @return array
     */
    public function preparePluginData(array $plugin, array $data): array
    {
        $settings = [];
        foreach ($plugin['settings'] as $key => $setting) {
            if (isset($data[$setting['short_name']])) {
                if ($setting['type'] != 'matrix') {
                    $settings[$setting['short_name']] = $data[$setting['short_name']];
                } else {
                    $settings[$setting['short_name']] = [];
                    if (isset($data[$setting['short_name']]['rows']) && is_array($data[$setting['short_name']]['rows'])) {
                        $rows = [];
                        foreach ($data[$setting['short_name']]['rows'] as $row) {
                            $rows[] = $row;
                        }

                        $settings[$setting['short_name']] = $rows;
                    }
                }
            }
        }

        return $settings;
    }
}
