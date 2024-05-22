<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_order_manager_ext
{
    public $settings = [];
    public $version;
    private $module_name = 'cartthrob_order_manager';

    /**
     * Constructor
     *
     * @param    mixed    settings array or empty string if none exist
     */
    public function __construct($settings = '')
    {
        $this->version = ee('Addon')->get($this->module_name)->getVersion();

        ee()->lang->loadfile($this->module_name);

        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob_order_manager/');

        $this->settings = ee('cartthrob:SettingsService')->settings($this->module_name);
    }

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     */
    public function activate_extension()
    {
        return true;
    }

    /**
     * @param string $current
     * @return mixed
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
     * @return mixed
     */
    public function disable_extension()
    {
        ee()->db->delete('extensions', ['class' => $this->module_name]);
    }

    /**
     * @return array
     */
    public function settings()
    {
        return [];
    }

    /**
     * @param array $menu
     * @return array
     */
    public function cp_menu_array(array $menu = []): array
    {
        $menu = array_shift($menu);

        if (ee()->extensions->last_call !== false) {
            $menu = ee()->extensions->last_call;
        }

        $menu['ct.route.nav.addons']['list']['ct.om.nav.om'] = [
            'path' => 'addons/settings/cartthrob_order_manager',
            'with_base_url' => false,
        ];

        return $menu;
    }

    /**
     * @return void
     */
    public function cartthrob_boot()
    {
        ee()->load->library('cartthrob_addons');
        ee()->cartthrob_addons->register('CartThrob\OrderManager\Module');
    }
}
