<?php

/*
 * Change if you move the cartthrob directory or otherwise need to specify it explicitly
 */
define('CARTTHROB_PATH', dirname(__FILE__) . '/');

// DO NOT CHANGE
const CARTTHROB_CORE_PATH = CARTTHROB_PATH . 'core/';
const CARTTHROB_DRIVER_PATH = CARTTHROB_PATH . 'drivers/';
const CARTTHROB_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/';
const CARTTHROB_SHIPPING_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/shipping/';
const CARTTHROB_GATEWAY_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/payment_gateways/';
const CARTTHROB_TAX_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/tax/';
const CARTTHROB_PRICE_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/price/';
const CARTTHROB_DISCOUNT_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/discount/';
const CARTTHROB_NOTIFICATION_PLUGIN_PATH = CARTTHROB_PATH . 'plugins/notification/';

/*
 * How to instantiate:
 *
 * $cartthrob = Cartthrob_core::instance('your_driver_name', $params);
 *
 * Where $params is an array of parameters.
 */
