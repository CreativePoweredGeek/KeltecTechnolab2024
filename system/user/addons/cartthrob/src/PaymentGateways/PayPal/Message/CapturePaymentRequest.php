<?php

namespace CartThrob\PaymentGateways\PayPal\Message;

use CartThrob\Dependency\Omnipay\PayPal\Message\AbstractRestRequest;

class CapturePaymentRequest extends AbstractRestRequest
{
    protected $order_id;

    /**
     * @return null
     * @throws \Omnipay\Common\Exception\InvalidRequestException
     */
    public function getData()
    {
        $this->validate('order_id');

        return null;
    }

    /**
     * @return string
     */
    protected function getEndpoint()
    {
        $base = $this->getTestMode() ? $this->testEndpoint : $this->liveEndpoint;

        return $base . '/v2/checkout/orders/' . $this->getOrderId() . '/capture';
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->getParameter('order_id');
    }

    public function setOrderId($order_id)
    {
        $this->setParameter('order_id', $order_id);

        return $this;
    }
}
