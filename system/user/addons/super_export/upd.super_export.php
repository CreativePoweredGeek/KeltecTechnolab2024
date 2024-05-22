<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'super_export/config.php';
use ExpressionEngine\Service\Addon\Installer;

class Super_export_upd extends Installer
{

	public $version 		= SUPER_EXPORT_VER;
	public $module_name 	= SUPER_EXPORT_MOD;

	function __construct()
	{
		parent::__construct();
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

		$fields = array(
		    'id' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => FALSE,
		        'auto_increment'=> TRUE
		    ),
		    'site_id' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => FALSE
		    ),
		    'relationships_key' => array(
		        'type'          => 'varchar',
		        'constraint'    => '10',
		        'null'          => TRUE
		    ),
		    'encode' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'date_format' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'encode_html' => array(
		        'type'          => 'varchar',
		        'constraint'    => '1',
		        'null'          => TRUE
		    ),
		    'csv_export_key' => array(
		        'type'          => 'varchar',
		        'constraint'    => '15',
		        'null'          => TRUE
		    ),
		    'csv_separator_s_array' => array(
		        'type'          => 'varchar',
		        'constraint'    => '2',
		        'null'          => TRUE
		    ),
		    'csv_separator_m_array' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'xml_root_name' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'xml_element_name' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'ob_clean' => array(
		        'type'          => 'varchar',
		        'constraint'    => '1',
		        'null'          => TRUE
		    ),
		    'ob_start' => array(
		        'type'          => 'varchar',
		        'constraint'    => '1',
		        'null'          => TRUE
		    ),
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table('super_export_settings');

		$fields = array(
		    'id' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => FALSE,
		        'auto_increment'=> TRUE
		    ),
		    'site_id' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => FALSE
		    ),
		    'member_id' => array(
		        'type'          => 'int',
		        'constraint'    => '5',
		        'unsigned'      => TRUE,
		        'null'          => TRUE
		    ),
		    'channel_id' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => FALSE
		    ),
		    'title' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'created_date' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => TRUE
		    ),
		    'last_modified_date' => array(
		        'type'          => 'int',
		        'constraint'    => '10',
		        'unsigned'      => TRUE,
		        'null'          => TRUE
		    ),
		    'counter' => array(
		        'type'          => 'int',
		        'constraint'    => '5',
		        'unsigned'      => TRUE,
		        'null'          => TRUE,
		        'default'       => 0
		    ),
		    'token' => array(
		        'type'          => 'varchar',
		        'constraint'    => '50',
		        'null'          => TRUE
		    ),
		    'format' => array(
		        'type'          => 'varchar',
		        'constraint'    => '5',
		        'null'          => TRUE
		    ),
		    'settings' => array(
		        'type'          => 'mediumtext',
		        'null'          => TRUE
		    ),
		);
		ee()->dbforge->add_field($fields);
		ee()->dbforge->add_key('id', TRUE);
		ee()->dbforge->create_table('super_export_data');

		$data = array(
		    'class'     => $this->module_name,
		    'method'    => 'super_export_frontend'
		);
		ee()->db->insert('actions', $data);

		$data = array(
		    'class'     => $this->module_name,
		    'method'    => 'super_export_download'
		);
		ee()->db->insert('actions', $data);

		return TRUE;

	}

	public function uninstall()
	{

		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => $this->module_name));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_roles');

		ee()->db->where('module_name', $this->module_name);
		ee()->db->delete('modules');

		ee()->db->where('class', $this->module_name);
		ee()->db->delete('actions');

		ee()->db->where('class', $this->module_name.'_mcp');
		ee()->db->delete('actions');

		ee()->dbforge->drop_table('super_export_data');
		ee()->dbforge->drop_table('super_export_settings');

		return TRUE;

	}

	public function update($current = '')
	{

		if ($current == $this->version)
		{
	        // No updates
			return FALSE;
		}

		/* Updates for  v 1.0.3 Start */
		if(! ee()->db->field_exists('ob_clean', 'super_export_settings'))
		{

		    /*Add fields to main setting table*/
		    $fields = array(
		        'ob_clean' => array(
				    'type'          => 'varchar',
				    'constraint'    => '1',
				    'null'          => TRUE
				),
				'ob_start' => array(
				    'type'          => 'varchar',
				    'constraint'    => '1',
				    'null'          => TRUE
				),
		    );
		    ee()->dbforge->add_column('super_export_settings', $fields);

		}

		return TRUE;

	}

}