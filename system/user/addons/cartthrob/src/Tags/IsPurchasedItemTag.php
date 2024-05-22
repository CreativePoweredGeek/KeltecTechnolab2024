<?php

namespace CartThrob\Tags;

class IsPurchasedItemTag extends Tag
{
    /**
     * Returns a conditional whether item has been purchased
     */
    public function process()
    {
        // @TODO add in the ability to pull up items with a particular status or recognize only completed items.

        ee()->load->model('purchased_items_model');

        $data['is_purchased_item'] = ee()->purchased_items_model->has_purchased($this->param('entry_id'));

        // single tag
        if (!$this->tagdata()) {
            return (int)$data['is_purchased_item'];
        }

        return $this->parseVariablesRow($data);
    }
}
