<?php

namespace CartThrob\Tags;

class IsInCartTag extends Tag
{
    public function process()
    {
        $data['is_in_cart'] = $this->hasParam('entry_id') && ee()->cartthrob->cart->find_item(['entry_id' => $this->param('entry_id')]);

        // single tag
        if (!$this->tagdata()) {
            return $data['is_in_cart'];
        }

        $data['item_in_cart'] = $data['is_in_cart'];

        return $this->parseVariablesRow($data);
    }
}
