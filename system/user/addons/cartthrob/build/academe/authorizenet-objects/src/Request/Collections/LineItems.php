<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Request\Collections;

/**
 *
 */
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractCollection;
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Model\LineItem;
class LineItems extends AbstractCollection
{
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof LineItem && $item->hasAny();
    }
    /**
     * The array of lineItems needs to be wrapped by a single lineItem element.
     */
    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        return ['lineItem' => $data];
    }
}
