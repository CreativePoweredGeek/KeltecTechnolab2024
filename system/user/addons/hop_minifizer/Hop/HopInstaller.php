<?php

use ExpressionEngine\Service\Addon\Installer;
use HopStudios\HopMinifizer\Utility\FileUtility;

if (!trait_exists('HopUpd')) {
    trait HopUpd
    {
        protected function setupLicenseSettings()
        {
            ee()->load->dbforge();

            $table_name = $this->short_name . '_settings';
            if (!ee()->db->table_exists($table_name)) {
                ee()->dbforge->add_field([
                    'setting_id'    => ['type' => 'int', 'constraint' => 4, 'unsigned' => true, 'auto_increment' => true],
                    'setting_name'  => ['type' => 'varchar', 'constraint' => 32],
                    'value'         => ['type' => 'text']
                ]);

                ee()->dbforge->add_key('setting_id', true);
                ee()->dbforge->create_table($table_name);
            }

            $license_setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license')->first();

            if ($license_setting === null) {
                $config = ee('Model')->make($this->short_name . ':Config');
                $config->setting_name = 'license';
                $config->value = 'n/a';
                $config->save();
            }
        }

        protected function checkLicense()
        {
            $table_name = $this->short_name . '_settings';

            try {
                if (ee()->db->table_exists($table_name)) {
                    $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();

                    if (!$license_valid || $license_valid->value != 'valid license') {
                        if (version_compare(APP_VER, '6', '<')) {
                            $js = '<script>$(\'.tbl-wrap td:contains("' . $this->name . '")\').closest(\'tr\').find(\'.toolbar-wrap .toolbar\').append(\'<li class="txt-only"><a href="' . ee('CP/URL', 'addons/settings/' . $this->short_name . '/license') . '" class="no">Unlicensed</a>\');</script>';
                        } else {
                            $js = '<script>$(\'.add-on-card__title:contains("' . $this->name . '")\').closest(\'.add-on-card__text\').append(\'<a href="' . ee('CP/URL', 'addons/settings/' . $this->short_name . '/license') . '" class="st-closed">Unlicensed</a>\');</script>';
                        }
                        ee()->cp->add_to_foot($js);
                    }
                }
            } catch (Exception $e) {
                // Make sure Hop License table is configured properly
                $js = '<script>$(\'.tbl-wrap td:contains("' . $this->name . '")\').closest(\'tr\').find(\'.toolbar-wrap .toolbar\').append(\'<li class="txt-only"><span class="no">Update needed</span>\');</script>';
                ee()->cp->add_to_foot($js);
            }
        }

        protected function hopUninstall($tables_to_be_removed = [])
        {
            ee()->db->select('module_id');
            $query = ee()->db->get_where('modules', ['module_name' => $this->class_name]);
            $module_id = $query->row('module_id');

            // Remove from allowed member groups/roles
            if (version_compare(APP_VER, '6', '<')) {
                ee()->db->delete('module_member_groups', ['module_id' => $module_id]);
            } else {
                ee()->db->delete('module_member_roles', ['module_id' => $module_id]);
            }

            // Remove from menu items
            ee()->db->delete('menu_items', ['name' => $this->name]);

            // Remove from actions
            ee()->db->delete('actions', ['class' => $this->class_name]);

            ee()->load->dbforge();
            // Drop the settings table
            $table_name = $this->short_name . '_settings';
            ee()->dbforge->drop_table($table_name);
            // Delete other tables
            foreach ($tables_to_be_removed as $remove_table) {
                ee()->dbforge->drop_table($remove_table);
            }

            // Remove our module :(
            ee()->db->delete('modules', ['module_name' => $this->class_name]);
        }

        protected function runDBUpgradeScripts($current_version)
        {
            ee()->load->dbforge();

            $updates_path = PATH_THIRD . $this->short_name . '/updates/';
            $updates = FileUtility::get('php', $updates_path);

            foreach ($updates as $script) {
                $script_version = str_replace([$updates_path, '.php'], '', $script);

                if (version_compare($script_version, $current_version, '>')) {
                    include $script;
                }
            }
        }
    }
}

if (class_exists('\ExpressionEngine\Service\Addon\Installer') && !class_exists('HopInstaller')) {
    class HopInstaller extends Installer
    {
        use HopUpd;
    }
} elseif (!class_exists('HopInstaller')) {
    class HopInstaller
    {
        use HopUpd;

        public function __construct()
        {
            // Nothing...
        }

        public function install()
        {
            $data = [
                'module_name'           => $this->class_name,
                'module_version'        => $this->version,
                'has_cp_backend'        => $this->has_cp_backend,
                'has_publish_fields'    => $this->has_publish_fields
            ];
            ee()->db->insert('modules', $data);
        }
    }
}