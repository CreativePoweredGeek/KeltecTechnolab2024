<?php

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

namespace Reinos\Webservice\Service;

require_once PATH_THIRD.'reinos_webservice/config.php';

class Settings
{

    public $default_post;
    public $act;
    public $act_url;
    public $default_settings;
    public $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        //fix document_root
        if (substr($_SERVER['DOCUMENT_ROOT'], -1) != '/')
            $path = $_SERVER['DOCUMENT_ROOT'].'/';
        else
            $path = $_SERVER['DOCUMENT_ROOT'];

        //load string helper
        ee()->load->helper('string');

        //set the default settings
        $this->default_settings = array(
            'module_dir'   => PATH_THIRD.REINOS_WEBSERVICE_MAP.'/',
            'theme_dir'   => PATH_THIRD_THEMES.REINOS_WEBSERVICE_MAP.'/',
            'theme_url'   => URL_THIRD_THEMES.REINOS_WEBSERVICE_MAP.'/',
            'site_id' => ee()->config->item('site_id'),
            'site_url' => reduce_double_slashes(ee()->config->item('site_url').'/'.ee()->config->item('site_index').'/'),
            'base_dir' => $path,
            'cache_path' => (ee()->config->item('cache_path') ? ee()->config->item('cache_path') : PATH_CACHE).REINOS_WEBSERVICE_MAP
        );

        //create a tmp dir
        if(!@is_dir($this->default_settings['cache_path']))
        {
            @mkdir($this->default_settings['cache_path'], 0777);
        }
        @chmod($this->default_settings['cache_path'], 0777);

