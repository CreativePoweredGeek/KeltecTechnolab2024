<?php

namespace CartThrob\Tags;

use EE_Session;

class CartSubtotalMinusDiscountTag extends Tag
{
    /**
     * CartSubtotalMinusDiscountTag constructor.
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns subtotal price of all items in cart minus discount
     */
    public function process()
    {
        $value = max(ee()->cartthrob->cart->subtotal() - ee()->cartthrob->cart->discount(), 0);

        switch (tag_param(2)) {
            case 'numeric':
                return $value;

            case 'plus_tax':
                $value = max(ee()->cartthrob->cart->subtotal_with_tax() - ee()->cartthrob->cart->discount(), 0);

                if (tag_param_equals(3, 'numeric')) {
                    return $value;
                }
                break;
        }

        return ee()->number->format($value);
    }
}
