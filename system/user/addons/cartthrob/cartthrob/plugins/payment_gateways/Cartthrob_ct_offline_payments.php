<?php

use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Plugins\Payment\TokenInterface;
use CartThrob\Transactions\TransactionState;

class Cartthrob_ct_offline_payments extends PaymentPlugin implements TokenInterface
{
    public $title = 'ct_offline_title';
    public $overview = 'ct_offline_overview';

    public $note = 'ct.payments.gateway.offline.note';

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
        'shipping_phone',
        'shipping_address',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country_code',
        'company',
        'phone',
        'email_address',
    ];

    public $settings = [
        [
            'name' => 'ct_processing_status',
            'short_name' => 'processing_status',
            'type' => 'select',
            'default' => 'complete',
            'options' => [
                'complete' => 'complete',
                'processing' => 'processing',
                'declined' => 'declined',
                'failed' => 'failed',
            ],
        ],
    ];

    /**
     * @param string $creditCardNumber
     * @return TransactionState
     */
    public function charge($creditCardNumber)
    {
        $state = new TransactionState();

        switch ($this->plugin_settings('processing_status')) {
            case 'complete':
                $state->setAuthorized()->setTransactionId(ee()->lang->line('ct_offline_transaction_id'));
                break;
            case 'declined':
                $state->setAuthorized(ee()->lang->line('ct_offline_error_message'));
                break;
            case 'failed':
                $state->setAuthorized(ee()->lang->line('ct_offline_error_message'));
                break;
            case 'processing':
            default:
                $state
                    ->setProcessing(ee()->lang->line('ct_offline_processing_message'))
                    ->setTransactionId(ee()->lang->line('ct_offline_transaction_id'));
        }

        return $state;
    }

    /**
     * @param $creditCardNumber
     * @return Cartthrob_token
     */
    public function createToken($creditCardNumber): Cartthrob_token
    {
        return new Cartthrob_token(['token' => uniqid('', true)]);
    }

    /**
     * @param $token
     * @param $customer_id
     * @return TransactionState
     */
    public function chargeToken($token, $customer_id): TransactionState
    {
        $state = new TransactionState();

        switch ($this->plugin_settings('processing_status')) {
            case 'complete':
                $state->setAuthorized()->setTransactionId(ee()->lang->line('ct_offline_transaction_id'));
                break;
            case 'declined':
            case 'failed':
                $state->setAuthorized(ee()->lang->line('ct_offline_error_message'));
                break;
            case 'processing':
            default:
                $state->setProcessing(ee()->lang->line('ct_offline_processing_message'))
                    ->setTransactionId(ee()->lang->line('ct_offline_transaction_id'));
        }

        return $state;
    }
}
