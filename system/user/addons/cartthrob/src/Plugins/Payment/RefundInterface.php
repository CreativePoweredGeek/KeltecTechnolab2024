<?php

namespace CartThrob\Plugins\Payment;

use CartThrob\Transactions\TransactionState;

interface RefundInterface
{
    /**
     * @param $transactionId
     * @param $amount
     * @param $creditCardNumber
     * @param $extra
     * @return TransactionState
     */
    public function refund($transactionId, $amount, $creditCardNumber, $extra): TransactionState;
}
