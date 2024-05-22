<?php

namespace CartThrob\Tags;

class NewCartTag extends Tag
{
    public function process()
    {
        ee()->cartthrob->cart->initialize()->save();
        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
