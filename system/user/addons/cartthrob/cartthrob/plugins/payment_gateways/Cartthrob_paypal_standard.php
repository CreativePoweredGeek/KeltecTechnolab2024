<?php

use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Dependency\Omnipay\PayPal\Message\RestResponse;
use CartThrob\Dependency\Omnipay\PayPal\RestGateway;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Transactions\TransactionState;

class Cartthrob_paypal_standard extends PaymentPlugin
{
    public $title = 'ct.payments.paypal_standard.title';

    public $overview = 'ct.payments.paypal_standard.overview';

    public $note = 'ct.payments.gateway.paypal_standard.note';

    public $settings = [
        [
            'name' => 'ct.payments.paypal_standard.api.sandbox.client_id',
            'short_name' => 'sandbox_client_id',
            'note' => 'ct.payments.paypal_standard.api.sandbox.client_id.note',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_standard.api.sandbox.secret',
            'note' => 'ct.payments.paypal_standard.api.sandbox.secret.note',
            'short_name' => 'sandbox_secret',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_standard.api.client_id',
            'short_name' => 'client_id',
            'note' => 'ct.payments.paypal_standard.api.client_id.note',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_standard.api.secret',
            'note' => 'ct.payments.paypal_standard.api.secret.note',
            'short_name' => 'secret',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_standard.api.mode',
            'note' => 'ct.payments.paypal_standard.api.mode.note',
            'short_name' => 'mode',
            'type' => 'select',
            'options' => [
                'test' => 'sandbox',
                'live' => 'live',
            ],
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_fields',
            'note' => 'ct.payments.paypal_standard.enable_fields.note',
            'short_name' => 'enable_fields',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
            'default' => 'y',
        ],
        [
            'name' => 'ct.payments.paypal_standard.additional_payment_sources',
            'short_name' => 'additional_payment_sources',
            'type' => 'header',
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_credit',
            'note' => 'ct.payments.paypal_standard.enable_credit.note',
            'short_name' => 'enable_credit',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
            'default' => 'y',
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_card',
            'note' => 'ct.payments.paypal_standard.enable_card.note',
            'short_name' => 'enable_card',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
            'default' => 'y',
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_venmo',
            'note' => 'ct.payments.paypal_standard.enable_venmo.note',
            'short_name' => 'enable_venmo',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_mybank',
            'note' => 'ct.payments.paypal_standard.enable_mybank.note',
            'short_name' => 'enable_mybank',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_bancontact',
            'note' => 'ct.payments.paypal_standard.enable_bancontact.note',
            'short_name' => 'enable_bancontact',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
        ],
        [
            'name' => 'ct.payments.paypal_standard.enable_eps',
            'note' => 'ct.payments.paypal_standard.enable_eps.note',
            'short_name' => 'enable_eps',
            'type' => 'select',
            'options' => [
                'n' => 'No',
                'y' => 'Yes',
            ],
        ],
    ];

    public $fields = [
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'company',
        'country_code',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country_code',
        'phone',
        'email_address',
    ];

    protected $client_id;

    protected $secret;

    /**
     * @var RestGateway
     */
    protected $omnipayGateway;

    public $embedded_fields;

    /**
     * @var array|string[]
     */
    protected array $funding_sources = [
        'venmo',
        'mybank',
        'bancontact',
        'eps',
        'giropay',
    ];

    /**
     * @var array|string[]
     */
    protected array $disable_funding = [
        'credit',
        'card',
    ];

    /**
     * A list of PayPal statuses that allow for refunds
     * @var array|string[]
     */
    protected array $refundable_statuses = [
        'partially_refunded',
        'completed',
    ];

    public function __construct()
    {
        $this->client_id = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('client_id') : $this->plugin_settings('sandbox_client_id');
        $this->secret = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('secret') : $this->plugin_settings('sandbox_secret');
        $this->omnipayGateway = Omnipay::create('PayPal\Rest');
        $this->omnipayGateway->initialize([
            'clientId' => $this->client_id,
            'secret' => $this->secret,
            'testMode' => $this->plugin_settings('mode') !== 'live',
        ]);

        $this->embedded_fields = ee('View')->make('cartthrob:payment_gateways/paypal_standard/embedded_fields')->render();
        if ($this->plugin_settings('enable_fields', 'n') == 'n') {
            $this->fields = [];
        }
    }

    public function refund($transactionId, $amount, $lastFour, array $extra = [])
    {
        $state = new TransactionState();
        $params = [
            'transactionReference' => $transactionId,
        ];

        $request = $this->omnipayGateway->fetchTransaction($params)->send();
        if (!$request instanceof RestResponse) {
            return $state->setFailed(lang('ct.payments.paypal_standard.errors.reference_not_found'));
        }

        $payment_ref = $request->getData();

        $status = $payment_ref['state'] ?? null;
        $amount_paid = $payment_ref['amount']['currency'] ?? 0;

        if (!in_array($status, $this->refundable_statuses)) {
            return $state->setFailed(lang('ct.payments.paypal_standard.errors.refund_on_not_completed_order'));
        }

        if ($amount <= $amount_paid) {
            $params['amount'] = $amount;
            $params['currency'] = $payment_ref['amount']['currency'] ?? 'USD';
        }

        $request = $this->omnipayGateway->refund($params)->send();
        $data = $request->getData();
        $status = $data['state'] ?? null;
        $refund_id = $data['id'] ?? null;
        if ($status == 'completed') {
            return $state->setRefunded()->setTransactionId($refund_id);
        }

        return $state->setFailed(lang('ct.payments.paypal_standard.errors.refund_failed'));
    }

    /**
     * @param string $creditCardNumber
     * @return TransactionState
     */
    public function charge(string $creditCardNumber): TransactionState
    {
        $ref = ee()->input->post('ref-id');
        if (!$ref) {
            return $this->fail(lang('ct.payments.paypal_standard.errors.missing_payment_details'));
        }

        $params = [
            'transactionReference' => $ref,
        ];

        $request = $this->omnipayGateway->fetchTransaction($params)->send();
        if (!$request instanceof RestResponse) {
            return $this->fail(lang('ct.payments.paypal_standard.errors.paypal_unresponsive'));
        }

        $payment_ref = $request->getData();
        if (element('errors', $payment_ref)) {
            return $this->fail(lang('ct.payments.paypal_standard.errors.reference_not_found'));
        }

        $status = $payment_ref['state'] ?? null;
        if ($status == 'completed') {
            return $this->authorize($ref);
        }

        return $this->fail(lang('ct.payments.paypal_standard.errors.payment_failed'));
    }

    public function form_extra()
    {
        $view_params = [
            'amount' => ee()->cartthrob->cart->total(),
            'client_id' => $this->client_id,
            'secret' => $this->secret,
            'currency' => ee('cartthrob:SettingsService')->get('cartthrob', 'number_format_defaults_currency_code'),
            'funding' => $this->getFundingSources(),
            'disabled_sources' => $this->getDisabledSources(),
        ];

        $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/paypal_standard/form_extra')->render($view_params);

        return $this->form_extra;
    }

    /**
     * Checks settings and creates an array of the active services
     * @return array
     */
    protected function getFundingSources(): array
    {
        $return = [];
        foreach ($this->funding_sources as $service) {
            if ($this->plugin_settings('enable_' . $service) == 'y') {
                $return[] = $service;
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    protected function getDisabledSources(): array
    {
        $return = [];
        foreach ($this->disable_funding as $service) {
            if ($this->plugin_settings('enable_' . $service) == 'n') {
                $return[] = $service;
            }
        }

        return $return;
    }
}
