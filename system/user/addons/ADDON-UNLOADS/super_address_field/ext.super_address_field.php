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
* @filesource   ./system/expressionengine/third_party/super_address_field/ext_super_address_field.php
*/

require PATH_THIRD.'super_address_field/config.php';

class Super_address_field_ext
{
	var $name       	= SUPER_ADDRESS_FIELD_NAME;
	var $version        = SUPER_ADDRESS_FIELD_VER;
	var $description    = SUPER_ADDRESS_FIELD_NAME;
	var $settings_exist = 'y';
	var $docs_url       = '';
	var $settings       = array();

    /**
     * Constructor
     *
     * @param   mixed   Settings array or empty string if none exist.
     */
    function __construct($settings = array())
    {
    	$this->settings = $settings;
    }

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     *
     * @return void
     */
    function activate_extension()
    {
    	$data = array(
    		'class'       => __CLASS__,
    		'method'      => 'after_channel_field_delete',
    		'hook'        => 'after_channel_field_delete',
    		'settings'    => serialize($this->settings),
    		'priority'    => 8,
    		'version'     => $this->version,
    		'enabled'     => 'y'
    	);

    	ee()->db->insert('extensions', $data);
    }

    /**
     * After Channel Field Delete
     *
     * Delete field data on custom table when field delete
     *
     *
     * @return Null
     */
    function after_channel_field_delete($channel_field, $values)
    {
        if (ee()->db->table_exists('super_address_field_data'))
        {
            if($values['field_type'] == 'super_address_field')
            {
                ee()->db->delete(
                    'super_address_field_data',
                    array(
                        'field_id' => $values['field_id']
                    )
                );
            }
        }
    }

    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return  mixed   TRUE if any update / false if none
     */
    function update_extension($current = '')
    {
    	if ($current == '' OR $current == $this->version)
    	{
    		return FALSE;
    	}

    	return TRUE;
    }

    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension()
    {
    	ee()->db->where('class',  __CLASS__);
    	ee()->db->delete('extensions');
    }

}

//EOF