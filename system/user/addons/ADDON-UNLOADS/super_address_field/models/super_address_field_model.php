<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* The software is provided "as is", without warranty of any
* kind, express or implied, including but not limited to the
* warranties of merchantability, fitness for a particular
* purpose and noninfringement. in no event shall the authors
* or copyright holders be liable for any claim, damages or
* other liability, whether in an action of contract, tort or
* otherwise, arising from, out of or in connection with the
* software or the use or other dealings in the software.
* -----------------------------------------------------------
* Amici Infotech - Super Address Field
*
* @package      superAddressField
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-address-field
* @filesource   ./system/expressionengine/third_party/super_address_field/models/super_address_field_model.php
*/

class Super_address_field_model extends CI_Model
{
	public $site_id;
	
	function __construct()
	{
		$this->site_id = ee()->config->item("site_id");
	}

	/**
	*  Save and update data in Database
	*
    * @param   string $value Stored data for the field
	* @return  boolean value
	*/
	function generalSettingsPost($values)
	{
		$this->db->select('id');
		$this->db->from('super_address_field_settings');
		$this->db->where('site_id', $this->site_id);
		$get = $this->db->get();
		$values['site_id'] = $this->site_id;

		if($get->num_rows == 0)
		{
			$this->db->insert('super_address_field_settings', $values);

			return true;
		}

		$id = $get->row('id');
		$this->db->where('id',$id);
		$this->db->update('super_address_field_settings',$values);

		return true;
	}

	/**
	*  fetch data for Display in SettingForm
	*
	* @return  result array
	*/
	function getGeneralSettings()
	{
		$this->db->select('*');
		$this->db->from('super_address_field_settings');
		$this->db->where('site_id', $this->site_id);
		$get = $this->db->get();

		if($get->num_rows == 0)
		{
			return false;
		}

		$result = $get->result_array();

		return $result[0];
	}

	/**
    * Api key fetch data form database
    *
    * @return  api key 
    */
	function apiKey()
	{
		ee()->db->select('api_key');
		ee()->db->from('super_address_field_settings');
		$setting_data = ee()->db->get();

		if($setting_data->num_rows() > 0)
		{
			$api_key = $setting_data->row('api_key');

			return $api_key;
		}

		return false;
	}

	/**
    * Save data form custom database table
    * 
    * @param   string $data Stored data for the field
    * @param   Defalut variables 
    * @return  True if sucess/ False if not 
    */
	function saveData($data, $fieldSettings)
	{

		$this->db->select('id');
		$this->db->from('super_address_field_data');
		$this->db->where('field_id', $fieldSettings['field_id']);
		$this->db->where('entry_id', $fieldSettings['entry_id']);
		$get 		= $this->db->get();
		$field_id 	= $fieldSettings['field_id']; 
		$entry_id 	= $fieldSettings['entry_id']; 

		$values = array(
			'entry_id'  	=> $fieldSettings['entry_id'],
			'field_id' 		=> $fieldSettings['field_id'],
			'address_1' 	=> (isset($data['address_1']) ? $data['address_1'] : ''),
			'address_2' 	=> (isset($data['address_2']) ? $data['address_2'] : ''),
			'city' 			=> (isset($data['city']) ? $data['city'] : ''),
			'state' 		=> (isset($data['state']) ? $data['state'] : ''),
			'postal_code' 	=> (isset($data['postal_code']) ? $data['postal_code'] : ''),
			'latitude' 		=> (isset($data['latitude']) ? $data['latitude'] : ''),
			'longitude' 	=> (isset($data['longitude']) ? $data['longitude'] : '')

		);

		if($get->num_rows($entry_id) == 0)
		{
			$this->db->insert('super_address_field_data', $values);
		}
		else if($get->num_rows($field_id) < 0 || $get->num_rows($entry_id) < 0)
		{
			$this->db->insert('super_address_field_data', $values);
		}
		else
		{
			$id = $get->row('id');
			$this->db->where('id', $id);
			$this->db->update('super_address_field_data', $values);
		}

		return true;

	}

	/**
    * fetch data form custom database table
    * 
    * @param   Defalut variables 
    * @return  data
    */
	function getData($fieldSettings)
	{

		$data = array('address_1', 'address_2', 'city', 'state', 'postal_code', 'latitude', 'longitude');

		$this->db->select(implode($data, ', '));
		$this->db->from('super_address_field_data');
		$this->db->where('entry_id', $fieldSettings['entry_id']);
		$this->db->where('field_id', $fieldSettings['field_id']);
		$get = $this->db->get();

		if($get->num_rows > 0)
		{	
			$result = $get->result_array();

			return $result[0];
		}

		$temp = array(
			'address_1' 	=> '',
			'address_2' 	=> '',
			'city' 			=> '',
			'state'			=> '', 
			'postal_code' 	=> '',
			'latitude' 		=> '',
			'longitude' 	=> ''
		);

		return $temp;
		
	}

	/**
    * delete  data form custom database table
    * 
    * @param   delete id $ids form table
    * @return  Null
    */
	public function delete($ids)
	{
		ee()->db->delete(
			'super_address_field_data',
			array(
				'entry_id' => $ids[0]
			)
		);
	}

	/**
    * update Data form custom database table
    * 
    * @param   string $data Stored data for the field
    * @param   Defalut variables 
    * @return  Null
    */
	public function updateData($data, $fieldSettings)
	{
		ee()->db->update(
		    'channel_data_field_' . $fieldSettings['field_id'],
		    array(
		        'field_id_' . $fieldSettings['field_id'] => $data
		    ),
		    array(
		        'entry_id'  => $fieldSettings['entry_id']
		    )
		);
	}
	
}

//EOF