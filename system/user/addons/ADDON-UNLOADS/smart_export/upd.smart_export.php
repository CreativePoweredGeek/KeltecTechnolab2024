<?php
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
* ZealousWeb - Smart Export
*
* @package      SmartExport
* @author       Mufi
* @copyright    Copyright (c) 2016, ZealousWeb.
* @link         http://zealousweb.com/expressionengine/smart-export
* @filesource   ./system/expressionengine/third_party/smart_export/mod.smart_export.php
*
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require PATH_THIRD.'smart_export/config.php';

class Smart_export_upd
{
    
    /* Important globel variables */ 
    public $version = ZEAL_SE_VER;
    private $module_name = ZEAL_SE_MOD_NAME;

    /* Constructor */
    public function __construct()
    {
        ee()->load->dbforge();
    }
    
    /**
    * Install the module
    *
    * @return boolean TRUE
    */
    public function install()
    {

        /* Default module insert function to let CMS know that we are installed and ready to serve */
        $mod_data = array(
            'module_name'           => $this->module_name,
            'module_version'        => $this->version,
            'has_cp_backend'        => "y",
            'has_publish_fields'    => 'n'
        );
        ee()->db->insert('modules', $mod_data);

        /* Create table to save all smart export settings */
        $fields = array(
            'id' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => FALSE,
                'auto_increment'=> TRUE
            ),
            'member_id' => array(
                'type'          => 'int',
                'constraint'    => '5',
                'unsigned'      => TRUE,
                'null'          => TRUE
            ),
            'name' => array(
                'type'          => 'varchar',
                'constraint'    => '150',
                'null'          => TRUE
            ),
            'created_date' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => TRUE
            ),
            'last_modified' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => TRUE
            ),
            'export_counts' => array(
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
            'download_without_login' => array(
                'type'          => 'varchar',
                'constraint'    => '1',
                'null'          => TRUE
            ),
            'type' => array(
                'type'          => 'varchar',
                'constraint'    => '10',
                'null'          => TRUE
            ),
            'format' => array(
                'type'          => 'varchar',
                'constraint'    => '3',
                'null'          => TRUE
            ),
            'settings' => array(
                'type'          => 'mediumtext',
                'null'          => TRUE
            ),
            'status' => array(
                'type'          => 'varchar',
                'constraint'    => '10',
                'null'          => TRUE
            ),
        );

        ee()->dbforge->add_field($fields);

        ee()->dbforge->add_key('id', TRUE);
        ee()->dbforge->create_table('smart_exports');

        /* Action to download export from outside of EE*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'se_export'
        );
        ee()->db->insert('actions', $data);

        $this->createSettingsTable();
        return TRUE;

    }
    
    function createSettingsTable()
    {

        if(! ee()->db->table_exists("smart_exports_settings"))
        {

            $fields = array(
                'id' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => FALSE,
                    'auto_increment'=> TRUE
                ),
                'settings' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                ),
            );
            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('id', TRUE);
            ee()->dbforge->create_table('smart_exports_settings');

            $data = array();
            $data = array(
                'general' => array(
                    'encode_content'        => 'encode_utf_8',
                    'convert_all_dates'     => '',
                    'covert_html_entities'  => 'n',
                ),
                'csv' => array(
                    'separator_for_array_entities'  => ',',
                    'encode_for_array'              => 'json',
                ),
                'xml' => array(
                    'root_tag_name'     => 'root',
                    'element_tags_name' => 'elements',
                )
            );
            ee()->db->insert('smart_exports_settings', array('settings' => base64_encode(serialize($data))));

        }

    }

    /**
    * Uninstall the module
    *
    * @return boolean TRUE
    */
    public function uninstall()
    {

        ee()->db->select('module_id');
        $query = ee()->db->get_where('modules', 
            array( 'module_name' => $this->module_name )
        );
        ee()->db->where('module_id', $query->row('module_id'));
        ee()->db->delete('module_member_groups');
        
        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');
        
        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');
        
        ee()->db->where('class', $this->module_name.'_mcp');
        ee()->db->delete('actions');

        ee()->dbforge->drop_table('smart_exports');
        
        return TRUE;

    }

    /**
    * Update the module
    *
    * @return boolean
    */
    public function update($current = '')
    {

        if ($current == $this->version) {
            /* No updates */
            return FALSE;
        }

        $this->createSettingsTable();

        return TRUE;

    }
}

/* End of file upd.smart_export.php */
/* Location: /system/expressionengine/third_party/smart_export/upd.smart_export.php */ 
?>