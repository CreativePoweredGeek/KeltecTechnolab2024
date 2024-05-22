<?php

namespace CartThrob\PaymentGateways\PayPal\Message;

use CartThrob\Dependency\Omnipay\PayPal\Message\AbstractRestRequest;

class ClientTokenRequest extends AbstractRestRequest
{
    public function getData()
    {
        return null;
    }

    protected function getEndpoint()
    {
        return parent::getEndpoint() . '/identity/generate-token';
    }
}
