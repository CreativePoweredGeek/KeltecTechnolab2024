<?php

use CartThrob\Dependency\Academe\AuthorizeNet\Request\AbstractRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\ApiGateway as OmnipayGateway;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\AuthorizeResponse;
use CartThrob\Dependency\Omnipay\Common\CreditCard;
use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Plugins\Payment\RefundInterface;
use CartThrob\Plugins\Payment\TokenInterface;
use CartThrob\Transactions\TransactionState;

class Cartthrob_authorize_net extends PaymentPlugin implements RefundInterface, TokenInterface
{
    public const DEFAULT_ERROR_MESSAGE = 'authorize_net_unknown_error';

    /** @var string */
    public $title = 'authorize_net_title';

    /** @var string */
    public $overview = 'authorize_net_overview';

    public $note = 'ct.payments.authorize.net.note';

    /** @var array */
    public $settings = [
        [
            'name' => 'mode',
            'short_name' => 'mode',
            'type' => 'select',
            'default' => 'test',
            'options' => [
                'test' => 'authorize_net_mode_test',
                'live' => 'authorize_net_mode_live',
            ],
        ],
        [
            'name' => 'authorize_net_api_login_id_test',
            'short_name' => 'api_login_id_test',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_transaction_key_test',
            'short_name' => 'api_transaction_key_test',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_public_client_key_test',
            'short_name' => 'api_public_client_key_test',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_api_login_id_live',
            'short_name' => 'api_login_id_live',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_transaction_key_live',
            'short_name' => 'api_transaction_key_live',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_public_client_key_live',
            'short_name' => 'api_public_client_key_live',
            'type' => 'text',
        ],
        [
            'name' => 'authorize_net_capture',
            'short_name' => 'authorize_net_capture',
            'type' => 'select',
            'default' => 'capture',
            'options' => [
                'capture' => 'authorize_net_auth_and_capture',
                'authorize' => 'authorize_net_auth_only',
            ],
            'note' => 'authorize_net_options',
        ],
    ];

    /** @var array */
    public array $nameless_fields = [
        'credit_card_number',
        'CVV2',
        'expiration_year',
        'expiration_month',
    ];

    /** @var array */
    public $fields = [
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'phone',
        'email_address',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_address',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'card_type',
        'credit_card_number',
        'CVV2',
        'expiration_year',
        'expiration_month',
    ];

    protected array $rules = [
        'api_login_id_live' => 'whenModeIs[live]|required',
        'api_transaction_key_live' => 'whenModeIs[live]|required',
        'api_public_client_key_live' => 'whenModeIs[live]|required',
        'api_login_id_test' => 'whenModeIs[test]|required',
        'api_public_client_key_test' => 'whenModeIs[test]|required',
        'api_transaction_key_test' => 'whenModeIs[test]|required',
    ];

    /** @var OmnipayGateway */
    protected $omnipayGateway;

