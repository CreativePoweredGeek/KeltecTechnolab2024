<?php

namespace CartThrob\Plugins\Payment;

use CartThrob\Transactions\TransactionState;
use Cartthrob_token;

interface TokenInterface
{
    /**
     * @param $creditCardNumber
     * @return Cartthrob_token
     */
    public function createToken($creditCardNumber): Cartthrob_token;

    /**
     * @param $token
     * @param $customer_id
     * @return TransactionState
     */
    public function chargeToken($token, $customer_id): TransactionState;
}
