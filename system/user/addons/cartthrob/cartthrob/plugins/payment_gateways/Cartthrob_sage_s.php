<?php

use CartThrob\Dependency\Omnipay\Common\CreditCard;
use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Dependency\Omnipay\SagePay\Message\ServerNotifyRequest;
use CartThrob\Dependency\Omnipay\SagePay\ServerGateway as OmnipayGateway;
use CartThrob\Plugins\Payment\ExtloadInterface;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Transactions\TransactionState;

class Cartthrob_sage_s extends PaymentPlugin implements ExtloadInterface
{
    /**
     * @var string
     */
    public const STATUS_OK = 'OK';

    /**
     * @var string
     */
    public const STATUS_AUTHENTICATED = 'AUTHENTICATED';

    /**
     * @var string
     */
    public const STATUS_COMPLETED = 'COMPLETED';

    /**
     * @var string
     */
    public const STATUS_NOTAUTHED = 'NOTAUTHED';

    /**
     * @var string
     */
    public const STATUS_ABORT = 'ABORT';

    /**
     * @var string
     */
    public const STATUS_REGISTERED = 'REGISTERED';

    /**
     * @var string
     */
    public const STATUS_REJECTED = 'REJECTED';

    /**
     * @var string
     */
    public const STATUS_ERROR = 'ERROR';

    public $title = 'sage_server_title';
    public $overview = 'sage_overview';
    public $language_file = true;
    public $hidden = ['description'];

    public $note = 'ct.payments.sage_server.note';

    public $settings = [
        [
            'name' => 'sage_payment_page_style',
            'short_name' => 'profile',
            'type' => 'radio',
            'default' => 'NORMAL',
            'options' => [
                'NORMAL' => 'sage_normal',
                'LOW' => 'sage_minimal_formatting',
            ],
        ],
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

    protected array $rules = [
        'vendor_name' => 'required',
    ];

    public $required_fields = [
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
    ];

    /**
     * @var OmnipayGateway
     */
    public $omnipayGateway;

    public function __construct()
    {
        $this->omnipayGateway = Omnipay::create('SagePay\Server');
        $this->omnipayGateway->initialize([
            'vendor' => $this->plugin_settings('vendor_name'),
            'testMode' => ('test' === $this->plugin_settings('mode')),
        ]);
    }

    /**
     * @param string $creditCardNumber
     * @return TransactionState|void
     */
    public function charge($creditCardNumber)
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

        /** @var CartThrob\Dependency\Omnipay\SagePay\Message\ServerAuthorizeResponse $response */
        $response = $this->omnipayGateway->purchase([
            'card' => $card,
            'amount' => number_format($this->total(), 2, '.', ''),
            'currency' => (ee()->input->post('currency_code') ? ee()->input->post('currency_code') : 'GBP'),
            'transactionId' => ($transactionId = $this->order('entry_id') . '_' . time()), // needs a unique ID for this transaction.
            'description' => ($this->order('description') ? $this->order('description') : 'Purchase from ' . $this->order('site_name')),
            'notifyUrl' => $this->responseUrl(__CLASS__),
            'clientIp' => ee()->input->ip_address(),
            'profile' => $this->plugin_settings('profile'),
        ])->send();

        $status = strtoupper($response->getStatus());
        $transaction = $response->getData();

        if ('OK' != $status) {
            switch ($status) {
                case 'MALFORMED':
                    $errorMsg = ee()->lang->line('sage_malformed') . $transaction['StatusDetail'];
                    break;
                case 'INVALID':
                    $errorMsg = ee()->lang->line('sage_invalid') . $transaction['StatusDetail'];
                    break;
                case 'ERROR':
                    $errorMsg = ee()->lang->line('sage_error');
                    break;
                default:
                    $errorMsg = ee()->lang->line('sage_default');
            }

            return $this->fail($errorMsg);
        }

        ee()->cartthrob->cart->update_order(['sage_key' => $transaction['SecurityKey']]);
        $state = (new TransactionState())->setProcessing(ee()->lang->line('status_offsite'));
        $this->saveCartSnapshot($this->order('entry_id'));
        $this->setStatus(Cartthrob_payments::STATUS_OFFSITE, $state, $this->order('order_id'), $emailData = false);

        if ($response->isRedirect()) {
            $response->redirect();
            exit;
        }

        // will we ever get here?
        return $this->fail(ee()->lang->line('sage_default'));
    }

