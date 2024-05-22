<?php

use CartThrob\Dependency\Omnipay\Mollie\Gateway as OmnipayGateway;
use CartThrob\Dependency\Omnipay\Mollie\Message\Response\FetchOrderResponse;
use CartThrob\Dependency\Omnipay\Omnipay;
use CartThrob\PaymentGateways\Transformers\MollieOrderTransformer;
use CartThrob\Plugins\Payment\ExtloadInterface;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Transactions\TransactionState;

class Cartthrob_mollie extends PaymentPlugin implements ExtloadInterface
{
    public const STATUS_CREATED = 'created';
    public const STATUS_PAID = 'paid';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_SHIPPING = 'shipping';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    public $note = 'ct.payments.gateway.mollie.note';

    public $title = 'mollie_title';
    public $overview = 'mollie_overview';
    public $language_file = true;

    public $settings = [
        [
            'name' => 'mollie_settings_live_api_login',
            'short_name' => 'api_login',
            'type' => 'text',
        ],
        [
            'name' => 'mollie_settings_test_api_login',
            'short_name' => 'test_api_login',
            'type' => 'text',
        ],
        [
            'name' => 'mollie_settings_locale',
            'short_name' => 'locale',
            'type' => 'select',
            'default' => 'en_US',
            'options' => [
                'ca_ES' => 'Catalan (Spain)',
                'da_DK' => 'Danish (Denmark)',
                'nl_BE' => 'Dutch (Belgium)',
                'nl_NL' => 'Dutch (Netherlands)',
                'en_US' => 'English (United States)',
                'fi_FI' => 'Finnish (Finland)',
                'fr_BE' => 'French (Belgium)',
                'fr_FR' => 'French (France)',
                'de_AT' => 'German (Austria)',
                'de_DE' => 'German (Germany)',
                'de_CH' => 'German (Switzerland)',
                'hu_HU' => 'Hungarian (Hungary)',
                'is_IS' => 'Icelandic (Iceland)',
                'it_IT' => 'Italian (Italy)',
                'lv_LV' => 'Latvian (Latvia)',
                'lt_LT' => 'Lithuanian (Lithuania)',
                'nb_NO' => 'Norwegian Bokmål (Norway)',
                'pl_PL' => 'Polish (Poland)',
                'pt_PT' => 'Portuguese (Portugal)',
                'es_ES' => 'Spanish (Spain)',
                'sv_SE' => 'Swedish (Sweden)',
            ],
        ],
        [
            'name' => 'mode',
            'short_name' => 'mode',
            'type' => 'radio',
            'default' => 'test',
            'options' => [
                'test' => 'test',
                'live' => 'live',
            ],
        ],
    ];

    public $required_fields = [
        'first_name',
        'email_address',
        'address',
        'zip',
        'city',
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
        'api_login' => 'whenModeIs[live]|required',
        'test_api_login' => 'whenModeIs[test]|required',
    ];

    /**
     * @var OmnipayGateway
     */
    protected $omnipayGateway;

    public function __construct()
    {
        $apiKey = $this->plugin_settings($this->plugin_settings('mode') == 'live' ? 'api_login' : 'test_api_login');

        $this->omnipayGateway = Omnipay::create('Mollie');
        $this->omnipayGateway->initialize([
            'apiKey' => $apiKey,
        ]);
    }

    /**
     * Run a charge
     *
     * @param string $unused This field is unused for Mollie
     * @return TransactionState|void
     */
    public function charge($unused)
    {
        try {
            $data = (new MollieOrderTransformer())->transform($this->order());

            $request = $this->omnipayGateway->createOrder($data);

            $response = $request->send();

            if ($response->isRedirect()) {
                // This will exit
                $this->completePaymentOffsite($response->getRedirectUrl());
            } else {
                throw new Exception($response->getData()['detail']);
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
        }

        return $this->fail($msg ?? ee()->lang->line('mollie_unknown_error'));
    }

    /**
     * Handle an async requests
     *
     * @param array $data
     */
    public function extload(array $data): void
    {
        unset($data['ACT'], $data['G'], $data['M']);

        if (empty($data)) {
            exit(ee()->lang->line('mollie_no_post'));
        }

        switch (trim($data['action'])) {
            case 'payment':
                if (!isset($data['id'])) {
                    exit(ee()->lang->line('mollie_no_order_id'));
                }

                $this->handlePaymentWebhook($data['id']);
                break;
            case 'order':
                ee()->cartthrob_payments->clearCart();
                ee()->functions->redirect(urldecode($data['redirect']));
                break;
            default:
                exit(ee()->lang->line('mollie_bad_extload_action'));
        }
    }

    /**
     * @param $transactionId
     */
    private function handlePaymentWebhook($transactionId)
    {
        $state = new TransactionState();

        try {
            /** @var FetchOrderResponse $response */
            $response = $this->omnipayGateway->fetchOrder([
                'transactionReference' => $transactionId,
            ])->send();

            if (!$response->isSuccessful()) {
                exit(ee()->lang->line('curl_gateway_failure'));
            }

            $entryId = $response->getMetadata()['entry_id'] ?? null;
            $orderStatus = $response->getStatus();
        } catch (Exception $e) {
            exit(ee()->lang->line('mollie_unknown_error'));
        }

        if (!$entryId) {
            exit(ee()->lang->line('mollie_no_entry_id'));
        } elseif (!$orderStatus) {
            exit(ee()->lang->line('mollie_no_order_status'));
        }

        switch ($orderStatus) {
            case self::STATUS_CREATED:
                $state->setProcessing()->setTransactionId($transactionId);
                break;
            case self::STATUS_PAID:
            case self::STATUS_AUTHORIZED:
                $state->setAuthorized()->setTransactionId($transactionId);
                break;
            case self::STATUS_CANCELED:
                $state->setCanceled()->setTransactionId($transactionId);
                break;
            case self::STATUS_EXPIRED:
                $state->setExpired()->setTransactionId($transactionId);
                break;
            case self::STATUS_SHIPPING:
            case self::STATUS_COMPLETED:
            default:
                // NOOP
                exit;
        }

        $this->checkoutCompleteOffsite($state, $entryId, Cartthrob_payments::COMPLETION_TYPE_STOP);
    }
}
