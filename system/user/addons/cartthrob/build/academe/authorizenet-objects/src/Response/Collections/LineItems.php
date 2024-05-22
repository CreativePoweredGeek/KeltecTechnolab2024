<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections;

/**
 * Collection of response LineItems.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Collections\LineItems as RequestLineitems;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Model\LineItem;
class LineItems extends RequestLineitems
{
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof LineItem && $item->hasAny();
    }
}
