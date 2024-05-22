<?php

namespace CartThrob\Tags;

class CartWeightTag extends Tag
{
    /**
     * Returns the total weight of all items in the cart
     */
    public function process()
    {
        return ee()->cartthrob->cart->weight();
    }
}
