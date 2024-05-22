<?php

namespace CartThrob\Tags;

class DuplicateItemTag extends Tag
{
    public function process()
    {
        ee()->cartthrob->cart->duplicate_item($this->param('row_id'));

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
