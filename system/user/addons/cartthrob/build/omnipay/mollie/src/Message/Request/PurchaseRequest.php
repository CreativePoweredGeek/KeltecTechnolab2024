<?php

namespace CartThrob\Dependency\Omnipay\Mollie\Message\Request;

use CartThrob\Dependency\Omnipay\Common\Exception\InvalidRequestException;
use CartThrob\Dependency\Omnipay\Common\Message\ResponseInterface;
use CartThrob\Dependency\Omnipay\Mollie\Message\Response\PurchaseResponse;
/**
 * Create a payment with the Mollie API.
 *
 * @see https://docs.mollie.com/reference/v2/payments-api/create-payment
 * @method PurchaseResponse send()
 */
class PurchaseRequest extends AbstractMollieRequest
{
    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->getParameter('metadata');
    }
    /**
     * @param array $value
     * @return $this
     */
    public function setMetadata(array $value)
    {
        return $this->setParameter('metadata', $value);
    }
    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->getParameter('locale');
    }
    /**
     * @param string $value
     * @return $this
     */
    public function setLocale($value)
    {
        return $this->setParameter('locale', $value);
    }
    /**
     * @return string
     */
    public function getBillingEmail()
    {
        return $this->getParameter('billingEmail');
    }
    /**
     * @param string $value
     * @return $this
     */
    public function setBillingEmail($value)
    {
        return $this->setParameter('billingEmail', $value);
    }
    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->getParameter('customerReference');
    }
    /**
     * @param string $value
     * @return $this
     */
    public function setCustomerReference($value)
    {
        return $this->setParameter('customerReference', $value);
    }
    /**
     * @return string
     */
    public function getSequenceType()
    {
        return $this->getParameter('sequenceType');
    }
    /**
     * @param string $value
     * @return $this
     */
    public function setSequenceType($value)
    {
        return $this->setParameter('sequenceType', $value);
    }
    /**
     * @return array
     * @throws InvalidRequestException
     */
    public function getData()
    {
        $this->validate('apiKey', 'amount', 'currency', 'description', 'returnUrl');
        $data = [];
        $data['amount'] = ["value" => $this->getAmount(), "currency" => $this->getCurrency()];
        $data['description'] = $this->getDescription();
        $data['redirectUrl'] = $this->getReturnUrl();
        $data['method'] = $this->getPaymentMethod();
        $data['metadata'] = $this->getMetadata();
        if ($this->getTransactionId()) {
            $data['metadata']['transactionId'] = $this->getTransactionId();
        }
        if ($issuer = $this->getIssuer()) {
            $data['issuer'] = $issuer;
        }
        $webhookUrl = $this->getNotifyUrl();
        if (null !== $webhookUrl) {
            $data['webhookUrl'] = $webhookUrl;
        }
        if ($locale = $this->getLocale()) {
            $data['locale'] = $locale;
        }
        if ($billingEmail = $this->getBillingEmail()) {
            $data['billingEmail'] = $billingEmail;
        }
        if ($customerReference = $this->getCustomerReference()) {
            $data['customerId'] = $customerReference;
        }
        if ($sequenceType = $this->getSequenceType()) {
            $data['sequenceType'] = $sequenceType;
        }
        return $data;
    }
    /**
     * @param array $data
     * @return ResponseInterface|PurchaseResponse
     */
    public function sendData($data)
    {
        $response = $this->sendRequest(self::POST, '/payments', $data);
        return $this->response = new PurchaseResponse($this, $response);
    }
}
