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

class Webservice_base_api
{
	/*
	*	error str
	*/
	public $error_str;

	/*
	*	auth retrun data
	*/
	public $auth_data;

	/*
	*	The userdata
	*/
	public $userdata;

	/*
	*	The servicesdata
	*/
	public $servicedata;

	/*
	*	the custom fields
	*/
	public $fields;

	/*
	*	assigned fields for the user
	*/
	public $assigned_channels;

	/*
	*	Type of service
	*/
	public $type;

	/*
	*	the log data
	*/
	public $log_data = array();

	/*
	*	The api
	*/
	public $api;

	/*
	*	The backtrace
	*/
	public $backtrace;


	public $session_id = null;

	private $memberModel = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		//load the genereal helper
		ee()->load->library('uri');

		//@todo change this, currently just a hack for EE3
		ee()->router->set_class('cp');
		ee()->load->library('cp');
		ee()->load->helper('form');
		//@end fix

		//set the debug trace
		$this->backtrace = debug_backtrace();
	}

	// ----------------------------------------------------------------

	/**
	 * Set the type for the services
	 *
	 * @param string $method [description]
	 */
	public function set_xmlrpc($method = '')
	{
		$this->type = 'xmlrpc';
	}
	public function set_rest()
	{
		$this->type = 'rest';
	}
	public function set_soap()
	{
		$this->type = 'soap';
	}

	// ----------------------------------------------------------------

	/**
	 * The default checks
	 *
	 * @param array $auth
	 * @param string $method
	 * @param int $site_id
	 * @return array [type]
	 */
	public function default_checks($auth = array(), $method = '', $site_id = 1)
	{
		//set the correct site to preform our actions
		ee()->config->site_prefs('', $site_id);

		//is admin, yeah sure you can do what you want
		//set the session so we can create a good session for him
		if(ee()->session->userdata('role_id') == 1 && ee()->session->userdata('session_id') != '')
		{
			$auth = array('session_id' => ee()->session->userdata('session_id'));
		}

        //check license
        if(!ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->hasValidLicense())
        {
            //log
            ee(REINOS_WEBSERVICE_SERVICE_NAME.':Log')->add_log('Your license is incorrect', 'error');

            //generate error
            return array(
                'succes' => false,
                'message' => \Reinos_webservice_config::service_error()['error_license']
            );
        }

		/** ---------------------------------------
		/**  No auth data and no free acces
		/** ---------------------------------------*/
		if(empty($auth) && ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, ee()->session->userdata('username')) == 0)
		{
			//generate error
			return array(
				'succes' => false,
				'message' => \Reinos_webservice_config::service_error()['error_access']
			);
		}

		/** ---------------------------------------
		/**  Auth the user
		/** ---------------------------------------*/
		$is_auth = $this->auth($auth);

		if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, ee()->session->userdata('username')) != 1)
		{
			if ( ! $is_auth)
			{
				//generate error
				return array(
					'succes' => false,
					'message' => \Reinos_webservice_config::service_error()['error_access']
				);
			}
		}

		/** ---------------------------------------
		/**  (1) Service check, is the services active
		/** ---------------------------------------*/
		if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, ee()->session->userdata('username')) != 1)
		{
			if( (!isset($this->servicedata->active) || ! $this->servicedata->active) && $this->servicedata->admin == false )
			{
				//generate error
				return array(
					'succes' => false,
					'message' => \Reinos_webservice_config::service_error()['error_inactive']
				);
			}
		}

		/** ---------------------------------------
		/**  (2) Service check, is the services selected (check by uri)
		/** ---------------------------------------*/
		if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, ee()->session->userdata('username')) != 1)
		{
			if( ( (@stripos($this->servicedata->services, ee()->uri->segment(2)) === false) && (@stripos($this->servicedata->services, ee()->session->cache[REINOS_WEBSERVICE_MAP]['API']) === false))  && $this->servicedata->admin == false)
			{
				//generate error
				return array(
					'succes' => false,
					'message' => \Reinos_webservice_config::service_error()['error_inactive']
				);
			}
		}

		/** ---------------------------------------
		/**  (3) IP blacklist check
		/** ---------------------------------------*/
		$ip_blacklist = array_filter(explode('|', ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('ip_blacklist')));
		if(in_array($_SERVER['REMOTE_ADDR'], $ip_blacklist))
		{
			//generate error
			return array(
				'succes' => false,
				'message' => \Reinos_webservice_config::service_error()['error_api_ip']
			);
		}

		/** ---------------------------------------
		/**  API check, runs this api for this server
		/** ---------------------------------------*/
		if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Permissions')->has_free_access($method, ee()->session->userdata('username')) != 1)
		{
			if( (@stripos($this->servicedata->apis, $this->api_type) === false)  && $this->servicedata->admin == false)
			{
				//generate error
				return array(
					'succes' => false,
					'message' => \Reinos_webservice_config::service_error()['error_api_type']
				);
			}
		}

		//generate succes message
		return array(
			'succes' => true
		);
	}

	// ----------------------------------------------------------------

	/**
	 * Auth a user
	 *
	 * @param array $auth
	 * @param bool $new_session
	 * @return array
	 */
	public function auth($auth = array(), $new_session = false)
	{
        $super_shortkey = false;

		//no value? shame on you, you must auth!
		if(empty($auth))
		{
			$this->error_str = 'no_auth_data';
			return false;
		}

		//auth, based on username and password
		if(isset($auth['username']) && isset($auth['password']))
		{
			$res_auth = $this->auth_data = $this->base_authenticate_username($auth['username'], $auth['password']);

			//is response false?
            if(!isset($res_auth['success']) || !$res_auth['success'])
			{
				$this->error_str = $res_auth['message'];
				return false;
			}

			//create new session if needed
			if($new_session || ee()->session->sdata['session_id'] == 0)
			{
				$this->session_id = ee()->session->create_new_session($res_auth['member_id']);
			}
			else
			{
				$this->session_id = ee()->session->sdata['session_id'];
			}

            $this->_fetch_member_data($res_auth['member_id']);
            $this->_setup_channel_privs();
            $this->_setup_module_privs();
            $this->_setup_template_privs();
            $this->_setup_assigned_sites();
		}

		//auth session
		else if (isset($auth['session_id']))
		{
			//auth the session with some fake data
			$res_auth = $this->authenticate_session($auth['session_id'], true);

			//is response false?
			if($res_auth == false)
			{
				$this->error_str = 'no valid session_id';
				return false;
			}

			// fetch a session ID if exists
            if(!isset(ee()->session->sdata) || !ee()->session->sdata) {
                $sessionId = ee()->session->getSessionModel()->session_id;
            } else {
                $sessionId = ee()->session->sdata['session_id'];
            }

			//create new session if needed
			if($new_session || !$sessionId || $sessionId === '')
			{
				$this->session_id = ee()->session->create_new_session($res_auth['member_id']);
			}
			else
			{
				$this->session_id = $sessionId;
			}

            $this->_fetch_member_data($res_auth['member_id']);
            $this->_setup_channel_privs();
            $this->_setup_module_privs();
            $this->_setup_template_privs();
            $this->_setup_assigned_sites();
		}

		//shortkeys?
		else if(isset($auth['shortkey']) && $auth['shortkey'] != '')
		{
			//get  the key
			$shortkey = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Shortkey')->filter('shortkey', $auth['shortkey']);

			//is this a super admin api key?
			$super_shortkey = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('super_admin_shortkey') == $auth['shortkey'];

			$new_member_id = null;

			//we got result, so the use has a correct auth
			if($shortkey->count() > 0)
			{
				//get the record
				$shortkey = $shortkey->first();

				//check if the user_id is empty, otherwise whe must get the member id
				//when the role_id is not empty
				if(($shortkey->Member->member_id == 0 || $shortkey->Member->member_id == '') && ($shortkey->Member->role_id != '' || $shortkey->Member->role_id != 0))
				{
					ee()->db->where('role_id', $shortkey->Member->role_id);
					$query = ee()->db->get('members');

					if($query->num_rows() > 0)
					{
						$shortkey->Member->member_id = $query->row()->member_id;
					}
				}

				//set the new member_id
				$new_member_id = $shortkey->Member->member_id;

			}

			//super admin key
			else if($super_shortkey)
			{
				$new_member_id = 1;
			}

			//do we have a new member_id
			if($new_member_id != null)
			{
				// create a new session id
	    		ee()->session->validation = null;
	   		 	//$session_id = ee()->session->create_new_session((int)$new_member_id);

				//create new session if needed
				if($new_session || ee()->session->sdata['session_id'] == 0)
				{
					$this->session_id = ee()->session->create_new_session((int)$new_member_id);
				}
				else
				{
					$this->session_id = ee()->session->sdata['session_id'];
				}

                $this->_fetch_member_data($new_member_id);
                $this->_setup_channel_privs();
                $this->_setup_module_privs();
                $this->_setup_template_privs();
                $this->_setup_assigned_sites();
			}
		}

		//key/secret
		else if(isset($auth['key']) && $auth['key'] != '' && isset($auth['secret']) && $auth['secret'] != '')
		{
			//get  the key
			$key = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Key')->filter('key', $auth['key'])->filter('secret', $auth['secret']);

			$new_member_id = null;

			//we got result, so the use has a correct auth
			if($key->count() > 0)
			{
				//get the record
				$key = $key->first();

				//check if the user_id is empty, otherwise whe must get the member id
				//when the role is not empty
				if(($key->Member->member_id == 0 || $key->Member->member_id == '') && ($key->Member->role_id != '' || $key->Member->role_id != 0))
				{
					ee()->db->where('role_id', $key->Member->role_id);
					$query = ee()->db->get('members');

					if($query->num_rows() > 0)
					{
						$key->Member->member_id = $query->row()->member_id;
					}
				}

				//set the new member_id
				$new_member_id = $key->Member->member_id;

			}

			//do we have a new member_id
			if($new_member_id != null)
			{
				// create a new session id
				ee()->session->validation = null;
				//$session_id = ee()->session->create_new_session((int)$new_member_id);

				//create new session if needed
				if($new_session || ee()->session->sdata['session_id'] == 0)
				{
					$this->session_id = ee()->session->create_new_session((int)$new_member_id);
				}
				else
				{
					$this->session_id = ee()->session->sdata['session_id'];
				}

				$this->_fetch_member_data($new_member_id);
				$this->_setup_channel_privs();
				$this->_setup_module_privs();
				$this->_setup_template_privs();
				$this->_setup_assigned_sites();
			}
		}

		//no auth? shame on you
		else
		{
			$this->error_str = 'no_auth_data';
			return false;
		}

		//look if the username is filled in, otherwise no access
		if(ee()->session->userdata('member_id') == '')
		{
			$this->error_str = 'no member_id';
			return false;
		}

		//get the services data
		//admin can do everything
		if(ee()->session->userdata('role_id') == 1)
		{
			$this->servicedata = new stdClass();
			$this->servicedata->active = true;
			$this->servicedata->services = ee()->uri->segment(2);
			$this->servicedata->admin = true;
		}
		else
		{
			//zoek eerst de member_id, most of the time this one has more right
			$webservice_member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('member_id', ee()->session->userdata('member_id'));

			//no member_id, then search the role_id
			if($webservice_member->count() == 0)
			{
				$webservice_member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('role_id', ee()->session->userdata('role_id'));
			}

            //get the result
            if($webservice_member->count() > 0)
            {
                $this->servicedata = (object)$webservice_member->first()->toArray();
                if(!isset($this->servicedata->webservice_member_id))
                {
                    $this->servicedata = new stdClass();
                    $this->servicedata->active = false;
                }

                //no admin
                $this->servicedata->admin = false;
            }
		}

		//get the webservice member
		$member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('member_id', ee()->session->userdata['member_id'])->orFilter('role_id', ee()->session->userdata['role_id']);

		//no member?
		if(!$super_shortkey && $member->count() == 0)
		{
			return array(
				'success' => false
			);
		}

        //get the first member
        //$member = $member->first();

		//check auth type, if its token based, create a new token and return it.
//		if($member->auth == 'token')
//		{
//
//		}

		return array(
			'success' => true,
			'member_id'	=> ee()->session->userdata['member_id'],
			'session_id' => $this->session_id

		);
	}

	// --------------------------------------------------------------------

	/**
	 * Authenticate Session
	 * @param string $session_id
	 * @return array|bool
	 */
    public function authenticate_session($session_id = '')
    {
    	//ee()->session->delete_old_sessions();

        // check for session id
        if ($session_id == '' || empty($session_id))
        {
            return false;
        }

        // check if session id exists in database and get member id
        ee()->db->select('member_id, user_agent, fingerprint');
        ee()->db->where('session_id', $session_id);
        $query = ee()->db->get('sessions');

        if (!$row = $query->row())
        {
            return false;
        }

        //set member_id
        $member_id = $row->member_id;

        // get member data
        ee()->db->select('*');
        ee()->db->where('member_id', $member_id);
        $query = ee()->db->get('members');

        $member_data = $query->row();

//        //set some session data
//        ee()->session->sdata['member_id'] = $member_id;
//		ee()->session->sdata['session_id'] = $session_id;
//		ee()->session->validation = null;
//		ee()->session->sess_crypt_key = $member_data->crypt_key;

		return array(
			'member_id' => $member_id,
			'username' => $member_data->username,
			'screen_name' => $member_data->screen_name
		);
    }

    // ----------------------------------------------------------------

	/**
	 * Auth based on username
	 *
	 * @param  string $username
	 * @param  string $password
	 * @return array
	 * @internal param array $post_data
	 */
	public function base_authenticate_username($username = '', $password = '')
	{
		//no username
		if(empty($username))
		{
			return \Reinos_webservice_config::service_error()['error_auth'];
		}

		// get member id
        $query = ee()->db->get_where('members', array('username' => $username));
        if ($query == false || $query->num_rows() == 0)
        {
        	return \Reinos_webservice_config::service_error()['error_auth'];
        }

        $row = $query->row();

        $member_id = $row->member_id;

       	// authenticate member
       	$auth = $this->authenticate_member($member_id, $password);

       	if(!$auth)
       	{
			return array(
				'message' => 'Auth error',
				'success' => false
			);
       	}

       	/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$auth['success'] = true;
		return $auth;
	}

	// ----------------------------------------------------------------

	/**
	 * Auth based on username
	 *
	 * @param  string $username
	 * @param  string $password
	 * @param  array  $post_data
	 * @return array
	 */