        // DB and BASE dependend
        if(REQ == 'CP' && ee()->has('db'))
        {
            //is the BASE constant defined?
//			if(!defined('BASE'))
//			{
//				$s = '';
//
//				if (ee()->config->item('admin_session_type') != 'c')
//				{
//                    if(ee()->has('session'))
//					{
//						$s = ee()->session->userdata('session_id', 0);
//					}
//				}
//
//				//lets define the BASE
//				define('BASE', SELF.'?S='.$s.'&amp;D=cp');
//			}

            if(ee()->has('session'))
            {
                $this->default_settings['base_url'] = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP);
                $this->default_settings['base_url_js'] = str_replace('&amp;', '&', ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP));
            }
        }

        //Custom (override) Config vars
        if(!empty(\Reinos_webservice_config::overide_settings()))
        {
            foreach(\Reinos_webservice_config::overide_settings() as $key=>$val)
            {
                $this->default_settings[$key] = ee()->config->item($key) != '' ? ee()->config->item($key) : str_replace(array('[theme_dir]', '[theme_url]'), array($this->default_settings['theme_dir'], $this->default_settings['theme_url']), $val);
            }
        }

        //check if all default settings are present
        //e.g. for MSM we must recreate the settings for the other site_id
        $this->check_db_settings();

        //get the settings
        $this->settings = $this->load_settings();

        //get the action id
        $this->act = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->fetch_action_id(REINOS_WEBSERVICE_CLASS, 'act_route');
        $this->act_url = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->act;
    }

    // ----------------------------------------------------------------

    // ----------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @return void
     */
    public function first_import_settings()
    {
        foreach(\Reinos_webservice_config::default_post() as $key => $val)
        {
            //check if the settings exists
            $exists = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')
                ->filter('site_id', $this->default_settings['site_id'])
                ->filter('var', $key);

            if($exists->count() == 0)
            {
                ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->set(array(
                    'site_id' => $this->default_settings['site_id'],
                    'var' => $key,
                    'value'=> $val,
                ))->save();
            }
        }
    }

    // ----------------------------------------------------------------

    /**
     * check if all default settings are present
     * e.g. for MSM we must recreate the settings for the other site_id
     *
     * @param none
     * @return void
     */
    public function check_db_settings()
    {
        if (ee()->db->table_exists(REINOS_WEBSERVICE_MAP.'_settings'))
        {
            //get the site IDS
            $sites = $this->get_sites();

            //check if there is any result
            $check = ee()->db->from(REINOS_WEBSERVICE_MAP.'_settings')->get();



            $cache_result = array();

            //cache result
            foreach($check->result() as $row)
            {
                $cache_result[$row->site_id][$row->var] = $row->value;
            }

            //loop over the sites
            foreach($sites as $site_id)
            {
                foreach(\Reinos_webservice_config::default_post() as $key=>$val)
                {
                    //check the setting
                    //ee()->db->where('var', $key);
                    //ee()->db->where('site_id', $site_id);
                    //$q = ee()->db->get(REINOS_WEBSERVICE_MAP.'_settings');

                    //if the setting not presents, we have to create this one
                    if(!isset($cache_result[$site_id][$key]))
                    {
                        $data = array(
                            'site_id' => $site_id,
                            'var' => $key,
                            'value'=> ($site_id != 1 ? $this->get_db_settings(1, $key) : $val),
                        );

                        //insert into db
                        ee()->db->insert(REINOS_WEBSERVICE_MAP.'_settings', $data);
                    }
                }
            }
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Get the Settings
     *
     * @param $all_sites
     * @return mixed array
     */
    public function load_settings($all_sites = TRUE)
    {
        if (ee()->db->table_exists(REINOS_WEBSERVICE_MAP.'_settings'))
        {
            //get the settings from the database
            $get_setting = ee()->db->get_where(REINOS_WEBSERVICE_MAP.'_settings', array(
                'site_id' => $this->default_settings['site_id']
            ));

            //load helper
            ee()->load->helper('string');

            //set the settings
            $settings = array();
            foreach ($get_setting->result() as $row)
            {
                //is serialized?
                if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->is_serialized($row->value))
                {
                    $settings[$row->var] = @unserialize($row->value);
                }
                //default value
                else
                {
                    $settings[$row->var] = $row->value;
                }
            }

            //clear data
            unset($get_setting);

            //return the settings
            return array_merge($this->default_settings, $settings);
        }
        else
        {
            return $this->default_settings;
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Get specific setting
     *
     * @param $all_sites
     * @return mixed array
     */
    public function get_settings($setting_name, $def_value = '')
    {
        if(isset($this->settings[$setting_name]))
        {
            //empty, return defualt value
            if($this->settings[$setting_name] == '')
            {
                return $def_value;
            }

            return $this->settings[$setting_name];
        }
        //nothing, return default value
        return $def_value;
    }
    //alias
    public function get_setting($setting_name, $def_value = '')
    {
        return $this->get_settings($setting_name, $def_value);
    }
    //alias
    public function item($setting_name, $def_value = '')
    {
        return $this->get_settings($setting_name, $def_value);
    }

    // ----------------------------------------------------------------------

    /**
     * Get specific setting from the db instead of the array
     *
     * @param $all_sites
     * @return mixed array
     */
    public function get_db_settings($site_id = '', $setting_name = '')
    {
        $q = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')
            ->filter('site_id', $site_id)
            ->filter('var', $setting_name);

        if($q->count() > 0)
        {
            return $q->first()->value;
        }

        return '';
    }

    // ----------------------------------------------------------------

    /**
     * Prepare settings to save
     *
     * @return 	DB object
     */
    public function save_post_settings($redirect_uri = '')
    {
        if(isset($_POST))
        {
            //remove submit value
            unset($_POST['submit']);

            //loop through the post values
            foreach(\Reinos_webservice_config::default_post() as $key=>$val)
            {
                if(isset($_POST[$key]))
                {
                    $val = $_POST[$key];
                    $this->save_setting($key, $val);
                }
            }
        }

        //set a message
        $alert = ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_settings')
            ->asSuccess()
            ->withTitle(lang('success'))
            ->addToBody(ee()->lang->line('preferences_updated'));

        $alert->defer();

        //redirect
        ee()->functions->redirect(ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.$redirect_uri));
    }

    // ----------------------------------------------------------------

    /**
     * Prepare settings to save
     *
     * @return 	DB object
     */
    public function save_setting($key = '', $val = '')
    {
        if($key != '')
        {
            //set the where clause
            ee()->db->where('var', $key);
            ee()->db->where('site_id', $this->item('site_id'));

            //is this a array?
            if(is_array($val))
            {
                $val = serialize($val);
            }

            //update the record
            ee()->db->update(REINOS_WEBSERVICE_MAP.'_settings', array(
                'value' => $val
            ));
        }
    }

    // ----------------------------------------------------------------

    /**
     * set a static setting
     *
     * @return 	DB object
     */
    public function set_setting($key = '', $val = '')
    {
        $this->settings[$key] = $val;
    }

    // ----------------------------------------------------------------

    /**
     * Get the sites
     *
     * @return 	DB object
     */
    public function get_sites()
    {
        ee()->db->select('site_id');
        $sites = ee()->db->get('sites');

        $return = array();

        if($sites->num_rows() > 0)
        {
            foreach($sites->result() as $site)
            {
                $return[] = $site->site_id;
            }
        }

        return $return;
    }

}
