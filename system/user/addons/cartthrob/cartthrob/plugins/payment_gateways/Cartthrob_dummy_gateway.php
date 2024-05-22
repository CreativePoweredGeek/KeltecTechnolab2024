<?php

use CartThrob\Dependency\Omnipay\Dummy\Gateway as OmnipayGateway;
use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Plugins\Payment\RefundInterface;
use CartThrob\Transactions\TransactionState;

class Cartthrob_dummy_gateway extends PaymentPlugin implements RefundInterface
{
    /** @var string */
    public $title = 'dummy_title';

    /** @var string */
    public $overview = 'dummy_overview';

    public $note = 'ct.payments.gateway.dummy.note';

    /** @var array */
    public $required_fields = [
        'first_name',
        'last_name',
        'address',
        'city',
        'state',
        'zip',
        'phone',
        'email_address',
        'credit_card_number',
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
        'shipping_phone',
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

    /** @var bool */
    public $payment_details_available = true;

    /** @var OmnipayGateway */
    protected $omnipayGateway;

    /**
     * Cartthrob_dummy_gateway constructor.
     *
     * @param array $required_fields
     */
    public function __construct()
    {
        $this->omnipayGateway = Omnipay::create('Dummy');
        $this->omnipayGateway->initialize([]);
    }

    /**
     * @param string $creditCardNumber
     * @return TransactionState
     */
    public function charge(string $creditCardNumber)
    {
        $params = [
            'card' => [
                'number' => $creditCardNumber,
                'expiryMonth' => ee()->input->post('expiration_month'),
                'expiryYear' => ee()->input->post('expiration_year'),
            ],
            'amount' => $this->total(),
        ];

        try {
            $response = $this->omnipayGateway->purchase($params)->send();

            if (!$response->isSuccessful()) {
                return $this->fail($response->getMessage());
            }

            return $this->authorize($response->getTransactionReference());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @param $transactionId
     * @param $amount
     * @param $lastFour
     * @return TransactionState
     */
    public function refund($transactionId, $amount, $creditCardNumber, $extra): TransactionState
    {
        try {
            $response = $this->omnipayGateway->refund(['transactionReference' => $transactionId])->send();

            if (!$response->isSuccessful()) {
                return $this->fail($response->getMessage());
            }

            return $this->authorize($response->getTransactionReference());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
