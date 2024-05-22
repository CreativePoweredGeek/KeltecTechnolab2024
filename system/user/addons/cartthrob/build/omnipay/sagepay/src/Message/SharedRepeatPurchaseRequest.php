<?php

namespace CartThrob\Dependency\Omnipay\SagePay\Message;

use CartThrob\Dependency\Omnipay\Common\Helper;
/**
 * Sage Pay Direct Repeat Authorize Request
 */
class SharedRepeatPurchaseRequest extends SharedRepeatAuthorizeRequest
{
    public function getTxType()
    {
        return static::TXTYPE_REPEAT;
    }
}
