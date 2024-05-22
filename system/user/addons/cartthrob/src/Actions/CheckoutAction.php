<?php

namespace CartThrob\Actions;

use CartThrob\Math\Number;
use CartThrob\Request\Request;
use CartThrob\Transactions\TransactionState;
use EE_Session;

class CheckoutAction extends Action
{
    public const ASYNC_METHOD_DISABLED = 0;

    public function __construct(EE_Session $session, Request $request)
    {
        parent::__construct($session, $request);

        ee()->load->library(['languages', 'cartthrob_payments', 'form_validation', 'form_builder']);
        ee()->load->model(['subscription_model', 'vault_model']);
    }

    public function process()
    {
        /* 2. Give hooks a chance to bail out * */
        if (ee()->extensions->active_hook('cartthrob_checkout_action_start') === true) {
            ee()->extensions->call('cartthrob_checkout_action_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        /* 3. Process checkout options */
        $checkoutOptions = $this->marshalCheckoutOptions();

        /* 4. Maybe save user info */
        $this->processCustomerInfo($checkoutOptions);

        /* 5. Load libraries */
        ee()->languages->set_language($this->request->input('language'));

        ee()->cartthrob_payments->setGateway($checkoutOptions['gateway']);
        ee()->cartthrob_payments->setGatewayMethod($checkoutOptions['gateway_method']);

        /* 6. start the form */
        $this->setGlobalValues();

        ee()->form_builder->set_value(['coupon_code']);
        ee()->form_builder
            ->set_show_errors(true)
            ->set_captcha($this->session->userdata('member_id') == 0 && ee()->cartthrob->store->config('checkout_form_captcha'))
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->set_error_callback([ee()->cartthrob, 'action_complete']);

        if (!$checkoutOptions['create_user'] && !$this->session->userdata('member_id') && ee()->cartthrob->store->config('logged_in')) {
            return ee()->form_builder
                ->add_error(ee()->lang->line('must_be_logged_in'))
                ->action_complete();
        }

        /* 7. Validate required fields */
        $not_required = explode('|', $this->request->decode('NRQ', ''));
        $required = array_diff(ee()->cartthrob_payments->requiredGatewayFields(), $not_required);

        if (!ee()->form_builder->set_required($required)->validate()) {
            return ee()->form_builder->action_complete();
        }

        /* 8. Bail out if user required but no user */
        if (ee()->cartthrob->store->config('logged_in') && !($checkoutOptions['create_user'] || $this->session->userdata('member_id'))) {
            return ee()->form_builder
                ->add_error(ee()->lang->line('must_be_logged_in'))
                ->action_complete();
        }

        /* 9. Add coupon if present */
        if ($this->request->has('coupon_code')) {
            ee()->cartthrob->cart->add_coupon_code($this->request->input('coupon_code', true));
        }

        /* 10. Subscription stuff  (maybe) */
        $this->processSubscriptionOptions($checkoutOptions);

        /* 11. Create user (maybe) */
        $this->createUser($checkoutOptions);

        $async = $this->isAsyncCheckout($checkoutOptions);

        $method = $async ? 'asyncCheckoutStart' : 'checkoutStart';
        $state = ee()->cartthrob_payments->{$method}($checkoutOptions);

        if (!$state instanceof TransactionState) {
            return ee()->form_builder
                ->add_error(ee()->cartthrob_payments->errors())
                ->action_complete();
        }

        $method = $async ? 'asyncCheckoutComplete' : 'checkoutComplete';

        ee()->cartthrob_payments->{$method}($state);
    }

    /**
     * @return array
     */
    protected function marshalCheckoutOptions(): array
    {
        $checkoutOptions = [];

        $checkoutOptions['create_user'] = bool_string($this->request->input('create_user'));

        if ($this->request->has('order_id')) {
            $checkoutOptions['update_order_id'] = $this->request->input('order_id');
        }

        if (ee()->cartthrob->store->config('allow_gateway_selection') && $this->request->has('gateway')) {
            $checkoutOptions['gateway'] = $this->request->decode('gateway');
        } elseif (ee()->cartthrob->store->config('allow_gateway_selection') && ee()->cartthrob->cart->customer_info('gateway')) {
            $checkoutOptions['gateway'] = ee()->cartthrob->cart->customer_info('gateway');
        } else {
            $checkoutOptions['gateway'] = ee()->cartthrob->store->config('payment_gateway');
        }

        if (empty($checkoutOptions['gateway'])) {
            $checkoutOptions['gateway'] = ee()->cartthrob->store->config('payment_gateway');
        }

        $checkoutOptions['gateway_method'] = $this->request->input('gateway_method');
        $checkoutOptions['credit_card_number'] = sanitize_credit_card_number($this->request->input('credit_card_number'));

        if ($this->request->has('EXP')) {
            $expiration = $this->request->decode('EXP');

            if ($expiration == abs(Number::sanitize($expiration))) {
                $checkoutOptions['expiration_date'] = $expiration;
            }
        }

        $checkoutOptions['tax'] = ee()->cartthrob->cart->tax();
        $checkoutOptions['shipping'] = ee()->cartthrob->cart->shipping();
        // discount MUST be calculated before shipping to set shipping free, etc.
        $checkoutOptions['discount'] = ee()->cartthrob->cart->discount();
        $checkoutOptions['shipping'] = ee()->cartthrob->cart->shipping();
        $checkoutOptions['shipping_plus_tax'] = ee()->cartthrob->cart->shipping_plus_tax();
        $checkoutOptions['subtotal'] = ee()->cartthrob->cart->subtotal();
        $checkoutOptions['subtotal_plus_tax'] = ee()->cartthrob->cart->subtotal_with_tax();
        $checkoutOptions['total'] = ee()->cartthrob->cart->total();

        if ($this->request->has('TX')) {
            $tax = $this->request->decode('TX');

            if ($tax == abs(Number::sanitize($tax))) {
                $checkoutOptions['total'] -= $checkoutOptions['tax'];
                $checkoutOptions['tax'] = $tax;
                $checkoutOptions['total'] += $checkoutOptions['tax'];
                unset($checkoutOptions['subtotal_plus_tax']);
                unset($checkoutOptions['shipping_plus_tax']);
            }
        }

        if ($this->request->has('SHP')) {
            $shipping = $this->request->decode('SHP');

            if ($shipping == abs(Number::sanitize($shipping))) {
                $checkoutOptions['total'] -= $checkoutOptions['shipping'];
                $checkoutOptions['shipping'] = $shipping;
                $checkoutOptions['total'] += $checkoutOptions['shipping'];
                unset($checkoutOptions['shipping_plus_tax']);
            }
        } elseif ($this->request->decode('AUS', false, true) && !is_null($this->request->input('shipping'))) {
            $shipping = $this->request->decode('shipping');

            $checkoutOptions['total'] -= $checkoutOptions['shipping'];
            $checkoutOptions['shipping'] = $shipping;
            $checkoutOptions['total'] += $checkoutOptions['shipping'];

            unset($checkoutOptions['shipping_plus_tax']);
        }

        $checkoutOptions['group_id'] = 5;

        if ($this->request->has('GI')) {
            $checkoutOptions['group_id'] = $this->request->decode('GI');

            if ($checkoutOptions['group_id'] < 5) {
                $checkoutOptions['group_id'] = 5;
            }
        }

        if ($this->request->has('PR')) {
            $expiration = $this->request->decode('PR');

            if ($expiration == abs(Number::sanitize($expiration))) {
                $checkoutOptions['total'] -= $checkoutOptions['subtotal'];
                $checkoutOptions['subtotal'] = $expiration;
                $checkoutOptions['total'] += $checkoutOptions['subtotal'];

                unset($checkoutOptions['subtotal_plus_tax']);
            }
        } elseif ($this->request->decode('AUP', false, true) && !is_null($this->request->input('price'))) {
            $checkoutOptions['total'] = abs(Number::sanitize($this->request->input('price')));
        }

        $checkoutOptions['subscription'] = $this->request->decode('SUB', false, true) || $this->request->boolean('sub_id');
        $checkoutOptions['subscription_options'] = [];
        $checkoutOptions['force_vault'] = $this->request->decode('VLT', $default = false, $boolean = true);
        $checkoutOptions['force_processing'] = $this->request->decode('FPR', $default = false, $boolean = true);

        if (isset($_POST['member_id']) && in_array($this->session->userdata('group_id'), ee()->config->item('cartthrob:admin_checkout_groups'))) {
            $checkoutOptions['member_id'] = $this->session->cache['cartthrob']['member_id'] = $this->request->input('member_id');
        }

        if ($this->request->has('vault_id')) {
            $vault_id = $this->request->decode('vault_id');

            if ($vault_id == abs(Number::sanitize($vault_id)) && $vault = ee()->vault_model->get_vault($vault_id)) {
                $checkoutOptions['vault'] = $vault;
            }
        }

        // if the sub_id is passed in, we're deleting the cart contents, and only updating the sub
        if ($this->request->has('sub_id')) {
            $checkoutOptions['update_subscription_id'] = $this->request->input('sub_id');
        }

        return $checkoutOptions;
    }

    /**
     * @param array $checkoutOptions
     * @return void
     */
    protected function processCustomerInfo(array $checkoutOptions): void
    {
        // if you're logged in as an admin, and you're creating a user, we don't want to save the info now, or your admin info will be overwritten
        if ($checkoutOptions['create_user'] === true) {
            if (!in_array($this->session->userdata('group_id'), ee()->config->item('cartthrob:admin_checkout_groups'))) {
                // Save the current customer info for use after checkout
                // needed for return trip after offsite processing
                ee()->cartthrob->save_customer_info();
            } elseif (ee()->cartthrob->cart->customer_info('email_address') == $this->request->input('email_address')) {
                // admin checkout with create user turned on... but checking out with their own account
                ee()->cartthrob->save_customer_info();
            }
        } elseif ($this->request->has('member_id') && in_array($this->session->userdata('group_id'), ee()->config->item('cartthrob:admin_checkout_groups'))) {
            // Save the current customer info for use after checkout
            // needed for return trip after offsite processing
            ee()->cartthrob->cart->set_meta('checkout_as_member', $this->request->input('member_id'));
        } elseif (!$this->request->has('order_id')) {
            // we also don't want to save data if you're tring to update an order id at this point.
            ee()->cartthrob->save_customer_info();
        }
    }

    /**
     * @param array $checkoutOptions
     * @return void
     */
    protected function processSubscriptionOptions(array &$checkoutOptions): void
    {
        if ($checkoutOptions['subscription'] === false) {
            return;
        }

        // iterating through those options. if they're in post, we'll add them to the "subscription_options" meta
        foreach (ee()->subscription_model->option_keys() as $encoded_key => $key) {
            $option = null;

            if ($this->request->has($encoded_key)) {
                $option = $this->request->decode($encoded_key);
            } elseif ($this->request->has('subscription_' . $key)) {
                if ($key == 'name' || $key == 'description') {
                    $option = $this->request->input('subscription_' . $key);
                } else {
                    $option = $this->request->decode('subscription_' . $key);
                }
            } elseif ($this->request->has($key)) {
                $option = $this->request->input($key);
            }

            if (!is_null($option)) {
                if (in_array($encoded_key, ee()->subscription_model->encoded_bools())) {
                    $option = bool_string($option);
                }

                if (strncmp($key, 'subscription_', 13) === 0) {
                    $key = substr($key, 13);
                }

                $checkoutOptions['subscription_options'][$key] = $option;
            }
        }
    }

    /**
     * @param array $checkoutOptions
     * @return void
     */
    protected function createUser(array &$checkoutOptions): void
    {
        if (false === $checkoutOptions['create_user']) {
            return;
        }

        $checkoutOptions['create_username'] = $this->request->input('username');
        $checkoutOptions['create_email'] = $this->request->has('email_address') ? $this->request->input('email_address') : ee()->cartthrob->cart->customer_info('email_address');
        $checkoutOptions['create_screen_name'] = $this->request->input('screen_name');
        $checkoutOptions['create_password'] = $this->request->input('password');
        $checkoutOptions['create_group_id'] = $checkoutOptions['group_id'];
        $checkoutOptions['create_password_confirm'] = $this->request->input('password_confirm');
        $checkoutOptions['create_language'] = ee()->cartthrob->cart->customer_info('language');
    }

    /**
     * @param $checkoutOptions
     * @return bool
     */
    protected function isAsyncCheckout($checkoutOptions): bool
    {
        return ((int)ee()->cartthrob->config('orders_async_method') !== static::ASYNC_METHOD_DISABLED)
            && element('order_id', $checkoutOptions) === false
            && element('update_order_id', $checkoutOptions) === false
            && element('subscription_id', $checkoutOptions) === false
            && element('is_subscription_rebill', $checkoutOptions) === false
            && element('update_subscription_id', $checkoutOptions) === false;
    }
}
