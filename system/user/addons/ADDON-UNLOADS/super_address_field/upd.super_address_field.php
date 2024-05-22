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
* @filesource   ./system/expressionengine/third_party/super_address_field/upd.super_address_field.php
*/
require PATH_THIRD.'super_address_field/config.php';

class Super_address_field_upd 
{
	
	public $version 		= SUPER_ADDRESS_FIELD_VER;
	private $module_name 	= SUPER_ADDRESS_FIELD_MOD;

	function __construct()
	{
		ee()->load->dbforge();
	}

	public function install()
	{

		$mod_data = array(
			'module_name'           => $this->module_name,
			'module_version'        => $this->version,
			'has_cp_backend'        => "y",
			'has_publish_fields'    => 'n'
		);

		ee()->db->insert('modules', $mod_data);
		
		$data = array(
			'class'     => $this->module_name,
			'method'    => 'saf_default_method'
		);

		ee()->db->insert('actions', $data);

		$fields = array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> '10',
				'unsigned'	 		=> TRUE,
				'null' 				=> FALSE,
				'auto_increment'	=> TRUE
			),
			'site_id' => array(
				'type'          => 'int',
				'constraint'    => '3',
				'null'          => FALSE
			),
			'api_key' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '500',
				'null' 			=> TRUE
			)
		);

		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table('super_address_field_settings');

		$fields = array(
			'id' => array(
				'type' 				=> 'int',
				'constraint' 		=> '10',
				'unsigned'	 		=> TRUE,
				'null' 				=> FALSE,
				'auto_increment'	=> TRUE
			),
			'entry_id' => array(
				'type' 			=> 'int',
				'constraint' 	=> '10',
				'null' 			=> TRUE
			),
			'field_id' => array(
				'type' 			=> 'int',
				'constraint' 	=> '10',
				'null' 			=> TRUE
			),
			'address_1' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '100',
				'null' 			=> TRUE
			),
			'address_2' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '100',
				'null' 			=> TRUE
			),
			'city' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '30',
				'null' 			=> TRUE
			),
			'state' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '30',
				'null' 			=> TRUE
			),
			'postal_code' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '10',
				'null' 			=> TRUE
			),
			'latitude' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '20',
				'null' 			=> TRUE
			),
			'longitude' => array(
				'type' 			=> 'varchar',
				'constraint' 	=> '20',
				'null' 			=> TRUE
			),

		);
		
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table('super_address_field_data');

		return TRUE;

	}

	/**
	 * Uninstall the module
	 *
	 * @return boolean TRUE
	 */
	public function uninstall()
	{
		
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => $this->module_name));
		
		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', $this->module_name);
		ee()->db->delete('modules');

		ee()->db->where('class', $this->module_name);
		ee()->db->delete('actions');

		ee()->db->where('class', $this->module_name.'_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('super_address_field_settings');
		
		ee()->dbforge->drop_table('super_address_field_data');

		return TRUE;

	}

	/**
	 * Update the module
	 *
	 * @return boolean
	 */
	public function update($current = '')
	{
		if ($current == $this->version) 
		{
	        // No updates
			return FALSE;
		}

		return TRUE;
	}

}

//EOF
