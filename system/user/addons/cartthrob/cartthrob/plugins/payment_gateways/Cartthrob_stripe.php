<?php

use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Dependency\Omnipay\Stripe\Message\AbstractRequest;
use CartThrob\Dependency\Omnipay\Stripe\Message\PaymentIntents\CancelPaymentIntentRequest;
use CartThrob\Dependency\Omnipay\Stripe\Message\PaymentIntents\Response as PiResponse;
use CartThrob\Dependency\Omnipay\Stripe\Message\PaymentIntents\Response as StripeResponse;
use CartThrob\Dependency\Omnipay\Stripe\Message\Response;
use CartThrob\Dependency\Omnipay\Stripe\PaymentIntentsGateway as OmnipayGateway;
use CartThrob\Exceptions\CartThrobException;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Plugins\Payment\RefundInterface;
use CartThrob\Plugins\Payment\TokenInterface;
use CartThrob\Plugins\Payment\VoidInterface;
use CartThrob\Transactions\TransactionState;

class Cartthrob_stripe extends PaymentPlugin implements RefundInterface, TokenInterface, VoidInterface
{
    public const STRIPE_VERSION = '2020-08-27';
    public const STATUS_REQUIRES_ACTION = 'requires_action';
    public const STATUS_REQUIRES_CONFIRMATION = 'requires_confirmation';
    public const STATUS_REQUIRES_CAPTURE = 'requires_capture';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const DEFAULT_ERROR_MESSAGE = 'stripe_unknown_error';

    public $note = 'ct.payments.stripe.note';

    /** @var string */
    public $title = 'stripe_title';

