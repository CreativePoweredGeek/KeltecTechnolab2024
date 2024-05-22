<?php

namespace CartThrob\Tags;

use EE_Session;

class CartShippingTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns total shipping price for cart
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->shipping();

        switch (tag_param(2)) {
            case 'numeric':
                return $value;

            case 'plus_tax':
                $value = ee()->cartthrob->cart->shipping_plus_tax();

                if (tag_param_equals(3, 'numeric')) {
                    return $value;
                }
                break;
        }

        return ee()->number->format($value);
    }
}
