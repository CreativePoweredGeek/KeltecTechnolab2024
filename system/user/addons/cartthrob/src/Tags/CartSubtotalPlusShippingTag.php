<?php

namespace CartThrob\Tags;

use EE_Session;

class CartSubtotalPlusShippingTag extends Tag
{
    /**
     * CartSubtotalPlusShippingTag constructor.
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns subtotal price of all items in cart plus shipping
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->subtotal() + ee()->cartthrob->cart->shipping();

        if (tag_param_equals(2, 'numeric')) {
            return $value;
        }

        return ee()->number->format($value);
    }
}