    /** @var string */
    public $overview = 'stripe_overview';

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
            'name' => 'stripe_capture',
            'short_name' => 'stripe_capture',
            'type' => 'select',
            'default' => 'capture',
            'options' => [
                'capture' => 'stripe_auth_and_capture',
                'authorize' => 'stripe_auth_only',
            ],
            'note' => 'stripe_auth_note',
        ],
        [
            'name' => 'stripe_hide_postal_code',
            'short_name' => 'hide_postal_code',
            'note' => 'stripe_hide_postal_code_description',
            'type' => 'radio',
            'default' => 'no',
            'options' => [
                'no' => 'no',
                'yes' => 'yes',
            ],
        ],
        [
            'name' => 'stripe_icon_style',
            'short_name' => 'icon_style',
            'note' => 'stripe_icon_style_description',
            'type' => 'radio',
            'default' => 'default',
            'options' => [
                'default' => 'stripe_icon_default',
                'solid' => 'stripe_icon_solid',
            ],
        ],
        [
            'name' => 'stripe_hide_icon',
            'short_name' => 'hide_icon',
            'note' => 'stripe_hide_icon_description',
            'type' => 'radio',
            'default' => 'no',
            'options' => [
                'no' => 'no',
                'yes' => 'yes',
            ],
        ],
        [
            'name' => 'stripe_styles',
            'short_name' => 'styles',
            'note' => 'stripe_styles_note',
            'type' => 'textarea',
        ],
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
    ];

    protected array $rules = [
        'api_key_live_secret' => 'whenModeIs[live]|required',
        'api_key_live_publishable' => 'whenModeIs[live]|required',
        'api_key_test_secret' => 'whenModeIs[test]|required',
        'api_key_test_publishable' => 'whenModeIs[test]|required',
    ];

    /** @var array */
    public array $nameless_fields = [];

    /** @var string */
    public $embedded_fields = '';

    /** @var OmnipayGateway */
    protected $omnipayGateway;

    /** @var publishableKey */
    protected $publishableKey;

    /** @var secretKey */
    protected $secretKey;

    public function __construct()
    {
        $this->publishableKey = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('api_key_live_publishable') : $this->plugin_settings('api_key_test_publishable');
        $this->secretKey = ($this->plugin_settings('mode') === 'live') ? $this->plugin_settings('api_key_live_secret') : $this->plugin_settings('api_key_test_secret');

        $this->omnipayGateway = Omnipay::create('Stripe\\PaymentIntents');
        $this->omnipayGateway->initialize(['apiKey' => $this->secretKey]);
        $this->embedded_fields = ee('View')->make('cartthrob:payment_gateways/stripe/embedded_fields')->render();
    }

    /**
     * @param $ignored
     * @return TransactionState
     */
    public function charge($ignored)
    {
        return $this->processCharge([
            'paymentMethod' => ee()->input->post('payment-method-id'),
            'confirm' => true,
            'returnUrl' => $this->generateReturnUrl(),
        ]);
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
        $state = new TransactionState();

        $paymentIntentReference = $this->createRequest('fetchPaymentIntent', ['paymentIntentReference' => $transactionId])->send();

        if ($charge = $paymentIntentReference->getData()['charges']['data'][0] ?? null) {
            $params['transactionReference'] = $charge['id'];

            if ($amount) {
                $params['amount'] = $amount;
            }

            $request = $this->createRequest('refund', $params);
            $data = $request->getData();

            if (!empty($extra['reason'])) {
                $data['reason'] = $extra['reason'];
            }

            $charge = (object)$request->sendData($data)->getData();
            if (empty($charge->failure_code) && ($charge->status === 'paid' || $charge->status === 'succeeded')) {
                return $state->setAuthorized()->setTransactionId($charge->id);
            }
        }

        return $state->setFailed(ee()->lang->line('stripe_refund_could_not_be_completed'));
    }

    /**
     * @param $params
     * @return TransactionState
     */
    protected function processCharge($params)
    {
        if (!isset($params['amount'])) {
            $params['amount'] = $this->total();
        }

        if (!isset($params['currency'])) {
            $currency = strtolower($this->order('currency_code') ? $this->order('currency_code') : 'USD');

            $params['currency'] = $currency;
        }

        if (!isset($params['description'])) {
            $params['description'] = $this->order('title') . ' (' . $this->orderId() . ')';
        }

        // Stripe allows for metadata to be attached to the charge object.
        $params['metadata'] = $this->prepare_metadata($_POST);

        try {
            /* @var StripeResponse $response */
            $params['confirm'] = true;
            $params['returnUrl'] = $this->generateReturnUrl();

            $request = $this->createRequest('authorize', $params);
            if (ee()->input->post('ct_idempotency')) {
                $request->setIdempotencyKeyHeader(ee()->input->post('ct_idempotency'));
            }

            $response = $request->send();
            $data = $response->getData();

            if ($response->isSuccessful() || $response->isRedirect()) {
                $paymentIntentReference = $response->getPaymentIntentReference();

                return $this->checkPaymentIntent($paymentIntentReference, $data);
            } else {
                throw new CartThrobException($data['error']['message']);
            }
        } catch (Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @param array $data
     * @return TransactionState
     */
    public function completeCheckout(array $data)
    {
        try {
            return $this->confirm($data['payment_intent']);
        } catch (CartThrobException $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @param $data
     * @return array
     */
    protected function prepare_metadata($data)
    {
        // Stripe's requirements for metadata are:
        // 1. Up to 20 keys
        // 2. Key names up to 40 characters
        // 3. Values up to 500 characters
        // See: https://stripe.com/docs/api#metadata
        $metadata = [];
        $maxKeys = 20;
        // Look for keys in the format meta:XYZ
        $keyCount = 0;
        foreach ($data as $k => $v) {
            if ($keyCount == $maxKeys) {
                break;
            }
            if (substr($k, 0, 5) == 'meta:' && strlen($k) > 5) {
                $key = substr($k, 5, 40);
                $value = substr($v, 0, 500);
                $metadata[$key] = $value;
                $keyCount++;
            }
        }

        return $metadata;
    }

    /**
     * @param string $paymentIntent
     * @param array $data
     * @return TransactionState
     * @throws CartThrobException
     */
    protected function checkPaymentIntent(string $paymentIntent, array $data)
    {
        if (self::STATUS_REQUIRES_CAPTURE === $data['status']) {
            if ($this->plugin_settings('stripe_capture') == 'authorize') {
                return $this->processing($data['id']);
            }

            return $this->capture($paymentIntent);
        }

        if (self::STATUS_REQUIRES_ACTION === $data['status']) {
            // This will redirect if necessary, so we only need to check
            // if the response is successful.
            return $this->confirm($paymentIntent);
        }

        return $this->fail();
    }

    /**
     * @param string $paymentIntent
     * @return void|TransactionState
     * @throws CartThrobException
     */
    protected function confirm(string $paymentIntent)
    {
        /** @var Response $response */
        $response = $this->createRequest('confirm', [
            'paymentIntentReference' => $paymentIntent,
            'returnUrl' => $this->generateReturnUrl(),
        ])->send();

        if ($response->isRedirect()) {
            $response->redirect();

            // The above was not automatically finishing the request?
            return;
        }

        if (!$response->isSuccessful()) {
            $error = ee()->lang->line('stripe_sca_failed');
            if ($response->getMessage()) {
                $error .= ' ' . $response->getMessage();
            }
            throw new CartThrobException($error);
        }

        return $this->checkPaymentIntent($paymentIntent, $response->getData());
    }

    /**
     * @param string $paymentIntent
     * @return TransactionState
     */
    public function capture(string $paymentIntent)
    {
        $response = $this->createRequest('capture', [
            'paymentIntentReference' => $paymentIntent,
        ])->send()->getData();

        if (self::STATUS_SUCCEEDED === $response['status']) {
            return $this->authorize($response['id']);
        }

        return $this->fail();
    }

    /**
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
     * @param $creditCardNumber
     * @return Cartthrob_token
     */
    public function createToken($creditCardNumber): Cartthrob_token
    {
        $token = new Cartthrob_token();

        // if there's no token it means that the end user doesn't have javascript enabled
        if (false === ($card_token = ee()->input->post('payment-method-id'))) {
            return $token->set_error_message(ee()->lang->line('stripe_javascript_required'));
        }

        try {
            $params = [
                'payment_method' => $card_token,
                'email' => $this->order('email_address'),
                'description' => $this->customerId(),
            ];

            $customer = (object)$this->createRequest('createCustomer', $params)->send()->getData();
            if (!empty($customer->id)) {
                return $token->set_customer_id($customer->id)->set_token($card_token);
            }

            return $token->set_error_message(ee()->lang->line('stripe_unknown_error'));
        } catch (Exception $e) {
            return $token->set_error_message($e->getMessage());
        }
    }

    /**
     * @param string $paymentIntent
     * @param array $extra
     */
    public function void(string $paymentIntent, array $extra = []): TransactionState
    {
        $params = [
            'paymentIntentReference' => $paymentIntent,
        ];

        $request = $this->createRequest('fetchPaymentIntent', $params)->send();
        if ($request instanceof PiResponse) {
            $allowed = ['requires_payment_method', 'requires_capture', 'requires_confirmation', 'requires_action', 'processing'];
            if (in_array($request->getStatus(), $allowed)) {
                $request = $this->createRequest('cancel', $params);
                if ($request instanceof CancelPaymentIntentRequest) {
                    $data = $request->getData();
                    if ($extra) {
                        // saving a spot to add in custom void form data :)
                    }

                    $response = $request->sendData($data);
                    if ($response->isCancelled()) {
                        return $this->authorize($response->getTransactionReference());
                    }
                }
            }
        }

        return $this->fail();
    }

    /**
     * @param $token
     * @param $customer_id
     * @return TransactionState
     */
    public function chargeToken($token, $customer_id): TransactionState
    {
        $params = ['paymentMethod' => $token, 'customerReference' => $customer_id];

        return $this->processCharge($params);
    }

    /**
     * @return string
     */
    public function form_extra()
    {
        $hidePostalCode = ($this->plugin_settings('hide_postal_code') === 'yes') ? 'true' : 'false';
        $iconStyle = ($this->plugin_settings('icon_style') === 'solid') ? 'solid' : 'default';
        $hideIcon = ($this->plugin_settings('hide_icon') === 'yes') ? 'true' : 'false';
        $styles = $this->plugin_settings('styles') ?? '';

        $view_params = [
            'publishable_key' => $this->publishableKey,
            'hide_postal_code' => $hidePostalCode,
            'icon_style' => $iconStyle,
            'hide_icon' => $hideIcon,
            'styles' => $styles,
        ];

        $this->form_extra .= ee('View')->make('cartthrob:payment_gateways/stripe/form_extra')->render($view_params);

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
        $request->setStripeVersion(self::STRIPE_VERSION);

        return $request;
    }

    /**
     * @return array[][][]
     */
    public function refundForm(): array
    {
        $options = [
            '' => '',
            'requested_by_customer' => lang('stripe_requested_by_customer'),
            'duplicate' => lang('stripe_duplicate'),
            'fraudulent' => lang('stripe_fraudulent'),
        ];

        return [
            'full_field_group' => [
                'settings' => [
                    [
                        'title' => 'stripe_refund_reason',
                        'fields' => [
                            'reason' => [
                                'type' => 'select',
                                'value' => '',
                                'choices' => $options,
                            ],
                        ],
                    ],
                ],
            ],
            'partial_field_group' => [
                'settings' => [
                    [
                        'title' => 'stripe_refund_reason',
                        'fields' => [
                            'reason' => [
                                'type' => 'select',
                                'value' => '',
                                'choices' => $options,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
