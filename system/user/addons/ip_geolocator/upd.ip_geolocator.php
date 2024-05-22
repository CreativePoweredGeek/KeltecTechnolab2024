<?php 


/**
 * 
 * IP Geo Locator Updater
 * 
 * @package     IP GEO Locator
 * @author      Anthony Mellor <anthonymellor@climbingturn.co.uk>
 * @version     2.1.0
 * @since       1.0.0
 * @copyright   Copyright (c)2019 Climbing Turn Ltd
 * @link        https://www.climbingturn.co.uk
 * 
 */
class Ip_geolocator_upd {





    /**
     * 
     * @var string
     * 
     */
    private $version = '2.1.0';



    /**
     * 
     * @var string
     * 
     */
    private $moduleName = 'Ip_geolocator';



    /**
     * 
     * Installs the add-on
     * 
     */
    function install()
    {
        $data = array(
           'module_name' => $this->moduleName,
           'module_version' => $this->version,
           'has_cp_backend' => 'y',
           'has_publish_fields' => 'n'
        );

        ee()->db->insert('modules', $data);

        return true;
    }



    /**
     * 
     * Updates the add-on
     * 
     */
    function update($current = '')
    {
        if (version_compare($current, $this->version, '=')) {
            return false;
        }

        return true;
    }



    /**
     * 
     * Uninstall the add-on
     * 
     */
    function uninstall()
    {
        ee()->db->where('module_name', $this->moduleName);
        ee()->db->delete('modules');

        return true;
    }

}
