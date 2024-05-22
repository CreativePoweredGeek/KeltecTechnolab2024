<?php

use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Services\PluginService;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Api_cartthrob_payment_gateways class
 *
 * This class returns information about gateways available in CartThrob
 * Among other things, it returns gateway fields HTML, and the list of available gateways
 * This class does NOT instantiate a gateway, or call gateway methods. For that purpose use Cartthrob_payments.php
 *
 * Usage: (in this example a gateway is set, and the gateway fields HTML is returned);
 *
 * Api_cartthrob_payment_gateways->set_gateway(gateway_name)->gateway_fields();
 *
 **/
class Api_cartthrob_payment_gateways // extends Api_cartthrob_plugins
{
    protected $gateway;
    protected $gateways;

    /**
     * Api_cartthrob_payment_gateways constructor.
     */
    public function __construct()
    {
        $this->reset_gateway();

        ee()->load->library('cartthrob_payments');
    }

    /**
     * @return $this
     */
    public function reset_gateway()
    {
        $this->gateway = ee()->cartthrob->store->config('payment_gateway');

        return $this;
    }

    /**
     * @param bool $clear_customer_info
     * @param string $fields_group
     * @param string $required_fields_group
     * @return string
     */
    public function gateway_fields($clear_customer_info = false, $fields_group = 'fields', $required_fields_group = 'required_fields')
    {
        if ($this->template()) {
            return '{embed="' . $this->template() . '"}';
        }

        ee()->load->library('locales');
        ee()->load->helper(['form', 'url']);

        if ($clear_customer_info) {
            ee()->cartthrob->cart->clear_customer_info();
        }

        ee()->load->library('locales');
        $data['cartthrob'] = ee()->cartthrob;
        $data['states'] = ee()->locales->states();
        $data['countries'] = ee()->locales->all_countries(true);
        $data['sections'] = [
            'billing' => [
                'first_name',
                'last_name',
                'address',
                'address2',
                'city',
                'state',
                'zip',
                'country',
                'country_code',
                'company',
                'region',
            ],
            'shipping' => [
                'shipping_first_name',
                'shipping_last_name',
                'shipping_phone',
                'shipping_address',
                'shipping_address2',
                'shipping_city',
                'shipping_state',
                'shipping_zip',
                'shipping_country',
                'shipping_country_code',
                'shipping_company',
                'shipping_region',
            ],
            'member' => [
                'username ',
                'screen_name',
                'password',
                'password_confirm ',
                'create_member',
                'group_id',
            ],
            'additional_info' => [
                'phone',
                'email_address',
                'ip_address',
                'description',
                'language',
                'currency_code',
                'description',
            ],
            'payment' => [
                'card_type',
                'credit_card_number',
                'card_code',
                'issue_number',
                'CVV2',
                'bday_month',
                'bday_day',
                'bday_year',
            ],
            'checking_payment' => [
                'po_number',
                'card_code',
                'transaction_type',
                'bank_account_number',
                'check_type',
                'account_type',
                'routing_number',
                'bank_name',
                'bank_account_name',
            ],
            'payment_expiration' => [
                'expiration_month',
                'expiration_year',
            ],
            'payment_begin' => [
                'begin_month',
                'begin_year',
            ],
            'subscription' => [
                'subscription_name',
                'subscription_price',
                'subscription_total_occurrences',
                'subscription_trial_price',
                'subscription_trial_occurrences',
                'subscription_start_date',
                'subscription_end_date',
                'subscription_interval_length',
                'subscription_interval_units',
                'subscription_allow_modification',
                'subscription_type',
            ],
        ];

        $data['extra_fields'] = $this->gateway('extra_fields', []);

        if (!empty($data['extra_fields'])) {
            $data['sections']['extra_fields'] = $data['extra_fields'];
        }

        $gateway_fields = $this->gateway($fields_group, []);
        if ($fields_group != 'fields' && !$gateway_fields) {
            $gateway_fields = $this->gateway('fields', []);
        }

        foreach ($data['sections'] as $section => $fields) {
            foreach ($fields as $i => $field) {
                if (!in_array($field, $gateway_fields)) {
                    unset($data['sections'][$section][$i]);
                }
            }

            if (empty($data['sections'][$section])) {
                unset($data['sections'][$section]);
            }
        }

        if (ee()->cartthrob->store->config('gateways_format')) {
            $data['field_format'] = ee()->cartthrob->store->config('gateways_format');
        }

        $data['nameless_fields'] = $this->gateway('nameless_fields', []);

        for ($i = 1; $i <= 12; $i++) {
            if ($i < 10) {
                $i = '0' . $i;
            }

            $data['months'][(string)$i] = lang('month_' . $i);
        }

        $data['bday_year'] = [];

        for ($year = date('Y') - 100; $year < date('Y') - 10; $year++) {
            $data['bday_year'][$year] = $year;
        }

        ksort($data['bday_year']);

        $data['bday_day'] = [];

        for ($day = 1; $day <= 31; $day++) {
            if (strlen($day) < 2) {
                $day_key = '0' . $day;
            } else {
                $day_key = $day;
            }
            $data['bday_day'][$day_key] = $day;
        }

        ksort($data['bday_day']);

        $data['exp_years'] = [];

        for ($year = date('Y'); $year < date('Y') + 10; $year++) {
            $data['exp_years'][$year] = $year;
        }

        $data['begin_years'] = [];

        for ($year = date('Y'); $year > date('Y') - 15; $year--) {
            $data['begin_years'][$year] = $year;
        }

        ksort($data['begin_years']);

        $data['subscription_interval_units'] = [
            'days' => 'Days',
            'weeks' => 'Weeks',
            'months' => 'Months',
            'years' => 'Years',
        ];
        $card_types = $this->gateway('card_types');

        $account_types = $this->gateway('account_types');

        if (!$card_types) {
            $card_types = [
                'visa',
                'mc',
                'amex',
                'discover',
            ];
        }

        if (!$account_types) {
            $account_types = [
                'savings',
                'business_checking',
                'checking',
            ];
        }

        foreach ($card_types as $key => $card_type) {
            if (!is_numeric($key)) {
                $data['card_types'][$key] = lang($card_type);
            } else {
                $data['card_types'][$card_type] = lang($card_type);
            }
        }
        foreach ($account_types as $key => $account_type) {
            if (!is_numeric($key)) {
                $data['account_types'][$key] = lang($account_type);
            } else {
                $data['account_types'][$account_type] = lang($account_type);
            }
        }

        $data['hidden'] = '';

        foreach ($this->gateway('hidden', []) as $hidden) {
            $data['hidden'] .= form_hidden($hidden, ee()->cartthrob->cart->customer_info($hidden)) . "\n";
        }

        $data['required_fields'] = $this->gateway($required_fields_group, []);

        loadCartThrobPath();

        $output = ee()->load->view('gateway_fields', $data, true);

        if ($embedded = $this->gateway('embedded_fields', null)) {
            $output .= $embedded;
        }

        return $output;
    }

