<?php

namespace CartThrob\Dependency\Omnipay\AuthorizeNet\Message;

use CartThrob\Dependency\Omnipay\Common\Message\AbstractResponse;
use CartThrob\Dependency\Omnipay\Common\Message\RequestInterface;
use CartThrob\Dependency\Omnipay\Common\Message\RedirectResponseInterface;
/**
 * Authorize.Net SIM Authorize Response
 */
class SIMAuthorizeResponse extends AbstractResponse implements RedirectResponseInterface
{
    protected $redirectUrl;
    public function __construct(RequestInterface $request, $data, $redirectUrl)
    {
        parent::__construct($request, $data);
        $this->redirectUrl = $redirectUrl;
    }
    public function isSuccessful()
    {
        return \false;
    }
    public function isRedirect()
    {
        return \true;
    }
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
    public function getRedirectMethod()
    {
        return 'POST';
    }
    public function getRedirectData()
    {
        return $this->getData();
    }
    public function getTransactionId()
    {
        return isset($this->data[SIMAbstractRequest::TRANSACTION_ID_PARAM]) ? $this->data[SIMAbstractRequest::TRANSACTION_ID_PARAM] : null;
    }
}
