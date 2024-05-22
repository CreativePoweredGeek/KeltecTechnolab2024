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

class Webservice_webservice
{

	protected $defaultServices = array('soap', 'xmlrpc', 'rest', 'custom');
	//-------------------------------------------------------------------------

	/**
     * Constructor
    */
	public function __construct()
	{
		$this->apis = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api_names();
	}

	//-------------------------------------------------------------------------

	/**
	 * Create a webservice member
	 *
	 * @param $data
	 * @return array
	 */
	public function create_webservice_member($data)
	{
		$default_data = array(
			'member_id' => 0,
			'role_id' => 0,
			'services' => '',
			'apis' => '',
			'shortkeys' => '',
			'active' => 0,
			'type' => ''
		);
		
		/** ---------------------------------------
		/** Check the member data
		/** ---------------------------------------*/

		//determine if we inserting a member or role
		if(isset($data['member_id']) && $data['member_id'] != '')
		{
			//check if this member exists
			$memberExists = ee('Model')->get('Member')->filter('member_id', $data['member_id']);
			if($memberExists->count() == 0)
			{
				//generate error
				return array(
					'message' => 'Member does not exists. Create first a member before you can create a Webservice member.'
				);
			}

			//check if this role is not created yet as member for the webservice
			$webserviceMemberExists = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('member_id', $data['member_id']);
			if($webserviceMemberExists->count() > 0)
			{
				//generate error
				return array(
					'message' => 'Member already created in the Webservice.'
				);
			}

			//set the data
			$default_data['member_id'] = $data['member_id'];
			$default_data['type'] = 'member';
		}
		else if(isset($data['role_id']) && $data['role_id'] != '')
		{
			//check if this role exists
			$memberExists = ee('Model')->get('Role')->filter('role_id', $data['role_id']);
			if($memberExists->count() == 0)
			{
				//generate error
				return array(
					'message' => 'Role does not exists. Create first a Role before you can create a Webservice member.'
				);
			}

			//check if this Role is not created yet as member for the webservice
			$webserviceMemberRoleExists = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('role_id', $data['role_id']);
			if($webserviceMemberRoleExists->count() > 0)
			{
				//generate error
				return array(
					'message' => 'Role already created in the Webservice.'
				);
			}

			//set the data
			$default_data['role_id'] = $data['role_id'];
			$default_data['type'] = 'role';
		}
		else
		{
			//generate error
			return array(
				'message' => 'Missing member_id or role_id.'
			);
		}

		/** ---------------------------------------
		/** check if the apis exists and get only the one that exists
		/** ---------------------------------------*/
		if(isset($data['apis']) && $data['apis'] != '')
		{
			$default_data['apis'] = $this->setApis($data['apis']);
		}

		/** ---------------------------------------
		/** check if the service exists and get only the one that exists
		/** ---------------------------------------*/
		if(isset($data['services']) && $data['services'] != '')
		{
			$default_data['services'] = $this->setServices($data['services']);
		}

		/** ---------------------------------------
		/** set the active
		/** ---------------------------------------*/
		$default_data['active'] = isset($data['active']) ? (bool) $data['active'] : $default_data['active'] ;

		/** ---------------------------------------
		/** set the shortkeys
		/** ---------------------------------------*/
		$default_data['shortkeys'] = isset($data['shortkeys']) ? str_replace('|', "\r\n", $data['shortkeys']) : $default_data['shortkeys'] ;

		/** ---------------------------------------
		/** Create the new model and save it
		/** ---------------------------------------*/
		$member = ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->set($default_data);
		$member->save();

		/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_create'];
		$return['metadata'] = array(
			'key' => $member->Key->key,
			'secret' => $member->Key->secret,
			'id' => $member->webservice_member_id,
		);
		$return['success'] = true;
		return $return;
	}

	//-------------------------------------------------------------------------

	/**
	 * Read a webservice member
	 *
	 * @param $data
	 * @return array
	 */
	public function read_webservice_member($data)
	{
		//create the model
		$member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member');

		/** ---------------------------------------
		/** get it based on the member_id, role_id or webservice_member_id
		/** ---------------------------------------*/
		if(isset($data['member_id']) && $data['member_id'] != '')
		{
			$member->filter('member_id', $data['member_id']);
		}
		else if(isset($data['role_id']) && $data['role_id'] != '')
		{
			$member->filter('role_id', $data['role_id']);
		}
		else if(isset($data['webservice_member_id']) && $data['webservice_member_id'] != '')
		{
			$member->filter('webservice_member_id', $data['webservice_member_id']);
		}
		else
		{
			//generate error
			return array(
				'message' => 'member_id, role_id or webservice_member_id not present to get the webservice Member'
			);
		}

		/** ---------------------------------------
		/** nothing found?
		/** ---------------------------------------*/
		if($member->count() == 0)
		{
			//generate error
			return array(
				'message' => 'Webservice member not found'
			);
		}

		/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_read'];
		$return['metadata'] = array();
		$return['success'] = true;
		$return['data'] = $member->first()->toArray();
		return $return;
	}

