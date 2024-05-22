<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import_export_model extends CI_Model
{

	/* Important globel variables */ 
	public $site_id;
	function __construct()
	{
		/*Define site ID*/
		$this->site_id = $this->config->item("site_id");
	}

	/**
    * Get list of generated exports
    * @param $offset     (Number of pagination row, Offset of table data)
    * @param $group_id   (Group ID of member)
    * @param $perPage (limit of table data per page)
    **/
    function getExportList($offset, $group_id, $perPage)
    {

        $this->db->select('*');
        // $this->db->from('smart_members_exports');
        $this->db->where('status', 'active');

        if($group_id != 1)
        {
            $this->db->where('type', 'public');
            $this->db->or_where('member_id', ee()->session->userdata('member_id'));
        }

        
        if($offset === "")
        {
            return $this->db->get('smart_members_exports')->num_rows;
        }
        else
        {
            
            $data = $this->db->get('smart_members_exports', $perPage, $offset);
            
            if($data->num_rows > 0)
            {
                return $data->result_array();
            }
            else
            {
                return false;
            }
            
        }

    }

    /**
    * Get list of generated Imports
    * @param $offset     (Number of pagination row, Offset of table data)
    * @param $group_id   (Group ID of member)
    * @param $perPage (limit of table data per page)
    **/
    function getImportList($offset, $group_id, $perPage)
    {

        $this->db->select('*');
        // $this->db->from('smart_members_imports');
        $this->db->where('status', 'active');

        if($group_id != 1)
        {
            $this->db->where('type', 'public');
            $this->db->or_where('member_id', ee()->session->userdata('member_id'));
        }
        
        if($offset === "")
        {
            return $this->db->get('smart_members_imports')->num_rows;
        }
        else
        {
            
            $data = $this->db->get('smart_members_imports', $perPage, $offset);
            
            if($data->num_rows > 0)
            {
                return $data->result_array();
            }
            else
            {
                return false;
            }
            
        }

    }

	/**
    * Get list of possible member groups
    * @param $data (Source of called method)
    **/
	function getMemberGroups($data="")
	{

		//for ee5
		if(SM_APP_VER_L6){
			$this->db->select('group_id, group_title');
			if($data == "social")
			{
				$this->db->where_not_in('group_id', array('1','2','4'));
			}
			$this->db->where('site_id', $this->site_id);
			$this->db->from('member_groups');

			return $this->db->get()->result_array();
		}else{
			//for ee6
			$this->db->select('role_id as group_id, name as group_title');
			if($data == "social")
			{
				$this->db->where_not_in('role_id', array('1','2','4'));
			}
			//$this->db->where('site_id', $this->site_id);
			$this->db->from('roles');

			return $this->db->get()->result_array();
		}

	}

	/* Get list of possible member static fields */
	function getMemberStaticFields()
	{
		
		$data = $this->db->list_fields('members');

		$rel_data = array();

		$i = 0;
		foreach ($data as $key => $value)
		{
			$rel_data[$i]['name'] = $value;
			$rel_data[$i++]['label'] = lang($value);	
			//For EE6: Add additional roles 
			if(!SM_APP_VER_L6){
				if($value == 'role_id'){
					$rel_data[$i]['name'] = 'multiple_role_id';
					$rel_data[$i++]['label'] = lang('multiple_role_id');	
				}
			}
		}
		
		return $rel_data;

	}

	/* Get list of possible member dynamic fields */
	function get_member_dynamic_fields()
	{

		$this->db->select('m_field_id, m_field_name, m_field_label');
		$this->db->from('member_fields');
		
		$data = $this->db->get();

		if($data->num_rows == 0)
		{
			return false;
		}
		else
		{
			return $data->result_array();
		}

	}

	/* Get list of possible member dynamic fields */
	function getMemberDynamicFields($fields = array())
	{

		$this->db->select('m_field_id, m_field_name, m_field_label, m_field_type');
		$this->db->from('member_fields');
		if(count($fields) > 0)
		{
			$this->db->where_in('m_field_id', $fields);
		}

		$data = $this->db->get();

		if($data->num_rows == 0)
		{
			return false;
		}
		else
		{

			$temp = $data->result_array();
			$ret = array();
			foreach ($temp as $key => $value)
			{
				$ret[$value['m_field_id']] = $value;
				unset($ret[$value['m_field_id']]['m_field_id']);
			}
			return $ret;

		}

	}

	/**
    * Check the requested token is exists in database or not
    * @param $token 	(Token of export row)
    **/
	function checkExportToken($token)
	{

		$this->db->select('*');
		$this->db->from('smart_members_exports');
		$this->db->where('token', $token);

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
    * Increase counter of export on every download
    * @param $token (Token of export row)
    **/
	function increaseCounter($token)
	{
		$this->db->where('token', $token);
		$this->db->set('export_counts', '`export_counts` + 1', FALSE);
		$this->db->update('smart_members_exports');
	}

	/**
    * Check the requested token is exists in database or not
    * @param $token (Token of import row)
    **/
	function checkImportToken($token)
	{

		$this->db->select('*');
		$this->db->from('smart_members_imports');
		$this->db->where('token', $token);

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

	function updateBasicImportSettings()
	{
		$data = $this->checkImportToken($_POST['token']);
		if(isset($data) && is_array($data))
		{
			$data = $data[0];
			$data['settings'] 				= unserialize(base64_decode($data['settings']));
			$data['format'] 				= $_POST['format'];
			$data['settings']['filename'] 	= $_POST['filename'];
			$data['settings']				= base64_encode(serialize($data['settings']));
			$this->updateImport($data, $_POST['token']);
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
    * Save import settings to database
    * @param $token (Token of import row)
    **/
	function saveImport($data)
	{
		$this->db->insert('smart_members_imports', $data);
	}

	/**
    * Update import and save new settings to database
    * @param $token (Token of import row)
    **/
	function updateImport($data, $token)
	{
		$this->db->where('token', $token);
		$this->db->update('smart_members_imports', $data);
	}

	/**
    * Count all the results where the key matches
    * @param $key   (name of table field)
    * @param $value (Value of table field)
    **/
	function matchKey($key, $value)
	{

		$this->db->where($key, $value);
		$num = $this->db->count_all_results('members');

		if($num == 0)
		{
			return false;
		}
		else
		{
			return true;
		}

	}

	/**
    * Get all fields from table
    * @param $table_name (Name of table)
    **/
	function listFields($table_name)
	{

		$data = $this->db->list_fields($table_name);
		$temp = array();

		for ($i=0; $i < count($data); $i++)
		{
			$temp[$data[$i]] = "";
		}

		unset($data);
		return $temp;

	}

	/**
    * Get member ID of user from conditional parameter
    * @param $key   (name of table field)
    * @param $value (Value of table field)
    **/
	function getMemberID($key, $value)
	{

		$this->db->select('member_id');
		$this->db->from('members');
		$this->db->where($key, $value);
		$this->db->limit(1);

		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}
		else
		{
			return $get->row('member_id');
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
    * Save export form settings
    * @param $data (Array of export settings)
    **/
    function saveExport($data)
    {
        $this->db->insert('smart_members_exports', $data);
    }

    /**
    * Update export form settings
    * @param $data (Array of export settings)
    **/
    function updateExport($data, $token)
    {
        $this->db->where('token', $token);
        $this->db->update('smart_members_exports', $data);
    }

    /**
    * Delete the export by token
    * @param $token (Token of export row)
    **/
    function deleteExport($removeIds)
    {
        $this->db->where_in('id', $removeIds);
        $this->db->delete('smart_members_exports');
    }

    /**
    * Delete the import by token
    * @param $token (Token of import row)
    **/
    function deleteImport($removeIds)
    {
        $this->db->where_in('id', $removeIds);
        $this->db->delete('smart_members_imports');
    }
	
}