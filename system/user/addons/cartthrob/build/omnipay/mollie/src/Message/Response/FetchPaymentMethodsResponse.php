<?php

namespace CartThrob\Dependency\Omnipay\Mollie\Message\Response;

use CartThrob\Dependency\Omnipay\Common\Message\FetchPaymentMethodsResponseInterface;
use CartThrob\Dependency\Omnipay\Common\PaymentMethod;
/**
 * @see https://docs.mollie.com/reference/v2/methods-api/list-methods
 */
class FetchPaymentMethodsResponse extends AbstractMollieResponse implements FetchPaymentMethodsResponseInterface
{
    /**
     * Return available payment methods as an associative array.
     *
     * @return PaymentMethod[]
     */
    public function getPaymentMethods()
    {
        if (isset($this->data['_embedded']["methods"]) === \false) {
            return [];
        }
        $paymentMethods = [];
        foreach ($this->data['_embedded']["methods"] as $method) {
            $paymentMethods[] = new PaymentMethod($method['id'], $method['description']);
        }
        return $paymentMethods;
    }
}
