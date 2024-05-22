<?php

namespace Reinos\Webservice\Core\Installation;

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
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
require_once(PATH_THIRD.'reinos_webservice/config.php');

class Installation_custom extends Installation_base
{

    public function __construct()
    {
        parent::__construct();
    }

    // ----------------------------------------------------------------

    public function install()
    {
        //do first a check if the module Maps is installed, otherwise we need to rename the tables and migrate the data
        //instead of just installing the tables etc.
        $addon_exist = ee()->db->from('modules')->where('module_name', 'Webservice')->get();
        if($addon_exist->num_rows() > 0)
        {
            //delete the module name
            ee()->db->where('module_name', 'Webservice')->delete('modules');

            //Remove the hooks and actions
            ee()->db->where('class', 'Webservice_ext')->delete('extensions');
            ee()->db->where('class', 'Webservice')->delete('actions');

            //renaming tables
            ee()->load->dbforge();
            ee()->dbforge->rename_table('webservice_api_logs', REINOS_WEBSERVICE_MAP.'_api_logs');
            ee()->dbforge->rename_table('webservice_keys', REINOS_WEBSERVICE_MAP.'_keys');
            ee()->dbforge->rename_table('webservice_logs', REINOS_WEBSERVICE_MAP.'_logs');
            ee()->dbforge->rename_table('webservice_members', REINOS_WEBSERVICE_MAP.'_members');
            ee()->dbforge->rename_table('webservice_sessions', REINOS_WEBSERVICE_MAP.'_sessions');
            ee()->dbforge->rename_table('webservice_settings', REINOS_WEBSERVICE_MAP.'settings');
            ee()->dbforge->rename_table('webservice_shortkeys', REINOS_WEBSERVICE_MAP.'_shortkeys');
            ee()->dbforge->rename_table('webservice_tokens', REINOS_WEBSERVICE_MAP.'_tokens');
        }
        else
        {
            //create the Login backup tables
            $this->create_tables();
        }

        //Default action route
        $this->register_action('act_route', 1);

        //install the extension
        $this->register_hook('sessions_start');
        $this->register_hook('webservice_modify_search');
        $this->register_hook('webservice_post_entry_row');
        $this->register_hook('entry_submission_end');
        $this->register_hook('cp_js_end');
        $this->register_hook('before_member_delete');

        //Add tabs
        //ee()->load->library('layout');
        //ee()->layout->add_layout_tabs($this->tabs(), REINOS_WEBSERVICE_MAP);

        // add custom email templates
//        ee()->db->insert('specialty_templates', array(
//            'template_name'	=> REINOS_WEBSERVICE_MAP.'_template_name',
//            'data_title'	=> '{title}',
//            'template_type'	=> 'email',
//            'template_subtype'	=> REINOS_WEBSERVICE_MAP,
//            'template_data'	=> <<<EOF
//Hi {name},
//
//We look forward to seeing you there!
//EOF
//        ));
    }

    public function uninstall()
    {
        //remove databases
        foreach(\Reinos_webservice_config::mysql_table_data() as $table => $data)
        {
            ee()->dbforge->drop_table(REINOS_WEBSERVICE_MAP.'_'.$table);
        }
    }

    // ----------------------------------------------------------------

    /**
     * Create a tab
     *
     * @return 	boolean 	TRUE
     */
    public function tabs()
    {
        $tabs['tab_name'] = array(
            'field_name_one'=> array(
                'visible'   => 'true',
                'collapse'  => 'false',
                'htmlbuttons'   => 'true',
                'width'     => '100%'
            ),
            'field_name_two'=> array(
                'visible'   => 'true',
                'collapse'  => 'false',
                'htmlbuttons'   => 'true',
                'width'     => '100%'
            ),
        );

        return $tabs;
    }

	
} // END CLASS