	//-------------------------------------------------------------------------

	/**
	 * Update a webservice member
	 *
	 * @param $data
	 * @return array
	 */
	public function update_webservice_member($data)
	{
		//create the model
		$member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member');

		/** ---------------------------------------
		/** get it based on the member_id, role_id or webservice_member_id
		/** ---------------------------------------*/
		if(isset($data['member_id']) && $data['member_id'] != '')
		{
			$member->filter('member_id', $data['member_id']);
		}
		else if(isset($data['role_id']) && $data['role_id'] != '')
		{
			$member->filter('role_id', $data['role_id']);
		}
		else if(isset($data['webservice_member_id']) && $data['webservice_member_id'] != '')
		{
			$member->filter('webservice_member_id', $data['webservice_member_id']);
		}
		else
		{
			//generate error
			return array(
				'message' => 'member_id, role_id or webservice_member_id not present to get the webservice Member'
			);
		}

		/** ---------------------------------------
		/** nothing found?
		/** ---------------------------------------*/
		if($member->count() == 0)
		{
			//generate error
			return array(
				'message' => 'Webservice member not found'
			);
		}

		/** ---------------------------------------
		/** check if the apis exists and get only the one that exists
		/** ---------------------------------------*/
		if(isset($data['apis']) && $data['apis'] != '')
		{
			$default_data['apis'] = $this->setApis($data['apis']);
		}

		/** ---------------------------------------
		/** check if the service exists and get only the one that exists
		/** ---------------------------------------*/
		if(isset($data['services']) && $data['services'] != '')
		{
			$default_data['services'] = $this->setServices($data['services']);
		}

		/** ---------------------------------------
		/** set the active
		/** ---------------------------------------*/
		$default_data['active'] = isset($data['active']) ? (bool) $data['active'] : $default_data['active'] ;

		/** ---------------------------------------
		/** set the shortkeys
		/** ---------------------------------------*/
		$default_data['shortkeys'] = isset($data['shortkeys']) ? $data['shortkeys'] : $default_data['shortkeys'] ;

		/** ---------------------------------------
		/** Create the new model and save it
		/** ---------------------------------------*/
		$member = $member->first();
		$member->set($default_data);
		$member->save();

		/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_update'];
		$return['metadata'] = array(
			'id' => $member->webservice_member_id
		);
		$return['success'] = true;
		return $return;
	}

	//-------------------------------------------------------------------------

	/**
	 * Delete a webservice member
	 *
	 * @param $data
	 * @return array
	 */
	public function delete_webservice_member($data)
	{
		//create the model
		$member = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member');

		/** ---------------------------------------
		/** get it based on the member_id, role_id or webservice_member_id
		/** ---------------------------------------*/
		if(isset($data['member_id']) && $data['member_id'] != '')
		{
			$member->filter('member_id', $data['member_id']);
		}
		else if(isset($data['role_id']) && $data['role_id'] != '')
		{
			$member->filter('role_id', $data['role_id']);
		}
		else if(isset($data['webservice_member_id']) && $data['webservice_member_id'] != '')
		{
			$member->filter('webservice_member_id', $data['webservice_member_id']);
		}
		else
		{
			//generate error
			return array(
				'message' => 'member_id, role_id or webservice_member_id not present to get the webservice Member'
			);
		}

		/** ---------------------------------------
		/** nothing found?
		/** ---------------------------------------*/
		if($member->count() == 0)
		{
			//generate error
			return array(
				'message' => 'Webservice member not found'
			);
		}

		//delete it
		$webservice_member_id = $member->first()->webservice_member_id;

        ee()->load->model('webservice_model');
		ee()->webservice_model->delete_webservice_member($webservice_member_id);

		/** ---------------------------------------*/
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_delete'];
		$return['metadata'] = array(
			'id' => $webservice_member_id
		);
		$return['success'] = true;
		return $return;
	}

	//-------------------------------------------------------------------------

	/**
	 * set the apis
	 *
	 * @param $services
	 * @return string
	 */
	private function setApis($apis)
	{
		$apis = explode('|', $apis);

		$newApis = array();

		if(is_array($apis))
		{
			foreach($apis as $api)
			{
				//check if exists
				if(isset($this->apis[$api]))
				{
					$newApis[] = $api;
				}
			}
		}

		return implode('|', $newApis);
	}

	//-------------------------------------------------------------------------

	/**
	 * set the services
	 *
	 * @param $services
	 * @return string
     */
	private function setServices($services)
	{
		$services = explode('|', $services);

		$newServices = array();

		if(is_array($services))
		{
			foreach($services as $service)
			{
				//check if exists
				if(in_array($service, $this->defaultServices))
				{
					$newServices[] = $service;
				}
			}
		}

		return implode('|', $newServices);
	}
}

