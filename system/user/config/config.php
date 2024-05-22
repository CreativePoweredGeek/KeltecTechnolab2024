<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config['site_license_key'] = '02499c4a63614d81427181d8e7ff07c00cac6706';
$config['legacy_member_templates'] = 'y';
$config['allow_php'] = 'y';
$config['share_analytics'] = 'y';
$config['cookie_prefix'] = '';
$config['require_cookie_consent'] = 'n';
$config['force_redirect'] = 'n';
$config['debug'] = '1';
$config['enable_devlog_alerts'] = 'y';
$config['cache_driver'] = 'file';
$config['ip2nation'] = 'y';
$config['ip2nation_db_date'] = '1335677198';
$config['index_page'] = '';
$config['stash_cookie_expire'] = "28800";

$config['expire_session_on_browser_close'] = 'y';
$config['website_session_type'] = 'c';
$config['website_session_length'] = '14400';  // 4 hours

$config['is_system_on'] = 'y';
$config['multiple_sites_enabled'] = 'n';
$config['show_ee_news'] = 'n';
// ExpressionEngine Config Items
// Find more configs and overrides at
// https://docs.expressionengine.com/latest/general/system_configuration_overrides.html
$config['hop_minifizer'] = array(
    'cache_path' => '/home/keltecinc/public_html/cache',
    'cache_url' => 'https://keltecinc.com/cache',
);



$config['app_version'] = '7.4.2';
$config['encryption_key'] = 'f3e7b941493f122bef25dd5abf4bbf1f4e199cef';
$config['session_crypt_key'] = 'cff4343b6c12b1707e4a3d56ff4cd8cbc11a6439';

$config['base_url'] = 'https://keltecinc.com/';
$config['base_path'] = '/home/keltecinc/public_html/';
$config['un_min_len'] = 2; // allow a username w/only two characters
$config['database'] = array(
	'expressionengine' => array(
		'hostname' => 'localhost',
		//'database' => 'keltecinc_eedata',  //THIS IS THE CURRENT DEV DATABASE
		//'database' => 'keltecinc_eeJan', //created due  to failed CT update
		//'database' => 'keltecinc_LiveApr', //created due  to failed CT update
		//'database' => 'keltecinc_JuneData', //Created from the DEV on 6/14/20 as part of the Live update 
		'database' => 'keltecinc_LiveJune', //Created from the DEV on 6/14/20 as part of the Live update 
		'username' => 'keltecinc_boss',
		'password' => '#kM4MtN%[lqm',
		'dbprefix' => 'exp_',
		'char_set' => 'utf8mb4',
		'dbcollat' => 'utf8mb4_unicode_ci',
		'port'     => ''
	),
);

// EOF