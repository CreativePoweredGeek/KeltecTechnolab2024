<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

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
require_once PATH_THIRD . 'reinos_webservice/config.php';

class Webservice_entry
{
	public $limit;
	public $offset;
	public $total_results;
	public $absolute_results;

	private $channel;
	private $fields;

	//-------------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// load the stats class because this is not loaded because of the use of the extension
		ee()->load->library('stats'); //@todo check if this is needed to load
		ee()->load->library('api/entry/fieldtypes/webservice_fieldtype');
	}

	//-------------------------------------------------------------------------

	/**
	 * Create a entry
	 *
	 * @param  array $post_data
	 * @return array
	 * @internal param string $auth
	 */
	public function create_entry($post_data = array())
	{
		//add the post data to the fieldtype
		ee()->webservice_fieldtype->post_data = $post_data;

		/* -------------------------------------------
		/* 'webservice_create_entry_start' hook.
		/*  - Added: 3.2.1
		*/
		$post_data = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('create_entry_start', $post_data);
		/* ---------------------------------------*/

		/** ---------------------------------------
		/**  Validate data
		/** ---------------------------------------*/
		$data_errors = array();

		/** ---------------------------------------
		/**  Title is for a insert always required
		/** ---------------------------------------*/
		if (!isset($post_data['title']) || $post_data['title'] == '') {
			$data_errors[] = 'title';
		}
		if (!isset($post_data['channel_name']) || $post_data['channel_name'] == '') {
			$data_errors['channel_name'] = 'channel_name';
		}
		//		if(!isset($post_data['channel_id']) || $post_data['channel_id'] == '')
		//		{
		//			$data_errors['channel_id'] = 'channel_id';
		//		}

		/** ---------------------------------------
		/**  in strict site id mode, expect a site_id
		/** ---------------------------------------*/
		if (ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Settings')->item('site_id_strict') && !isset($post_data['site_id'])) {
			$data_errors[] = 'site_id';
		}

		/** ---------------------------------------
		/**  Set the site_id is empty
		/** ---------------------------------------*/
		if (!isset($post_data['site_id']) || $post_data['site_id'] == '') {
			$post_data['site_id'] = 1;
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if (!empty($data_errors) || count($data_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields are not filled in: ' . implode(', ', $data_errors)
			);
		}

		/** ---------------------------------------
		/**  Parse Out Channel Information and check if the use is auth for the channel
		/** ---------------------------------------*/
		$channel_check = $this->_parse_channel($post_data['channel_name'], __FUNCTION__);
		if (!$channel_check['success']) {
			return $channel_check;
		}

		/** ---------------------------------------
		/**  Check if the site_id are a match
		/** ---------------------------------------*/
		if ($post_data['site_id'] != $this->channel->site_id) {
			//generate error
			return array(
				'message' => 'The site_id for this channel is wrong'
			);
		}

		/** ---------------------------------------
        /**  get the custom fields
        /** ---------------------------------------*/
		ee()->webservice_entry_lib->fetch_custom_channel_fields($this->channel->channel_id);

		/** ---------------------------------------
		/**  Check the other fields witch are required
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if ($val['field_required'] == 'y') {
					if (!isset($post_data[$val['field_name']]) || $post_data[$val['field_name']] == '') {
						$data_errors[] = $val['field_name'];
					}
				}
			}
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if (!empty($data_errors) || count($data_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields have errors: ' . implode(', ', $data_errors)
			);
		}

		/** ---------------------------------------
		/**  validate fields by the fieldtype parser
		/** ---------------------------------------*/
		$validate_errors = array();
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if (isset($post_data[$val['field_name']])) {
					//validate the data
					$validate_field = ee()->webservice_fieldtype->validate($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, true, 0);

					/*if($validate_field == false)
					{
						$validate_errors[] = $val['field_name'].' : '.ee()->webservice_fieldtype->validate_error;
					}*/

					//do we got some errors?
					if ($validate_field !== true && $validate_field != null && $validate_field != '' && !is_array($validate_field)) {
						$error = ee()->webservice_fieldtype->validate_error != '' ? ee()->webservice_fieldtype->validate_error : $validate_field;
						$validate_errors[] = $val['field_name'] . ' : ' . $error;
					}

					//if the validate function return an array?
					if (isset($validate_field['error'])) {
						$validate_errors[] = $val['field_name'] . ' : ' . $validate_field['error'];
					}

					//if the validate only holds the new value
					if (isset($validate_field['value'])) {
						$post_data[$val['field_name']] = $validate_field['value'];
					}
				}
			}
		}

		/** ---------------------------------------
		/**  Return the errors from the validate functions
		/** ---------------------------------------*/
		if (!empty($validate_errors) || count($validate_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields have errors: ' . implode(', ', $validate_errors)
			);
		}

		/** ---------------------------------------
		/** Convert built-in date fields to UNIX timestamps
		/** ---------------------------------------*/

		// Set entry_date and edit_date to "now" if empty
		$post_data['entry_date'] = isset($post_data['entry_date']) ? $post_data['entry_date'] : ee()->localize->now;
		//$post_data['edit_date'] = isset($post_data['edit_date']) ? $post_data['edit_date'] : ee()->localize->now ;

		//validate dates
		$date_error = $this->validate_dates(array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), $post_data);
		if ($date_error !== true) {
			return $date_error;
		}

		/** ---------------------------------------
		/**  default Entry data
		/** ---------------------------------------*/
		$entry = ee('Model')->make('ChannelEntry');
		$entry->Channel = $this->channel;
		$entry->site_id = $post_data['site_id'];
		$entry->author_id = isset($post_data['member_id']) ? $post_data['member_id'] : ee()->session->userdata('member_id');
		$entry->title = $post_data['title'];
		$entry->url_title = isset($post_data['url_title']) ? url_title($post_data['url_title']) : url_title($post_data['title']);
		$entry->ip_address = ee()->input->ip_address();
		$entry->entry_date = $post_data['entry_date'];
		//        $entry->edit_date = gmdate("YmdHis", $post_data['edit_date']);
		$entry->status = isset($post_data['status']) ? $post_data['status'] : $this->channel->deft_status;
		$entry->versioning_enabled = $this->channel->enable_versioning;
		$entry->sticky = isset($post_data['sticky']) ? $post_data['sticky'] : FALSE;
		$entry->allow_comments = isset($post_data['allow_comments']) ? $post_data['allow_comments'] : $this->channel->deft_comments;
		$entry->expiration_date = $post_data['expiration_date'];
		$entry->comment_expiration_date = $post_data['comment_expiration_date'];
		$entry->Categories = array();

		/** ---------------------------------------
		/**  Override url_title
		/** ---------------------------------------*/
		if (isset($post_data['url_title']) && $post_data['url_title'] != '') {
			$entry->url_title = $post_data['url_title'];
		}

		/** ---------------------------------------
		/**  Publisher support
		/** ---------------------------------------*/
		if (isset($post_data['publisher_lang_id'])) {
			$entry->publisher_lang_id = $post_data['publisher_lang_id'];
		}
		if (isset($post_data['publisher_status'])) {
			$entry->publisher_status = $post_data['publisher_status'];
		}

		/** ---------------------------------------
		/**  Fill out the other fields
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if (isset($post_data[$val['field_name']])) {
					//set the data
					$entry->{'field_ft_' . $val['field_id']}  = $val['field_fmt'];
					try {
						$entry->{'field_id_' . $val['field_id']}  = ee()->webservice_fieldtype->save($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, true);
					} catch (Throwable $e) {
						return array(
							'message' => $e->getMessage()
						);
					} catch (Exception $e) {
						
						return array(
							'message' => $e->getMessage()
						);
					}
				}
			}
		}

		/** ---------------------------------------
        /**  Default category
        /** ---------------------------------------*/
		if (isset($this->channel->deft_category)) {
			$cat = ee('Model')->get('Category', $this->channel->deft_category)->first();
			if ($cat) {
				$entry->Categories[] = $cat;
			}
		}

		/** ---------------------------------------
        /**  posted category
        /** ---------------------------------------*/
		if (isset($post_data['category'])) {
			$post_data['category'] = array_filter(explode('|', $post_data['category']));

			if (is_array($post_data['category'])) {
				$cat_ids = array_filter($post_data['category']);
				if (!empty($cat_ids)) {
					$entry->Categories = ee('Model')->get('Category')->filter('cat_id', 'IN', $cat_ids)->all();
				}
			}
		}

		/* -------------------------------------------
        /* 'webservice_create_entry_start' hook.
        /*  - Added: 3.2.1
        */
		$entry = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('create_entry', $entry, false, $post_data);
		/* ---------------------------------------*/

		/** ---------------------------------------
		/** create a new entry
		/** ---------------------------------------*/
		$result = $entry->validate();

		// is the entry valid
		if ($result->isValid()) {
			$entry->save();
		} else {
			//generate error
			ee()->lang->loadfile('content');
			foreach ($result->getAllErrors() as $field => $error) {
				if (isset($error['callback'])) {
					$error_msg = lang($error['callback']);
				} else {
					$error_msg = json_encode($error);
				}

				return array(
					'message' => $error_msg
				);
			}
		}

		//get the new entry_id
		$new_entry_id = $entry->getProperty('entry_id');

		/** ---------------------------------------
		/**  Post save callback
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if (isset($post_data[$val['field_name']])) {
					//validate the data
					ee()->webservice_fieldtype->post_save($entry->{'field_ft_' . $val['field_id']}, $val['field_type'], $val['field_name'], $val, $this->channel, $entry, $new_entry_id);
				}
			}
		}

		/* -------------------------------------------
		/* 'webservice_create_entry_end' hook.
		/*  - Added: 2.2
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('create_entry_end', $new_entry_id, false, $post_data);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_create'];
		$return['metadata'] = array(
			'id' => $new_entry_id
		);
		$return['success'] = true;
		return $return;
	}

	// ----------------------------------------------------------------

	/**
	 * Read a entry
	 * @param  array $post_data
	 * @return array
	 * @internal param string $auth
	 */
	public function read_entry($post_data = array())
	{
		return $this->search_entry($post_data, 'read_entry');
	}

	// ----------------------------------------------------------------

	/**
	 * build a entry data array for a new entry
	 *
	 * @param array $post_data
	 * @return array
	 */
	public function update_entry($post_data = array())
	{
		//add the post data to the fieldtype
		ee()->webservice_fieldtype->post_data = $post_data;

		/* -------------------------------------------
		/* 'webservice_update_entry_start' hook.
		/*  - Added: 3.2.1
		*/
		$post_data = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('update_entry_start', $post_data);

		$entry = false;
		$data_errors = [];

		/** ---------------------------------------
        /**  get the entry with the entry_id
        /** ---------------------------------------*/
		if (isset($post_data['entry_id'])) {
			$entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $post_data['entry_id'])->first();
		}

		/** ---------------------------------------
        /**  get the entry with the search param
        /** ---------------------------------------*/
		else if (
			isset($post_data['search'])
			&&
			(count($post_data['search']) > 0
				||
				(isset($post_data['search']['force']) && count($post_data['search']) > 1)
			)
		) {
			// get the force param
			$forceFirstEntry = isset($post_data['search']['force']);

			if ($forceFirstEntry) {
				unset($post_data['search']['force']);
				$post_data['search']['limit'] = 1;
			}

			/** ---------------------------------------
            /**  Basic search on the entry. This return only the entry IDS
            /** ---------------------------------------*/
			ee()->load->library('webservice_entry_lib');
			$search_result_query = ee()->webservice_entry_lib->search_entry($post_data['search'], ee()->session->userdata('username'), 'update_entry');

			if ($search_result_query->count() > 1 && !$forceFirstEntry) {
				return array(
					'message' => 'Your search param returned more than one entry. You can use data[search][force] to force use the first entry'
				);
			}

			$entry = $search_result_query->first();
		}

		/** ---------------------------------------
        /**  No params found
        /** ---------------------------------------*/
		else {
			return array(
				'message' => 'You should use data[entry_id] or data[search][custom_field|or|title] to get the entry that you are updating.'
			);
		}

		// any result?
		if (!$entry) {
			//generate error
			return array(
				'message' => 'No Entry found'
			);
		}

		/** ---------------------------------------
		/**  Publisher support
		/** ---------------------------------------*/
		if (isset($post_data['publisher_lang_id'])) {
			$entry->publisher_lang_id = $post_data['publisher_lang_id'];
		}
		if (isset($post_data['publisher_status'])) {
			$entry->publisher_status = $post_data['publisher_status'];
		}

		/** ---------------------------------------
		/**  Parse Out Channel Information and check if the use is auth for the channel
		/** ---------------------------------------*/
		$channel_check = $this->_parse_channel($entry->channel_id, __FUNCTION__);
		if (!$channel_check['success']) {
			return $channel_check;
		}

		/** ---------------------------------------
        /**  get the custom fields
        /** ---------------------------------------*/
		ee()->webservice_entry_lib->fetch_custom_channel_fields($entry->channel_id);

		/** ---------------------------------------
		/**  Check the other fields witch are required
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if ($val['field_required'] == 'y') {
					if (!isset($post_data[$val['field_name']]) || $post_data[$val['field_name']] == '') {
						$data_errors[] = $val['field_name'];
					}
				}
			}
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if (!empty($data_errors) || count($data_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields are not filled in: ' . implode(', ', $data_errors)
			);
		}

		/** ---------------------------------------
		/**  check if the given channel_id match the channel_id of the entry
		/** ---------------------------------------*/
		//@todo do we need this??
		if ($entry->channel_id != $this->channel->channel_id) {
			//generate error
			return array(
				'message' => 'Specified entry does not appear in the specified channel'
			);
		}

		/** ---------------------------------------
		/**  validate fields by the fieldtype parser
		/** ---------------------------------------*/
		$validate_errors = array();
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if (isset($post_data[$val['field_name']])) {
					//validate the data
					$validate_field = (bool) ee()->webservice_fieldtype->validate($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, false, $entry->entry_id);

					if ($validate_field == false) {
						$validate_errors[] = $val['field_name'] . ' : ' . ee()->webservice_fieldtype->validate_error;
					}
				}
			}
		}

		/** ---------------------------------------
		/**  Return the errors from the validate functions
		/** ---------------------------------------*/
		if (!empty($validate_errors) || count($validate_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields have errors: ' . implode(', ', $validate_errors)
			);
		}

		/** ---------------------------------------
        /**  validate dates
        /** ---------------------------------------*/
		$date_error = $this->validate_dates(array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), $post_data);
		if ($date_error !== true) {
			return $date_error;
		}

		/** ---------------------------------------
        /**  default data
        /** ---------------------------------------*/

		$entry->title = isset($post_data['title']) ? $post_data['title'] : $entry->title;
		$entry->status = isset($post_data['status']) ? $post_data['status'] : $entry->status;
		$entry->sticky = isset($post_data['sticky']) ? $post_data['sticky'] : $entry->sticky;
		$entry->allow_comments = isset($post_data['allow_comments']) ? $post_data['allow_comments'] : $entry->allow_comments;
		$entry->entry_date = isset($post_data['entry_date']) && $post_data['entry_date'] > 0 ? $post_data['entry_date'] : $entry->entry_date;
		$entry->edit_date = isset($post_data['edit_date']) && $post_data['edit_date'] > 0 ? $post_data['edit_date'] : ee()->localize->now;
		$entry->expiration_date = isset($post_data['expiration_date']) ? $post_data['expiration_date'] : 0;
		$entry->comment_expiration_date = isset($post_data['comment_expiration_date']) ? $post_data['comment_expiration_date'] : 0;
		$entry->author_id = isset($post_data['member_id']) ? $post_data['member_id'] : $entry->author_id;

		/** ---------------------------------------
		/**  Fill out the other custom fields
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				//set the Posted data
				if (isset($post_data[$val['field_name']])) {
					$entry->{'field_ft_' . $val['field_id']}  = $val['field_fmt'];
					try {
						$entry->{'field_id_' . $val['field_id']}  = ee()->webservice_fieldtype->save($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, false, $entry->entry_id);
					} catch (Throwable $e) {
						return array(
							'message' => $e->getMessage()
						);
					} catch (Exception $e) {
						return array(
							'message' => $e->getMessage()
						);
					}
				}
			}
		}

		/** ---------------------------------------
		/**  Set the new categories
		/** ---------------------------------------*/
		if (isset($post_data['category'])) {
			$categories = ee('Model')->get('Category')
				->filter('cat_id', 'IN', $post_data['category'])
				->filter('group_id', 'IN', $entry->Channel->CategoryGroups->pluck('group_id'))
				->all();
			$entry->Categories = $categories;
		}

		/* -------------------------------------------
		/* 'webservice_update_entry_start' hook.
		/*  - Added: 4.4.2
		*/
		$entry = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('update_entry', $entry, false, $post_data);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/** update the entry
		/** ---------------------------------------*/

		//validate
		$result = $entry->validate();

		//valide?
		if ($result->isValid()) {
			$entry->save();
		} else {
			//generate error
			ee()->lang->loadfile('content');
			foreach ($result->renderErrors() as $field => $error) {
				return array(
					'message' => $field . ': ' . strip_tags($error)
				);
			}
		}

		/** ---------------------------------------
		/**  Post save callback
		/** ---------------------------------------*/
		//@todo fix EE4 $this->fields comes from entry_lib now
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				if (isset($post_data[$val['field_name']])) {
					//validate the data
					ee()->webservice_fieldtype->post_save($post_data[$val['field_name']], $val['field_type'], $val['field_name'], $val, $this->channel, $entry, $entry->entry_id);
				}
			}
		}

		/* -------------------------------------------
		/* 'webservice_update_entry_end' hook.
		/*  - Added: 2.2
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('update_entry_end', $entry, false, $post_data);
		// -------------------------------------------

		/** ---------------------------------------
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_update'];
		$return['metadata'] = array(
			'id' => $entry->entry_id
		);
		$return['success'] = true;
		return $return;
	}

	// ----------------------------------------------------------------

	/**
	 * build a entry data array for a new entry
	 *
	 * @return 	void
	 */
	public function delete_entry($post_data = array())
	{
		//add the post data to the fieldtype
		ee()->webservice_fieldtype->post_data = $post_data;

		/* -------------------------------------------
		/* 'webservice_delete_entry_start' hook.
		/*  - Added: 3.2.1
		*/
		$post_data = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('delete_entry_start', $post_data);

		/** ---------------------------------------
		/**  Validate data
		/** ---------------------------------------*/
		$data_errors = array();

		/** ---------------------------------------
		/**  entry_id is always required for a select
		/** ---------------------------------------*/
		if (!isset($post_data['entry_id']) || $post_data['entry_id'] == '') {
			$data_errors[] = 'entry_id';
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if (!empty($data_errors) || count($data_errors) > 0) {
			//generate error
			return array(
				'message' => 'The following fields are not filled in: ' . implode(', ', $data_errors)
			);
		}

		/** ---------------------------------------
		/**  get the entry data and check if the entry exists
		/** ---------------------------------------*/
		$entry = ee('Model')->get('ChannelEntry')->filter('entry_id', $post_data['entry_id'])->first();

		// anything?
		if (!$entry) {
			//generate error
			return array(
				'message' => 'No Entry found'
			);
		}

		/** ---------------------------------------
		/**  Parse Out Channel Information and check if the use is auth for the channel
		/** ---------------------------------------*/
		$channel_check = $this->_parse_channel($entry->channel_id, __FUNCTION__);
		if (!$channel_check['success']) {
			return $channel_check;
		}

		/** ---------------------------------------
        /**  get the custom fields
        /** ---------------------------------------*/
		ee()->webservice_entry_lib->fetch_custom_channel_fields($entry->channel_id);

		/** ---------------------------------------
		/**  check if the given channel_id match the channel_id of the entry
		/** ---------------------------------------*/
		//@todo, do we need this??
		if ($entry->channel_id != $this->channel->channel_id) {
			//generate error
			return array(
				'message' => 'Specified entry does not appear in the specified channel'
			);
		}

		/** ---------------------------------------
		/**  Call the fieldtype delete function per field
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				$fieldname = 'field_id_' . $val['field_id'];

				$entryArray = $entry->toArray();

				if (array_key_exists($fieldname, $entryArray)) {
					ee()->webservice_fieldtype->delete($entry->{$fieldname}, $val['field_type'], $val['field_name'], $val, $this->channel, $entry->entry_id);
				}
			}
		}

		/** ---------------------------------------
		/**  delete entry
		/** ---------------------------------------*/
		//but first clone it
		$clone_entry = clone $entry;

		//now delete it
		$entry->delete();

		/** ---------------------------------------
		/**  Call the fieldtype post_delete function per field
		/** ---------------------------------------*/
		if (!empty(ee()->webservice_entry_lib->custom_fields)) {
			foreach (ee()->webservice_entry_lib->custom_fields as $key => $val) {
				$fieldname = 'field_id_' . $val['field_id'];
				if (isset($clone_entry->{$fieldname})) {
					ee()->webservice_fieldtype->post_delete($clone_entry->{$fieldname}, $val['field_type'], $val['field_name'], $val, $this->channel, $clone_entry->entry_id);
				}
			}
		}

		/* -------------------------------------------
		/* 'webservice_delete_entry_end' hook.
		/*  - Added: 2.2
		*/
		ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('delete_entry_end', $entry->entry_id);
		// -------------------------------------------

		/** ---------------------------------------*/
		/** return response
		/** ---------------------------------------*/
		$return = \Reinos_webservice_config::service_error()['succes_delete'];
		$return['metadata'] = array(
			'id' => $clone_entry->entry_id
		);
		$return['success'] = true;
		return $return;
	}

	// ----------------------------------------------------------------

	/**
	 * Search a entry
	 * @param  array $post_data
	 * @param string $method
	 * @return array
	 */
	public function search_entry($post_data = array(), $method = 'search_entry')
	{
		//add the post data to the fieldtype
		ee()->webservice_fieldtype->post_data = $post_data;

		/* -------------------------------------------
		/* 'webservice_search_entry_start' hook.
		/*  - Added: 3.2.1
		*/
		$post_data = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('search_entry_start', $post_data);
		$post_data = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('read_entry_start', $post_data);

		/** ---------------------------------------
        /**  Any search param?
        /** ---------------------------------------*/
		if (count($post_data) == 0 || (isset($post_data['site_id']) && count($post_data) == 1)) {
			return array(
				'message' => 'There should at least one search param exists'
			);
		}

		/** ---------------------------------------
		/**  Set the site_id is empty
		/** ---------------------------------------*/
		if (!isset($post_data['site_id']) || $post_data['site_id'] == '') {
			$post_data['site_id'] = 1;
		}

		/** ---------------------------------------
		/**  Basic search on the entry. This return only the entry IDS
		/** ---------------------------------------*/
		ee()->load->library('webservice_entry_lib');
		$search_result_query = ee()->webservice_entry_lib->search_entry($post_data, ee()->session->userdata('username'), $method);

		if (!$search_result_query) {
			return array(
				'message' => 'No Entry found'
			);
		}

		// parse the query
		$search_result = ee()->webservice_entry_lib->parseQuery($search_result_query);

		//we have an arror?
		if (isset($search_result['error'])) {
			return array(
				'message' => $search_result['error']
			);
		}

		// any result?
		if (!$search_result) {
			return array(
				'message' => 'No Entry found'
			);
		} else {
			/* -------------------------------------------
			/* 'webservice_search_entry_end' hook.
			/*  - Added: 2.2
			*/
			$search_result = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('search_entry_end', $search_result, false, $post_data);
			$search_result = ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Helper')->add_hook('read_entry_end', $search_result, false, $post_data);
			// -------------------------------------------

			/** ---------------------------------------
			/** Lets collect all the entry_ids so we can return
			/** ---------------------------------------*/
			$entry_ids = array();
			foreach ($search_result as $row) {
				$entry_ids[] = $row['entry_id'];
			}

			/** ---------------------------------------
			/** return response
			/** ---------------------------------------*/
			$return = \Reinos_webservice_config::service_error()['succes_read'];
			$return['metadata'] = array(
				'id' => implode('|', $entry_ids),
				'limit' => ee()->webservice_entry_lib->limit,
				'offset' => ee()->webservice_entry_lib->offset,
				'total_results' => ee()->webservice_entry_lib->total_results,
				'absolute_results' => ee()->webservice_entry_lib->absolute_results
			);
			$return['success'] = true;
			$return['data'] = isset($post_data['return']) && $post_data['return'] === 'meta' ? array() : $search_result;

			return $return;
		}
	}

	// ----------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// ----------------------------------------------------------------

	/**
	 * Parses out received channel parameters
	 *
	 * @param string $channel_id
	 * @param string $method
	 * @internal param $int
	 * @return array
	 */
	private function _parse_channel($channel_id = '', $method = '')
	{
		$channel = ee('Model')->get('Channel')->filter('site_id', ee()->config->item('site_id'));

		if (is_numeric($channel_id)) {
			$channel->filter('channel_id', $channel_id);
		} else {
			$channel->filter('channel_name', $channel_id);
		}

		$channel = $channel->first();

		//no result?
		if (!$channel) {
			return array(
				'success' => false,
				'message' => 'Given channel does not exist'
			);
		}

		//channel data array
		$this->channel = false;

		// check if the channel_id is assigned to the user
		// Only do this if there is no free access
		if (ee(REINOS_WEBSERVICE_SERVICE_NAME . ':Permissions')->has_free_access($method, ee()->session->userdata('username')) == 0) {
			if (!array_key_exists($channel->channel_id, ee()->session->userdata('assigned_channels')) && ee()->session->userdata('group_id') != '1') {
				//no rights to the channel
				return array(
					'success' => false,
					'message' => 'You are not authorized to use this channel'
				);
			}
		}

		//assign to global
		$this->channel = $channel;

		//get the custom fields, this is done in the construct of the webservice_entry_lib
		ee()->load->library('webservice_entry_lib');

		//everything is ok
		return array('success' => true);
	}

	// ----------------------------------------------------------------

	//validate dates
	function validate_dates($dates = array('entry_date', 'edit_date', 'expiration_date', 'comment_expiration_date'), &$post_data = array())
	{

		//validate the date if needed
		$validate_dates = array();

		//loop over the default dates
		foreach ($dates as $date) {
			//no date set?
			if (!isset($post_data[$date]) or !$post_data[$date]) {
				$post_data[$date] = 0;
			}

			//otherwise save it, and validate it later
			else {
				$validate_dates[] = $date;
			}
		}

		//validate the dates
		foreach ($validate_dates as $date) {
			if (!is_numeric($post_data[$date]) && trim($post_data[$date])) {
				$post_data[$date] = ee()->localize->string_to_timestamp($post_data[$date]);
			}

			if ($post_data[$date] === FALSE) {
				//generate error
				return array(
					'message' => 'the field ' . $date . ' is an invalid date.'
				);
			}

			if (isset($post_data['revision_post'][$date])) {
				$post_data['revision_post'][$date] = $post_data[$date];
			}
		}

		return true;
	}
}
