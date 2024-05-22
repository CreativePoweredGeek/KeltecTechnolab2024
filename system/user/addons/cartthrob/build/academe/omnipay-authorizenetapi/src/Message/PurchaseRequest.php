<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message;

use CartThrob\Dependency\Academe\AuthorizeNet\AmountInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Transaction\AuthCapture;
class PurchaseRequest extends AuthorizeRequest
{
    /**
     * Create a new instance of the transaction object.
     */
    protected function createTransaction(AmountInterface $amount)
    {
        return new AuthCapture($amount);
    }
}
