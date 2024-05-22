<?php

if (!defined('CARTTHROB_VERSION')) {
    define('CARTTHROB_VERSION', '8.1.0');
}

if (defined('PATH_THEMES')) {
    if (!defined('PATH_THIRD_THEMES')) {
        define('PATH_THIRD_THEMES', PATH_THEMES . '../user/');
    }

    if (!defined('URL_THIRD_THEMES')) {
        define('URL_THIRD_THEMES', get_instance()->config->slash_item('theme_folder_url') . 'user/');
    }
}

$config['name'] = 'CartThrob';
$config['version'] = CARTTHROB_VERSION;
