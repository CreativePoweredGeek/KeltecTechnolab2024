<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections;

/**
 * Collection of response messages, with an overall result code.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Model\HostedPaymentSetting;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Model\Message;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\HasDataTrait;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractCollection;
class Messages extends AbstractCollection
{
    use HasDataTrait;
    public function __construct(array $data = [])
    {
        $this->setData($data);
        // An array of message records.
        foreach ($this->getDataValue('message') as $message_data) {
            $this->push(new Message($message_data));
        }
    }
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof Message;
    }
}