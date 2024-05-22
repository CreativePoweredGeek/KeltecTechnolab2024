<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Request;

/**
 *
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Auth\MerchantAuthentication;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractModel;
abstract class AbstractRequest extends AbstractModel
{
    /**
     * All requests require authentication.
     */
    protected $merchantAuthentication;
    /**
     * The suffix applied to the request name when sending the request.
     */
    protected $objectNameSuffix = 'Request';
    /**
     * Set the authentication object.
     */
    public function __construct(MerchantAuthentication $merchantAuthentication)
    {
        parent::__construct();
        $this->setMerchantAuthentication($merchantAuthentication);
    }
    /**
     * API authentication details.
     */
    protected function setMerchantAuthentication(MerchantAuthentication $value)
    {
        $this->merchantAuthentication = $value;
    }
}
