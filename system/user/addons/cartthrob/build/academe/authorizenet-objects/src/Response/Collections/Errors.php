<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections;

/**
 * Collection of transaction errors.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Model\HostedPaymentSetting;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\HasDataTrait;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Model\Error;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractCollection;
class Errors extends AbstractCollection
{
    use HasDataTrait;
    public function __construct(array $data = [])
    {
        $this->setData($data);
        // ...and an array of error records.
        foreach ($this->getData() as $message_data) {
            $this->push(new Error($message_data));
        }
    }
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof Error;
    }
}
