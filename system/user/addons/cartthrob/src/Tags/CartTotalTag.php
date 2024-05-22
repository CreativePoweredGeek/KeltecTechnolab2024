<?php

namespace CartThrob\Tags;

use EE_Session;

class CartTotalTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns total price of all items in cart
     * The formula is subtotal + tax + shipping - discount
     */
    public function process()
    {
        if (tag_param_equals(2, 'numeric')) {
            return ee()->cartthrob->cart->total();
        }

        return ee()->number->format(ee()->cartthrob->cart->total());
    }
}
