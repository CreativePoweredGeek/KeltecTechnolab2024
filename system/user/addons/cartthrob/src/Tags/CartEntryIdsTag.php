<?php

namespace CartThrob\Tags;

class CartEntryIdsTag extends Tag
{
    /**
     * Returns a pipe delimited list of entry ids
     */
    public function process()
    {
        return implode('|', ee()->cartthrob->cart->product_ids());
    }
}
