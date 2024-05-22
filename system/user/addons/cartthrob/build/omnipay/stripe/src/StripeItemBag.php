<?php

/**
 * Stripe Item bag
 */
namespace CartThrob\Dependency\Omnipay\Stripe;

use CartThrob\Dependency\Omnipay\Common\ItemBag;
use CartThrob\Dependency\Omnipay\Common\ItemInterface;
/**
 * Class StripeItemBag
 *
 * @package Omnipay\Stripe
 */
class StripeItemBag extends ItemBag
{
    /**
     * Add an item to the bag
     *
     * @see Item
     *
     * @param ItemInterface|array $item An existing item, or associative array of item parameters
     */
    public function add($item)
    {
        if ($item instanceof ItemInterface) {
            $this->items[] = $item;
        } else {
            $this->items[] = new StripeItem($item);
        }
    }
}
