<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNet\Message;

/**
 * Authorize.Net CIM Get payment profiles Response
 */
class CIMGetPaymentProfileResponse extends CIMCreatePaymentProfileResponse
{
    protected $responseType = 'getCustomerPaymentProfileResponse';
    public function getCustomerPaymentProfileId()
    {
        if ($this->isSuccessful()) {
            return $this->data['paymentProfile']['customerPaymentProfileId'];
        }
        return null;
    }
}
