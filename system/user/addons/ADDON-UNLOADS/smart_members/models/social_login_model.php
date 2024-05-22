<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Social_login_model extends CI_Model
{

	public $site_id;

	function __construct()
	{
		/*define site id*/
		$this->site_id = $this->config->item("site_id");
	}

	/**
    * Get social media settings from database
    *
    * @param   $id (ID of social media setting row)
    * @return  Array of social settings row
    **/
	function getSocialFormSettings($id = "")
	{

		$this->db->select('*');
		$this->db->from('smart_members_social_settings');
		$this->db->where_not_in('short_name', array('px500', 'soundcloud', 'tumblr'));


		if(! ($id == "" || $id == "all"))
		{
			$this->db->where('id', $id);
		}

		$get = $this->db->get();
		
		if($get->num_rows == 0)
		{
			return false;
		}

		return $get->result_array();

	}

	/**
    * Update social media settings row in DB
    *
    * @param   $key (ID of social media setting row)
    * @param   $value (Array of social settings row)
    **/
	function updateSocialSettingForm($key, $value)
	{
		$this->db->where('id', $key);
		$this->db->update('smart_members_social_settings', $value);
	}

	/**
    * Get provider from DB
    *
    * @param   $providers (Array of provider short names)
    * @return  Array of provider(s)   
    **/
	function getProvidersList($providers)
	{

		$this->db->select('*');
		$this->db->from('smart_members_social_settings');

		$this->db->where(array('key !=' => "", 'secret !=' => ""));

		if(count($providers) > 0)
		{
			$this->db->where_in('short_name', $providers);
		}

		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}
		
		$data = $get->result_array();

		/*Set data with unserialize the setting*/
		for ($i=0; $i < count($data); $i++)
		{ 
			$data[$i]['settings'] = unserialize($data[$i]['settings']);
		}

		return $data;
		
	}

	/**
    * Update member
    *
    * @param   $member_id (ID of member to update the data of)
    * @param   $data (Array of final data to be store in database)
    **/
	function updateMember($member_id, $data)
	{

		$this->db->where('member_id', $member_id);
		$this->db->update('members', $data);

	}

	/**
    * Fetch social login callback URL from DB
    **/
	function slCallbackURL()
	{

		$this->db->limit(1);
		$this->db->select('sl_callback_url');
		$this->db->from('smart_members_settings');

		return $this->db->get()->row('sl_callback_url');

	}

	function checkRowExists($column, $data, $table)
	{
		return $this->db->where($column, $data)->get($table)->num_rows;
	}
}