<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member_fields_model extends CI_Model
{

	function __construct()
	{

	}

    /**
    * Get all possible member fields
    * @param $status (label to be true or false)
    **/
	function getMemberFields($status = false)
	{

		$this->db->select('*');
		$this->db->from('member_fields');
		$this->db->order_by('m_field_order', 'asc');
		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}
		
		$data = $get->result_array();

        if($status == true)
        {
            
            /*Unserialize the settings and return the final array*/
            for ($i=0; $i < count($data); $i++)
            {
            
                if(isset($data[$i]['m_sm_settings']) && $data[$i]['m_sm_settings'] != NULL && $data[$i]['m_sm_settings'] != "")
                {
                    $data[$i]['m_sm_settings'] = unserialize($data[$i]['m_sm_settings']);
                }

                $data[$data[$i]['m_field_name']]  = $data[$i];
                unset($data[$i]);

            }

        }

        return $data;

	}

    /**
    * List all possible directories created by use in file manager
    * @param $status (label to be true or false)
    **/
    function getAllowedDirectory($status = false)
    {

        $data = array();

        if($status == false)
        {
            /*$data[0]['id'] = 'all';
            $data[0]['name'] = 'All';*/
            
            $this->db->select('id, name');
        }
        else
        {
            $this->db->select('*');
        }

        $this->db->from('upload_prefs');

        $get = $this->db->get();

        if($get->num_rows > 0)
        {
            $data = array_merge($data, $get->result_array());
        }
        
        if($status == false)
        {
            return $data;
        }
        else
        {

            $temp = array();
            for ($i=0; $i < count($data); $i++)
            {
                $temp[$data[$i]['id']] = $data[$i];
            }
            unset($data);

            return $temp;

        }

    }

}