<?php

namespace CartThrob\Tags;

class CouponCountTag extends Tag
{
    /**
     * Returns total number of coupon codes in Cart
     *
     * @return int
     */
    public function process()
    {
        return count(ee()->cartthrob->cart->coupon_codes());
    }
}
