<?php

use CartThrob\Plugins\Plugin;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * CI Wrapper for CartThrob
 *
 * Loads the settings, the session and then the cart
 *
 * @property Cartthrob_settings_model $cartthrob_settings_model
 * @property Cartthrob_session $cartthrob_session
 * @property Cart_model $cart_model
 * @property Customer_model $customer_model
 */
class Cartthrob_loader
{
    private $setup = [];

    /**
     * Cartthrob_loader constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        if (!$this->cartthrobIsInstalled()) {
            return;
        }

        if (!ee()->has('cartthrob')) {
            // if you don't provide a cart in the construct params (like an empty cart array), initialize the session to get the cart
            if (!isset($params['cart'])) {
                $this->loadCart($params);
            }

            // normally we'd want to instantiate with a config array,
            // but the Cartthrob_core_ee driver overrides the use of the config array and uses the cartthrob_settings_model's config cache
            ee()->set('cartthrob', Cartthrob_core::instance('ee', ['cart' => $params['cart']]));

            $this->loadFirstPartyPlugins();
        }
    }

    /**
     * @param $object
     */
    public function setup(&$object)
    {
        if (!is_object($object)) {
            return;
        }

        if (!in_array($object, $this->setup)) {
            $this->setup[] = $object;
        }

        $object->cartthrob = ee()->cartthrob;
        $object->cart = ee()->cartthrob->cart;
        $object->store = ee()->cartthrob->store;
    }

    /**
     * @return bool
     */
    private function cartthrobIsInstalled()
    {
        return ee('Addon')->get('cartthrob')->isInstalled();
    }

    /**
     * Load CartThrob's first-party plugins
     */
    private function loadFirstPartyPlugins()
    {
        $config = include PATH_THIRD . 'cartthrob/config/plugins.php';

        if (count($config) <= 0) {
            return;
        }

        collect($config)
            ->flatten()
            ->each(function ($className) {
                $plugin = cartthrob($className);

                if ($plugin instanceof Plugin) {
                    $plugin->register();
                }
            });
    }

    /**
     * @param $params
     */
    private function loadCart(&$params)
    {
        // load the settings into CI
        ee()->load->model('cartthrob_settings_model');

        // load the session
        ee()->load->library('cartthrob_session');

        // get the cart id from the session
        $cartId = ee()->cartthrob_session->cart_id();

        ee()->load->model('cart_model');

        // get the cart data from the db
        $params['cart'] = ee()->cart_model->fetch($cartId);

        ee()->load->model('customer_model');

        $existingCustomerInfo = (isset($params['cart']['customer_info'])) ? $params['cart']['customer_info'] : null;

        $params['cart']['customer_info'] = ee()->customer_model->get_customer_info($existingCustomerInfo);
    }
}
