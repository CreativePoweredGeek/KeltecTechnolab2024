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
* Amici Infotech - Super Dynamic Fields
*
* @package      superDynamicFields
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-dynamic-fields
* @filesource   ./system/expressionengine/third_party/super_dynamic_fields/addon.setup.php
*/
require PATH_THIRD.'super_dynamic_fields/config.php';

return array(
	'author'         => SUPER_DYNAMIC_FIELDS_AUTHOR,
	'author_url'     => SUPER_DYNAMIC_FIELDS_AUTHOR_URL,
	'name'           => SUPER_DYNAMIC_FIELDS_NAME,
	'description'    => '',
	'version'        => SUPER_DYNAMIC_FIELDS_VER,
	'namespace'      => 'Mufi\Addons\SuperDynamicFields',
	'settings_exist' => FALSE,
	'fieldtypes'     => array(
		'super_dynamic_fields' => array(
			'compatibility' => 'list'
		)
	)
);

// EOF
