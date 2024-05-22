<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNetApi\Message;

/**
 * The TransactionResponse can contain full details of a transaction
 * creation or fetch result, or errors.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections\TransactionMessages;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections\Errors;
use CartThrob\Dependency\Omnipay\Common\Message\RequestInterface;
class Response extends AbstractResponse
{
    public function __construct(RequestInterface $request, $data)
    {
        // Parse the request into a structured object.
        parent::__construct($request, $data);
    }
    /**
     * Return the message code from the transaction if available,
     * or the response envelope.
     */
    public function getCode()
    {
        return $this->getTransactionCode() ?: parent::getCode();
    }
    /**
     * Get the transaction message text if available, falling back
     * to the response envelope.
     */
    public function getMessage()
    {
        return $this->getTransactionMessage() ?: parent::getMessage();
    }
}
