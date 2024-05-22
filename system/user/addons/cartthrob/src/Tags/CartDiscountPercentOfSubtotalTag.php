<?php

namespace CartThrob\Tags;

class CartDiscountPercentOfSubtotalTag extends Tag
{
    /**
     * Returns discount percentage of subtotal
     */
    public function process()
    {
        return ee()->cartthrob->cart->discount() / ee()->cartthrob->cart->subtotal() * 100;
    }
}
