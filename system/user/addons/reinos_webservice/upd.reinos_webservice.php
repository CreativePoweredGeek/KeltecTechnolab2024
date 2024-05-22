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
 
class Reinos_webservice_upd {
	
	public $version = REINOS_WEBSERVICE_VERSION;
	
	// ----------------------------------------------------------------
	
	/**
	 * Installation Method
	 *
	 * @return 	boolean 	TRUE
	 */
	public function install()
	{
		if (strnatcmp(phpversion(),REINOS_WEBSERVICE_PHP_MIN_VERSION) <= 0)
		{ 
			show_error(REINOS_WEBSERVICE_TITLE.' require PHP '.REINOS_WEBSERVICE_PHP_MIN_VERSION.' or higher.', 500, 'Oeps!');
			return FALSE;
		}

        //coming from the old module?
        $addon_exists = ee()->db->from('modules')->where('module_name', 'Webservice')->get();
        if($addon_exists->num_rows() > 0)
        {
            //check if the files already altered?
            $check_altered_file = ee('Filesystem')->read(PATH_THIRD.'webservice/addon.setup.php');
            if(strpos($check_altered_file, 'ALTERED') === false)
            {
                $this->alteredFile('addon.setup.php');
                $this->alteredFile('Service/License.php');
                $this->alteredFile('Service/Log.php');
                $this->alteredFile('Model/Api_logs.php');
                $this->alteredFile('Model/Grid.php');
                $this->alteredFile('Model/Key.php');
                $this->alteredFile('Model/	Log.php');
                $this->alteredFile('Model/Member.php');
                $this->alteredFile('Model/PublisherEntryTranslation.php');
                $this->alteredFile('Model/Shortkey.php');

                $alert = ee('CP/Alert')->makeInline('first-party')
                    ->asSuccess()
                    ->withTitle('Webservice module prepared for migration')
                    ->addToBody('You can now install the new Webservice module to finish the update process')
                    ->defer();

                if (ee()->input->get('return'))
                {
                    $return = ee('CP/URL')->decodeUrl(ee()->input->get('return'));
                    ee()->functions->redirect($return);
                }
            }
            else
            {
                try {
                    ee('Filesystem')->deleteDir(PATH_THIRD.'webservice/');
                } catch (Exception $e) {}
            }
        }

		//set the module data
		$mod_data = array(
			'module_name'			=> REINOS_WEBSERVICE_CLASS,
			'module_version'		=> REINOS_WEBSERVICE_VERSION,
			'has_cp_backend'		=> "y",
			'has_publish_fields'	=> 'n'
		);

        //insert the module
        ee()->db->insert('modules', $mod_data);

        //install module specific
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Installer')->install();

        //insert the settings data
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->first_import_settings();
		
		return TRUE;
	}

	// ----------------------------------------------------------------
	
	/**
	 * Uninstall
	 *
	 * @return 	boolean 	TRUE
	 */	
	public function uninstall()
	{
        //delete the module
        ee()->db->where('module_name', REINOS_WEBSERVICE_CLASS);
        ee()->db->delete('modules');

        //uninstall module specific
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Installer')->uninstall();

        //remove actions
        ee()->db->where('class', REINOS_WEBSERVICE_CLASS);
        ee()->db->delete('actions');

        //remove the extension
        ee()->db->where('class', REINOS_WEBSERVICE_CLASS.'_ext');
        ee()->db->delete('extensions');

        return TRUE;
	}
	
	// ----------------------------------------------------------------

    /**
     * Module Updater
     *
     * @param string $current
     * @return bool TRUE
     */
    public function update($current = '')
    {
        if ($current == '' OR $current == $this->version)
            return FALSE;

        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Installer')->update($current);

        return true;
    }

    private function alteredFile($path)
    {
        $path = PATH_THIRD.'webservice/'.$path;
        if(ee('Filesystem')->exists($path))
        {
            $file = ee('Filesystem')->read($path);
            $file =  str_replace(array('Reinos\Webservice', 'Reinos.nl Internet Media'), array('Reinos\WebserviceModule', 'Reinos.nl Internet Media (ALTERED)'), $file);
            ee('Filesystem')->write($path, $file, true);
        }

    }
}
