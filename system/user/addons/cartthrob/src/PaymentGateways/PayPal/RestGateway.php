<?php

namespace CartThrob\PaymentGateways\PayPal;

use CartThrob\Dependency\Omnipay\PayPal\RestGateway as OgPayPal;

class RestGateway extends OgPayPal
{
    /**
     * @param array $parameters
     * @return \Omnipay\PayPal\Message\AbstractRestRequest
     */
    public function getClientToken(array $parameters = [])
    {
        return $this->createRequest('\CartThrob\PaymentGateways\PayPal\Message\ClientTokenRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return \Omnipay\PayPal\Message\AbstractRestRequest
     */
    public function createOrder(array $parameters = [])
    {
        return $this->createRequest('\CartThrob\PaymentGateways\PayPal\Message\CreateOrderRequest', $parameters);
    }

    /**
     * @param array $parameters
     * @return \Omnipay\PayPal\Message\AbstractRestRequest
     */
    public function capturePayment(array $parameters = [])
    {
        return $this->createRequest('\CartThrob\PaymentGateways\PayPal\Message\CapturePaymentRequest', $parameters);
    }
}
