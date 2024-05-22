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
* Amici Infotech - Super Dynamic Fields
*
* @package      superDynamicFields
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-dynamic-fields
* @filesource   ./system/expressionengine/third_party/super_dynamic_fields/ft.super_dynamic_fields.php
*/

require PATH_THIRD.'super_dynamic_fields/config.php';

class Super_dynamic_fields_ft extends EE_Fieldtype
{

	var $info = array(
		'name'		=> SUPER_DYNAMIC_FIELDS_NAME,
		'version'	=> SUPER_DYNAMIC_FIELDS_VER
	);

	var $has_array_data = TRUE;

	function __construct()
	{
		parent::__construct();
		ee()->load->library('super_dynamic_fields_lib', null, 'superDynamicFields');
		ee()->load->library('Super_dynamic_fields_parsing', null, 'superDynamicFieldsParse');
		ee()->lang->loadfile('super_dynamic_fields');
	}

	/**
	*Displays the field for the CP or Frontend, and accounts for grid
	*
	* @param   string $data Stored data for the field
	* @return  string Field display and default variables
	*/
	public function display_field($data)
	{
		return $this->_displayField($data);
	}

	function grid_display_field($data)
	{
		return $this->_displayField($data, 'grid');
	}

	function _displayField($data, $grid = 'fieldset')
	{
		$r = '';
		$fieldSettings = array(
			'entry_id' 			=> $this->content_id,
			'option_type'		=> $this->get_setting('option_type'),
			'type' 				=> $this->get_setting('type'),
			'template' 			=> $this->get_setting('template'),
			'body' 				=> $this->get_setting('body'),
			'field_name' 		=> $this->field_name,
			'content' 			=> $this->content_type(),
			'field_reqiure' 	=> $this->get_setting('field_required'),
			'field_disable'		=> $this->get_setting('field_disabled'),
			'gird_container' 	=> $this->grid_padding_container($r)
		);

		return ee()->superDynamicFields->displayField($data, $fieldSettings, $grid);

	}

	/**
	*  The data you return will be saved and returned to your field on
	* 	display on the frontend and when editing the field.
	*
	* @param   string $data Stored data for the field
	* @return  string data to store
	*/
	function save($data)
	{
		if(is_array($data) && isset($data[0]) && $data[0] == "")
		{
			unset($data[0]);
			$data = array_values($data);
		}

		return ee()->superDynamicFields->save($data);
	}

	/**
	*   Display Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  string Settings for display
	*/
	function display_settings($data)
	{
		return ee()->superDynamicFields->displaySettings($data, 'fieldset');
	}

	function grid_display_settings($data)
	{
		return ee()->superDynamicFields->gridDisplayFields($data, 'grid');
	}

	/**
	*   Save Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  save display settings
	*/
	public function save_settings($data)
	{
		return ee()->superDynamicFields->saveSettings($data);
	}

	/**
	*   Validate display Field Settings
	*
	* @param   string $data Stored data for the field
	* @return  validate display settings
	*/
	public function validate_settings($data)
	{
		return ee()->superDynamicFields->validateSettings($data);
	}

	/**
	* Default replace_tag implementation
	*
	* @param   Defalut variables 
	*/
	function replace_tag($data, $params = array(), $tagdata = FALSE)
	{

		$fieldSettings = array(
			'get_format' => $this->get_format(),
			'row' 		 => $this->row('channel_html_formatting', 'all'),
			'row_1' 	 => $this->row('channel_auto_link_urls', 'n'),
			'row_2' 	 => $this->row('channel_allow_img_urls', 'y'),
			'option_type'=> $this->get_setting('option_type'),
			'template' 	 => $this->get_setting('template'),
			'body' 		 => $this->get_setting('body'),
		);

		return ee()->superDynamicFields->replaceTag($data, $params, $tagdata, $fieldSettings);

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

	/**
	*   Validate display Fields 
	*
	* @param   string $data Stored data for the field
	* @return  validate display fields and defalut variables
	*/
	public function validate($data)
	{

		$fieldSettings = array(
			'option_type'		=> $this->get_setting('option_type'),
			'template' 			=> $this->get_setting('template'),
			'body' 				=> $this->get_setting('body'),
			'field_reqiure' 	=>  $this->get_setting('field_required')
		);

		return ee()->superDynamicFields->validate($data , $fieldSettings);

	}

}
// END Super_dynamic_fields_ft class