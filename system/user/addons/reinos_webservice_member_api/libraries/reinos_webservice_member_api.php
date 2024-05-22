<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2020 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2020 Reinos.nl Internet Media
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

class Reinos_webservice_member_api
{
	//-------------------------------------------------------------------------

	/**
     * Constructor
    */
	public function __construct()
	{
		// load the stats class because this is not loaded because of the use of the extension
		ee()->load->library('stats');
		ee()->load->library('logger');

		/** ---------------------------------------
		/** load the models
		/** ---------------------------------------*/
		//ee()->load->model('member_model');

		//set the default data
		$this->_default_data();
	}

	//-------------------------------------------------------------------------

	/**
     * create_member
    */
	public function create_member($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new member, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_create_members') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate members'
			);
		}

		/** ---------------------------------------
		/**  allow member registration
		/** ---------------------------------------*/
		if (ee()->config->item('allow_member_registration') == 'n')
		{
			return array(
				'message' => 'Member Registration has been disabled'
			);
		}

        /** ---------------------------------------
        /**  catch group ID
        /** ---------------------------------------*/
        if(!isset($post_data['group_id']) || $post_data['group_id'] == '') {
            return array(
                'message' => 'Missing group_id param'
            );
        }

		/** ---------------------------------------
		/**  Restrict access to the Super Admin group
		/** ---------------------------------------*/
		if ($post_data['group_id'] == 1 && ee()->config->item('group_id') != 1)
		{
			return array(
				'message' => 'You dont have access to create a member for group 1'
			);
		}

		/** ---------------------------------------
		/**  Set the defaul globals
		/** ---------------------------------------*/
		$default = array(
			'username', 'password', 'password_confirm', 'email',
			'screen_name', 'url', 'location'
		);

		//assign them to a val
		foreach ($default as $val)
		{
			if ( ! isset($post_data[$val])) $post_data[$val] = '';
		}

		//screen name is the same as username if empty
		if ($post_data['screen_name'] == '')
		{
			$post_data['screen_name'] = $post_data['username'];
		}

		/** ---------------------------------------
		/**  Create member object
		/** ---------------------------------------*/
		$member = ee('Model')->make('Member');
		$member->group_id = $post_data['group_id']; // Needed to get member fields at the moment

		// Separate validator to validate confirm_password
		$validator = ee('Validation')->make();
		$validator->setRules(array(
			'confirm_password' => 'matches[password]'
		));

		/** ---------------------------------------
		/**  Set the custom fields
		/** ---------------------------------------*/
		foreach ($member->getDisplay()->getFields() as $field)
		{
			$post_data['m_field_id_'.$field->get('m_field_id')] = isset($post_data[$field->get('m_field_name')]) ? $post_data[$field->get('m_field_name')] : '';
		}

		/** ---------------------------------------
		/**  Set the data
		/** ---------------------------------------*/
		$member->set($post_data);

		// Set some other defaults
		$member->screen_name = $post_data['screen_name'];
		$member->ip_address = ee()->input->ip_address();
		$member->join_date = ee()->localize->now;
		$member->language = ee()->config->item('deft_lang');
		$member->timezone = ee()->config->item('default_site_timezone');
		$member->date_format = ee()->config->item('date_format');
		$member->time_format = ee()->config->item('time_format');
		$member->include_seconds = ee()->config->item('include_seconds');

		$result = $member->validate();
		$password_confirm = $validator->validate($post_data);

		// Add password confirmation failure to main result object
		if ($password_confirm->failed())
		{
			$rules = $password_confirm->getFailed('confirm_password');
			$result->addFailed('confirm_password', $rules[0]);
		}

		if ($result->isValid())
		{
			// Now that we know the password is valid, hash it
			ee()->load->library('auth');
			$hashed_password = ee()->auth->hash_password($member->password);
			$member->password = $hashed_password['password'];
			$member->salt = $hashed_password['salt'];

			// -------------------------------------------
			// 'cp_members_member_create_start' hook.
			//  - Take over member creation when done through the CP
			//  - Added 1.4.2
			//
			ee()->extensions->call('cp_members_member_create_start');
			if (ee()->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------

			$member->save();

			// -------------------------------------------
			// 'cp_members_member_create' hook.
			//  - Additional processing when a member is created through the CP
			//
			ee()->extensions->call('cp_members_member_create', $member->getId(), $member->getValues());
			if (ee()->extensions->end_script === TRUE) return;
			//
			// -------------------------------------------

			ee()->logger->log_action(lang('new_member_added').NBS.$member->username);
			ee()->stats->update_member_stats();
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

		/** ---------------------------------------
		/**  RIP FROM mod.member_register
		/** ---------------------------------------*/

		// We generate an authorization code if the member needs to self-activate
		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$member->authcode = ee()->functions->random('alnum', 10);
			$member->save();
		}

		$data = $member->toArray();

		// Send admin notifications
		if (ee()->config->item('new_member_notification') == 'y' &&
			ee()->config->item('mbr_notification_emails') != '')
		{
			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			$swap = array(
				'name'					=> $name,
				'site_name'				=> stripslashes(ee()->config->item('site_name')),
				'control_panel_url'		=> ee()->config->item('cp_url'),
				'username'				=> $data['username'],
				'email'					=> $data['email']
			);

			$template = ee()->functions->fetch_email_template('admin_notify_reg');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			// Remove multiple commas
			$notify_address = reduce_multiples(ee()->config->item('mbr_notification_emails'), ',', TRUE);

			// Send email
			ee()->load->helper('text');

			ee()->load->library('email');
			ee()->email->wordwrap = true;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($notify_address);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();
		}

		// -------------------------------------------
		// 'member_member_register' hook.
		//  - Additional processing when a member is created through the User Side
		//  - $member_id added in 2.0.1
		//
		ee()->extensions->call('member_member_register', $data, $member->member_id);
		if (ee()->extensions->end_script === TRUE) return;
		//
		// -------------------------------------------

		// Send user notifications
		if (ee()->config->item('req_mbr_activation') == 'email')
		{
			$action_id  = ee()->functions->fetch_action_id('Member', 'activate_member');

			$name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

			//$board_id = ($post_data['board_id'] !== FALSE && is_numeric($post_data['board_id'])) ? $post_data['board_id'] : 1;

			//$forum_id = ($post_data['FROM'] == 'forum') ? '&r=f&board_id='.$board_id : '';
			$forum_id = '';

			//$add = ($mailinglist_subscribe !== TRUE) ? '' : '&mailinglist='.$post_data['mailinglist_subscribe'];
			$add = '';

			$swap = array(
				'name'				=> $name,
				'activation_url'	=> ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'].$forum_id.$add,
				'site_name'			=> stripslashes(ee()->config->item('site_name')),
				'site_url'			=> ee()->config->item('site_url'),
				'username'			=> $data['username'],
				'email'				=> $data['email']
			);

			$template = ee()->functions->fetch_email_template('mbr_activation_instructions');
			$email_tit = $this->_var_swap($template['title'], $swap);
			$email_msg = $this->_var_swap($template['data'], $swap);

			// Send email
			ee()->load->helper('text');

			ee()->load->library('email');
			ee()->email->wordwrap = true;
			ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
			ee()->email->to($data['email']);
			ee()->email->subject($email_tit);
			ee()->email->message(entities_to_ascii($email_msg));
			ee()->email->Send();
		}

		/* -------------------------------------------
		/* 'create_member_end' hook.
		/*  - Added: 3.5
		*/
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('create_member_end', $member->member_id);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/**  Return the result
		/** ---------------------------------------*/
		return array(
			'message' => 'Successfully created',
			'metadata' => array(
				'id' => $member->member_id,
			),
			'success' => true
		);
	}

	//-------------------------------------------------------------------------

	/**
     * read_member
    */
	public function read_member($post_data = array())
	{
		/** ---------------------------------------
		/**  can we update member profiles, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_view_profiles') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate members'
			);
		}

		/** ---------------------------------------
		/**  Validate data
		/** ---------------------------------------*/
		$data_errors = array();

		/** ---------------------------------------
		/**  member_id is for a insert always required
		/** ---------------------------------------*/
		if(!isset($post_data['member_id']) || $post_data['member_id'] == '') {
			$data_errors[] = 'member_id';
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if(!empty($data_errors) || count($data_errors) > 0)
		{
			//generate error
			return array(
				'message' => 'The following fields are not filled in: '.implode(', ',$data_errors)
			);
		}

		/** ---------------------------------------
		/** Get the member
		/** ---------------------------------------*/
		$member = ee('Model')->get('Member')->filter('member_id', $post_data['member_id']);

		/** ---------------------------------------
		/** Any result
		/** ---------------------------------------*/
		if($member->count() == 0)
		{
			return array(
				'message' => 'No member found'
			);
		}

		//get the first one
		$member = $member->first();

		//set the data
		$member_data = $member->toArray();

		//filter data
		$member_data = $this->filter_memberdata($member_data);

		//fetch the custom fields
        foreach($member->getCustomFields() as $field) {
            $member_data[$field->getShortName()] = $field->getData();
        }

		//also get the entries written by this user
		$member_data['entries'] = $member->AuthoredChannelEntries->pluck('entry_id');

		/* -------------------------------------------
		/* 'read_member_end' hook.
		/*  - Added: 3.5
		*/
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('read_member_end', $member_data);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'message' => 'Successfully readed',
			'metadata' => array(
				'id' => $member_data['member_id']
			),
			'data' => array($member_data),
			'success' => true
		);
	}

    //-------------------------------------------------------------------------

    /**
     * search_member
     */
    public function search_member($post_data = array())
    {
        /** ---------------------------------------
        /**  can we update member profiles, do we have the right for it
        /** ---------------------------------------*/
        if(ee()->session->userdata('can_view_profiles') != 'y')
        {
            return array(
                'message' => 'You have no right to administrate members'
            );
        }


        /** ---------------------------------------
        /** Get the member
        /** ---------------------------------------*/
        $member = ee('Model')->get('Member');

        // search params
        foreach(array('email', 'screen_name', 'username', 'member_id', 'group_id') as $value)
        {
            if(isset($post_data[$value]))
            {
                $member->filter($value, $post_data[$value]);
            }
        }

        /** ---------------------------------------
        /** Any result
        /** ---------------------------------------*/
        if($member->count() == 0)
        {
            return array(
                'message' => 'No member found'
            );
        }

        //get the first one
        $members = $member->all();

        $return = array();
        $member_ids = array();
        foreach($members as $member)
        {
            //set the data
            $member_data = $this->filter_memberdata($member->toArray());

            // fetch the custom fields
            foreach($member->getCustomFields() as $field) {
                $member_data[$field->getShortName()] = $field->getData();
            }

            $member_ids[] = $member_data['member_id'];

            //also get the entries written by this user
            $member_data['entries'] = $member->AuthoredChannelEntries->pluck('entry_id');

            $return[] = $member_data;
        }

        /* -------------------------------------------
        /* 'read_member_end' hook.
        /*  - Added: 3.5
        */
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('search_member_end', $return);
        /** ---------------------------------------*/

        /** ---------------------------------------
        /**  We got luck, it works
        /** ---------------------------------------*/
        return array(
            'message' => 'Successfully searched',
            'metadata' => array(
                'id' => implode('|', $member_ids)
            ),
            'data' => $return,
            'success' => true
        );
    }


    //-------------------------------------------------------------------------

	/**
     * update_member
    */
	public function update_member($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new member, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_edit_members') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate members'
			);
		}

		/** ---------------------------------------
		/**  Member_id is for a insert always required
		/** ---------------------------------------*/
		$data_errors = array();
		if(!isset($post_data['member_id']) || $post_data['member_id'] == '') {
			$data_errors[] = 'member_id';
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if(!empty($data_errors) || count($data_errors) > 0)
		{
			//generate error
			return array(
				'message' => 'The following fields are not filled in: '.implode(', ',$data_errors)
			);
		}

		/** ---------------------------------------
		/**  Check if the member exists
		/** ---------------------------------------*/
		$member = ee('Model')->get('Member')->filter('member_id', $post_data['member_id']);
		if($member->count() == 0)
		{
			//generate error
			return array(
				'message' => 'No member found'
			);
		}

		//get the first member
		$member = $member->first();

		/** ---------------------------------------
		/**  Set the custom fields
		/** ---------------------------------------*/
		foreach ($member->getDisplay()->getFields() as $field)
		{
			$post_data['m_field_id_'.$field->get('m_field_id')] = isset($post_data[$field->get('m_field_name')]) ? $post_data[$field->get('m_field_name')] : $member->{'m_field_id_'.$field->get('m_field_id')};
		}


		//set the fields that can be updated
		$fields = array(
			'url',
			'location',
			'occupation',
			'interests',
			'bday_y',
			'bday_m',
			'bday_d',
			'aol_im',
			'yahoo_im',
			'msn_im',
			'icq',
			'bio',
			'signature',
			'avatar_filename',
			'avatar_width',
			'avatar_height',
			'photo_filename',
			'photo_width',
			'photo_height',
			'sig_img_filename',
			'sig_img_width',
			'sig_img_height',
			'language',
			'timezone',
			'profile_theme',
			'forum_theme',
			'notepad'
		);

		$data = array();

		//get the memberdata
		$member_data = $member->toArray();;

		foreach ($fields as $val)
		{
			$member_data[$val] = isset($member_data[$val]) ? $member_data[$val] : '';
			$data[$val] = (isset($post_data[$val])) ? ee()->security->xss_clean($post_data[$val]) : $member_data[$val];
			unset($post_data[$val]);
		}

		ee()->load->helper('url');
		$data['url'] = preg_replace('/[\'"]/is', '', $data['url']);
		$data['url'] = prep_url($data['url']);

		if (is_numeric($data['bday_d']) AND is_numeric($data['bday_m']))
		{
			ee()->load->helper('date');
			$year = ($data['bday_y'] != '') ? $data['bday_y'] : date('Y');
			$mdays = days_in_month($data['bday_m'], $year);

			if ($data['bday_d'] > $mdays)
			{
				$data['bday_d'] = $mdays;
			}
		}

		/** ---------------------------------------
		/**  Set the data
		/** ---------------------------------------*/
		$member->set($post_data);

		//validate
		$result = $member->validate();

		if ($result->isValid())
		{
			$member->save();
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
		/* 'update_member_end' hook.
		/*  - Added: 3.5
		*/
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('update_member_end', $post_data);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'message' => 'Successfully updated',
			'id' => $post_data['member_id'], //@deprecated
			'metadata' => array(
				'id' => $post_data['member_id']
			),
			'success' => true
		);
	}

	//-------------------------------------------------------------------------

	/**
     * delete_member
    */
	public function delete_member($post_data = array())
	{
		/** ---------------------------------------
		/**  can we add a new channel, do we have the right for it
		/** ---------------------------------------*/
		if(ee()->session->userdata('can_delete_members') != 'y')
		{
			return array(
				'message' => 'You have no right to administrate members'
			);
		}

		/** ---------------------------------------
		/**  member_id is required
		/** ---------------------------------------*/
		$data_errors = array();
		if(!isset($post_data['member_id']) || $post_data['member_id'] == '') {
			$data_errors[] = 'member_id';
		}

		/** ---------------------------------------
		/**  Return error when there are fields who are empty en shoulnd`t
		/** ---------------------------------------*/
		if(!empty($data_errors) || count($data_errors) > 0)
		{
			//generate error
			return array(
				'message' => 'The following fields are not filled in: '.implode(', ',$data_errors)
			);
		}

		/** ---------------------------------------
		/**  we cannot delete our own account
		/** ---------------------------------------*/
		if(ee()->session->userdata('member_id') == $post_data['member_id'])
		{
			return array(
				'message' => 'Cannot delete yourself'
			);
		}

		/** ---------------------------------------
		/**  check the member
		/** ---------------------------------------*/
		$member = ee('Model')->get('Member')->filter('member_id', $post_data['member_id']);
		if($member->count() == 0)
		{
			return array(
				'message' => 'No member found'
			);
		}

		//get the raw data
		$member = $member->first();

		/** ---------------------------------------
		/**  Never delete a super admin
		/** ---------------------------------------*/
		if($member->group_id == 1)
		{
			return array(
				'message' => 'Cannot delete a superadmin'
			);
		}

		/** ---------------------------------------
		/**  Now lets delete the member an get the member_id that can take over the entries
		/** ---------------------------------------*/
		$member->delete();

		// Update
		ee()->stats->update_member_stats();

		/* -------------------------------------------
		/* 'delete_member_end' hook.
		/*  - Added: 3.5
		*/
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('delete_member_end', $post_data['member_id']);
		/** ---------------------------------------*/

		/** ---------------------------------------
		/**  We got luck, it works
		/** ---------------------------------------*/
		return array(
			'message' => 'Successfully deleted',
			'metadata' => array(
				'id' => $post_data['member_id']
			),
			'success' => true
		);
	}


	// ----------------------------------------------------------------

	/**
	 * Only allow save memberdata
	 */
	public function filter_memberdata($data, $delete = array())
	{
		$return = array();

		foreach($this->default as $val)
		{
			if(isset($data[$val]) && !in_array($val, $delete))
			{
				$return[$val] = $data[$val];
			}
		}

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * default_data function.
	 *
	 * @access public
	 * @return void
	 */
	private function _default_data()
	{
		$this->default = array(
			'member_id',
			'group_id',
			'username',
			'screen_name',
			'email',
			'url',
			'location',
			'occupation',
			'interests',
			'bday_d',
			'bday_m',
			'bday_y',
			'aol_im',
			'yahoo_im',
			'msn_im',
			'icq',
			'bio',
			'signature',
			'avatar_filename',
			'avatar_width',
			'avatar_height',
			'photo_filename',
			'photo_width',
			'photo_height',
			'sig_img_filename',
			'sig_img_width',
			'sig_img_height',
			'ignore_list',
			'private_messages',
			'accept_messages',
			'last_view_bulletins',
			'last_bulletin_date',
			'ip_address',
			'join_date',
			'last_visit',
			'last_activity',
			'total_entries',
			'total_comments',
			'total_forum_topics',
			'total_forum_posts',
			'last_entry_date',
			'last_comment_date',
			'last_forum_post_date',
			'last_email_date',
			'in_authorlist',
			'accept_admin_email',
			'accept_user_email',
			'notify_by_default',
			'notify_of_pm',
			'display_avatars',
			'display_signatures',
			'parse_smileys',
			'smart_notifications',
			'language',
			'timezone',
			'time_format',
			'cp_theme',
			'profile_theme',
			'forum_theme',
			'tracker',
			'template_size',
			'notepad',
			'notepad_size',
			'quick_links',
			'quick_tabs',
			'show_sidebar',
			'pmember_id',
			'rte_enabled',
			'rte_toolset_id'
		);
	}

	// --------------------------------------------------------------------

	/**
	 * Replace variables
	 */
	function _var_swap($str, $data)
	{
		if ( ! is_array($data))
		{
			return FALSE;
		}

		foreach ($data as $key => $val)
		{
			$str = str_replace('{'.$key.'}', $val, $str);
		}

		return $str;
	}
}

