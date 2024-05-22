<?php

namespace CartThrob\Dependency\Omnipay\SagePay\Message;

/**
 * Sage Pay Direct Purchase Request
 */
class DirectPurchaseRequest extends DirectAuthorizeRequest
{
    public function getTxType()
    {
        return static::TXTYPE_PAYMENT;
    }
}
