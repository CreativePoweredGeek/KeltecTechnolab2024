<?php

namespace CartThrob\Tags;

class HasTaxTag extends Tag
{
    /**
     * Returns true if cart has tax, false if not
     */
    public function process()
    {
        return (float)ee()->cartthrob->cart->tax() ? 'TRUE' : 'FALSE';
    }
}
