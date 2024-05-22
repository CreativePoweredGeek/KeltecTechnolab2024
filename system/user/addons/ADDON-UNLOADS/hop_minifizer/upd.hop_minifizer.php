<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once PATH_THIRD . 'hop_minifizer/config.php';
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';
require_once PATH_THIRD . 'hop_minifizer/Helper/HopInstallerHelper.php';

class Hop_minifizer_upd extends HopInstallerHelper
{
    public $name        = HOP_MINIFIZER_NAME;
    public $version     = HOP_MINIFIZER_VERSION;
    public $short_name  = HOP_MINIFIZER_SHORT_NAME;
    public $class_name  = HOP_MINIFIZER_CLASS_NAME;

    public $has_cp_backend = 'y';
    public $has_publish_fields = 'n';

    public function __construct()
    {
        parent::__construct();

        $this->_checkLicense();
    }

    // ----------------------------------------
    //  Module installer
    // ----------------------------------------
    public function install()
    {
        parent::__construct();

        $data = [
            'module_name' => $this->class_name,
            'module_version' => $this->version,
            'has_cp_backend' => $this->has_cp_backend,
            'has_publish_fields' => $this->has_publish_fields
        ];

        ee()->db->insert('modules', $data);

        $data = [
            'class' => 'Hop_minifizer',
            'method' => 'test_amazon_access_keys',
        ];

        ee()->db->insert('actions', $data);

        $this->_initialDbScripts();

        return true;
    }

    /**
     * Module Uninstaller
     *
     * @access    public
     * @return    bool
     */
    public function uninstall()
    {
        $this->_hopUninstall();

        return true;
    }

    public function update($current = '')
    {
        $this->_initialDbScripts($current);

        // 4.2.0
        if ( ! empty($current) && version_compare($current, '4.2.0', '<')) {
            // Sync config from extension settings
            $query = ee()->db
                        ->select('settings')
                        ->from('extensions')
                        ->where(array( 'enabled' => 'y', 'class' => 'Hop_minifizer_ext' ))
                        ->limit(1)
                        ->get();

            if ($query->num_rows() > 0) {
                $settings = unserialize($query->row()->settings);

                foreach ($settings as $setting_name => $setting_value) {
                    $setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', $setting_name)->first();
                    if (empty($setting)) {
                        $setting = ee('Model')->make($this->short_name . ':Config', ['setting_name' => $setting_name, 'value' => $setting_value]);
                    }

                    $setting->value = $setting_value;
                    $setting->save();
                }
            }
            $query->free_result();
        }

        return true;
    }

    private function _updateOldLicenseTable()
    {
        ee()->load->dbforge();

        $settings_table = $this->short_name . '_settings';
        if (ee()->db->table_exists($settings_table) && ee()->db->field_exists('values', $settings_table)) {
            ee()->dbforge->modify_column($this->short_name . '_settings', [
                'values' => [
                    'name' => 'value',
                    'type' => 'TEXT',
                ]
            ]);
        }
    }

    private function _initialDbScripts($current = '')
    {
        if (!empty($current) && version_compare($current, '3.1.0', '<')) {
            $this->_updateOldLicenseTable();
        }
        $this->_setupLicenseSettings();
    }

    private function _hopUninstall($tables_to_be_removed = [])
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

        // Remove from plugins
        ee()->db->delete('plugins', ['plugin_package' => $this->short_name]);

        // Remove from fieldtypes
        ee()->db->delete('fieldtypes', ['name' => $this->short_name]);

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

    private function _setupLicenseSettings()
    {
        ee()->load->dbforge();

        // We want to make sure we're not loading the table name from cache...
        ee()->db->data_cache['table_names'] = null;

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

    private function _checkLicense()
    {
        $table_name = $this->short_name . '_settings';

        try {
            if (ee()->db->table_exists($table_name)) {
                $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();

                if ( empty($license_valid) || $license_valid->value != 'valid license') {
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
}