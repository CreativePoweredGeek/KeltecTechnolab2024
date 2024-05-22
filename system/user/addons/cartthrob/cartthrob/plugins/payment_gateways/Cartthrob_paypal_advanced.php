<?php

use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Dependency\Omnipay\PayPal\Message\RestResponse;
use CartThrob\Dependency\Omnipay\PayPal\RestGateway;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Transactions\TransactionState;

class Cartthrob_paypal_advanced extends PaymentPlugin
{
    public $title = 'ct.payments.paypal_advanced.title';

    public $overview = 'ct.payments.paypal_advanced.overview';

    public $note = 'ct.payments.paypal_advanced.note';

    public $settings = [
        [
            'name' => 'ct.payments.paypal_advanced.api.sandbox.client_id',
            'short_name' => 'sandbox_client_id',
            'note' => 'ct.payments.paypal_advanced.api.sandbox.client_id.note',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_advanced.api.sandbox.secret',
            'note' => 'ct.payments.paypal_advanced.api.sandbox.secret.note',
            'short_name' => 'sandbox_secret',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_advanced.api.client_id',
            'short_name' => 'client_id',
            'note' => 'ct.payments.paypal_advanced.api.client_id.note',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_advanced.api.secret',
            'note' => 'ct.payments.paypal_advanced.api.sandbox.client_id.note',
            'short_name' => 'secret',
            'type' => 'text',
            'default' => '',
        ],
        [
            'name' => 'ct.payments.paypal_advanced.api.mode',
            'short_name' => 'mode',
            'type' => 'select',
            'options' => [
                'test' => 'sandbox',
                'live' => 'live',
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
        'shipping_phone',
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

    public function __construct()
    {
        $this->client_id = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('client_id') : $this->plugin_settings('sandbox_client_id');
        $this->secret = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('secret') : $this->plugin_settings('sandbox_secret');
        $this->omnipayGateway = Omnipay::create('\CartThrob\PaymentGateways\PayPal\RestGateway');
        $this->omnipayGateway->initialize([
            'clientId' => $this->client_id,
            'secret' => $this->secret,
            'testMode' => $this->plugin_settings('mode') !== 'live',
        ]);
        $this->embedded_fields = ee('View')->make('cartthrob:payment_gateways/paypal_advanced/embedded_fields')->render();
    }

    /**
     * @param string $creditCardNumber
     * @return TransactionState
     */
    public function charge(string $creditCardNumber): TransactionState
    {
        $paypal_order_id = ee()->input->post('paypal-order-id') ?? null;
        if (!$paypal_order_id) {
            return $this->fail('PayPal Payment Details are missing from POST payload :(');
        }

        $parameters = [
            'order_id' => $paypal_order_id,
        ];

        $request = $this->omnipayGateway->capturePayment($parameters)->send();
        if (!$request instanceof RestResponse) {
            return $this->fail('No response from PayPal on order verification');
        }

        if (!$request->isSuccessful()) {
            return $this->failedRequest($request);
        }

        if (!$this->isSuccessful($request)) {
            return $this->fail($request->getTransactionReference());
        }

        return $this->authorize($request->getTransactionReference());
    }

    /**
     * @param RestResponse $request
     * @return bool
     */
    protected function isSuccessful(RestResponse $request): bool
    {
        $data = $request->getData();
        if (isset($data['purchase_units']['0']['payments']['captures']) && is_array($data['purchase_units']['0']['payments']['captures'])) {
            foreach ($data['purchase_units']['0']['payments']['captures'] as $payment) {
                if (isset($payment['status']) && $payment['status'] == 'COMPLETED') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param RestResponse $response
     * @return TransactionState
     */
    protected function failedRequest(RestResponse $response): TransactionState
    {
        $error = $response->getData();
        $name = $error['name'] ?? 'UNPROCESSABLE_ENTITY';
        $details = $error['details']['0']['issue'] ?? '';
        $details .= ' ' . $error['details']['0']['description'] ?? '';

        return $this->fail($name . ' : ' . $details . ' : ' . $response->getMessage());
    }

    /**
     * @param $transactionId
     * @param $amount
     * @param $lastFour
     * @return TransactionState|void
     * @todo extend paypal_standard gateway once approved
     */
    public function refund($transactionId, $amount, $lastFour)
    {
    }

    /**
     * @return string
     */
    public function form_extra()
    {
        $oauth_token = $this->omnipayGateway->getToken();
        $client_token = $this->omnipayGateway->getClientToken()->send();
        $token_data = $client_token->getData();
        $order_data = $this->createCheckoutOrder();
        $view_params = [
            'token' => $token_data['client_token'] ?? null,
            'client_id' => $this->client_id,
            'order_id' => $order_data['id'],
        ];

        $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/paypal_advanced/form_extra')->render($view_params);

        return $this->form_extra;
    }

    /**
     * @return mixed
     */
    protected function createCheckoutOrder()
    {
        $parameters = ['intent' => 'CAPTURE', 'amount' => ee()->cartthrob->cart->total(), 'currency' => 'USD'];
        $order_data = $this->omnipayGateway->createOrder($parameters)->send();

        return $order_data->getData();
    }
}
