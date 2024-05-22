<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// require_once __DIR__.'/vendor/autoload.php';
if(file_exists(__DIR__.'/language/english/zenbu_lang.php'))
{
	include_once __DIR__.'/language/english/zenbu_lang.php';
}

$config['name']           = isset($lang['zenbu_module_name']) ? $lang['zenbu_module_name'] : 'Zenbu';
$config['version']        = '3.4.2';
$config['description']    = isset($lang['zenbu_module_description']) ? $lang['zenbu_module_description'] : '';
$config['author']         = 'Nicolas Bottari - Zenbu Studio';
$config['author_url']     = 'https://zenbustudio.com/software/zenbu';
$config['docs_url']       = 'https://zenbustudio.com/software/docs/zenbu';
$config['namespace']      = 'Zenbu';
$config['settings_exist'] = TRUE;

if( ! defined('ZENBU_VER') )
{
	define('ZENBU_VER', $config['version']);
	define('ZENBU_NAME', $config['name']);
	define('ZENBU_DESCRIPTION', $config['description']);
	define('ZENBU_SETTINGS_EXIST', $config['settings_exist']);
}

$config['models'] = [
	'DisplaySetting'    => 'models\DisplaySetting',
	'FilterSetting'    => 'models\FilterSetting',
	'SavedSearch'       => 'models\SavedSearch',
	'SavedSearchFilter' => 'models\SavedSearchFilter',
	'Permission'        => 'models\Permission',
];

return $config;