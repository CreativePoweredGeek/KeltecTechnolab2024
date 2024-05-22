<?php

namespace CartThrob\Plugins\Payment;

use CartThrob\Transactions\TransactionState;

interface VoidInterface
{
    /**
     * @param string $paymentIntent
     * @param array $extra
     * @return TransactionState
     */
    public function void(string $paymentIntent, array $extra = []): TransactionState;
}
