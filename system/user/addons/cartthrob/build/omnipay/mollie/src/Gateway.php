<?php

namespace CartThrob\Dependency\Omnipay\Mollie;

use CartThrob\Dependency\Omnipay\Common\AbstractGateway;
use CartThrob\Dependency\Omnipay\Common\Message\RequestInterface;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CompleteOrderRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CompletePurchaseRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CreateCustomerMandateRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CreateCustomerRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CreateOrderRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\CreateShipmentRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchCustomerMandatesRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchCustomerRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchIssuersRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchOrderRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchPaymentMethodsRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\FetchTransactionRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\PurchaseRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\RefundRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\RevokeCustomerMandateRequest;
use CartThrob\Dependency\Omnipay\Mollie\Message\Request\UpdateCustomerRequest;
/**
 * Mollie Gateway provides a wrapper for Mollie API.
 * Please have a look at links below to have a high-level overview and see the API specification
 *
 * @see https://www.mollie.com/en/developers
 * @see https://docs.mollie.com/index
 *
 * @method RequestInterface authorize(array $options = array())
 * @method RequestInterface completeAuthorize(array $options = array())
 * @method RequestInterface capture(array $options = array())
 * @method RequestInterface void(array $options = array())
 * @method RequestInterface createCard(array $options = array())
 * @method RequestInterface updateCard(array $options = array())
 * @method RequestInterface deleteCard(array $options = array())
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class Gateway extends AbstractGateway
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Mollie';
    }
    /**
     * @return array
     */
    public function getDefaultParameters()
    {
        return array('apiKey' => '');
    }
    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->getParameter('apiKey');
    }
    /**
     * @param  string $value
     * @return $this
     */
    public function setApiKey($value)
    {
        return $this->setParameter('apiKey', $value);
    }
    /**
     * @param  array $parameters
     * @return FetchIssuersRequest
     */
    public function fetchIssuers(array $parameters = [])
    {
        /** @var FetchIssuersRequest $request */
        $request = $this->createRequest(FetchIssuersRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return FetchPaymentMethodsRequest
     */
    public function fetchPaymentMethods(array $parameters = [])
    {
        /** @var FetchPaymentMethodsRequest $request */
        $request = $this->createRequest(FetchPaymentMethodsRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return FetchTransactionRequest
     */
    public function fetchTransaction(array $parameters = [])
    {
        /** @var FetchTransactionRequest $request */
        $request = $this->createRequest(FetchTransactionRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return PurchaseRequest
     */
    public function purchase(array $parameters = [])
    {
        /** @var PurchaseRequest $request */
        $request = $this->createRequest(PurchaseRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return CompletePurchaseRequest
     */
    public function completePurchase(array $parameters = [])
    {
        /** @var CompletePurchaseRequest $request */
        $request = $this->createRequest(CompletePurchaseRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return RefundRequest
     */
    public function refund(array $parameters = [])
    {
        /** @var RefundRequest $request */
        $request = $this->createRequest(RefundRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return CreateOrderRequest
     */
    public function createOrder(array $parameters = [])
    {
        /** @var CreateOrderRequest $request */
        $request = $this->createRequest(CreateOrderRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return FetchOrderRequest
     */
    public function fetchOrder(array $parameters = [])
    {
        /** @var FetchOrderRequest $request */
        $request = $this->createRequest(FetchOrderRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return CompleteOrderRequest
     */
    public function completeOrder(array $parameters = [])
    {
        /** @var CompleteOrderRequest $request */
        $request = $this->createRequest(CompleteOrderRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return CreateShipmentRequest
     */
    public function createShipment(array $parameters = [])
    {
        /** @var CreateShipmentRequest $request */
        $request = $this->createRequest(CreateShipmentRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return CreateCustomerRequest
     */
    public function createCustomer(array $parameters = [])
    {
        /** @var CreateCustomerRequest $request */
        $request = $this->createRequest(CreateCustomerRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return UpdateCustomerRequest
     */
    public function updateCustomer(array $parameters = [])
    {
        /** @var UpdateCustomerRequest $request */
        $request = $this->createRequest(UpdateCustomerRequest::class, $parameters);
        return $request;
    }
    /**
     * @param  array $parameters
     * @return FetchCustomerRequest
     */
    public function fetchCustomer(array $parameters = [])
    {
        /** @var FetchCustomerRequest $request */
        $request = $this->createRequest(FetchCustomerRequest::class, $parameters);
        return $request;
    }
    /**
     * @param array $parameters
     * @return FetchCustomerMandatesRequest
     */
    public function fetchCustomerMandates(array $parameters = [])
    {
        return $this->createRequest(FetchCustomerMandatesRequest::class, $parameters);
    }
    /**
     * @param array $parameters
     * @return CreateCustomerMandateRequest
     */
    public function createCustomerMandate(array $parameters = [])
    {
        return $this->createRequest(CreateCustomerMandateRequest::class, $parameters);
    }
    /**
     * @param array $parameters
     * @return RevokeCustomerMandateRequest
     */
    public function revokeCustomerMandate(array $parameters = [])
    {
        return $this->createRequest(RevokeCustomerMandateRequest::class, $parameters);
    }
}
