<?php

namespace CartThrob\Tags;

use Cartthrob_item;

class ChangeQuantityTag extends Tag
{
    public function process()
    {
        /** @var CartThrob_item $item */
        if ($item = ee()->cartthrob->cart->item($this->param('row_id'))) {
            $item->set_quantity($this->param('quantity'));
        }

        ee()->cartthrob->cart->save();

        ee()->template_helper->tag_redirect($this->param('return'));
    }
}
