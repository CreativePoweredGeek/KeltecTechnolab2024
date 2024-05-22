<?php

namespace CartThrob\Tags;

class CartDiscountPercentOfTotalTag extends Tag
{
    /**
     * Returns discount percentage of total
     */
    public function process()
    {
        return ee()->cartthrob->cart->discount() / ee()->cartthrob->cart->total() * 100;
    }
}
