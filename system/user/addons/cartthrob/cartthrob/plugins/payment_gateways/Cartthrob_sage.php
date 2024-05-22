<?php

use CartThrob\Dependency\Omnipay\Common\CreditCard;
use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Dependency\Omnipay\SagePay\DirectGateway as OmnipayGateway;
use CartThrob\Dependency\Omnipay\SagePay\Message\Response;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Transactions\TransactionState;

class Cartthrob_sage extends PaymentPlugin
{
    public const STATUS_OK = 'OK';
    public const STATUS_NOTAUTHED = 'NOTAUTHED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_ERROR = 'ERROR';
    public const STATUS_INVALID = 'INVALID';
    public const STATUS_MALFORMED = 'MALFORMED';
    public const DEFAULT_ERROR_MESSAGE = 'sage_default';

    public $title = 'sage_title';

    // @TODO add notes about extload when using subs
    public $overview = 'sage_overview';

    public $note = 'ct.payments.sage_form.note';

    public $settings = [
        [
            'name' => 'mode',
            'short_name' => 'mode',
            'type' => 'radio',
            'default' => 'test',
            'options' => [
                'simulator' => 'simulator',
                'test' => 'test',
                'live' => 'live',
            ],
        ],
        [
            'name' => 'sage_vendor_name',
            'short_name' => 'vendor_name',
            'type' => 'text',
        ],
    ];

    public $required_fields = [
        'credit_card_number',
        'expiration_month',
        'expiration_year',
        'card_type',
        'first_name',
        'last_name',
        'address',
        'city',
        'zip',
        'country_code',
    ];

    public $fields = [
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'zip',
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
        'card_type',
        'issue_number',
        'credit_card_number',
        'CVV2',
        'expiration_month',
        'expiration_year',
        'begin_month',
        'begin_year',
    ];
    public $payment_details_available = true;

    /**
     * @var OmnipayGateway
     */
    public $omnipayGateway;

    protected array $rules = [
        'vendor_name' => 'required',
    ];

    // description and currency_code are also used by this gateway

    public function __construct()
    {
        $this->omnipayGateway = Omnipay::create('SagePay\Direct');
        $this->omnipayGateway->initialize([
            'vendor' => $this->plugin_settings('vendor_name'),
            'testMode' => ('test' === $this->plugin_settings('mode')),
        ]);
    }

