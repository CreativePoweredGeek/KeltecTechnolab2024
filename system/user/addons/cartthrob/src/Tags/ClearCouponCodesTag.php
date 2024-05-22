<?php

namespace CartThrob\Tags;

class ClearCouponCodesTag extends Tag
{
    public function process()
    {
        ee()->cartthrob->cart->clear_coupon_codes()->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
