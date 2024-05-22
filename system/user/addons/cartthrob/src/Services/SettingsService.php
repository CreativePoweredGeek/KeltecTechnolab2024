<?php

namespace CartThrob\Services;

use CartThrob\Dependency\Illuminate\Support\Arr;

class SettingsService
{
    /**
     * @var string either module, extension, or child
     */
    public string $type = 'module';

    /**
     * @var string set this if using 'child' type (see Get_settings::$type)
     */
    public string $parent_namespace;

    /**
     * A collection of POST keys we want to ignore
     * @var string[]
     */
    protected array $remove_keys = [
        'name',
        'submit',
        'x',
        'y',
        'XID',
        'CSRF_TOKEN',
    ];

    /**
     * SettingsService constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param string $namespace
     * @param bool $by_site_id
     * @return mixed
     */
    public function extension_settings(string $namespace, bool $by_site_id = false)
    {
        if (isset(ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')])) {
            return ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')];
        }

        $query = ee()->db->where('class', ucwords($namespace) . '_ext')
            ->limit(1)
            ->get('extensions');

        ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')] = [];

        if ($query->num_rows() > 0) {
            $settings = @unserialize($query->row('settings'));

            $query->free_result();

            if ($by_site_id) {
                $settings = isset($settings[ee()->config->item('site_id')]) ? $settings[ee()->config->item('site_id')] : [];
            }

            ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')] = $settings ? $settings : [];
        }

        return ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')];
    }

    /**
     * Use when your settings are part of another addon's settings
     *
     * ex. ee()->get_settings->child_settings('cartthrob', 'cartthrob_wish_list')
     *
     * @param string $parent_namespace
     * @param string $namespace
     * @param bool $saved_settings
     * @return mixed
     * @TODO Rewrite default setting handling so file is unnecessary
     */
    public function child_settings(string $parent_namespace, string $namespace, bool $saved_settings = false)
    {
        return Arr::get($this->settings($parent_namespace, $saved_settings), $namespace, []);
    }

    /**
     * @param string $namespace
     * @param bool $saved_settings
     * @return array|mixed
     *
     * looks for $namespace.default_settings config array
     * looks in db for $namespace._settings
     * looks in third_party/$namespace/config/config.php
     *
     * @TODO Improve handling of settings table.
     */
    public function settings(string $namespace, $saved_settings = true)
    {
        if ($this->type === 'extension') {
            return $this->extension_settings($namespace);
        } elseif ($this->type === 'child') {
            return $this->child_settings($this->parent_namespace, $namespace, $saved_settings);
        }

        $settings = [];

        if ($saved_settings) {
            if (isset(ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')])) {
                return ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')];
            }

            ee()->config->load(PATH_THIRD . $namespace . '/config/config.php', false, true);

            $settings = ee()->config->item($namespace . '_default_settings');

            if (empty($settings)) {
                @include PATH_THIRD . $namespace . '/config/config.php';

                if (!empty($config[$namespace . '_default_settings'])) {
                    $settings = (array)$config[$namespace . '_default_settings'];
                }
            }
        }

        // Attempts to load settings from the add-ons settings Model
        // Falls back to checking the table
        // @TODO Look for a better way to handle this
        if (ee()->db->table_exists($namespace . '_settings')) {
            try {
                $siteConfigs = ee('Model')->get($namespace . ':Setting')
                    ->filter('site_id', ee()->config->item('site_id'))
                    ->all();

                if (!$settings) {
                    $settings = [];
                }

                foreach ($siteConfigs as $row) {
                    $settings[$row->key] = $row->value;
                }
            } catch (\Exception $e) {
                $siteConfigs = ee()->db
                    ->where('site_id', ee()->config->item('site_id'))
                    ->get($namespace . '_settings')
                    ->result();

                foreach ($siteConfigs as $row) {
                    if (property_exists($row, 'serialized') && $row->serialized) {
                        $row->value = unserialize($row->value);
                    }

                    $settings[$row->key] = $row->value;
                }
            }

            // don't want to set the cache to ON if there are no settings.
            if ($settings) {
                if (!isset(ee()->session->cache[$namespace]['settings']) || !is_array(ee()->session->cache[$namespace]['settings'])) {
                    ee()->session->cache[$namespace]['settings'] = [];
                }
                ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')] = $settings;
            }
        }

        // so using ee()->config->load('cartthrob', false, true) puts the entire custom config into the global config layer
        // not my ideal since it should be purely for CartThrob. Worse, using the ee()->config->loadFile('cartthrob') method
        // requires the config have a return instead of how EE normally does config (a $config['key'] variable)
        // so we do this instead to keep user experience in line with their dev experience.
        // ... This may be stupid.
        $userpath = SYSPATH . 'user/config/' . $namespace . '.php';
        if (file_exists($userpath)) {
            $config = [];
            include $userpath;
            if ($config) {
                $settings = array_merge($settings, $config);
                if ($settings) {
                    ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')] = $settings;
                }
            }
        }

        return $settings;
    }

    /**
     * @param $namespace
     * @param $key
     * @return mixed|null
     */
    public function get(string $namespace, string $key)
    {
        return Arr::get($this->settings($namespace), $key);
    }

    /**
     * @param string $namespace
     * @param array $settings
     * @return bool
     */
    public function save(string $namespace, array $settings = []): bool
    {
        $existing = $this->settings($namespace);
        $settings = $this->prepareDataForSave($settings);
        if ($this->sessionFingerprintMethodChanged($existing, $settings)) {
            ee()->db->truncate('cartthrob_sessions');
        }

        foreach ($settings as $key => $value) {
            $where = [
                'site_id' => ee()->config->item('site_id'),
                '`key`' => $key,
            ];

            $row['serialized'] = 0;
            $row['value'] = $value;
            if (is_array($value)) {
                $row['serialized'] = 1;
                $row['value'] = serialize($value);
            }

            $result = ee()->db->select()->where($where)->from('cartthrob_settings')->get();
            if ($result instanceof \CI_DB_mysqli_result) {
                if ($result->num_rows == 0) {
                    ee()->db->insert('cartthrob_settings', array_merge($row, $where));
                } else {
                    if ($value !== $existing[$key]) {
                        ee()->db->update('cartthrob_settings', $row, $where);
                    }
                }
            }
        }

        return true;
    }

    /**
     * Takes the POST data and prepares it for saving
     * @param array $settings
     * @return array
     */
    protected function prepareDataForSave(array $settings): array
    {
        $data = [];
        foreach (array_keys($settings) as $key) {
            $pattern = '/^(Cartthrob_.*?_settings|product_weblogs|product_weblog_fields|default_location|tax_settings)_.*/';
            if (in_array($key, $this->remove_keys) || preg_match($pattern, $key)) {
                continue;
            }

            $data[$key] = $settings[$key];
        }

        return $data;
    }

    protected function sessionFingerprintMethodChanged(array $existing, array $settings): bool
    {
        return isset($settings['session_fingerprint_method']) && isset($existing['session_fingerprint_method']) && $settings['session_fingerprint_method'] != $existing['session_fingerprint_method'];
    }
}
