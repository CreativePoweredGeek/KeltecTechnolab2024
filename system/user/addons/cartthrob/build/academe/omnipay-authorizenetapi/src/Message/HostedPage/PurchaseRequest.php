<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\HostedPage;

/**
 * Also known as "Authorize.Net Accept Hosted".
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Transaction\AuthCapture;
use CartThrob\Dependency\Academe\AuthorizeNet\AmountInterface;
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
