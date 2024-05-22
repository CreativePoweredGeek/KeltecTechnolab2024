<?php

use CartThrob\Controllers\Cp;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_mcp extends Cp
{
    protected $route_namespace = 'CartThrob\Controllers';

    public function index()
    {
        ee()->functions->redirect(ee('CP/URL')->make('addons/settings/cartthrob/settings/general'));
    }

    public function package_filter()
    {
        return $this->route('package_filter', func_get_args());
    }

    public function settings()
    {
        return $this->route('settings', func_get_args());
    }

    public function products()
    {
        return $this->route('products', func_get_args());
    }

    public function purchasedItems()
    {
        return $this->route('purchased-items', func_get_args());
    }

    public function discounts()
    {
        return $this->route('discounts', func_get_args());
    }

    public function orders()
    {
        return $this->route('orders', func_get_args());
    }

    public function coupons()
    {
        return $this->route('coupons', func_get_args());
    }

    public function shippingPlugins()
    {
        return $this->route('shipping', func_get_args());
    }

    public function paymentPlugins()
    {
        return $this->route('payment', func_get_args());
    }

    public function taxPlugins()
    {
        return $this->route('tax', func_get_args());
    }

    public function install()
    {
        return $this->route('install', func_get_args());
    }

    public function taxDb()
    {
        return $this->route('tax-db', func_get_args());
    }

    public function importExport()
    {
        return $this->route('import-export', func_get_args());
    }

    public function garbage_collection()
    {
        return $this->route('actions', ['garbage_collection']);
    }

    public function email_test()
    {
        return $this->route('actions', ['email_test']);
    }

    public function configurator_ajax()
    {
        return $this->route('actions', ['configurator-ajax']);
    }

    public function save_price_modifier_presets_action()
    {
        return $this->route('actions', ['save_price_modifier_presets_action']);
    }

    public function notifications()
    {
        return $this->route('notifications', func_get_args());
    }

    public function vaults()
    {
        return $this->route('vaults', func_get_args());
    }
}
