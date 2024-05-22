<?php

namespace CartThrob\Tags;

class ClearCartTag extends Tag
{
    /**
     * Empties the cart
     */
    public function process()
    {
        ee()->cartthrob->cart
            ->clear_items()
            ->clear_coupon_codes()
            ->clear_shipping_info()
            ->clear_totals();

        if ($this->param('clear_customer_info')) {
            ee()->cartthrob->cart
                ->clear_customer_info()
                ->clear_custom_data();
        }

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
