<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

class Reinos_webservice_ext
{

    public $name			= REINOS_WEBSERVICE_TITLE;
    public $description		= REINOS_WEBSERVICE_DESCRIPTION;
    public $version			= REINOS_WEBSERVICE_VERSION;
    public $settings 		= array();
    public $docs_url		= REINOS_WEBSERVICE_DOCS;
    public $settings_exist	= 'n';
    public $required_by 	= array('Webservice Module');

    /**
     * Constructor
     *
     * @param 	mixed	Settings array or empty string if none exist.
     */
    public function __construct($settings = ''){}

    // ----------------------------------------------------------------------

    /**
     * Generic call
     *
     * @param $name
     * @param $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        //build the class name
        $class = '\\Reinos\\'.REINOS_WEBSERVICE_NAMESPACE_CLASS.'\\Core\\Hook\\' . ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->studlyCase($name);

        //nothing fount?
        if (class_exists($class) === false) {
            $error = 'Hook Class not found: ' . str_replace('\\', '&#x5C;', $class);
            return ee()->output->fatal_error($error);
        }

        //init the class
        $hook = new $class();

        // play nice with other extensions on this hook
        if (ee()->extensions->last_call !== false) {
            $hook->lastCall = ee()->extensions->last_call;
        }

        //call the execute function, if needed assign the result to the ret and return it
        $ret = call_user_func_array(array($hook, 'execute'), $args);

        //handle the endScript part
        if ($hook->endScript == true) {
            ee()->extensions->end_script = true;
        }

        return $ret;
    }

    // ----------------------------------------------------------------------

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     * @see http://codeigniter.com/user_guide/database/index.html for
     * more information on the db class.
     *
     * @return void
     */
    public function activate_extension()
    {
        //the module will install the extension if needed
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension()
    {
        //the module will disable the extension if needed
        return true;
    }

    // ----------------------------------------------------------------------

    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return 	mixed	void on update / false if none
     */
    function update_extension($current = '')
    {
        //the module will update the extension if needed
        return true;
    }
}
