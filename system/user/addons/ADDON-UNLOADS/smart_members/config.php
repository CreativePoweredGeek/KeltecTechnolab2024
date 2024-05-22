<?php
if ( ! defined('SM_VER') )
{
	define('SM_VER', '4.0.5');
	define('SM_NAME', 'Smart members Pro');
	define('SM_MOD_NAME', 'Smart_members');

	define('SM_AUTHOR', 'ZealousWeb');
	define('SM_AUTHOR_URL', 'http://www.zealousweb.com/');

	define('SM_DOC_URL', "https://www.zealousweb.com/documentation/expressionengine-addons/smartmemberspro/");
	define('SM_MORE_INFO_URL', "https://www.zealousweb.com/documentation/expressionengine-addons/smartmemberspro/#social-settings");

	//for ee6
	if (! defined('APP_VER')) {
    	define('APP_VER', ee()->config->item('app_version'));
    }
	$app_ver_int = (int) APP_VER;
	$app_ver_l6 = version_compare($app_ver_int, 6, '<');
	define('SM_APP_VER_L6',  $app_ver_l6); //ee6
	
}

$config['sm_version'] 	= SM_VER;
$config['sm_name'] 		= SM_NAME;
$config['sm_mod_name'] 	= SM_MOD_NAME;
$config['sm_doc_url'] 	= SM_DOC_URL;