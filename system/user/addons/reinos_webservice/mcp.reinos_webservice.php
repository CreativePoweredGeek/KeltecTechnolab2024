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

use EllisLab\ExpressionEngine\Library\CP\Table;

/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

class Reinos_webservice_mcp {

    public $return_data;
    public $settings;

    public $api_url = '';

    private $base_url;
    private $show_per_page = 25;
    private $error_msg;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP);

        //add cp css
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->mcp_meta_parser('css_custom_path', ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->act_url.'&method=cp_css');

        //if there is no valid license, redirect to the license page
        if(REINOS_WEBSERVICE_LICENSE_ENABLED && !ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->hasValidLicense() && (ee()->uri->segment(5) != 'license' && ee()->uri->segment(5) != 'logs'))
        {
            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/license/'));
        }
    }

    // ----------------------------------------------------------------

    /**
     * Index Function
     *
     * @return 	void
     */
    public function index()
    {
        //show error if needed
        if ($this->error_msg != '')
        {
            return $this->error_msg;
        }

        ee()->functions->redirect(ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/members'));
    }

    // ----------------------------------------------------------------

    /**
     * Index Function
     *
     * @return 	void
     */
    public function members()
    {
        // Specify other options
        $table = ee('CP/Table', array(
            'autosort' => TRUE,
            'autosearch' => TRUE,
            'limit' => 10
        ));

        //set the columns
        $table->setColumns(
            array(
                lang('reinos_webservice_member'),
                lang('reinos_webservice_services'),
                lang('reinos_webservice_apis'),
                lang('reinos_webservice_active'),
                'manage' => array(
                    'type'  => Table::COL_TOOLBAR
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No <b>member(s)</b> found <a class="btn action" href="'.ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member').'">Create new member</a>');

        //get all cache
        $results = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->all();


        //format the data
        $data = array();
        foreach ($results as $result)
        {
            $data[] = array(
                $result->getMemberName() .' ('.$result->type.')',
                !empty($result->services) ? str_replace('|', ', ', $result->services) : 'not set',
                !empty($result->apis) ? str_replace('|', ', ', $result->apis) : 'not set',
                $result->active == 1 ? 'Yes' : 'No',
                array('toolbar_items' => array(
                    'edit' => array(
                        'href' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member/'.$result->webservice_member_id),
                        'title' => lang('edit')
                    ),
                    'remove' => array(
                        'href' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/delete_member/'.$result->webservice_member_id),
                        'title' => lang('settings')
                    )
                ))
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/overview');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
            ->perPage($vars['table']['limit'])
            ->currentPage($vars['table']['page'])
            ->render($base_url);

        return $this->output('member_overview', $vars);
    }

    // ----------------------------------------------------------------

    /**
     * show channel Function
     *
     * @return 	void
     */
    public function show_member($webservice_member_id = 0)
    {
        //get the data
        if($webservice_member_id != 0)
        {
            $webservice_user = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('webservice_member_id', $webservice_member_id)->first();
        }
        else
        {
            $webservice_user = ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':Member');
            $webservice_user->active = 1;
            $webservice_user->auth = 'basic';
            $webservice_user->type = isset($_POST['member_id']) && $_POST['member_id'] != 0 ? 'member' : 'role';
        }

        //is there some data tot save?
        if(
            isset($_POST['active']) ||
            (
                $webservice_member_id == 0 &&
                (
                    isset($_POST['member_id']) ||
                    isset($_POST['role_id'])
                )
            )
        )
        {
            //update
            if($webservice_member_id != 0)
            {
                //set the data
                $services = ee()->input->post('services') != '' ? implode('|', ee()->input->post('services')) : '';
                $apis = ee()->input->post('apis') != '' ?  implode('|', ee()->input->post('apis')) : '';

                //set the data on the model
                $webservice_user->services = $services;
                $webservice_user->apis = $apis;
                $webservice_user->active = ee()->input->post('active');
                $webservice_user->auth = ee()->input->post('auth');
                $webservice_user->shortkeys = ee()->input->post('shortkeys');
                $webservice_user->save();
            }

            //insert
            else
            {
                //no member_id or role_id
                if(ee()->input->post('member_id') == 0 && ee()->input->post('role_id') == 0)
                {
                    //set a message
                    ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_settings')
                        ->asWarning()
                        ->withTitle(lang('error'))
                        ->addToBody('No member or role is given')
                        ->defer();

                    ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member/'));
                }

                //set the data
                $webservice_user->member_id = ee()->input->post('member_id');
                $webservice_user->role_id = ee()->input->post('role_id');
                $webservice_user->services = implode('|', (array) ee()->input->post('services'));
                $webservice_user->active = ee()->input->post('active');
                $webservice_user->auth = ee()->input->post('auth');
                $webservice_user->apis = implode('|', (array) ee()->input->post('apis'));
                $webservice_user->shortkeys = ee()->input->post('shortkeys');
                $result = $webservice_user->save();

                //set the id
                $webservice_member_id = $result->webservice_member_id;
            }

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_settings')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Member saved and duplicated shortkeys where skipped')
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member/'.$webservice_member_id));
        }

        //set the settings form
        $vars['sections'] = array(array());

        //show the member if it is not a new one
        if($webservice_member_id != 0)
        {
            $vars['sections'][0][] = array(
                'title' => $webservice_user->type,
                'fields' => array(
                    'member_id' => array(
                        'type' => 'text',
                        'value' => $webservice_user->getMemberName(),
                        'disabled' => TRUE
                    )
                )
            );

            $vars['sections'][0][] = array(
                'title' => 'Key',
                'fields' => array(
                    'key' => array(
                        'type' => 'text',
                        'value' => $webservice_user->Key->key,
                        'disabled' => TRUE
                    )
                )
            );

            $vars['sections'][0][] = array(
                'title' => 'Secret',
                'fields' => array(
                    'secret' => array(
                        'type' => 'text',
                        'value' => $webservice_user->Key->secret,
                        'disabled' => TRUE
                    )
                )
            );

        }
        else
        {
            $vars['sections'][0][] = array(
                'title' => 'Member type',
                'fields' => array(
                    'member_type' => array(
                        'type' => 'inline_radio',
                        'value' => 'member_id',
                        'choices' => array(
                            'member_id' => 'Member',
                            'role_id' => 'Role'
                        ),
                        'group_toggle' => array(
                            'member_id' => 'member_id_group',
                            'role_id' => 'role_id_group'
                        )
                    )
                )
            );

            $vars['sections'][0][] = array(
                'title' => 'Member',
                'group' => 'member_id_group',
                'fields' => array(
                    'member_id' => array(
                        'type' => 'select',
                        'choices' => array(),
                        'attrs' => 'id="webservice_member_auto_complete"style="width:100%;position:relative;top:-2px;"',
                        //'type' => 'select',
                        //'choices' => array()//$this->build_select_members()
                    )
                )
            );

            $vars['sections'][0][] = array(
                'title' => 'Role',
                'desc' => 'Create a new role if you like to give group access',
                'group' => 'role_id_group',
                'fields' => array(
                    'role_id' => array(
                        'type' => 'select',
                        'choices' => $this->build_select_roles()
                    )
                )
            );

        }

        $vars['sections'][0][] = array(
            'title' => 'Service(s)',
            'fields' => array(
                'services' => array(
                    'type' => 'checkbox',
                    'value' => explode('|',$webservice_user->services ?? ''),
                    'choices' => \Reinos_webservice_config::services()
                )
            )
        );
        $vars['sections'][0][] = array(
            'title' => 'API(s)',
            'fields' => array(
                'apis' => array(
                    'type' => 'checkbox',
                    'value' => explode('|',$webservice_user->apis ?? ''),
                    'choices' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api_names()
                )
            )
        );

        $vars['sections'][0][] = array(
            'title' => 'Active',
            'desc' => 'is this member profile active or not',
            'fields' => array(
                'active' => array(
                    'type' => 'inline_radio',
                    'value' => $webservice_user->active,
                    'choices' => array(
                        '1' => 'Yes',
                        '0' => 'No'
                    )
                )
            )
        );

        $vars['sections'][0][] = array(
            'title' => 'Auth type',
            'desc' => 'Set the auth type',
            'fields' => array(
                'auth' => array(
                    'type' => 'inline_radio',
                    'value' => $webservice_user->auth,
                    'choices' => array(
                        'basic' => 'Basic',
//						'token' => 'Token'
                    )
                )
            )
        );

        $vars['sections'][0][] = array(
            'title' => 'Shortkeys',
            'desc' => 'Create a shortkey so you can auth the webservice with an shortkey. (Each key goes on a new line)',
            'fields' => array(
                'shortkeys' => array(
                    'type' => 'textarea',
                    'value' => $webservice_user->shortkeys
                )
            )
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member/'.$webservice_member_id),
            'cp_page_title' => lang('Edit member'),
            'save_btn_text' => 'Save member',
            'save_btn_text_working' => 'btn_saving',
            'alerts_name' => REINOS_WEBSERVICE_MAP.'_settings'
        );

        ee()->javascript->set_global('member_ajax_url', ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/ajax_get_members'));

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
            'ui' => array('core', 'autocomplete', 'menu')
        ));

        return $this->output('form', $vars, 'Edit member');

    }

    // ----------------------------------------------------------------

    /**
     * Delete Function
     *
     * @return 	void
     */
    public function delete_member($webservice_member_id = 0)
    {
        //delete member
        if(ee()->input->post('confirm') == 'ok')
        {
            ee()->load->model('webservice_model');
            ee()->webservice_model->delete_webservice_member($webservice_member_id);

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_member_overview')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Member deleted')
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/members/'));
        }

        $vars = array();
        $vars['webservice_member_id'] = $webservice_member_id;

        return $this->output('member_delete', $vars, 'Delete member');
    }

    // ----------------------------------------------------------------

    /**
     * Clear cache
     *
     * @return 	void
     * //@todo EE3 compat
     */
    public function clear_cache()
    {
        //de we need to delete
        if(isset($_POST['confirm']) && $_POST['confirm'] == 'ok')
        {
            ee()->cache->delete('/webservice/');

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_cache_clear')
                ->asSuccess()
                ->withTitle(lang('reinos_webservice_cache_cleared_succes'))
                ->addToBody('Cache cleared')
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/clear_cache/'));
        }

        $vars = array();

        return $this->wrapContent($vars, 'Clear Cache', 'cache_clear');

//        return $this->output('cache_clear', $vars, 'Clear Cache');
    }

    // ----------------------------------------------------------------

    /**
     * Status check Function
     *
     * @return 	array
     */
    public function status_check()
    {
        $vars['xmlrpc'] = extension_loaded('xmlrpc');
        $vars['soap'] = extension_loaded('soap');
        $vars['curl'] = extension_loaded('curl');

        $vars['xmlrpc_url'] = reduce_double_slashes(ee()->config->item('site_url').ee()->config->item('site_index').'/'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger').'/xmlrpc');
        $vars['soap_url'] = reduce_double_slashes(ee()->config->item('site_url').ee()->config->item('site_index').'/'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger').'/soap');
        $vars['rest_url'] = reduce_double_slashes(ee()->config->item('site_url').ee()->config->item('site_index').'/'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger').'/rest');

        return $this->wrapContent($vars, 'Status Check', 'status_check');
    }

    // ----------------------------------------------------------------

    /**
     * Status check Function
     *
     * @return 	void
     */
    public function api_overview()
    {
        // Specify other options
        $table = ee('CP/Table', array(
            'autosort' => TRUE,
            'autosearch' => TRUE
        ));

        //set the columns
        $table->setColumns(
            array(
                'API',
                'Version',
                'Status'
            )
        );

        //set a no result text
        $table->setNoResultsText('No API(s) found');

        //get the results
        $results = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->load_apis();

        //format the data
        $data = array();
        foreach ($results['apis'] as $result)
        {
            $data[] = array(
                $result->name . ' (' . $result->label . ')',
                $result->version,
                $result->enabled ? 'Enabled' : 'Disabled'
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_overview');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];

        return $this->output('api_overview', $vars);
    }

    /**
     * This method will be called by the table class to get the results
     *
     * @return 	void
     */
    public function api_log_detail($log_id = 0)
    {

        // Specify other options
        $table = ee('CP/Table', array(
            'autosort' => false,
            'autosearch' => false,
            'sortable' => false
        ));

        //set the columns
        $table->setColumns(
            array(
                'Time',
                array(
                    'label' => 'Query',
                    'encode' => false,
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No Queries found');

        $logs = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->filter('log_id', $log_id)->first();

        $queries = unserialize(base64_decode($logs->queries));
//var_dump($queries);
        //format the data
        $data = array();
        foreach ($queries as $query)
        {
            $data[] = array(
                $query[2],
                array(

                    'content' => '
						<pre>'.$query[0].'</pre>
						<br>
						<small><strong>'.$query[1].'</stong></small>
					'
                )
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_overview');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];

        return $this->output('api_log_detail', $vars);
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return 	void
     */
    public function api_logs()
    {
        $per_page = 10;

        $sort_col = ee()->input->get('sort_col') ?: REINOS_WEBSERVICE_MAP.'_column_time';
        $sort_dir = ee()->input->get('sort_dir') ?: 'desc';

        // Specify other options
        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir
        ));

        //var_dump($table);

        //set the columns
        $table->setColumns(
            array(
                REINOS_WEBSERVICE_MAP.'_column_time',
                REINOS_WEBSERVICE_MAP.'_column_username',
                REINOS_WEBSERVICE_MAP.'_column_ip',
                REINOS_WEBSERVICE_MAP.'_column_service',
                REINOS_WEBSERVICE_MAP.'_column_method',
                REINOS_WEBSERVICE_MAP.'_column_msg',
                REINOS_WEBSERVICE_MAP.'_column_total_queries'
            )
        );

        //set a no result text
        $table->setNoResultsText('No Logs available');

        //get all data
        $cur_page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($cur_page - 1) * $per_page; // Offset is 0 indexed

        $results = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')
            ->order(str_replace(REINOS_WEBSERVICE_MAP.'_column_', '', $table->config['sort_col']), $table->config['sort_dir'])
            ->limit($per_page)
            ->offset($offset);

        //format the data
        $data = array();
        foreach ($results->all() as $result)
        {
            $data[] = array(
                ee()->localize->human_time($result->time),
                $result->username,
                $result->ip,
                $result->service,
                $result->method,
                $result->msg,
                array(
                    'content' => $result->total_queries,
                    'href' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_log_detail/'.$result->log_id)
                )
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_logs');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->count())
            ->perPage($per_page)
            ->currentPage($cur_page)
            ->render($base_url);


        return $this->output('api_logs_overview', $vars, 'Log viewer');
    }

    // ----------------------------------------------------------------

    /**
     * This method will be called by the table class to get the results
     *
     * @return 	void
     */
    public function api_logs_delete($webservice_id = 0)
    {
        //delete member
        if(ee()->input->post('confirm') == 'ok')
        {
            //delete all
            ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->all()->delete();

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_delete_logs')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Logs deleted')
                ->defer();

            //redirect
            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_logs/'));
        }

        return $this->wrapContent(array(), 'Delete Logs', 'api_logs_delete');
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return 	void
     */
    public function shortkeys()
    {
        // Specify other options
        $table = ee('CP/Table', array(
            'autosort' => TRUE,
            'autosearch' => TRUE,
            'limit' => 10
        ));

        //set the columns
        $table->setColumns(
            array(
                lang('reinos_webservice_shortkey'),
                'manage' => array(
                    'type'  => Table::COL_TOOLBAR
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No <b>Shortkey(s)</b> made yet. <a class="btn action" href="'.ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member').'">Create a new shortkey</a>');

        //get all cache
        $results = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey')->all();

        //format the data
        $data = array();
        foreach ($results as $result)
        {
            $data[] = array(
                $result->shortkey,
                array('toolbar_items' => array(
                    'edit' => array(
                        'href' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member/'.$result->webservice_member_id),
                        'title' => lang('edit')
                    )
                ))
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/overview');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', $vars['table']['total_rows'])
            ->perPage($vars['table']['limit'])
            ->currentPage($vars['table']['page'])
            ->render($base_url);

        return $this->output('api_overview', $vars, 'API Overview');
    }

    // ----------------------------------------------------------------

    /**
     * Settings Function
     *
     * @return 	void
     */
    public function settings()
    {
        //is there some data tot save?
        if(isset($_POST) && !empty($_POST))
        {
            //validate the form
            $formValidationResult = ee('Validation')
                ->make(array())
                ->validate($_POST);

            //assign to vars
            $vars['errors'] = $formValidationResult;

            //save only when it is valid
            if ($formValidationResult->isValid())
            {
                ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->save_post_settings('/settings');
            }
        }

        //set the settings form
        $vars['sections'] = array(
            array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_log',
                    'desc' => 'What should we log? <br><br><i>This only logs the behaviour of the Webservice, not the calls. You can enable the API calls below in the section <strong>"Debug settings"</strong></i>',
                    'fields' => array(
                        'log_severity' => array(
                            'type' => 'radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('log_severity'),
                            'choices' => array(
                                'errors' => 'only errors',
                                'all' => 'Everything',
                                'none' => 'Nothing',
                            ),
                            'required' => TRUE
                        )
                    )
                ),
            ),
            'Specific settings' => array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_free_apis',
                    'desc' => 'the selected free api require <b>no</b> inlog.',
                    'fields' => array(
                        'free_apis[]' => array(
                            'type' => 'select',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('free_apis'),
                            'choices' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api_free_names(),
                            'attrs' => 'multiple="multiple"',
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_ip_blacklist',
                    'desc' => 'IP seperated by a pipline (|)',
                    'fields' => array(
                        'ip_blacklist' => array(
                            'type' => 'textarea',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('ip_blacklist'),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_url_trigger',
                    'desc' => 'Trigger segment_1 in de url',
                    'fields' => array(
                        'url_trigger' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger'),
                            'required' => TRUE
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_super_admin_shortkey',
                    'desc' => 'The super admin shortkey. With this key you can login as super admin. <br /><b style="color:red;">Be carefull with it, it provides full access to the API.</b>',
                    'fields' => array(
                        'super_admin_shortkey' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('super_admin_shortkey'),
                            'required' => false
                        )
                    ),
                    'caution' => true,
                    'security' => true
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_rest_output_header',
                    'desc' => 'Set the output header for the rest service, handy in some cases with "access control allow origin" issues. <br/><b>For example:</b> <i>Access-Control-Allow-Origin: *</i> (place each header on a new row)',
                    'fields' => array(
                        'rest_output_header' => array(
                            'type' => 'textarea',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('rest_output_header'),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_http_user_agent',
                    'desc' => 'Restrict to the given HTTP User Agent using the REST service. It search for the first occurring, not a strict match. <b>Leave blank to allow all.</b><br>Put each User Agent on a new line',
                    'fields' => array(
                        'http_user_agent' => array(
                            'type' => 'textarea',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('http_user_agent'),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_site_id_strict',
                    'desc' => 'Handle strict site_id usage.',
                    'fields' => array(
                        'site_id_strict' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('site_id_strict'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_testing_tool_url',
                    'desc' => 'This address is used by the testing tool',
                    'fields' => array(
                        'testing_tool_url' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('testing_tool_url'),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_parse_service_classes',
                    'desc' => 'This option will re-generated the needed classes for the XMLRPC and SOAP servers. Requires file permission.',
                    'fields' => array(
                        'parse_service_classes' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_service_classes'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            )
                        )
                    )
                ),
            ),
            REINOS_WEBSERVICE_MAP.'_entry_settings' => array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_parse_rel_data',
                    'desc' => 'Enrich the Relationship data with the entry data. <br><b style="color:red;">Note, this will break your parsed data when you got deep nested relationships and entries that are related in a matter of a loop.</b>',
                    'fields' => array(
                        'parse_rel_data' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_rel_data'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_parse_matrix_grid_data',
                    'desc' => 'Parse Grid/Matrix fields',
                    'fields' => array(
                        'parse_matrix_grid_data' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_matrix_grid_data'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_show_feature_entries',
                    'desc' => 'Show feature entries',
                    'fields' => array(
                        'show_feature_entries' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('show_feature_entries'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_show_expiration_entries',
                    'desc' => 'show expired entries',
                    'fields' => array(
                        'show_expiration_entries' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('show_expiration_entries'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_round_date',
                    'desc' => 'Round down or up to the start of the end of the day',
                    'fields' => array(
                        'round_date' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('round_date'),
                            'choices' => array(
                                'no' => 'No',
                                'down' => 'Down (00:00)',
                                'up' => 'Up (23:59)',
                            )
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_offset_time',
                    'desc' => 'set the offset for the entry dates. (PHP strtotime())',
                    'fields' => array(
                        'offset_time' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('offset_time'),
                            'required' => false
                        )
                    )
                ),
            ),
            REINOS_WEBSERVICE_MAP.'_debug_settings' => array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_debug',
                    'desc' => 'Debug/log the API Calls <br><strong>Remeber, enable the debug option in EE to get the queries</strong>',
                    'fields' => array(
                        'debug' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_debug_max_logs',
                    'desc' => 'Maximumium log records, otherwhise the oldest get deleted',
                    'fields' => array(
                        'debug_max_logs' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug_max_logs'),
                            'required' => false
                        )
                    )
                ),
            ),
            REINOS_WEBSERVICE_MAP.'_cache_settings' => array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_cache',
                    'desc' => 'Enable the cache',
                    'fields' => array(
                        'cache' => array(
                            'type' => 'inline_radio',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache'),
                            'choices' => array(
                                '1' => 'Yes',
                                '0' => 'No'
                            ),
                            'required' => false
                        )
                    )
                ),
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_cache_time',
                    'desc' => 'In seconds, default is 1 day',
                    'fields' => array(
                        'cache_time' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache_time'),
                            'required' => false
                        )
                    )
                ),
            )
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/settings'),
            'cp_page_title' => lang('general_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving',
            'extra_alerts' => array(
                REINOS_WEBSERVICE_MAP.'_settings'
            )
        );

        return $this->output('form', $vars, 'settings');
    }

    // ----------------------------------------------------------------

    /**
     * License Function
     *
     * @return array
     */
    public function license()
    {
        //redirect to the login page of reinos
        if(isset($_GET['auth']) && ee()->input->get('auth') == 'yes')
        {
            $ret = str_replace('&auth=yes', '', reduce_double_slashes(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->currentUrl()));
            $data = json_encode(array(
                'return' => $ret,
                'license_key' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('license_key'),
                'module_name' => str_replace('reinos_', '', REINOS_WEBSERVICE_MAP)
            ));
            ee()->functions->redirect('https://addons.reinos.nl/auth?data='.base64_encode($data));
        }

        //login successfull from addons.reinos.nl
        if(isset($_GET['auth']) && (ee()->input->get('auth') == 'success' || ee()->input->get('auth') == 'failed'))
        {
            //save the member ID
            ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->save_setting('license_reinos_member_id', ee()->input->get('member_id'));
            ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->set_setting('license_reinos_member_id', ee()->input->get('member_id'));

            //check the license with the server
            $license_check = ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->checkLicense(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('license_key'), ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('license_reinos_member_id'));

            //License check okay?
            if($license_check->success)
            {
                //set a message
                ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_settings')
                    ->addToBody(ee()->lang->line('preferences_updated'))
                    ->asSuccess()
                    ->withTitle(lang('success'))
                    ->addToBody($license_check->message)
                    ->now();
            }
        }

        //is there some data tot save?
        if(isset($_POST) && !empty($_POST))
        {
            //validate the form
            $formValidationResult = ee('Validation')
                ->make(array(
                    'license_key' => 'required'
                ))
                ->validate($_POST);

            //assign to vars
            $vars['errors'] = $formValidationResult;

            //save only when it is valid
            if ($formValidationResult->isValid())
            {
                //save the settings
                ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->save_post_settings('/license&auth=yes');
            }
        }

        //License not correct?
        if(!ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->hasValidLicense())
        {
            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_license')
                ->asIssue()
                ->withTitle(lang('error'))
                ->addToBody('You have an incorrect license. Enter a valid license in order to activate the addon.')
                ->now();
        }

        //set the settings form
        $vars['sections'] = array(
            array(
                array(
                    'title' => REINOS_WEBSERVICE_MAP.'_license_key',
                    'desc' => 'Your license key',
                    'fields' => array(
                        'license_key' => array(
                            'type' => 'text',
                            'value' => ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('license_key'),
                            'required' => TRUE
                        )
                    )
                ),
                array(
                    'title' => '',
                    'fields' => array(
                        'info' => array(
                            'type' => 'html',
                            'content' => '
                                <div class="reinos-license_status">
                                    <div class="reinos-license-info" id="'.REINOS_WEBSERVICE_MAP.'_license_status">
                                        <span class="st-closed invalid_license" style="display: none;">Invalid license</span>
                                        <span class="st-closed unlicensed" style="display: none;">Unlicensed</span>
                                        <span class="st-info valid_license" style="display: none;">Valid license</span>
                                    </div>
                                   
                                    <h4>License info</h4>
                                    <ul>
                                        <li>Every valid license will work on every local development <i>(*.local, *.test, *.dev or *.localhost)</i>. </li>
                                        <li>
                                            In order to let your license work on your domain, <strong>you have to set the production url</strong> and/or test url. This can be done in you account on
                                            <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a>.
                                        </li>
                                        <li>
                                            If you run a MSM site, you have to enter all your MSM sites in your account on <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a>.
                                        </li>
                                        <li>
                                            Running with an invalid license, will <strong>cease the addon to work</strong>.
                                        </li>
                                        <li>If you bought a license in the EE store, it will transferred also to your account on <a href="http://addons.reinos.nl/profile/licenses" target="_blank">addons.reinos.nl</a></li>
                                        <li>By using this software, you agree to the <a href="https://addons.reinos.nl/commercial-license" target="_blank">Add-on License Agreement.</a></li>
                                    </ul>
                                </div>
                                '
                        )
                    )
                )
            )
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/license'),
            'cp_page_title' => lang(REINOS_WEBSERVICE_MAP.'_license_settings'),
            'save_btn_text' => 'save and auth license',
            'save_btn_text_working' => 'btn_saving',
            'extra_alerts' => array(
                REINOS_WEBSERVICE_MAP.'_settings',
                REINOS_WEBSERVICE_MAP.'_license',
            )
        );

        return $this->output('form', $vars, 'settings');
    }

    // ----------------------------------------------------------------

    /**
     * Overview Function
     *
     * @return 	void
     */
    public function logs()
    {
        //delete action
        if(isset($_POST['delete']) && !empty($_POST['delete']))
        {
            //do your delete stuff here
            ee('Model')->get(REINOS_WEBSERVICE_MAP.':Log')->filter('log_id', ee()->input->post('delete'))->delete();

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_notice')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Log ID #'.ee()->input->post('delete')." deleted")
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/'.__FUNCTION__));
            exit;
        }

        //--------------------------
        //custom settings
        $title_page = 'Log overview';
        $action_buttons = array(
            REINOS_WEBSERVICE_MAP.'_delete_all_logs' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/delete_all_logs')
        );
        $per_page = 25;
        $sort_col = ee()->input->get('sort_col') ?: 'column_log_id';
        $sort_dir = ee()->input->get('sort_dir') ?: 'desc';
        //end custom settings
        //-------------------------

        // Specify other options
        $table = ee('CP/Table', array(
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir
        ));

        //set the columns
        $table->setColumns(
            array(
                'column_log_id',
                'column_severity',
                'column_time',
                'column_message',
                'manage' => array(
                    'type'  => Table::COL_TOOLBAR
                )
            )
        );

        //set a no result text
        $table->setNoResultsText('No logs available');

        //get all data
        $cur_page = ((int) ee()->input->get('page')) ?: 1;
        $offset = ($cur_page - 1) * $per_page; // Offset is 0 indexed

        $results = ee('Model')->get(REINOS_WEBSERVICE_MAP.':Log')
            ->order(str_replace('column_', '', $table->config['sort_col']), $table->config['sort_dir'])
            ->limit($per_page)
            ->filter('site_id', ee()->config->item('site_id'))
            ->offset($offset);

        //format the data
        $data = array();
        $ids = array();
        foreach ($results->all() as $result)
        {
            //save IDS for the delete confirm dialog
            $ids[] = array(
                'id' => $result->log_id,
                'msg' => 'ID:'
            );

            //set the data
            $data[] = array(
                $result->log_id,
                $result->severity,
                ee()->localize->human_time($result->time),
                $result->message,
                array('toolbar_items' => array(
                    'remove' => array(
                        'href' => '',
                        'title' => lang('remove'),
                        'rel' => "modal-confirm-".$result->log_id,
                        'class' => 'm-link'
                    )
                ))
            );
        }

        //set the data
        $table->setData($data);

        // Pass in a base URL to create sorting links
        $base_url = ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/logs');
        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $vars['table']['base_url'];
        $vars['action_url'] = $base_url;
        $vars['ids'] = $ids;

        //create the paging
        $vars['pagination'] = ee('CP/Pagination', $results->count())
            ->perPage($per_page)
            ->currentPage($cur_page)
            ->render($base_url);

        //Set the title
        $vars['title_page'] = $title_page;

        //set the buttons
        $vars['action_buttons'] = $action_buttons;

        return $this->output('overview', $vars, $title_page);
    }

    /**
     * @return array
     */
    public function delete_all_logs()
    {
        //delete member
        if(ee()->input->post('confirm') == 'ok')
        {
            $result = ee('Model')->get(REINOS_WEBSERVICE_MAP.':Log')->all();
            $result->delete();

            //set a message
            ee('CP/Alert')->makeInline(REINOS_WEBSERVICE_MAP.'_notice')
                ->asSuccess()
                ->withTitle(lang('success'))
                ->addToBody('Logs deleted')
                ->defer();

            ee()->functions->redirect(ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/logs/'));
        }

        $vars = array();
        $vars['title_page'] = 'Delete all Logs';
        $vars['form_url'] = ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/delete_all_logs/');

        return $this->output('delete', $vars, $vars['title_page']);
    }

    // ----------------------------------------------------------------
    // Testing methods
    // ----------------------------------------------------------------


    public function buildTestingToolForm($type = '', $api = '', $api_method = '', $fields = array())
    {
        //a type prefix that we remove later in the controller
        $field_prefix = $type.':';

        //set the base path
        $base_path = reduce_double_slashes(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('testing_tool_url').'/index.php/'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger'));

        switch($type)
        {
            case 'xmlrpc':
                $path = $base_path.'/xmlrpc';
                break;
            case 'soap':
                $path = $base_path.'/soap?wsdl';
                break;
            case 'rest':
                $path = $base_path.'/rest/'.$api_method.'/php';
                break;
            case 'custom':
                $path = 'custom';
                break;
        }

        $form_fields = array();

        //default path
        $form_fields[] = array(
            'title' => 'Path',
            'fields' => array(
                $field_prefix.'path' => array(
                    'type' => 'text',
                    'value' => $path,
                    'required' => false,
                    'attrs' => 'readonly="readlonly"'
                ),
                $field_prefix.'method' => array(
                    'type' => 'hidden',
                    'value' => $api_method,
                ),
                $field_prefix.'type' => array(
                    'type' => 'hidden',
                    'value' => $type,
                )
            )
        );


        if(!empty($fields))
        {
            foreach($fields as $field)
            {
                //get dynamic value
                if(isset($field->value) && preg_match('/call::/', $field->value, $match))
                {
                    //get the model class name
                    $method = array_filter(explode(":", str_replace($match[0], '',$field->value)));
                    $_method = array_pop($method);
                    $_class = array_shift($method);
                    //load the model
                    ee()->load->model($_class);
                    //execute method of the model
                    $value = ee()->{$_class}->{$_method}();

                }

                //from a string
                else if(isset($field->value) && preg_match('/explode::/', $field->value, $match))
                {
                    foreach(explode('|', $field->value) as $pair)
                    {
                        list($key, $val) = explode('-', $pair, 2);
                        $value[$key] = $val;
                    }
                }
                else if(!isset($field->value))
                {
                    $value = '';
                }
                else
                {
                    $value = $field->value;
                }

                //no type?
                if(!isset($field->type))
                {
                    $field->type = 'form_input';
                }
                switch($field->type)
                {

                    case "form_dropdown":
                        $form_fields[] = array(
                            'title' => $field->name,
                            'fields' => array(
                                $field_prefix.'field:'.$field->name => array(
                                    'type' => 'select',
                                    'value' => '',
                                    'choices' => $value,
                                )
                            )
                        );
                        break;
                    case "form_input":
                        $form_fields[] = array(
                            'title' => $field->name,
                            'fields' => array(
                                $field_prefix.'field:'.$field->name => array(
                                    'type' => 'text',
                                    'value' => $value,
                                )
                            )
                        );
                        break;
                    case "form_textarea":
                        $form_fields[] = array(
                            'title' => $field->name,
                            'fields' => array(
                                $field_prefix.'field:'.$field->name => array(
                                    'type' => 'textarea',
                                    'value' => $value,
                                )
                            )
                        );
                        break;
                }
            }
        }


        //set the correct structure
        $vars['sections'] = array(
            $form_fields
        );

        // Final view variables we need to render the form
        $vars += array(
            'base_url' => ee('CP/URL', 'cp/addons/settings/'.REINOS_WEBSERVICE_MAP.'/testing_tools/'.$api.'/'.$api_method),
            'cp_page_title' => lang('general_settings'),
            'save_btn_text' => 'Send request',
            'save_btn_text_working' => 'Sending request',
            'alerts_name' => REINOS_WEBSERVICE_MAP.'_settings',
            'cp_page_title_alt' => $api_method
        );

        return ee('View')->make('ee:_shared/form')->render($vars);
    }

    /**
     * Retrieve site path
     */
    function testing_tools($api = '', $method = '')
    {
        //set some defaults
        $vars_final['response'] = array(
            'response' => '',
            'request' => '',
            'service' => '',
            'url' => ''
        );
        $vars_final['show_response'] = false;
        if(!empty($_POST))
        {
            $vars_final['response'] = array_merge($vars_final['response'], ee(REINOS_WEBSERVICE_SERVICE_NAME.':TestingTool')->init());
            $vars_final['show_response'] = true;
        }

        /** ---------------------------------------
        /** load the overview of all apis if not anyone is selected
        /** ---------------------------------------*/
        if($method == '')
        {
            //get all the apis
            $load_apis = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->load_apis();
            $api = $load_apis['apis'][$api];

            $vars['methods'] = array();
            foreach($api->test as $method_name => $data)
            {
                if(is_array($data))
                {
                    $vars['methods'][] = array(
                        'name' => $method_name,
                        'url' => ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/testing_tools/'.$api->name.'/'.$method_name)
                    );
                }
            }

            //load the view
            return $this->output('testing_tools/method_choice', $vars, 'Choose a method to test');
        }

        /** ---------------------------------------
        /** get the selected api
        /** ---------------------------------------*/
        $api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api);
//var_dump($api_settings);exit;
        $fields = isset($api_settings->test->{$method}) ? $api_settings->test->{$method} : array();

        $vars_final['xmplrpc'] = $this->buildTestingToolForm('xmlrpc', $api, $method, $fields);
        $vars_final['soap'] = $this->buildTestingToolForm('soap', $api, $method, $fields);
        $vars_final['rest'] = $this->buildTestingToolForm('rest', $api, $method, $fields);
        $vars_final['custom'] = $this->buildTestingToolForm('custom', $api, $method, $fields);

        return $this->output('testing_tools/tester', $vars_final, 'Testing tool');

        //weird fix...???:|
//		if(ee()->input->post('type') == 'custom')
//		{
//			ee()->load->add_package_path(PATH_THIRD.'/webservice/');
//		}
    }

    // --------------------------------------------------------------------

    //
    private function build_select_members($q = '')
    {
        //get the member_ids that have already an account
        //we need to filter them away
        $webservice_user = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('member_id', '!=', 1)->fields('member_id')->all();
        $exception = array();
        if($webservice_user->count() > 0)
        {
            foreach($webservice_user as $user)
            {
                $exception[$user->member_id] = $user->member_id;
            }
        }

        //get all members
        $members = ee('Model')->get('Member')->fields('member_id', 'username');

        if($q != '')
        {
            $members->search('username', $q);
        }

        $members = $members->filter('member_id', '!=', 1)->all();
        $list[0] = '--- choose a member ---';
        if($members != null)
        {
            foreach($members as $member)
            {
                if(!isset($exception[$member->member_id]))
                {
                    $list[$member->member_id] = $member->username;
                }

            }
        }
        return $list;
    }

    // --------------------------------------------------------------------

    private function build_select_roles()
    {
        //get the member_ids that have already an account
        //we need to filter them away
        $webservice_user = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->fields('role_id')->all();
        $exception = array();
        if($webservice_user->count() > 0)
        {
            foreach($webservice_user as $user)
            {
                $exception[$user->role_id] = $user->role_id;
            }
        }

        //get all members
        $roles = ee('Model')->get('Role')->fields('role_id', 'name')->filter('role_id', 'NOT IN', array(2,3,4))->all();
        $list[0] = '--- choose a role ---';
        if($roles->count() > 0)
        {
            foreach($roles as $role)
            {
                if(!isset($exception[$role->role_id]))
                {
                    $list[$role->role_id] = $role->name;
                }

            }
        }

        return $list;
    }

    // --------------------------------------------------------------------

    /**
     * VBuild the ajax list for select2
     */
    public function ajax_get_members()
    {
        $list = $this->build_select_members(ee()->input->get('q'));
        unset($list[0]);

        $new_list = array();

        foreach($list as $member_id => $member)
        {
            $new_list[] = array(
                'id' => $member_id,
                'text' => $member,
            );
        }

        echo json_encode($new_list);
        exit;
    }

    private function wrapContent($vars, $title, $view) {
        // set the vars for the new viewbox
        $vars = [
            'body' => ee('View')->make(REINOS_WEBSERVICE_SERVICE_NAME.':'.$view)->render($vars),
            'title' => $title,
        ];

        return $this->output('wrapper/panel_page', $vars, $title);
    }

    // --------------------------------------------------------------------

    private function output($template, $vars, $heading = '')
    {
        //support for sidebar?
        $sidebar = ee('CP/Sidebar')->make();

        $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_settings'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/settings'));
        $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_log'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/logs'));
        $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_members'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/members'))
            ->withButton(lang('new'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/show_member'));

        //create a list
        $module_sidebar = $sidebar->addHeader(lang('API'));
        $module_list = $module_sidebar->addBasicList();
        //$module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_testing_tools'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/testing_tools'));
        $module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_api_overview'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_overview'));
        $module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_shortkeys'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/shortkeys'));
        $module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_status_check'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/status_check'));
        $module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_clear_cache'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/clear_cache'));
        $module_list->addItem(lang(REINOS_WEBSERVICE_MAP.'_api_logs'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/api_logs'));

        $module_sidebar = $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_documentation'), REINOS_WEBSERVICE_DOCS);

        //create a list
        $module_sidebar = $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_testing_tools'));
        $module_list = $module_sidebar->addBasicList();

        //get all the apis
        $load_apis = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->load_apis();
        foreach($load_apis['apis'] as $api)
        {
            if(isset($api->test) && $api->test)
            {

                $module_list->addItem($api->label, ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/testing_tools/'.$api->name));
            }
        }

        if(REINOS_WEBSERVICE_LICENSE_ENABLED)
        {
            $sidebar->addHeader(lang(REINOS_WEBSERVICE_MAP.'_license'), ee('CP/URL', 'addons/settings/'.REINOS_WEBSERVICE_MAP.'/license'));
        }


        return array(
            'body'       => ee('View')->make(REINOS_WEBSERVICE_SERVICE_NAME.':'.$template)->render($vars),
//            'breadcrumb' => array(
//                ee('CP/URL', 'addons/settings/fortune_cookie')->compile() => lang('fortune_cookie_management')
//            ),
            'heading'    => REINOS_WEBSERVICE_TITLE.' - '.lang($heading)
        );
    }
}