//	public function base_authenticate_email($email = '', $password = '')
//	{
//		// get member id
//        $query = ee()->db->get_where('members', array('email' => $email));
//
//        if (!$row = $query->row())
//        {
//        	return \Reinos_webservice_config::service_error()['error_access'];
//        }
//
//        $member_id = $row->member_id;
//
//        // authenticate member
//       	$auth = $this->authenticate_member($member_id, $password);
//
//       	if(!$auth)
//       	{
//       		return \Reinos_webservice_config::service_error()['error_access'];
//       	}
//
//       	/** ---------------------------------------
//		/** return response
//		/** ---------------------------------------*/
//		$this->service_error['succes_auth']['data'][0] = $auth;
//		return $this->service_error['succes_auth'];
//	}

	// --------------------------------------------------------------------

    /**
     * Authenticate Member
     */
    public function authenticate_member($member_id, $password)
    {
        // load auth library
        ee()->load->library('auth');

        // authenticate member id
        $userdata = ee()->auth->authenticate_id($member_id, $password);

        if (!$userdata)
        {
            return false;
        }

        // get member details
        $query = ee()->db->get_where('members', array('member_id' => $member_id));
        $member = $query->row();

        return array(
            'member_id' => $member_id,
            'username' => $member->username,
            'screen_name' => $member->screen_name
        );
    }

    // --------------------------------------------------------------------

	/**
	 * Setup Assigned Sites
	 *
	 * @return void
	 */
	protected function _setup_assigned_sites()
	{

        // Fetch Assigned Sites Available to User
        $assigned_sites = ee('Model')->get('Site')
            ->fields('site_id', 'site_label')
            ->order('site_label', 'desc');

        if (ee()->session->userdata('role_id') != 1) {
            $roles = $this->memberModel->getAllRoles()->pluck('role_id');
            $site_ids = ee('Model')->get('Permission')
                ->fields('site_id')
                ->filter('permission', 'can_access_cp')
                ->filter('role_id', 'IN', $roles)
                ->all()
                ->pluck('site_id');

            if (empty($site_ids)) {
                $this->userdata['assigned_sites'] = [];

                return;
            }

            $assigned_sites->filter('site_id', 'IN', $site_ids);
        }

        $this->userdata['assigned_sites'] = $assigned_sites->all()
            ->getDictionary('site_id', 'site_label');
	}

	// --------------------------------------------------------------------

	/**
	 * Setup CP Channel Privileges
     *
     * part of system/ee/legacy/libraries/Session.php::_setup_channel_privs()
	 *
	 * @return void
	 */
	protected function _setup_channel_privs()
	{
        $assigned_channels = $this->memberModel->getAssignedChannels()
            ->filter(function($channel)
            {
                return $channel->site_id == ee()->config->item('site_id');
            })
            ->getDictionary('channel_id', 'channel_title');

		ee()->session->userdata['assigned_channels'] = $assigned_channels;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Module Privileges
	 *
	 * @return void
	 */
	protected function _setup_module_privs()
	{
        $assigned_modules = array();

        foreach ($this->memberModel->getAssignedModules() as $module)
        {
            $assigned_modules[$module->getId()] = TRUE;
        }

        ee()->session->userdata['assigned_modules'] = $assigned_modules;
	}

	// --------------------------------------------------------------------

	/**
	 * Setup Template Privileges
	 *
	 * @return void
	 */
	protected function _setup_template_privs()
	{
        $assigned_template_groups = [];

        foreach ($this->memberModel->getAssignedTemplateGroups() as $template_group)
        {
            $assigned_template_groups[$template_group->getId()] = TRUE;
        }

        ee()->session->userdata['assigned_template_groups'] = $assigned_template_groups;
	}

	// --------------------------------------------------------------------

	/**
	 * Perform the big query to grab member data
     *
     * This is a combination of system/ee/legacy/libraries/Session.php::_do_member_query()
     * and system/ee/legacy/libraries/Session.php::fetch_member_data()
	 *
	 * @param int $member_id
	 * @return object database result.
	 */
	private function _fetch_member_data($member_id = 0)
	{
		// Query DB for member data.  Depending on the validation type we'll
		// either use the cookie data or the member ID gathered with the session query.

        ee()->db->from(array('members m', 'role_settings g'))
            ->where('g.site_id', (int) ee()->config->item('site_id'))
            ->where('m.role_id', ' g.role_id', FALSE);

        ee()->db->where('member_id', (int) $member_id);

		$member_query = ee()->db->get();

        $this->memberModel = ee('Model')->get('Member', $member_id)->with('PrimaryRole', 'Roles', 'RoleGroups')->all()->first();

        // Turn the query rows into array values
        foreach ($member_query->row_array() as $key => $val)
        {
            if (in_array($key, ['timezone', 'date_format', 'time_format', 'include_seconds']) && $val === '')
            {
                $val = NULL;
            }

            if ($key != 'crypt_key')
            {
                ee()->session->userdata[$key] = $val;
            }
        }

        // Add in Primary Role data
        ee()->session->userdata['primary_role_id']          = $this->memberModel->PrimaryRole->getId();
        ee()->session->userdata['primary_role_name']        = $this->memberModel->PrimaryRole->name;
        ee()->session->userdata['primary_role_description'] = $this->memberModel->PrimaryRole->description;

        // Member Group backwards compatibility
        ee()->session->userdata['group_id']          = $this->memberModel->PrimaryRole->getId();
        ee()->session->userdata['group_title']       = $this->memberModel->PrimaryRole->name;
        ee()->session->userdata['group_description'] = $this->memberModel->PrimaryRole->description;

        // Add in the Permissions for backwards compatibility
        foreach ($this->memberModel->getPermissions() as $perm => $perm_id)
        {
            ee()->session->userdata[$perm] = 'y';
        }

		//set member_id
		ee()->session->userdata['member_id'] = $member_id;

        // Create the array for the Ignore List
        ee()->session->userdata['ignore_list'] = (ee()->session->userdata['ignore_list'] == '') ? array() : explode('|', ee()->session->userdata['ignore_list']);

        // Fix the values for forum posts and replies
        ee()->session->userdata['total_forum_posts'] = $member_query->row('total_forum_topics') + $member_query->row('total_forum_posts') ;
        ee()->session->userdata['total_forum_replies'] = $member_query->row('total_forum_posts') ;
	}
}
