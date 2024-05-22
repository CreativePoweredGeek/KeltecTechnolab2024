<?php

namespace CartThrob\Tags;

use EE_Session;

class CartSubtotalTag extends Tag
{
    /**
     * CartSubtotalTag constructor.
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns subtotal price of all items in cart
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->subtotal();

        switch (tag_param(2)) {
            case 'numeric':
                return $value;

            case 'plus_tax':
                $value = ee()->cartthrob->cart->subtotal_with_tax();

                if (tag_param_equals(3, 'numeric')) {
                    return $value;
                }
                break;
        }

        return ee()->number->format($value);
    }
}
