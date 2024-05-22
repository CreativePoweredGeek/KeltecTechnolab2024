<?php

if (!defined('CT_ORDER_MANAGER')) {
    define('CT_ORDER_MANAGER', '8.1.0');
}

if (defined('PATH_THEMES')) {
    if (!defined('URL_THIRD_THEMES')) {
        define('URL_THIRD_THEMES', get_instance()->config->slash_item('theme_folder_url') . 'user/');
    }
}

$config['version'] = CT_ORDER_MANAGER;
$config['name'] = 'CartThrob Order Manager';
$config['cartthrob_order_manager_description'] = 'cartthrob_order_manager_description';
