<?php

use CartThrob\Dependency\Omnipay\Stripe\Message\PaymentIntents\Response;
use CartThrob\Exceptions\CartThrobException;
use CartThrob\Transactions\TransactionState;

class Cartthrob_stripe_payments extends Cartthrob_stripe
{
    /** @var string */
    public $title = 'stripe_payments_title';

    /** @var string */
    public $overview = 'stripe_payments_overview';

    public $note = 'ct.payments.stripe_payments.note';

    /** @var array */
    public $settings = [
        [
            'name' => 'mode',
            'short_name' => 'mode',
            'type' => 'select',
            'default' => 'test',
            'options' => [
                'test' => 'stripe_mode_test',
                'live' => 'stripe_mode_live',
            ],
        ],
        [
            'name' => 'stripe_private_key',
            'short_name' => 'api_key_test_secret',
            'type' => 'text',
        ],
        [
            'name' => 'stripe_api_key',
            'short_name' => 'api_key_test_publishable',
            'type' => 'text',
        ],
        [
            'name' => 'stripe_live_key_secret',
            'short_name' => 'api_key_live_secret',
            'type' => 'text',
        ],
        [
            'name' => 'stripe_live_key',
            'short_name' => 'api_key_live_publishable',
            'type' => 'text',
        ],
        [
            'name' => 'ct.payments.stripe_payments.enable_fields',
            'note' => 'ct.payments.stripe_payments.enable_fields.note',
            'short_name' => 'enable_fields',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
            'default' => 'y',
        ],
        [
            'name' => 'stripe_element_theme',
            'short_name' => 'theme',
            'type' => 'select',
            'default' => 'stripe',
            'note' => 'stripe_element_theme_note',
            'options' => [
                'none' => 'stripe_element_theme_none',
                'stripe' => 'stripe_element_theme_stripe',
                'night' => 'stripe_element_theme_night',
                'flat' => 'stripe_element_theme_flat',
            ],
        ],
        [
            'name' => 'stripe_element_style_labels',
            'short_name' => 'labels',
            'type' => 'select',
            'default' => 'floating',
            'note' => 'stripe_element_style_labels_note',
            'options' => [
                'floating' => 'stripe_element_label_floating',
                'above' => 'stripe_element_style_above',
            ],
        ],
        [
            'name' => 'stripe_element_style_variables',
            'short_name' => 'variables',
            'note' => 'stripe_element_style_variables_note',
            'type' => 'textarea',
        ],
        [
            'name' => 'stripe_element_style_rules',
            'short_name' => 'rules',
            'note' => 'stripe_element_style_rules_note',
            'type' => 'textarea',
        ],
    ];

    protected array $rules = [
        'api_key_live_secret' => 'whenModeIs[live]|required',
        'api_key_live_publishable' => 'whenModeIs[live]|required',
        'api_key_test_secret' => 'whenModeIs[test]|required',
        'api_key_test_publishable' => 'whenModeIs[test]|required',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->embedded_fields = ee('View')->make('cartthrob:payment_gateways/stripe_payments/embedded_fields')->render();
        if ($this->plugin_settings('enable_fields', 'y') == 'n') {
            $this->fields = [];
        }
    }

    public function vault_form_extra()
    {
        $options = [];
        $request = $this->createRequest('CreateCustomer', $options);
        $customer = $request->sendData($options)->getData();
        if (isset($customer['error']['code'])) {
            return $this->form_extra;
        }

        $customer_id = $customer['id'] ?? false;
        if ($customer_id) {
            $options = [
                'payment_method_types' => ['card'],
                'customer_id' => $customer_id,
                'setup_future_usage' => 'off_session',
            ];
            $setup_intent = $this->createRequest('CreateSetupIntent', $options)->send();
            $intent = $setup_intent->getData();
            if (isset($intent['error']['code'])) {
                return $this->form_extra;
            }

            $view_params = [
                'return_url' => ee()->cartthrob_payments->responseUrl('stripe_payments'),
                'publishable_key' => $this->publishableKey,
                'client_secret' => $intent['client_secret'],
                'theme' => $this->plugin_settings('theme'),
                'labels' => $this->plugin_settings('labels'),
                'variables' => $this->plugin_settings('variables'),
                'rules' => $this->plugin_settings('rules'),
            ];

            $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/stripe_payments/vault_form_extra')->render($view_params);
        }

        return $this->form_extra;
    }

