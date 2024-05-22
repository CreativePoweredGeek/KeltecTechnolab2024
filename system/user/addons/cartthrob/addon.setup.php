<?php

use CartThrob\Services\CartService;
use CartThrob\Services\EmailService;
use CartThrob\Services\EncryptionService;
use CartThrob\Services\GarbageCollectionService;
use CartThrob\Services\IdempotencyService;
use CartThrob\Services\InputService;
use CartThrob\Services\MoneyService;
use CartThrob\Services\MsmService;
use CartThrob\Services\NotificationsService;
use CartThrob\Services\NumberService;
use CartThrob\Services\Order\OrderService;
use CartThrob\Services\OrdersService;
use CartThrob\Services\PluginService;
use CartThrob\Services\SettingsService;
use CartThrob\Services\SidebarService;
use CartThrob\Services\VaultService;

require_once PATH_THIRD . 'cartthrob/build/autoload.php';

return [
    'author' => 'Foster Made',
    'author_url' => 'https://fostermade.co',
    'name' => 'CartThrob',
    'description' => 'The most powerful and versatile ecommerce system available for ExpressionEngine.',
    'version' => CARTTHROB_VERSION,
    'namespace' => 'CartThrob',
    'settings_exist' => true,
    'services' => [
        'Config' => function ($addon) {
            return ee()->cartthrob->store->config();
        },
        'EmailService' => function ($addon) {
            return new EmailService();
        },
        'EncryptionService' => function ($addon) {
            return new EncryptionService(
                ee('Encrypt'),
                ee('Security/XSS')
            );
        },
        'MoneyService' => function ($addon) {
            return new MoneyService(
                ee()->cartthrob->store->config()
            );
        },
        'NumberService' => function ($addon) {
            return new NumberService(
                ee()->cartthrob->store->config()
            );
        },
        'SettingsService' => function ($addon) {
            return new SettingsService();
        },
        'OrderService' => function ($addon) {
            return new OrderService();
        },
        'OrdersService' => function ($addon) {
            return new OrdersService();
        },
        'GarbageCollectionService' => function ($addon) {
            return new GarbageCollectionService();
        },
        'SidebarService' => function ($addon) {
            return new SidebarService();
        },
        'CartService' => function ($addon) {
            return new CartService([]);
        },
        'NotificationsService' => function ($addon) {
            return new NotificationsService();
        },
        'IdempotencyService' => function ($addon) {
            return new IdempotencyService();
        },
        'VaultService' => function ($addon) {
            return new VaultService();
        },
        'InputService' => function ($addon) {
            return new InputService();
        },
    ],
    'services.singletons' => [
        'PluginService' => function ($addon) {
            return new PluginService();
        },
        'MsmService' => function ($addon) {
            return new MsmService();
        },
    ],
    'fieldtypes' => [
        'cartthrob_discount' => [
            'name' => 'CartThrob Discount Settings',
        ],
        'cartthrob_order_items' => [
            'name' => 'CartThrob Order Items',
        ],
        'cartthrob_package' => [
            'name' => 'CartThrob Package',
        ],
        'cartthrob_price_by_member_group' => [
            'name' => 'CartThrob Price - By Member Group',
        ],
        'cartthrob_price_modifiers' => [
            'name' => 'CartThrob Price Modifiers',
        ],
        'cartthrob_price_modifiers_configurator' => [
            'name' => 'CartThrob Price Modifiers Configurator',
        ],
        'cartthrob_price_quantity_thresholds' => [
            'name' => 'CartThrob Price - Quantity',
        ],
        'cartthrob_price_simple' => [
            'name' => 'CartThrob Price - Simple',
            'compatibility' => 'text',
        ],
    ],
    'models' => [
        'CartthrobStatus' => 'Model\CartthrobStatus',
        'Setting' => 'Model\Setting',
        'Vault' => 'Model\Vault',
        'Permission' => 'Model\Permission',
        'PermissionItem' => 'Model\PermissionItem',
        'Idempotency' => 'Model\Idempotency',
        'Tax' => 'Model\Tax',
        'Session' => 'Model\Session',
        'Cart' => 'Model\Cart',
        'NotificationLog' => 'Model\NotificationLog',
    ],
    'models.dependencies' => [
        'CartthrobStatus' => [
            'ee:ChannelEntry',
        ],
    ],
    'seeder' => [
        'seeds' => [
            'order' => CartThrob\Seeds\Order::class,
            'product' => CartThrob\Seeds\Product::class,
            'cartthrob/product' => CartThrob\Seeds\Product::class,
            'cartthrob/order' => CartThrob\Seeds\Order::class,
            'cartthrob/vault' => CartThrob\Seeds\Vault::class,
        ],
        'fields' => [
            'cartthrob_price_simple' => CartThrob\Seeds\Fields\Price\Simple::class,
            'cartthrob_discount' => CartThrob\Seeds\Fields\Discount::class,
            'cartthrob_price_modifiers' => CartThrob\Seeds\Fields\Price\Modifiers::class,
            'cartthrob_order_items' => CartThrob\Seeds\Fields\Order\Items::class,
        ],
    ],
    'commands' => [
        'cartthrob:version' => CartThrob\Commands\Version::class,
        'cartthrob:gc' => CartThrob\Commands\GarbageCollection::class,
    ],
    'cookies.functionality' => [
        'language',
    ],
    'cookies.necessary' => [
        'cartthrob_session_id',
    ],
    'cookie_settings' => [
        'cartthrob_session_id' => [
            'description' => 'ct.cookie.desc.cartthrob_session_id',
        ],
        'language' => [
            'description' => 'ct.cookie.desc.language',
        ],
    ],
];
