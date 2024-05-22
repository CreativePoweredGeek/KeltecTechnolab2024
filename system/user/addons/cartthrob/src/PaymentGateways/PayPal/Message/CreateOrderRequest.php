<?php

namespace CartThrob\PaymentGateways\PayPal\Message;

use CartThrob\Dependency\Omnipay\PayPal\Message\AbstractRestRequest;

class CreateOrderRequest extends AbstractRestRequest
{
    /**
     * @return array
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $this->validate('currency', 'amount');

        return [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $this->getCurrency(),
                    'value' => $this->getAmount(),
                ],
            ]],
        ];
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;

        return $base . '/v2/checkout/orders';
    }
}
