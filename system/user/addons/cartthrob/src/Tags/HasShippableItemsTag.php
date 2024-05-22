<?php

namespace CartThrob\Tags;

class HasShippableItemsTag extends Tag
{
    public function process()
    {
        foreach (ee()->cartthrob->cart->items() as $row_id => $item) {
            $product = $item->product_id() ? ee()->product_model->get_product($item->product_id()) : false;

            if ($product) {
                $data = ee()->cartthrob_entries_model->entry_vars($product);

                if (isset($data, $data['product_shippable']) && $data['product_shippable'] == 'Yes') {
                    return true;
                }
            }
        }

        return false;
    }
}
