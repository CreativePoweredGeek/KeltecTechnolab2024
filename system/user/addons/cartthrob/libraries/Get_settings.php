<?php

use CartThrob\Dependency\Illuminate\Support\Arr;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (!class_exists('Get_settings')) {
    class Get_settings
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
         * @param $namespace
         * @param bool $by_site_id
         * @return mixed
         */
        public function extension_settings($namespace, $by_site_id = false)
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
         * @param $parent_namespace
         * @param $namespace
         * @param bool $saved_settings
         * @return mixed
         */
        public function child_settings($parent_namespace, $namespace, bool $saved_settings = false)
        {
            return Arr::get($this->settings($parent_namespace, $saved_settings), $namespace, []);
        }

        /**
         * looks for $namespace.default_settings config array
         * looks in db for $namespace._settings
         * looks in third_party/$namespace/config/config.php
         *
         * @param $namespace
         * @param bool $saved_settings
         * @return array|mixed
         */
        public function settings($namespace, bool $saved_settings = false)
        {
            $settings = [];

            if ($this->isExtension()) {
                return $this->extension_settings($namespace);
            }

            if ($this->isChild()) {
                return $this->child_settings($this->parent_namespace, $namespace, $saved_settings);
            }

            if (!$saved_settings) {
                if (isset(ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')])) {
                    return ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')];
                }

                ee()->config->load(PATH_THIRD . $namespace . '/config/config.php', false, true);

                $settings = ee()->config->item($namespace . '_default_settings');

                if (empty($settings)) {
                    @include PATH_THIRD . $namespace . '/config/config.php';

                    if (!empty($config[$namespace . '_default_settings'])) {
                        $settings = $config[$namespace . '_default_settings'];
                    }
                }
            }

            if (ee()->db->table_exists($namespace . '_settings')) {
                $siteConfigs = ee()->db
                    ->where('site_id', ee()->config->item('site_id'))
                    ->get($namespace . '_settings')
                    ->result();

                if (!is_array($settings)) {
                    $settings = [];
                }

                foreach ($siteConfigs as $row) {
                    if ($row->serialized) {
                        $row->value = unserialize($row->value);
                    }

                    $settings[$row->key] = $row->value;
                }

                // don't want to set the cache to ON if there are no settings.
                if (!empty($settings)) {
                    ee()->session->cache[$namespace]['settings'][ee()->config->item('site_id')] = $settings;
                }
            }

            return $settings;
        }

        /**
         * @param $namespace
         * @param $key
         * @return mixed|null
         */
        public function get($namespace, $key)
        {
            return Arr::get($this->settings($namespace), $key);
        }

        /**
         * @return bool
         */
        protected function isExtension(): bool
        {
            return $this->type === 'extension';
        }

        /**
         * @return bool
         */
        protected function isChild(): bool
        {
            return $this->type === 'child';
        }
    }
}
