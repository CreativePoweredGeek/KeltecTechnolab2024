<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNetApi;

/**
 *
 */
use CartThrob\Dependency\Omnipay\Common\Exception\InvalidRequestException;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\AuthorizeRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\PurchaseRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\VoidRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\RefundRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\FetchTransactionRequest;
use CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message\AcceptNotification;
class ApiGateway extends AbstractGateway
{
    /**
     * The common name for this gateway driver API.
     */
    public function getName()
    {
        return 'Authorize.Net API';
    }
    /**
     * The authorization transaction.
     */
    public function authorize(array $parameters = [])
    {
        return $this->createRequest(AuthorizeRequest::class, $parameters);
    }
    /**
     * The purchase transaction.
     */
    public function purchase(array $parameters = [])
    {
        return $this->createRequest(PurchaseRequest::class, $parameters);
    }
    /**
     * Void an authorized transaction.
     */
    public function void(array $parameters = [])
    {
        return $this->createRequest(VoidRequest::class, $parameters);
    }
    /**
     * Refund a captured transaction (before it is cleared).
     */
    public function refund(array $parameters = [])
    {
        return $this->createRequest(RefundRequest::class, $parameters);
    }
    /**
     * Fetch an existing transaction details.
     */
    public function fetchTransaction(array $parameters = [])
    {
        return $this->createRequest(FetchTransactionRequest::class, $parameters);
    }
    /**
     * Accept a notification.
     */
    public function acceptNotification(array $parameters = [])
    {
        return $this->createRequest(AcceptNotification::class, $parameters);
    }
}
