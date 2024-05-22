<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2019 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2019 Reinos.nl Internet Media
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

class Reinos_webservice_channel_api
{	
	//-------------------------------------------------------------------------

	/**
     * Constructor
    */
	public function __construct()
	{
	}

	// ----------------------------------------------------------------

	/**
	 * Create a new channel
	 * 
	 * @param  array  $post_data 
	 * @return array            
	 */
	public function create_channel($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new channel, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_admin_channels') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate channel'
			);
		}

		/** ---------------------------------------
		/**  channel_name
		/** ---------------------------------------*/
		if(!isset($post_data['channel_name']) || $post_data['channel_name'] == '') {
			return array(
				'message' => 'channel_name: is required'
			);
		}

		/** ---------------------------------------
		/**  channel_title
		/** ---------------------------------------*/
		if(!isset($post_data['channel_title']) || $post_data['channel_title'] == '') {
			return array(
				'message' => 'channel_title: is required'
			);
		}

		/** ---------------------------------------
		/**  Set the site_id is empty
		/** ---------------------------------------*/
		if(!ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('site_id_strict') && (!isset($post_data['site_id']) || $post_data['site_id'] == '')) {
			$post_data['site_id'] = 1;
		}
		
		/** ---------------------------------------
		/**  try to create a new channel
		/** ---------------------------------------*/
		$new_channel = ee('Model')->make('Channel')->set($post_data);

		//validate the channel
		$result = $new_channel->validate();

		//is valid?
		if ($result->isValid())
		{
			$new_channel->save();
		}
		else
		{
			//get the errors
			$errors = $result->getAllErrors();

			// only show the first error for each field to match CI's old behavior
			$field_errors = array_map('current', $errors);

			return array(
				'message' => key($field_errors). ': '.$field_errors[key($field_errors)]
			);
		}

		/* -------------------------------------------
		/* 'webservice_create_channel_end' hook.
		/*  - Added: 3.0
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('create_channel_end', $new_channel->toArray());
		// -------------------------------------------

		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'metadata' => array(
				'id' => $new_channel->channel_id
			),
			'success' => true,
			'message' => 'Created successfully'
		);
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Read a channel
	 *
	 * @param  array  $post_data 
	 * @return array            
	 */
	public function read_channel($post_data = array())
	{
		return $this->search_channel($post_data);
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Update a channel
	 *
	 * @param  array  $post_data
	 * @return array            
	 */
	public function update_channel($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new channel, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_admin_channels') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate channel'
			);
		}

		/** ---------------------------------------
		/**  channel_id
		/** ---------------------------------------*/
		if(!isset($post_data['channel_id']) || $post_data['channel_id'] == '') {
			return array(
				'message' => 'channel_id: is required'
			);
		}

		/** ---------------------------------------
		/**  Remove site_id, modify this will result in some weirdness ?
		/** ---------------------------------------*/
		if(isset($post_data['site_id']))
		{
			unset($post_data['site_id']);
		}

		/** ---------------------------------------
		/**  Channel exists
		/** ---------------------------------------*/
		$channel = ee('Model')->get('Channel')->filter('channel_id', $post_data['channel_id']);
		if($channel->count() == 0)
		{
			return array(
				'message' => 'Channel does not exists'
			);
		}

		//set the first
		$channel = $channel->first();

		/** ---------------------------------------
		/**  try to update a channel
		/** ---------------------------------------*/
		$channel = $channel->set($post_data);

		//validate the channel
		$result = $channel->validate();

		//is valid?
		if ($result->isValid())
		{
			$channel->save();
		}
		else
		{
			//get the errors
			$errors = $result->getAllErrors();

			// only show the first error for each field to match CI's old behavior
			$field_errors = array_map('current', $errors);

			return array(
				'message' => key($field_errors). ': '.$field_errors[key($field_errors)]
			);
		}

		
		/* -------------------------------------------
		/* 'webservice_update_channel_end' hook.
		/*  - Added: 3.0
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('update_channel_end', $channel->toArray());
		// -------------------------------------------
		
		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'metadata' => array(
				'id' => $channel->channel_id
			),
			'success' => true,
			'message' => 'Update successfully'
		);

	}
	
	// ----------------------------------------------------------------
	
	/**
	 * delete a channel
	 *
	 * @param  array  $post_data 
	 * @return array            
	 */
	public function delete_channel($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new channel, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_admin_channels') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate channel'
			);
		}
		
		/** ---------------------------------------
		/**  Check if there is an channel_id present
		/** ---------------------------------------*/
		if(!isset($post_data['channel_id']))
		{
			return array(
				'message' => 'Channel_id is missing'
			);
		}

		/** ---------------------------------------
		/**  Channel exists
		/** ---------------------------------------*/
		$channel = ee('Model')->get('Channel')->filter('channel_id', $post_data['channel_id']);
		if($channel->count() == 0)
		{
			return array(
				'message' => 'Channel does not exists'
			);
		}

		/** ---------------------------------------
		/**  try to delete a channel
		/** ---------------------------------------*/
		$channel->delete();
		
		/* -------------------------------------------
		/* 'webservice_delete_channel_end' hook.
		/*  - Added: 3.0
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('delete_channel_end', $post_data['channel_id']);
		// -------------------------------------------
		
		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'metadata' => array(
				'id' => $post_data['channel_id']
			),
			'success' => true,
			'message' => 'Deleted successfully'
		);
	}

	// ----------------------------------------------------------------

	/**
	 * search a channel
	 *
	 * @param  array  $post_data
	 * @return array
	 */
	public function search_channel($post_data = array())
	{
		/** ---------------------------------------
		/**  Default vars
		/** ---------------------------------------*/
		$limit = isset($post_data['limit']) ? $post_data['limit'] : 25;
		$offset = isset($post_data['offset']) ? $post_data['offset'] : 0;
		$sort = isset($post_data['sort']) ? $post_data['sort'] : 'DESC';
		$orderby = isset($post_data['orderby']) ? $post_data['orderby'] : 'channel_id';

		/** ---------------------------------------
		/**  selecting the data
		/** ---------------------------------------*/
		$channels = ee('Model')->get('Channel')->order($orderby, $sort)->offset($offset)->limit($limit);

		//get the filters
		foreach($post_data as $key=>$val)
		{
			$channels->filter($key, $val);
		}

		/** ---------------------------------------
		/**  Check if there is an channel_id present
		/** ---------------------------------------*/
		if($channels->count() == 0)
		{
			return array(
				'message' => 'Given channel does not exist'
			);
		}

		/* -------------------------------------------
		/* 'webservice_read_channel_end' hook.
		/*  - Added: 3.0
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('search_channel_end', $channels->all()->toArray());
		// -------------------------------------------

		/** ---------------------------------------
		/**  Format the data
		/** ---------------------------------------*/
		$return_data = array();
		$channel_ids = array();
		foreach($channels->all() as $key=>$channel)
		{
			$channel_data[$key] = $channel->toArray();

			//set the id
			$channel_ids[] = $channel->channel_id;

			//get the entries
			$channel_data[$key]['entry_ids'] = implode('|', $channel->Entries->pluck('entry_id'));

			//assign back
			$return_data[] = $channel_data;
		}

		return array(
			'success' => true,
			'message' => 'Readed successfully',
			'metadata' => array(
				'id' => implode('|', $channel_ids)
			),
			'data' => $channel_data
		);
	}
}
