<?php

namespace CartThrob\Forms\Settings\Payments;

use CartThrob\Forms\Settings\Plugin as PluginForm;

class Plugin extends PluginForm
{
    /**
     * @var array
     */
    protected array $enabled_plugins = [];

    /**
     * @return array
     */
    public function generate(): array
    {
        $plugin_data = $this->getPluginData();
        $this->data['enabled'] = 0;
        if (isset($this->enabled_plugins[$plugin_data['classname']])) {
            $this->data['enabled'] = 1;
        }

        $form = parent::generate();

        $key = lang($plugin_data['title']) . ' ' . lang('settings');
        $table = $this->buildInputTable($plugin_data);

        $gateway = '';
        if (str_starts_with($plugin_data['classname'], 'Cartthrob_')) {
            $gateway = substr($plugin_data['classname'], 10);
        }
        ee()->load->library('paths');
        $query = ['gateway' => $gateway];
        $extload_url = ee()->paths->build_action_url('Cartthrob', 'extload_action', $query);
        $form[$key] = [
            [
                'title' => 'ct.route.extload_action_url',
                'desc' => 'ct.route.form.extload_action_url.note',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<a href="' . $extload_url . '">' . $extload_url . '</a>',
                    ],
                ],
            ],
            [
                'title' => 'ct.route.override_values',
                'desc' => 'ct.route.override_values.desc',
                'fields' => [
                    'details' => [
                        'type' => 'html',
                        'content' => ee('View')->make('ee:_shared/table')->render($table),
                    ],
                ],
            ],
            [
                'title' => 'gateways_sample_html',
                'wide' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<textarea rows="50" style="font-size:10px;" readonly>' . htmlentities($plugin_data['html']) . '</textarea>',
                    ],
                ],
            ],
        ];

        return $form;
    }

    /**
     * @param array $plugin_data
     * @return mixed
     */
    protected function buildInputTable(array $plugin_data)
    {
        $encoded = ee('Encrypt')->encode(\Cartthrob_core::get_class($plugin_data['classname']));
        $table = ee('CP/Table', [
            'lang_cols' => true,
            'class' => 'product_channels',
        ]);

        $table->setColumns([
            'ct.route.details' => ['sort' => false],
            'ct.route.value' => ['sort' => false],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('product_channels')));

        $data = [];
        $data[] = [
            lang('plugin_select'),
            \Cartthrob_core::get_class($plugin_data['classname']),
        ];

        $data[] = [
            lang('gateways_form_input'),
            $encoded,
        ];

        $data[] = [
            lang('gateways_form_input_urlencoded'),
            urlencode($encoded),
        ];

        $table->setData($data);

        $base_url = ee('CP/URL')->make($this->base_url);

        return $table->viewData($base_url);
    }

    /**
     * @param array $gateways
     * @return $this
     */
    public function setEnabledGateways(array $gateways): Plugin
    {
        $this->enabled_plugins = $gateways;

        return $this;
    }
}
