<?php

namespace CartThrob\Plugins\Payment;

use CartThrob\Transactions\TransactionState;

interface RecurrentBillingInterface
{
    /**
     * @param $amount
     * @param $creditCardNumber
     * @param $subData
     * @return TransactionState
     */
    public function createRecurrentBilling($amount, $creditCardNumber, $subData): TransactionState;

    /**
     * @param $id
     * @param $creditCardNumber
     * @return TransactionState
     */
    public function updateRecurrentBilling($id, $creditCardNumber): TransactionState;
}
