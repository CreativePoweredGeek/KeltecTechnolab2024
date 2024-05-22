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

class Webservice_model
{

	public function __construct()
	{					
		//load other models
		ee()->load->model('webservice_category_model');
	}

	// ----------------------------------------------------------------------

	/**
	 * delete a webservice member
	 *
	 * @param int $webservice_member_id
	 * @internal param $none
	 */
	public function delete_webservice_member($webservice_member_id = 0)
	{
		//todo because of the bug with the reverse relation, this is not working when attached to a native model
		//https://ellislab.com/forums/viewthread/248823/
//			$webservice_user = ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':Member')->filter('webservice_member_id', $webservice_member_id)->first();
//			$webservice_user->delete();

		//tmp solution for now is deleting the record manually with the old DB Class
		ee()->db
			->where('webservice_member_id', $webservice_member_id)
			->delete(REINOS_WEBSERVICE_MAP.'_members');
		ee()->db
			->where('webservice_member_id', $webservice_member_id)
			->delete(REINOS_WEBSERVICE_MAP.'_shortkeys');
		ee()->db
			->where('webservice_member_id', $webservice_member_id)
			->delete(REINOS_WEBSERVICE_MAP.'_keys');
	}

    // ----------------------------------------------------------------------

    /**
     * get the fields, based on channel_id or not.
     *
     * @param null $channel_id
     * @return array
     */
    public function get_fields($channel_id = null)
    {
        $return_fields = array();

        $fields = array();

        //search channel
        if($channel_id !== null)
        {
            $query = ee('Model')->get('Channel')->filter('channel_id', $channel_id);

            //are the fields in the fieldgroup?
            if($query->first()->FieldGroups->count() > 0)
            {
                if($query->first()->FieldGroups->ChannelFields->count() > 0)
                {

                    foreach($query->first()->FieldGroups->ChannelFields as $group)
                    {
                        $fields = array_merge($group->toArray(), $fields);
                    }

                }
            }

            //are the fields directly connect to an channel>
            if($query->first()->CustomFields->count() > 0)
            {
                $fields = array_merge($query->first()->CustomFields->toArray(), $fields);
            }
        }
        else
        {
            $query = ee('Model')->get('ChannelField');

            if($query->count() > 0)
            {
                $fields = $query->all()->toArray();
            }
        }

        if(count($fields) > 0)
        {
            foreach($fields as $val)
            {
                $return_fields[$val['field_name']] = $val;
            }
        }

        return $return_fields;
    }

	// ----------------------------------------------------------------------
	
	/**
	 * get channel data
	 *
	 * @deprecated
	 *
	 * @param none
	 * @return void
	 */
//	public function get_member_based_on_username($username = '')
//	{
//		if($username == '')
//		{
//			return '';
//		}
//
//		//get the channels
//		ee()->db->select('members.*, webservice_members.*');
//		ee()->db->from('members');
//		ee()->db->join('webservice_members', 'members.member_id = webservice_members.member_id', 'left');
//
//		//build where query
//		$where = array();
//		$where['members.username'] = $username;
//
//
//		ee()->db->where($where);
//		$query = ee()->db->get();
//
//		$member = array();
//
//		//format a array
//		if ($query->num_rows() > 0)
//		{
//			$member = $query->row();
//			/*$channel->entry_statuses = $this->get_statuses($channel->status_group);
//			$channel->entry_status = $channel->entry_status != '' ? $channel->entry_status : $channel->deft_status ;*/
//			return $member;
//		}
//		return '';
//	}

	// ----------------------------------------------------------------------
		
	/**
	 * 	get the channels based on the member
	 *
	 * 	@access public
	 *	@param string
	 * 	@param string
	 *	@return mixed
	 */
//	public function get_channels_for_member($member_id)
//	{
//		//get the member
//		ee()->db->select('group_id');
//		ee()->db->from('members');
//		$query = ee()->db->get();
//		$result = $query->row();
//
//		//is super admin
//		if($result->group_id == 1)
//		{
//			ee()->db->select('channels.channel_name, channels.channel_id');
//			ee()->db->from('channels');
//			$query = ee()->db->get();
//		}
//
//		//normal user
//		else
//		{
//			ee()->db->select('channels.channel_name, channels.channel_id');
//			ee()->db->where('channel_member_groups.group_id', $result->group_id);
//			ee()->db->from('channel_member_groups');
//			ee()->db->join('channels', 'channels.channel_id = channel_member_groups.channel_id', 'right');
//			$query = ee()->db->get();
//		}
//
//		$channels = array();
//
//		//format a array
//		if ($query->num_rows() > 0)
//		{
//			foreach ($query->result() as $val)
//			{
//
//				$channels[$val->channel_id] = $val->channel_name;
//			}
//		}
//		return $channels;
//	}

	// ----------------------------------------------------------------------

	/**
	 * 	add session and useragent
	 *
	 * 	@access public
	 *	@param string
	 *	@return mixed
	 */
	public function insert_user_agent()
	{
		//remove older ones
		ee()->db->where('session_id', ee()->session->userdata('session_id'));
		ee()->db->delete(REINOS_WEBSERVICE_MAP.'_sessions');

		//insert new one
		ee()->db->insert(REINOS_WEBSERVICE_MAP.'_sessions', array(
			'session_id' => ee()->session->userdata('session_id'),
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'timestamp' => time()
		));
	}

	// ----------------------------------------------------------------------

	/**
	 * 	add session and useragent
	 *
	 * 	@access public
	 *	@param string
	 *	@return mixed
	 */
	public function get_user_agent($session_id)
	{
		$query = ee()->db
			->where('session_id', $session_id)
			->from(REINOS_WEBSERVICE_MAP.'_sessions')
			->get();

		if($query->num_rows() > 0)
		{
			return $query->row();
		}

		return '';
	}
}
