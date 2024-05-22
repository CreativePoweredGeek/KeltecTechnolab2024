<?php

namespace CartThrob\Tags;

use EE_Session;

class CartTaxTag extends Tag
{
    /**
     * CartTaxTag constructor.
     * @param EE_Session $session
     */
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('number');
    }

    /**
     * Returns total tax amount for cart
     */
    public function process()
    {
        $value = ee()->cartthrob->cart->tax();

        if (tag_param_equals(2, 'numeric')) {
            return $value;
        }

        return ee()->number->format($value);
    }
}