    /**
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function __construct()
    {
        ee()->load->library('paths');
        $this->omnipayGateway = Omnipay::create('AuthorizeNetApi_Api');

        $mode = $this->plugin_settings('mode');
        $this->omnipayGateway->setAuthName($this->plugin_settings('api_login_id_' . $mode));
        $this->omnipayGateway->setTransactionKey($this->plugin_settings('api_transaction_key_' . $mode));
        if ($mode == 'test') {
            $this->omnipayGateway->setTestMode(true);
        }
    }

    /**
     * @return TransactionState
     */
    public function charge()
    {
        $params = [
            'amount' => $this->total(),
            'currency' => strtolower($this->order('currency_code') ? $this->order('currency_code') : 'USD'),
            'invoiceNumber' => $this->order('title'),
            'description' => $this->order('title') . ' (' . $this->orderId() . ')',
        ];

        // if there's no opaque Authorize.net tokens it means that the end user doesn't have javascript enabled
        if (!ee()->input->post('opaqueDataDescriptor') && !ee()->input->post('opaqueDataValue')) {
            return $this->fail(ee()->lang->line('authorize_net_javascript_required'));
        } else {
            $params['opaqueDataDescriptor'] = ee()->input->post('opaqueDataDescriptor');
            $params['opaqueDataValue'] = ee()->input->post('opaqueDataValue');
        }

        $customerInformation = $this->compileCustomerInfo();
        $params['card'] = new CreditCard($customerInformation);

        try {
            $auth_capture = $this->plugin_settings('authorize_net_capture');
            if ($auth_capture == 'authorize') {
                /** @var AuthorizeResponse $response */
                $response = $this->createRequest('authorize', $params)->send();
            } else {
                /** @var AuthorizeResponse $response */
                $response = $this->createRequest('purchase', $params)->send();
            }

            if (!$response->isSuccessful()) {
                return $this->fail($response->getMessage());
            }

            return $this->authorize($response->getTransactionReference());
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Builds the customer data array
     * @return array
     */
    protected function compileCustomerInfo()
    {
        $customerInformation = [];

        /*** Billing Information ***/
        // billing first name
        if (ee('cartthrob:InputService')->data('first_name')) {
            $customerInformation['billingFirstName'] = ee('cartthrob:InputService')->data('first_name');
        }
        // billing last name
        if (ee('cartthrob:InputService')->data('last_name')) {
            $customerInformation['billingLastName'] = ee('cartthrob:InputService')->data('last_name');
        }
        // billing address
        if (ee('cartthrob:InputService')->data('address')) {
            $customerInformation['billingAddress1'] = ee('cartthrob:InputService')->data('address');
        }
        // billing address2
        if (ee('cartthrob:InputService')->data('address2')) {
            $customerInformation['billingAddress2'] = ee('cartthrob:InputService')->data('address2');
        }
        // billing city
        if (ee('cartthrob:InputService')->data('city')) {
            $customerInformation['billingCity'] = ee('cartthrob:InputService')->data('city');
        }
        // billing state
        if (ee('cartthrob:InputService')->data('state')) {
            $customerInformation['billingState'] = ee('cartthrob:InputService')->data('state');
        }
        // billing zip
        if (ee('cartthrob:InputService')->data('zip')) {
            $customerInformation['billingPostcode'] = ee('cartthrob:InputService')->data('zip');
        }
        // email address
        if (ee('cartthrob:InputService')->data('email_address')) {
            $customerInformation['email'] = ee('cartthrob:InputService')->data('email_address');
        }
        // email address
        if (ee('cartthrob:InputService')->data('phone')) {
            $customerInformation['billingPhone'] = ee('cartthrob:InputService')->data('phone');
        }

        /*** Shipping Information ***/
        // shipping first name
        if (ee('cartthrob:InputService')->data('shipping_first_name')) {
            $customerInformation['shippingFirstName'] = ee('cartthrob:InputService')->data('shipping_first_name');
        }
        // shipping last name
        if (ee('cartthrob:InputService')->data('shipping_last_name')) {
            $customerInformation['shippingLastName'] = ee('cartthrob:InputService')->data('shipping_last_name');
        }
        // shipping address
        if (ee('cartthrob:InputService')->data('shipping_address')) {
            $customerInformation['shippingAddress1'] = ee('cartthrob:InputService')->data('shipping_address');
        }
        // shipping address
        if (ee('cartthrob:InputService')->data('shipping_address2')) {
            $customerInformation['shippingAddress2'] = ee('cartthrob:InputService')->data('shipping_address2');
        }
        // shipping city
        if (ee('cartthrob:InputService')->data('shipping_city')) {
            $customerInformation['shippingCity'] = ee('cartthrob:InputService')->data('shipping_city');
        }
        // shipping state
        if (ee('cartthrob:InputService')->data('shipping_state')) {
            $customerInformation['shippingState'] = ee('cartthrob:InputService')->data('shipping_state');
        }
        // shipping zip
        if (ee('cartthrob:InputService')->data('shipping_zip')) {
            $customerInformation['shippingPostcode'] = ee('cartthrob:InputService')->data('shipping_zip');
        }

        return $customerInformation;
    }

    /**
     * @param $transactionId
     * @param $amount
     * @param $creditCardNumber
     * @param $extra
     * @return TransactionState
     */
    public function refund($transactionId, $amount, $creditCardNumber, $extra): TransactionState
    {
        try {
            $response = $this->omnipayGateway->refund([
                'transactionReference' => $transactionId,
                'amount' => $amount,
                'numberLastFour' => $creditCardNumber,
            ])->send();

            if (!$response->isSuccessful()) {
                return $this->fail($response->getMessage());
            }

            return $this->authorize($response->getTransactionReference());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @param $ignored
     * @return TransactionState|Cartthrob_token
     */
    public function createToken($creditCardNumber): Cartthrob_token
    {
        $gateway = $this->buildCimDriver();
        $request_data = [
            'opaqueDataDescriptor' => ee()->input->post('opaqueDataDescriptor'),
            'opaqueDataValue' => ee()->input->post('opaqueDataValue'),
            'name' => ee('cartthrob:InputService')->data('first_name') . ' ' . ee('cartthrob:InputService')->data('last_name'),
            'email' => ee('cartthrob:InputService')->data('email_address'), // Authorize.net will use the email to identify the CustomerProfile
            'customerType' => 'individual',
            'testMode' => true,
            'customerId' => null, // a customer ID generated by your system or send null
            'description' => 'MEMBER', // whichever description you wish to send
            'forceCardUpdate' => true,
            'card' => $this->compileCustomerInfo(),
        ];

        $request = $gateway->createCard($request_data);
        $response = $request->send();
        $data = $response->getData();
        $token = new Cartthrob_token();
        if (!empty($data['paymentProfile']['customerProfileId']) && !empty($data['paymentProfile']['customerPaymentProfileId'])) {
            return $token->set_customer_id($data['paymentProfile']['customerProfileId'])->set_token($data['paymentProfile']['customerPaymentProfileId']);
        }

        return $token->set_error_message(ee()->lang->line('authorizenet_unknown_token_error'));
    }

    /**
     * @param $token
     * @param $customer_id
     * @return TransactionState
     */
    public function chargeToken($token, $customer_id): TransactionState
    {
        $params = [
            'amount' => $this->total(),
            'currency' => strtolower($this->order('currency_code') ? $this->order('currency_code') : 'USD'),
            'invoiceNumber' => $this->order('title'),
            'description' => $this->order('title') . ' (' . $this->orderId() . ')',
            'cardReference' => json_encode(['customerProfileId' => trim($customer_id), 'customerPaymentProfileId' => trim($token)]),
        ];

        $gateway = $this->buildCimDriver();

        $auth_capture = $this->plugin_settings('authorize_net_capture');
        if ($auth_capture == 'authorize') {
            /** @var AuthorizeResponse $response */
            $response = $gateway->authorize($params)->setToken(trim($token))->send();
        } else {
            /** @var AuthorizeResponse $response */
            $response = $gateway->purchase($params)->setToken(trim($token))->send();
        }

        if (!$response->isSuccessful()) {
            return $this->fail($response->getMessage());
        }

        return $this->authorize($response->getTransactionReference());
    }

    /**
     * @return \Omnipay\Common\GatewayInterface
     */
    protected function buildCimDriver()
    {
        $mode = $this->plugin_settings('mode');
        $gateway = Omnipay::create('AuthorizeNet_CIM');
        $gateway->setApiLoginId($this->plugin_settings('api_login_id_' . $mode));
        $gateway->setTransactionKey($this->plugin_settings('api_transaction_key_' . $mode));
        if ($mode == 'test') {
            $gateway->setDeveloperMode(true);
        }

        return $gateway;
    }

    /**
     * @return string
     */
    public function form_extra()
    {
        $mode = $this->plugin_settings('mode');
        $view_params = [
            'mode' => $mode,
            'client_key' => $this->plugin_settings('api_public_client_key_' . $mode),
            'api_login_id' => $this->plugin_settings('api_login_id_' . $mode),
        ];

        $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/authorize_net/form_extra')->render($view_params);

        return $this->form_extra;
    }

    /**
     * @param string $method
     * @param array $params
     * @return AbstractRequest
     */
    protected function createRequest(string $method, array $params)
    {
        /** @var AbstractRequest $request */
        $request = $this->omnipayGateway->$method($params);

        return $request;
    }
}
