<?php

namespace CartThrob\Tags;

class CartEmptyRedirectTag extends Tag
{
    /**
     * Redirects if cart is empty.
     * Place on your view cart page.
     */
    public function process()
    {
        if (ee()->cartthrob->cart->is_empty()) {
            ee()->template_helper->tag_redirect($this->param('return'));
        }
    }
}
