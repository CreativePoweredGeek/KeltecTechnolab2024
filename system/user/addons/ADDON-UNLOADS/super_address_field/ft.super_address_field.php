<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
* @filesource   ./system/expressionengine/third_party/super_address_field/ft.super_address_field.php
*/

require PATH_THIRD.'super_address_field/config.php';

class Super_address_field_ft extends EE_Fieldtype
{

	var $info = array(
		'name'		=> SUPER_ADDRESS_FIELD_NAME,
		'version'	=> SUPER_ADDRESS_FIELD_VER
	);

	var $has_array_data = TRUE;

	function __construct()
	{
		parent::__construct();
		ee()->load->model('super_address_field_model', 'saf_model');
		ee()->load->library('super_address_field_lib', null, 'superAddressField');
	}

	/**
	*Displays the field for the CP or Frontend, and accounts for grid
	*
	* @param   string $data Stored data for the field
	* @return  string Field display and default variables
	*/
	public function display_field($data)
	{
		$fieldSettings = array(
			'entry_id' 				=> $this->content_id,
			'field_id'				=> $this->get_setting('field_id'),
			'field_name' 			=> $this->field_name,
			'allowed_field'			=> $this->get_setting('allowed_field'),
			'field_default_value' 	=> $this->get_setting('field_default_value'),
			'fluid_field_data_id'	=> $this->get_setting('fluid_field_data_id'),
		);

		return ee()->superAddressField->displayField($data, $fieldSettings);
	}

	/**
	*  The data you return will be saved and returned to your field on
	* 	display on the frontend and when editing the field.
	*
	* @param   string $data Stored data for the field
	* @return  string data to store
	*/
	public function save($data)
	{
		$fieldSettings = array(
			'entry_id' 				=> $this->content_id,
			'field_id'				=> $this->get_setting('field_id'),
			'fluid_field_data_id'	=> $this->get_setting('fluid_field_data_id'),
		);
		
		return ee()->superAddressField->save($data, $fieldSettings);
	}

	public function validate($data)
	{
		$fieldSettings = array(
			'field_reqiure' => $this->get_setting('field_required')
		);

		return ee()->superAddressField->validate($data, $fieldSettings);
	}

	/**
	*  This method implement to fetch newly created entry content ID to store custom table
	*
	* @param   string $data Stored data for the field
	* @return  string data to store
	*/
	function post_save($data)
	{
		$fieldSettings = array(
			'entry_id' 		=> $this->content_id,
			'field_name' 	=> $this->get_setting('field_name'),
			'field_id'		=> $this->get_setting('field_id'),
		);

		return ee()->superAddressField->postSave($data, $fieldSettings);
	}
	
	/**
	*   Delete the field data for custom table
	*
	* @param   string $ids current id of field
	* @return  id for the deletion
	*/
	function delete($ids)
	{
		return ee()->saf_model->delete($ids);
	}

	/**
	*   Display Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  string Settings for display
	*/
	function display_settings($data)
	{
		$fieldSettings = array(
			'field_name' 	=> $this->field_name,
			'disable' 		=> $this->get_setting('field_disabled'),
		);

		return ee()->superAddressField->displaySettings($data, $fieldSettings);
	}

	/**
	*   Display Grid Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  string Settings for display
	*/
	function grid_display_settings($data)
	{
		$fieldSettings = array(
			'field_name' 	=> $this->field_name,
			'disable' 		=> $this->get_setting('field_disabled'),
		);
		
		return ee()->superAddressField->gridDisplaySettings($data, $fieldSettings);
	}

	/**
	*   Validate display Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  validate display settings
	*/
	public function validate_settings($data)
	{
		return ee()->superAddressField->validateSettings($data);
	}

	/**
	*   Save Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  save display settings
	*/
	function save_settings($data)
	{
		return ee()->superAddressField->saveSettings($data);
	}

	/**
	*  replace_tag for display data to frontend 
	*
	* @param   Defalut variables 
	*/
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 				=> $this->content_id,
			'field_id'				=> $this->get_setting('field_name'),
			'fluid_field_data_id'	=> $this->get_setting('fluid_field_data_id'),
			'get_format' 			=> $this->get_format(),
			'row' 		 			=> $this->row('channel_html_formatting', 'all'),
			'row_1' 	 			=> $this->row('channel_auto_link_urls', 'n'),
			'row_2' 	 			=> $this->row('channel_allow_img_urls', 'y'),
		);
		
		return ee()->superAddressField->replaceTag($data, $params, $tagdata, $fieldSettings);
	}

	function replace_address_1($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id' 	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'address_1');
	}

	function replace_address_2($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id'	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'address_2');
	}

	function replace_city($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id' 	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'city');
	}

	function replace_state($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id' 	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'state');
	}

	function replace_postal_code($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id'	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'postal_code');
	}

	function replace_latitude($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id' 	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'latitude');
	}

	function replace_longitude($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id'	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceSubField($data, $fieldSettings, 'longitude');
	}

	function replace_lat_long($data, $params = array(), $tagdata = FALSE)
	{
		$fieldSettings = array(
			'entry_id' 	=> $this->content_id,
			'field_id'	=> $this->get_setting('field_name'),
		);

		return ee()->superAddressField->replaceLatLong($data, $fieldSettings);
	}

	/**
	 * Accept all content types.
	 *
	 * @param string  The name of the content type
	 * @return bool   Accepts all content types
	 */
	public function accepts_content_type($name)
	{
		return in_array($name, ['channel', 'fluid_field', 'grid'], true);
	}
	
	/**
	 * Update the fieldtype
	 *
	 * @param string $version The version being updated to
	 * @return boolean TRUE if successful, FALSE otherwise
	 */
	public function update($version)
	{
		return TRUE;
	}

}

// EOF
