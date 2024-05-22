<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Smart_members_model extends CI_Model
{

	/* Important globel variables */ 
	public $site_id;
	function __construct()
	{

		/*define site id*/
		$this->site_id = $this->config->item("site_id");
	}

	/*Overall settings of smart members*/
	function getFormSettings()
	{

		if($this->db->table_exists("smart_members_settings"))
        {

			$this->db->select('*');
			$this->db->from('smart_members_settings');
			$this->db->limit(1);

			$data = $this->db->get();
			
			if($data->num_rows > 0)
			{
				$selectWrapper = array();
				$selectWrapper = $data->result_array();
				return $selectWrapper[0];
			}
			else
			{
				return false;
			}

		}

	}

	/*Get recaptcha secret from DB*/
	function getReCaptchaSecret()
	{

		$this->db->select('recaptcha_secret');
		$this->db->from('smart_members_settings');
		$this->db->limit(1);

		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			return $data->row("recaptcha_secret");
		}
		else
		{
			return false;
		}

	}

	/**
	* Get member groups from DB
	* @param $where_param to filter data with group IDs
	**/
	function getMemberGroups($where_param = "")
	{	

		if(SM_APP_VER_L6){
			//ee5
			$this->db->select('group_id,group_title');
			$this->db->from('member_groups');
		}else{
			//ee6
			$this->db->select('role_id AS group_id, name AS group_title');
			$this->db->from('roles');
		}

		if($where_param != "")
		{	
			$this->db->where_in( (SM_APP_VER_L6) ? 'group_id' : 'role_id' ,$where_param);
			if(SM_APP_VER_L6){
				$this->db->where('site_id',ee()->config->item('site_id'));
			}
		}

		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			return $data->result_array();
		}
		else
		{
			return false;
		}

	}

	/**
	* Get member fields
	* @param $where_param to filter data with
	**/
	function getMemberFields($where_param = "")
	{

		// $this->db->select('m_field_id,m_field_name,m_field_label,m_field_list_items');
		$this->db->select('*');
		$this->db->from('member_fields');

		if($where_param != "")
		{
			$this->db->where($where_param);
		}

		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			return $data->result_array();
		}
		else
		{
			return false;
		}

	}

	/*Get Templates*/
	function templates() 
	{

		$this->db->order_by('group_name','asc');
		
		$this->db->select('t.template_id, tg.group_name, t.template_name');
		$this->db->from('templates t');
		
		$this->db->join('template_groups tg','tg.group_id = t.group_id');
		
		$this->db->not_like('template_name','.','after');
		$this->db->where('t.template_type', 'webpage');
		
		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			return $data->result_array();
		}
		else
		{
			return false;
		}

	}

	/**
	* Update query of basic setting forms
	* @param $value (ID of setting row)
	**/
	function updateBasicForm($value)
	{
		$this->db->where('id', $value['id']);
		$this->db->update('smart_members_settings', $value);
	}

	/**
	* Get dynamic fields
	* @param $member_id (ID of Current member)
	**/
	function dynamicMemberFields($member_id='')
	{

		//$this->db->select('mf.m_field_id, mf.m_field_name, mf.m_field_label, mf.m_field_description, mf.m_field_type, mf.m_field_list_items');
		$this->db->select('mf.m_field_name');
		$this->db->from('member_fields mf');

		$data = $this->db->get();

		if($data->num_rows == 0)
		{
			return false;
		}

		$ret = array_map (function($value){
			return $value['m_field_name'];
		} , $data->result_array());

		unset($data);
		return $ret;
	}

	/**
	* Get dynamic fields
	* @param $member_id (ID of Current member)
	**/
	function dynamicMemberFieldsWithID($reverse = false)
	{

		/*if(! ee()->db->field_exists('m_sm_settings', 'member_fields'))
        {
            $fields = array(
                'm_sm_settings'       => array(
                    'type' => 'mediumtext',
                    'null' => TRUE
                    )
                );
            ee()->dbforge->add_column('member_fields', $fields);
        }*/

		//$this->db->select('mf.m_field_id, mf.m_field_name, mf.m_field_label, mf.m_field_description, mf.m_field_type, mf.m_field_list_items');
		$this->db->select('m_field_id, m_field_name, m_field_label, m_field_type, m_sm_settings');
		$this->db->from('member_fields');

		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}

		$result = $get->result_array();
		$ret = array();
		for ($i = 0; $i < count($result); $i++)
		{
			$settings = @unserialize($result[$i]['m_sm_settings']);
			if($reverse == true)
			{
				$ret[$result[$i]['m_field_name']]['id'] 	= $result[$i]['m_field_id'];
				$ret[$result[$i]['m_field_name']]['label'] 	= $result[$i]['m_field_label'];
				$ret[$result[$i]['m_field_name']]['name'] 	= $result[$i]['m_field_name'];
				if(isset($settings['field_type']) && $settings['field_type'] != "")
				{
					$ret[$result[$i]['m_field_name']]['type'] 	= $settings['field_type'];
				}
				else
				{
					$ret[$result[$i]['m_field_name']]['type'] 	= $result[$i]['m_field_type'];
				}
			}
			else
			{
				$ret[$result[$i]['m_field_id']]['id'] 		= $result[$i]['m_field_id'];
				$ret[$result[$i]['m_field_id']]['label'] 	= $result[$i]['m_field_label'];
				$ret[$result[$i]['m_field_id']]['name'] 	= $result[$i]['m_field_name'];
				if(isset($settings['field_type']) && $settings['field_type'] != "")
				{
					$ret[$result[$i]['m_field_id']]['type'] 	= $settings['field_type'];
				}
				else
				{
					$ret[$result[$i]['m_field_id']]['type'] 	= $result[$i]['m_field_type'];
				}
			}
		}
		unset($data);
		unset($result);

		return $ret;

	}

	/**
	* Get member field
	* @param $field (name of field)
	* @param $where (filter data with where)
	**/
	function getMemberFieldFromEmail($field = "", $where = "")
	{

		$this->db->select($field);
		$this->db->from('members');
		
		if($where != "")
		{
			$this->db->where($where);
		}
		$get = $this->db->get();
		if($get->num_rows == 0) 
		{
			return false;
		}

		return $get->row($field);

	}

	/**
	* Get static member fields
	* @param $where (filter the data with conditions)
	* @param $select (A select query)
	* @param $member_id (ID of current member)
	* @return Array of member data
	**/
	public function memberStaticFields($where = "", $select = "", $member_id = "")
	{

		if($where == "")
		{
			return false;
		}

		if($select == "")
		{
			$select = "*";
		}

		$this->db->select($select);
		$this->db->from('members');
		
		if($where != "")
		{
			$this->db->where($where);
		}
		
		if($member_id != "")
		{
			$this->db->where_not_in('member_id', $member_id);
		}

		$data = $this->db->get();

		if($data->num_rows > 0)
		{
			return $data->result_array();
		}
		else
		{
			return false;
		}

	}

	function allStaticFields()
	{

		$allFields = ee()->db->list_fields('members');

		$allFields[] = "recaptcha";
		$allFields[] = "password_confirm";
		$allFields[] = "captcha";
		$allFields[] = "auto_login";
		$allFields[] = "accept_terms";
		$allFields[] = "current_password";
		$allFields[] = "group_title";

		return $allFields;

	}

	/**
	* Save the pending users to database to send them mail when they active
	* @param $member_id (ID of current member)
	* @param $group_id (ID of GROUP)
	**/
	function saveMemberForWelcomeEmail($member_id,$group_id)
	{

		$this->db->select('id');
		$this->db->from('smart_members_email_list');
		$this->db->where('member_id', $member_id);

		$data = $this->db->get();

		if($data->num_rows == 0)
		{

			$insert_basic_settings = array(
				'member_id'		=> $member_id, 
				'group_id'      => $group_id, 
				'email_sent'    => "no", 
				);

			$this->db->insert('smart_members_email_list', $insert_basic_settings);

		}

	}

	/*List of members pending sent the welcome email*/
	function getMembersFromMailingList()
	{

		$this->db->select('m.member_id, sme.group_id');
		$this->db->from('members m');
		$this->db->join('smart_members_email_list sme','m.member_id = sme.member_id');
		$this->db->where(array('sme.email_sent' => 'no'));

		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			return $data->result_array();
		}
		else
		{
			return false;
		}

	}

	/**
	* Update mailing list after send confirmation email
	* @param $change_ids (IDs of members)
	**/
	function updateMailingList($change_ids)
	{

		$this->db->where_in('member_id', $change_ids);
		$this->db->update('smart_members_email_list', array('email_sent' => 'yes'));

		return $this->db->affected_rows();

	}

	/**
	* Update member group if selected some other group
	* @param $member_id (Current member ID)
	* @param $group_id (Group ID)
	* @return Afftected rows
	**/
	function updateMemberGroup($member_id, $group_id)
	{

		$this->db->where('member_id',$member_id);
		$this->db->update('members',array( SM_APP_VER_L6 ? 'group_id' : 'role_id' => $group_id)); //ee6

		return $this->db->affected_rows();

	}

	/**
	* remove old reset password codes from DB
	* @param $member_id (Current member ID)
	**/
	function cleanOldResetCodes($member_id)
	{

		$this->db->where('member_id', $member_id);
		$this->db->or_where('date < UNIX_TIMESTAMP()-7200');
		$this->db->delete('reset_password');

	}

	/**
	* Generate new reset code and save in DB
	* @param $member_id (Current member ID)
	* @return Reset code to send the user in EMail
	**/
	function generateResetCode($member_id)
	{
		$resetcode = strtolower(ee()->functions->random('alpha',10));
		$data = array(
			'member_id' => $member_id,
			'resetcode' => $resetcode,
			'date' => ee()->localize->now,
			);

		$this->db->insert('reset_password', $data);

		return $resetcode;

	}

	/**
	* Get template path from requested template ID
	* @param $template_id (ID of template to get path of)
	* @return String of template path
	**/
	function getTemplatePath($template_id)
	{

		$this->db->select('t.template_id, t.group_id, t.template_name,tg.group_name'); 
		$this->db->from('templates t');
		$this->db->join('template_groups tg', 'tg.group_id=t.group_id');
		$this->db->where('t.template_id',$template_id);

		$result = $this->db->get();

		if($result->num_rows > 0)
		{
			$selectWrapper = array();
			$selectWrapper = $result->result_array();
			return $selectWrapper[0]['group_name'] .'/' . $selectWrapper[0]['template_name'];
		}
		else
		{
			return "";	
		}
		
	}

	/**
	* Get template path from requested template ID
	* @param $template_id (ID of template to get path of)
	* @return String of template path
	**/
	function identifyResetToken($resetcode)
	{

		$this->db->select('reset_id');
		$this->db->from('reset_password');
		$this->db->where('resetcode',$resetcode);

		if($this->db->get()->num_rows > 0)
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}

	}

	/**
	* Get data from reset_password table
	* @param $field (list of select item)
	* @param $where (filter data by condition)
	* @return String of result or array of row
	**/
	function resetPasswordFields($field = "", $where = "")
	{

		$this->db->select($field);
		$this->db->from('reset_password');
		
		if($where != "")
		{
			$this->db->where($where);
		}
		
		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}

		if($field == "*")
		{
			return $get->result_array();
		}
		else
		{
			return $get->row($field);
		}

	}

	/*Update online status of user, the function to be called after creating session of user*/
    public function updateOnlineUserStats()
    {
    	
        if (ee()->config->item('enable_online_user_tracking') == 'n' OR ee()->config->item('disable_all_tracking') == 'y')
        {
            return;
        }

        $cuttime = ee()->localize->now - (15 * 60);
        $anon = (ee()->input->post('anon') == 1) ? '' : 'y';

        $in_forum = (ee()->input->get_post('FROM') == 'forum') ? 'y' : 'n';

        $escaped_ip = $this->db->escape_str(ee()->input->ip_address());

        $this->db->where('site_id', ee()->config->item('site_id'))
                     ->where("(ip_address = '".$escaped_ip."' AND member_id = '0')", '', false)
                     ->or_where('date < ', $cuttime)
                     ->delete('online_users');

        $data = array(
                        'member_id'		=> ee()->session->userdata('member_id'),
                        'name'			=> (ee()->session->userdata('screen_name') == '') ? ee()->session->userdata('username') : ee()->session->userdata('screen_name'),
                        'ip_address'	=> ee()->input->ip_address(),
                        'in_forum'		=> $in_forum,
                        'date'			=> ee()->localize->now,
                        'anon'			=> $anon,
                        'site_id'		=> ee()->config->item('site_id')
                    );

        $this->db->where('ip_address', ee()->input->ip_address())
                     ->where('member_id', $data['member_id'])
                     ->update('online_users', $data);
    }

    /**
	* Get member preferences
	* @param $site_id (Current site ID)
	* @return site member pref row
	**/
    function getMemberPreferences($site_id)
    {

    	// for ee6
    	if (ee()->db->table_exists('config'))
		{
			$member_prefs = ee('Model')->get('Config')
				->filter('site_id', $site_id)
				->filter('key', 'IN', ee()->config->divination('member'))
				->all()
				->getDictionary('key', 'value');
		}
		else
		{
			// for ee5
			$sites = ee()->db->get_where('sites', ['site_id' => $site_id]);
			$site = $sites->result_array();
			$member_prefs = unserialize(base64_decode($site[0]['site_member_preferences']));
		}
		
		return $member_prefs;	

    }

    /**
	* Update member preferences
	* @param $data (Array of data to be updated)
	* @param $site_id (Current site ID)
	**/
    function updateMemberPreferences($data, $site_id)
    {

    	// for ee6
    	if (ee()->db->table_exists('config'))
		{

			$data = unserialize(base64_decode($data));

			foreach ($data as $key => $value) {

				$member_prefs = ee('Model')->get('Config')->filter('site_id', $site_id)->filter('key', $key)->first();
	    	
		    	if($member_prefs)
		        {
		            $member_prefs->value = $value;
		            $member_prefs->save();
		        }

			}

		}
		else{
			//for ee5
	    	$this->db->where('site_id', $site_id);
	    	$this->db->update('sites', array('site_member_preferences'=>$data));
		}

    }
    
    /**
	* Fetch Group IDs from database
	* @return Results array
	**/
    function allGroupIds($all_group_ids)
    {

    	$this->db->select( SM_APP_VER_L6 ? 'group_id' : 'role_id' ); // ee6
    	$this->db->from( SM_APP_VER_L6 ? 'member_groups' : 'roles' ); //ee6

    	// solved issue of those group is not accessed by the edit profile form
    	if($all_group_ids == true){
    		$group_id = array();
    	}else{
    		$group_id = array('2','3','4');	  	
    	}

    	if(count($group_id)){
			$this->db->where_not_in(SM_APP_VER_L6 ? 'group_id' : 'role_id', $group_id);
    	}
    	
		$data = $this->db->get();
		
		if($data->num_rows > 0)
		{
			$temp = $data->result_array();
			$ret = array();
			for ($i = 0; $i < count($temp); $i++)
			{
				$ret[] = $temp[$i][SM_APP_VER_L6 ? 'group_id' : 'role_id'];
			}

			unset($temp);
			unset($data);
			return $ret;
		}
		else
		{
			return false;
		}

    }

    /**
	* Get action ID from method
	* @param $method (To find the action ID of perticular method)
	* @return Action ID
	**/
    function getActionID($method)
    {

    	$this->db->limit(1);
    	$this->db->select('action_id');
    	$this->db->from('actions');
    	$this->db->where('method', $method);
    	
    	return $this->db->get()->row("action_id");
    	
    }

    /**
	* Patch if comment module is installed and user has comments in.
	* @param $member_id (Member ID of USER)
	* @param $screen_name (New screen name to be replace to comment module screen names for the user.)
	* @return 
	**/
    function updateCommentsScreenNames($member_id, $screen_name)
    {

    	$this->db->select('module_name');
        $this->db->where('module_name', 'Comment');
        $query = $this->db->get('modules');

        if ($query->num_rows() > 0)
        {
            $this->db->where('author_id', $member_id);
            $this->db->update(
                'comments', 
                array('name' => $screen_name)
            );
        }
    	
    	return true;

    }

    /**
	* Patch if Forum module is installed and user has Forums in.
	* @param $member_id (Member ID of USER)
	* @param $screen_name (New screen name to be replace to Forum module screen names for the user.)
	* @return 
	**/
    function updateForumScreenNames($member_id, $screen_name)
    {
    	
    	if(ee()->config->item('forum_is_installed') == "y")
    	{

    		$this->db->where('forum_last_post_author_id', $member_id);
    		$this->db->update(
    			'forums',
    			array('forum_last_post_author' => $screen_name)
    			);

    		$this->db->where('mod_member_id', $member_id);
    		$this->db->update(
    			'forum_moderators',
    			array('mod_member_name' => $screen_name)
    			);

    	}
   	
    	return true;

    }
    
}