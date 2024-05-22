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
* @copyright    Copyright (c) 2021, ZealousWeb.
* @link         https://www.zealousweb.com/expressionengine-addons/smart-import-export/
* @filesource   ./system/expressionengine/third_party/smart_import_export/config.php
*
*/
if (!defined('ZEAL_SIE_VER'))
{
	define('ZEAL_SIE_VER', '3.2.4');
	define('ZEAL_SIE_NAME', 'Smart Import Export');
	define('ZEAL_SIE_MOD_NAME', 'Smart_import_export');

	define('ZEAL_SIE_AUTHOR', 'ZealousWeb');
	define('ZEAL_SIE_AUTHOR_URL', 'http://www.zealousweb.com/');

	define('ZEAL_SIE_DOC_URL', "https://www.zealousweb.com/documentation/expressionengine-addons/smartimportexport/");

	//for ee6
	if (! defined('APP_VER')) {
    	define('APP_VER', ee()->config->item('app_version'));
    }
	$app_ver_int = (int) APP_VER;
	$app_ver_l6 = version_compare($app_ver_int, 6, '<'); // it retrues true when ee version less than 6
	define('SIE_APP_VER_L6',  $app_ver_l6); //ee6 : 

}

$config['sie_version']		= ZEAL_SIE_VER;
$config['sie_name'] 		= ZEAL_SIE_NAME;
$config['sie_mod_name'] 	= ZEAL_SIE_MOD_NAME;
$config['doc_url'] 			= ZEAL_SIE_DOC_URL;