    public function form_extra()
    {
        if (!ee()->cartthrob->cart->total()) {
            return;
        }

        $currency = ee('cartthrob:SettingsService')->get('cartthrob', 'number_format_defaults_currency_code') ?? 'usd';
        $options = [
            'amount' => ee()->cartthrob->cart->total(),
            'currency' => strtolower($currency),
            'paymentMethod' => '',
        ];

        $request = $this->createRequest('Authorize', $options);
        $data = $request->getData();
        $data['capture_method'] = 'automatic';
        $data['confirmation_method'] = 'automatic';

        if ($this->cartHasSub()) {
            $data['setup_future_usage'] = 'off_session';
        }

        $payment = $request->sendData($data)->getData();
        if (isset($payment['error']['code'])) {
            return;
        }

        $view_params = [
            'publishable_key' => $this->publishableKey,
            'client_secret' => $payment['client_secret'],
            'theme' => $this->plugin_settings('theme'),
            'labels' => $this->plugin_settings('labels'),
            'variables' => $this->plugin_settings('variables'),
            'rules' => $this->plugin_settings('rules'),
        ];

        $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/stripe_payments/form_extra')->render($view_params);

        return $this->form_extra;
    }

    /**
     * ONLY handles the confirmation of a Payment Intent
     *  We already have the money if the user made it here.
     * @param $ignored
     * @return \CartThrob\Transactions\TransactionState
     * @throws CartThrobException
     */
    public function charge($ignored)
    {
        if (!ee()->input->post('payment-intent-id') ||
            !ee()->input->post('client-secret') ||
            !ee()->input->post('payment-method-id')
        ) {
            return $this->fail('Stripe Payment Details are missing from POST payload :(');
        }

        $params = [
            'paymentIntentReference' => ee()->input->post('payment-intent-id'),
        ];

        $request = $this->createRequest('fetchPaymentIntent', $params)->send();
        if (!$request instanceof Response) {
            return $this->fail('No response from Stripe on PaymentIntent verification');
        }

        $payment_intent = $request->getData();
        if (element('errors', $payment_intent)) {
            return $this->fail("Can't find Payment Intent");
        }

        if (element('id', $payment_intent) != ee()->input->post('payment-intent-id')) {
            return $this->fail("Payment Intent doesn't match what was expected");
        }

        if (element('client_secret', $payment_intent) != ee()->input->post('client-secret')) {
            return $this->fail("Client Secret doesn't match what was expected");
        }

        if (!$request->isSuccessful()) {
            return $this->fail($request->getMessage() . ' (' . $request->getCode() . ')');
        }

        if ($request->isRedirect()) {
            $request->redirect();
            exit;
        }

        if (!empty($payment_intent['charges']['data']['0']['payment_method_details']['card'])) {
            $this->saveCardDetailsToOrder($payment_intent);
        }

        return $this->authorize($payment_intent['id']);
    }

    /**
     * @param $token
     * @param $customer_id
     * @return TransactionState
     */
    public function chargeToken($token, $customer_id): TransactionState
    {
        if (ee()->input->post('payment-intent-id')) {
            return $this->authorize(ee()->input->post('payment-intent-id'));
        }

        return parent::chargeToken($token, $customer_id);
    }

    /**
     * Where users are returned AFTER checkout has started BUT are required to head
     *  offsite for various reasons (likely confirmation).
     * @return string
     */
    protected function generateReturnUrl()
    {
        ee()->load->library('paths');
        $enc = ee('Encrypt');

        return ee()->paths->build_action_url(
            'Cartthrob',
            'payment_return_action',
            [
                'method' => base64_encode($enc->encode('completeCheckout')),
                'gateway' => base64_encode($enc->encode(__CLASS__)),
                'orderId' => base64_encode($enc->encode($this->orderId())),
            ]
        );
    }

    /**
     * Since Stripe Payments doens't allow for payment_method inspection in the JS API
     *  we have to update the Order payment meta explicitly. Not a BIG deal, but does smell
     * @param array $payment_intent
     * @return \ExpressionEngine\Model\Content\ContentModel
     */
    protected function saveCardDetailsToOrder(array $payment_intent)
    {
        $entry = ee('Model')
            ->get('ChannelEntry')
            ->with('Channel')
            ->filter('entry_id', $this->orderId())
            ->first();

        if ($entry instanceof \ExpressionEngine\Model\Channel\ChannelEntry) {
            if (ee()->cartthrob->store->config('orders_card_type')) {
                $field = 'field_id_' . ee()->cartthrob->store->config('orders_card_type');
                $entry->$field = $payment_intent['charges']['data']['0']['payment_method_details']['card']['brand'];
            }

            if (ee()->cartthrob->store->config('orders_last_four_digits')) {
                $field = 'field_id_' . ee()->cartthrob->store->config('orders_last_four_digits');
                $entry->$field = $payment_intent['charges']['data']['0']['payment_method_details']['card']['last4'];
            }

            return $entry->save();
        }
    }

    /**
     * Determines if we need to setup future_usage for a Token later
     * @return bool
     */
    protected function cartHasSub()
    {
        foreach (ee()->cartthrob->cart->items_array() as $item) {
            if (!empty($item['meta']['subscription_options'])) {
                return true;
            }
        }

        return false;
    }
}