    /**
     * @param $post
     * @throws \Omnipay\Common\Exception\InvalidResponseException
     */
    public function extload(array $data): void
    {
        /** @var ServerNotifyRequest $notifyRequest */
        $notifyRequest = $this->omnipayGateway->acceptNotification();

        if ($transactionId = $notifyRequest->getTransactionId()) {
            list($orderId) = explode('_', $transactionId);
            $this->relaunchCart(null, $orderId);
        } else {
            exit(ee()->lang->line('sage_default'));
        }

        if (strpos($this->order('return'), 'http') === 0) {
            $returnUrl = $this->order('return');
        } else {
            $returnUrl = ee()->functions->create_url($this->order('return'));
        }

        $state = new TransactionState();
        $notifyRequest->setExitOnResponse(true);
        $notifyRequest->setSecurityKey($this->order('sage_key'));

        if (!$notifyRequest->isValid()) {
            $state->setFailed(ee()->lang->line('sage_signature_not_valid'));
            $this->checkoutCompleteOffsite($state, $orderId, Cartthrob_payments::COMPLETION_TYPE_STOP);
            $notifyRequest->invalid($returnUrl, ee()->lang->line('sage_signature_not_valid'));
        }

        $status = strtoupper($notifyRequest->getTransactionStatus());

        if (in_array($status, [self::STATUS_OK, self::STATUS_AUTHENTICATED, self::STATUS_COMPLETED])) {
            $state->setAuthorized()->setTransactionId(trim($notifyRequest->getVPSTxId(), '{}'));
            $this->checkoutCompleteOffsite($state, $orderId, Cartthrob_payments::COMPLETION_TYPE_STOP);
            $notifyRequest->confirm($returnUrl);
        } else {
            $redirectUrl = null;

            $notifyRequest->setExitOnResponse(false);

            switch ($status) {
                case self::STATUS_NOTAUTHED:
                    $state->setFailed(ee()->lang->line('sage_notauthed'));
                    $notifyRequest->confirm($returnUrl);
                    break;
                case self::STATUS_ABORT:
                    $notifyRequest->confirm($returnUrl);
                    $state->setCanceled(ee()->lang->line('transaction_cancelled'));
                    $this->setStatus(Cartthrob_payments::STATUS_CANCELED, $state, $this->order('entry_id'), $emailData = false);
                    ee()->cartthrob->cart->save();
                    break;
                case self::STATUS_AUTHENTICATED:
                    $notifyRequest->confirm($returnUrl);
                    $state->setFailed(ee()->lang->line('sage_authenticated'));
                    break;
                case self::STATUS_REGISTERED:
                    $notifyRequest->confirm($returnUrl);
                    $state->setFailed(ee()->lang->line('sage_registered'));
                    break;
                case self::STATUS_REJECTED:
                    $notifyRequest->invalid($returnUrl);
                    $state->setDeclined(ee()->lang->line('sage_rejected'));
                    break;
                case self::STATUS_ERROR:
                    $notifyRequest->invalid($returnUrl);
                    $state->setFailed(ee()->lang->line('sage_error'));
                    break;
                default:
                    $notifyRequest->invalid($returnUrl);
                    $state->setFailed(ee()->lang->line('sage_default'));
            }

            $this->checkoutCompleteOffsite($state, $orderId, Cartthrob_payments::COMPLETION_TYPE_STOP);

            exit;
        }
    }
}
