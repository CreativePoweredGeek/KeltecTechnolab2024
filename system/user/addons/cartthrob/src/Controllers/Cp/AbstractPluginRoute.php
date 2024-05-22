<?php

namespace CartThrob\Controllers\Cp;

use CartThrob\Dependency\Illuminate\Support\Collection;

abstract class AbstractPluginRoute extends AbstractRoute
{
    /**
     * @return array
     */
    protected function getShippingPlugins(): array
    {
        return (new Collection($this->plugins('shipping')))
            ->map(function ($plugin) {
                $plugin['fulltext_title'] = lang($plugin['title']);

                return $plugin;
            })
            ->sortBy('fulltext_title')
            ->toArray();
    }

    /**
     * @param $plugin
     */
    protected function getShippingPlugin($plugin)
    {
        $plugins = $this->getShippingPlugins();
        foreach ($plugins as $_plugin) {
            if ($plugin == element('classname', $_plugin)) {
                return $_plugin;
            }
        }
    }

    /**
     * Loads plugins
     *
     * @param $type
     * @return array $plugins
     */
    protected function plugins($type)
    {
        $plugins = $this->loadPlugins($type);

        foreach (ee('cartthrob:PluginService')->{'get' . ucfirst($type)}() as $plugin) {
            $className = get_class($plugin);
            $data = get_class_vars($className);
            $data['classname'] = $className;

            $plugins->push($data);
        }

        return $plugins->toArray();
    }

    /**
     * @return array
     */
    protected function getTaxPlugins(): array
    {
        return (new Collection($this->plugins('tax')))
            ->map(function ($plugin) {
                $plugin['fulltext_title'] = lang($plugin['title']);

                return $plugin;
            })
            ->sortBy('fulltext_title')
            ->toArray();
    }

    /**
     * @param $plugin
     */
    protected function getTaxPlugin($plugin)
    {
        $plugins = $this->getTaxPlugins();
        foreach ($plugins as $_plugin) {
            if ($plugin == element('classname', $_plugin)) {
                return $_plugin;
            }
        }
    }

    /**
     * @param $type
     * @return Collection
     */
    protected function loadPlugins($type): Collection
    {
        ee()->load->helper(['file', 'data_formatting']);

        $plugins = new Collection();
        $paths[] = CARTTHROB_PATH . 'plugins/' . $type . '/';

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (get_filenames($path, true) as $file) {
                if (!str_starts_with(basename($file, '.php'), 'Cartthrob_')) {
                    continue;
                }

                $className = basename($file, '.php');

                $this->loadPluginLang($className, $path);

                $pluginInfo = get_class_vars($className);
                $pluginInfo['classname'] = $className;
                $pluginInfo['settings'] = $this->loadPluginSettings($className, $pluginInfo['settings']);

                $plugins->push($pluginInfo);
            }
        }

        return $plugins;
    }

    /**
     * @param string $className
     * @param $path
     */
    protected function loadPluginLang(string $className, $path): void
    {
        $language = set(ee()->session->userdata('language'), ee()->input->cookie('language'), ee()->config->item('deft_lang'), 'english');
        $formattedClassName = strtolower($className);

        if (file_exists(PATH_THIRD . 'cartthrob/language/' . $language . '/' . $formattedClassName . '_lang.php')) {
            ee()->lang->loadfile($formattedClassName, $package = 'cartthrob', $show_errors = false);
        } elseif (file_exists($path . '../language/' . $language . '/' . $formattedClassName . '_lang.php')) {
            ee()->lang->load(
                $formattedClassName,
                $language,
                $return = false,
                $add_suffix = true,
                $alt_path = $path . '../',
                $show_errors = false
            );
        } elseif (file_exists($path . 'language/' . $language . '/' . $formattedClassName . '_lang.php')) {
            ee()->lang->load(
                $formattedClassName,
                $language,
                $return = false,
                $add_suffix = true,
                $alt_path = $path,
                $show_errors = false
            );
        }
    }

    /**
     * @param string $className
     * @param array $pluginSettings
     * @return array
     */
    protected function loadPluginSettings(string $className, array $pluginSettings): array
    {
        $sysSettings = $this->settings->settings('cartthrob');

        foreach ($pluginSettings as $key => $setting) {
            // retrieve the current set value of the field
            $current_value = $sysSettings[$className . '_settings'][$setting['short_name']] ?? false;
            // set the value to the default value if there is no set value and the default value is defined
            $current_value = ($current_value === false && isset($setting['default'])) ? $setting['default'] : $current_value;

            if ($setting['type'] == 'matrix') {
                if (!is_array($current_value) || !count($current_value)) {
                    $current_values = [[]];

                    foreach ($setting['settings'] as $matrixSetting) {
                        $current_values[0][$matrixSetting['short_name']] = $matrixSetting['default'] ?? '';
                    }
                } else {
                    $current_values = $current_value;
                }
            } else {
                $current_values = $current_value;
            }

            $pluginSettings[$key]['default'] = $current_values;
        }

        return $pluginSettings;
    }

    public function getPaymentGateways(): array
    {
        ee()->load->helper('file');
        ee()->load->library('api/api_cartthrob_payment_gateways');
        ee()->load->library('data_filter');
        ee()->load->model('template_model');

        $templates = ['' => ee()->lang->line('gateways_default_template')];
        /** @var CI_DB_mysqli_result $query */
        $query = ee()->template_model->get_templates();

        foreach ($query->result_array() as $row) {
            $templates[$row['group_name'] . '/' . $row['template_name']] = $row['group_name'] . '/' . $row['template_name'];
        }

        $gateways = ee()->api_cartthrob_payment_gateways->gateways();

        foreach ($gateways as &$pluginData) {
            ee()->lang->loadfile(strtolower($pluginData['classname']), 'cartthrob', false);

            foreach (['title', 'overview'] as $key) {
                if (isset($pluginData[$key])) {
                    $pluginData[$key] = ee()->lang->line($pluginData[$key]);
                }
            }

            $pluginData['html'] = ee()->api_cartthrob_payment_gateways
                ->set_gateway($pluginData['classname'])
                ->gateway_fields(true);

            if (isset($pluginData['settings']) && is_array($pluginData['settings'])) {
                foreach ($pluginData['settings'] as $key => $setting) {
                    $pluginData['settings'][$key]['name'] = ee()->lang->line($setting['name']);
                }

                $pluginData['settings'][] = [
                    'name' => ee()->lang->line('template_settings_name'),
                    'note' => ee()->lang->line('template_settings_note'),
                    'type' => 'select',
                    'short_name' => 'gateway_fields_template',
                    'options' => $templates,
                ];

                if (count($pluginData['vault_fields']) >= 1) {
                    $pluginData['settings'][] = [
                        'name' => ee()->lang->line('vault_template_settings_name'),
                        'note' => ee()->lang->line('vault_template_settings_note'),
                        'type' => 'select',
                        'short_name' => 'vault_fields_template',
                        'options' => $templates,
                    ];
                }
            }
        }

        ee()->data_filter->sort($gateways, 'title');

        return $gateways;
    }

    public function getPaymentPlugin($plugin)
    {
        $plugins = $this->getPaymentGateways();
        foreach ($plugins as $_plugin) {
            if ($plugin == element('classname', $_plugin)) {
                return $_plugin;
            }
        }
    }
}