    /**
     * @param string $creditCardNumber
     * @param bool $createToken
     * @return TransactionState
     */
    public function charge($creditCardNumber, $createToken = false)
    {
        $basket = '';

        if ($this->order('items')) {
            $basket = (count($this->order('items')) + 2) . ':';

            foreach ($this->order('items') as $row_id => $item) {
                $basket .= str_replace(':', '', $item['title']) . ':';
                $basket .= $item['quantity'] . ':';
                $basket .= number_format($item['price'], 2, '.', '') . ':';
                $basket .= ':';
                $basket .= number_format($item['price'], 2, '.', '') . ':';
                $basket .= number_format($item['price'] * $item['quantity'], 2, '.', '') . ':';
            }

            $basket .= 'Shipping:----:----:----:----:';
            $basket .= number_format($this->order('shipping'), 2, '.', '') . ':';
            $basket .= 'VAT/Tax:----:----:----:----:';
            $basket .= number_format($this->order('tax'), 2, '.', '');
        }

        if (strlen($basket) > 7499) {
            // the basket can't be over 7500, and has to be formatted a specific way. We'll remove it if it's too long.
            $basket = '';
        }

        $card = new CreditCard([
            'number' => $creditCardNumber,
            'expiryMonth' => $this->order('expiration_month'),
            'expiryYear' => $this->order('expiration_year'),
            'CVV' => $this->order('CVV2'),
            'billingFirstName' => substr($this->order('first_name'), 0, 20),
            'billingLastName' => substr($this->order('last_name'), 0, 20),
            'billingAddress1' => substr($this->order('address'), 0, 100),
            'billingAddress2' => substr($this->order('address2'), 0, 100),
            'billingCity' => substr($this->order('city'), 0, 40),
            'billingPostcode' => substr($this->order('zip'), 0, 10),
            'billingCountry' => ($country_code = $this->order('country_code') ? alpha2_country_code($this->order('country_code')) : 'GB'),
            'billingState' => ('US' === $country_code ? strtoupper($this->order('state')) : ''),
            'billingPhone' => preg_replace('/[^0-9-]/', '', $this->order('phone')),
            'shippingFirstName' => substr($this->order('shipping_first_name') ? $this->order('shipping_first_name') : $this->order('first_name'), 0, 20),
            'shippingLastName' => substr($this->order('shipping_last_name') ? $this->order('shipping_last_name') : $this->order('last_name'), 0, 20),
            'shippingAddress1' => substr($this->order('shipping_address') ? $this->order('shipping_address') : $this->order('address'), 0, 100),
            'shippingAddress2' => substr($this->order('shipping_address2') ? $this->order('shipping_address2') : $this->order('address2'), 0, 100),
            'shippingCity' => substr($this->order('shipping_city') ? $this->order('shipping_city') : $this->order('city'), 0, 40),
            'shippingPostcode' => substr($this->order('shipping_zip') ? $this->order('shipping_zip') : $this->order('zip'), 0, 10),
            'shippingCountry' => ($shipping_country_code = ($this->order('shipping_country_code') ? alpha2_country_code($this->order('shipping_country_code')) : $country_code)),
            'shippingState' => 'US' === $shipping_country_code ? strtoupper($this->order('state')) : '',
            'email' => $this->order('email_address'),
            'basket' => $basket,
        ]);

        /** @var Response $response */
        $response = $this->omnipayGateway->purchase([
            'card' => $card,
            'amount' => number_format($this->total(), 2, '.', ''),
            'currency' => (ee()->input->post('currency_code') ? ee()->input->post('currency_code') : 'GBP'),
            'transactionId' => ($transactionId = $this->order('entry_id') . '_' . time()), // needs a unique ID for this transaction.
            'description' => ($this->order('description') ? $this->order('description') : 'Purchase from ' . $this->order('site_name')),
            'returnUrl' => $this->generateReturnUrl($createToken ? $transactionId : null),
        ])->send();

        if ($response->isRedirect()) {
            $response->redirect();
        }

        // will we ever get here?
        return $this->fail(ee()->lang->line('sage_default'));
    }

    /**
     * @param array $data
     * @return TransactionState
     */
    public function scaPaymentReturn(array $data)
    {
        $params = isset($data['transactionId']) ? ['transactionId' => $data['transactionId']] : [];

        /** @var Response $response */
        $response = $this->omnipayGateway->completeAuthorize($params)->send();

        switch ((string)$response->getStatus()) {
            case self::STATUS_OK:
                return $this->authorize(trim($response->getVPSTxId(), '{}'));
            case self::STATUS_NOTAUTHED:
                return $this->fail(ee()->lang->line('sage_notauthed'));
            case self::STATUS_REJECTED:
                return $this->fail(ee()->lang->line('sage_rejected'));
            case self::STATUS_MALFORMED:
                return $this->fail(ee()->lang->line('sage_malformed') . $response->getData()['StatusDetail']);
            case self::STATUS_INVALID:
                return $this->fail(ee()->lang->line('sage_invalid') . $response->getData()['StatusDetail']);
            case self::STATUS_ERROR:
                return $this->fail(ee()->lang->line('sage_error'));
            default:
                return $this->fail();
        }
    }

    /**
     * @param string|null $transactionId
     * @return string
     */
    protected function generateReturnUrl(string $transactionId = null)
    {
        ee()->load->library('paths');

        $enc = ee('Encrypt');

        $params = [
            'method' => base64_encode($enc->encode('scaPaymentReturn')),
            'gateway' => base64_encode($enc->encode(__CLASS__)),
            'orderId' => base64_encode($enc->encode($this->orderId())),
        ];

        if (null !== $transactionId) {
            $params['transactionId'] = base64_encode($enc->encode($transactionId));
        }

        return ee()->paths->build_action_url('Cartthrob', 'payment_return_action', $params);
    }
}
