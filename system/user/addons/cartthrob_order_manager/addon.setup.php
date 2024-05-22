<?php

use CartThrob\OrderManager\Services\ReportService;

include_once PATH_THIRD . 'cartthrob_order_manager/config.php';
require_once PATH_THIRD . 'cartthrob_order_manager/vendor/autoload.php';

return [
    'author' => 'Foster Made',
    'author_url' => 'https://fostermade.co',
    'docs_url' => 'https://cartthrob.com/docs/docs_cartthrob-order-manager',
    'name' => 'CartThrob Order Manager',
    'description' => 'Order management system for CartThrob',
    'version' => CT_ORDER_MANAGER,
    'namespace' => 'CartThrob\OrderManager',
    'settings_exist' => true,
    'services' => [
        'ReportService' => function ($addon) {
            return new ReportService();
        },
    ],
    'models' => [
        'Setting' => 'Model\Setting',
        'OrderReport' => 'Model\OrderReport',
    ],
];