    /**
     * @return bool
     */
    public function template()
    {
        if (!$this->gateway) {
            return false;
        }

        return ee()->cartthrob->store->config($this->gateway . '_settings', 'gateway_fields_template');
    }

    /**
     * @return bool
     */
    public function vault_template()
    {
        if (!$this->gateway) {
            return false;
        }

        if (ee()->cartthrob->store->config($this->gateway . '_settings', 'vault_fields_template')) {
            return ee()->cartthrob->store->config($this->gateway . '_settings', 'vault_fields_template');
        }

        return $this->template();
    }

    /**
     * @param bool $key
     * @param bool $default
     * @return bool|mixed
     */
    public function gateway($key = false, $default = false)
    {
        $gateway_vars = false;

        foreach ($this->gateways() as $vars) {
            if ($vars['classname'] === $this->gateway) {
                $gateway_vars = $vars;
                break;
            }
        }

        $return = ($key !== false) ? element($key, $gateway_vars) : $gateway_vars;

        return $return === false ? $default : $return;
    }

    /**
     * @return array
     */
    public function gateways()
    {
        if (is_null($this->gateways)) {
            $this->gateways = [];

            $this->loadGatewaysByPath();
            $this->loadGatewaysByPluginService();
        }

        return $this->gateways;
    }

    /**
     * @param $gateway
     * @return $this
     */
    public function set_gateway($gateway)
    {
        $this->gateway = 'Cartthrob_' . Cartthrob_core::get_class($gateway);

        return $this;
    }

    /**
     * @return array
     */
    public function subscription_gateways()
    {
        $gateways = [];

        foreach ($this->gateways() as $gateway) {
            if (method_exists($gateway['classname'], 'createToken') && method_exists($gateway['classname'], 'chargeToken')) {
                $gateways[] = $gateway;
            }
        }

        return $gateways;
    }

    private function loadGatewaysByPath(): void
    {
        ee()->load->helper(['data_formatting', 'file']);

        $loadedGateways = [];

        foreach (ee()->cartthrob_payments->paths() as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach (get_filenames($path, true) as $file) {
                $class = basename($file, '.php');

                if ($class === 'CartThrob\PaymentGateways\Cartthrob_payment_gateway' ||
                    !preg_match('/^Cartthrob_/', $class) ||
                    in_array($class, $loadedGateways) ||
                    !class_exists($class)) {
                    continue;
                }

                $loadedGateways[] = $class;

                $classObj = new $class();

                $gatewayVars = get_object_vars($classObj);
                $gatewayVars['extload'] = method_exists($classObj, 'extload');
                $gatewayVars['classname'] = $class;

                unset($gatewayVars['core']);

                $this->gateways[] = $gatewayVars;
            }
        }
    }

    private function loadGatewaysByPluginService(): void
    {
        ee('cartthrob:PluginService')
            ->getByType(PluginService::TYPE_PAYMENT)
            ->each(function (PaymentPlugin $plugin) {
                $classObj = new $plugin();

                $gatewayVars = get_object_vars($classObj);
                $gatewayVars['extload'] = method_exists($classObj, 'extload');
                $gatewayVars['classname'] = get_class($classObj);

                unset($gatewayVars['core']);

                $this->gateways[] = $gatewayVars;
            });
    }
}
