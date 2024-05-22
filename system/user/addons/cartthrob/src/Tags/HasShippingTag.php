<?php

namespace CartThrob\Tags;

class HasShippingTag extends Tag
{
    /**
     * Returns true if cart has shipping, false if not
     */
    public function process()
    {
        return (float)ee()->cartthrob->cart->shipping() ? 'TRUE' : 'FALSE';
    }
}
