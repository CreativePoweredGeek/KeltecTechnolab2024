<?php

namespace CartThrob\Tags;

use EE_Session;

class CartDiscountTag extends Tag
{
    /**
     * CartDiscountTag constructor.
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns total discount amount for cart
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->discount();

        switch (tag_param(2)) {
            case 'numeric':
                return $value;

            case 'minus_tax':
                /*
                 * We are ADDING the tax amount, because the discount will INCREASE due to the offset of the reduced tax
                 * applied to everything else based on this discount. Technically the discount is a negative amount... flip
                 * your brain... we're representing the total negative amount applied to the cart.
                 */
                $value = ee()->cartthrob->cart->discount() + ee()->cartthrob->cart->discount_tax();

                if (tag_param_equals(3, 'numeric')) {
                    return $value;
                }
                break;
        }

        return ee()->number->format($value);
    }
}
