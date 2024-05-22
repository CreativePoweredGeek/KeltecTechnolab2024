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
* ZealousWeb - Smart Import Export
*
* @package      SmartImportExport
* @author       Himanshu
* @copyright    Copyright (c) 2020, ZealousWeb.
* @link         https://www.zealousweb.com/expression-engine/smart-import-export
* @filesource   ./system/expressionengine/third_party/smart_import_export/upd.smart_import_export.php
*
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require PATH_THIRD.'smart_import_export/config.php';

class smart_import_export_upd
{
    
    /* Important globel variables */ 
    public $version = ZEAL_SIE_VER;
    private $module_name = ZEAL_SIE_MOD_NAME;

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

        /* Create table to save all sie exports settings */
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
                'constraint'    => '250',
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

        if(! ee()->db->table_exists("sie_exports")){
            ee()->dbforge->create_table('sie_exports');
        }

         /* Create table to save all sie imports settings */
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
            'import_counts' => array(
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
            'import_without_login' => array(
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
                'constraint'    => '250',
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

        if(! ee()->db->table_exists("sie_imports")){
            ee()->dbforge->create_table('sie_imports');
        }

        /* Action to download export from outside of EE*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sie_export'
        );
        ee()->db->insert('actions', $data);

        //cron feature : using action URL
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sie_import'
        );
        ee()->db->insert('actions', $data);

        $this->createSettingsTable();

        if(!ee()->db->table_exists("zeal_subscription")) {
            $fields = array(
                'subscription_id' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null'          => FALSE,
                ),
                'addon_name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null' => FALSE,
                ),
                'subscription_key' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null'          => TRUE
                ),
                'api_response' => array(
                    'type' => 'tinyint',
                    'constraint' => '2',
                    'unsigned' => TRUE,
                ),
                'last_api_call_date' => array(
                    'type' => 'int',
                    'null' => TRUE,
                ),
                    
            );
            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('addon_name', TRUE);
            ee()->dbforge->create_table('zeal_subscription');
        }

        return TRUE;

    }
    
    /**
    * Create setting table for export
    *
    * @return boolean TRUE
    */
    function createSettingsTable()
    {

        if(! ee()->db->table_exists("sie_exports_settings"))
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
            ee()->dbforge->create_table('sie_exports_settings');

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
            ee()->db->insert('sie_exports_settings', array('settings' => base64_encode(serialize($data))));

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

        //only for till ee5
        if(SIE_APP_VER_L6){
            ee()->db->where('module_id', $query->row('module_id'));
            ee()->db->delete('module_member_groups');
        }
        
        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');
        
        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');
        
        ee()->db->where('class', $this->module_name.'_mcp');
        ee()->db->delete('actions');

        ee()->dbforge->drop_table('sie_exports');
        ee()->dbforge->drop_table('sie_imports');
        ee()->dbforge->drop_table('sie_exports_settings');
        
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

        $fields = array(
            'format' => array(
                'name'          => 'format',
                'type'          => 'varchar',
                'constraint'    => '250',
                'null'          => TRUE
            ),
        );

        ee()->dbforge->modify_column('sie_exports', $fields);


        $fields = array(
            'format' => array(
                'name'          => 'format',
                'type'          => 'varchar',
                'constraint'    => '250',
                'null'          => TRUE
            ),
        );

        ee()->dbforge->modify_column('sie_imports', $fields);

        //cron feature
        $import_action = ee()->db->select('')
          ->from('actions')
          ->where(array(
            'class'     => $this->module_name,
            'method'    => 'sie_import'       
          ))
          ->get();

        if ($import_action->num_rows() == 0){
            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sie_import'
            );
            ee()->db->insert('actions', $data);
        }

        if(!ee()->db->table_exists("zeal_subscription")) {
            $fields = array(
                'subscription_id' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null'          => FALSE,
                ),
                'addon_name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null' => FALSE,
                ),
                'subscription_key' => array(
                    'type'          => 'varchar',
                    'constraint'    => '255',
                    'null'          => TRUE
                ),
                'api_response' => array(
                    'type' => 'tinyint',
                    'constraint' => '2',
                    'unsigned' => TRUE,
                ),
                'last_api_call_date' => array(
                    'type' => 'int',
                    'null' => TRUE,
                ),
                    
            );
            ee()->dbforge->add_field($fields);
            ee()->dbforge->add_key('addon_name', TRUE);
            ee()->dbforge->create_table('zeal_subscription');
        }

        $hooks = array(
            "core_boot"                         => "core_boot",
        );

        $data_class = str_replace('_upd', '_ext', __CLASS__);
        foreach ($hooks as $hook => $method) {
            $data = array(
                'class'     => $data_class,
                'method'    => $method,
                'hook'      => $hook,
                'settings'  => "",
                'priority'  => 10,
                'version'   => $this->version,
                'enabled'   => 'y'
            );
        
            ee()->db->insert('extensions', $data);
        }

        return TRUE;

    }
}

/* End of file upd.smart_import_export.php */
/* Location: /system/expressionengine/third_party/smart_import_export/upd.smart_import_export.php */ 
?>