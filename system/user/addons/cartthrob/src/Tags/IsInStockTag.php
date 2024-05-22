<?php

namespace CartThrob\Tags;

use Cartthrob_product;

class IsInStockTag extends Tag
{
    /**
     * @return bool
     */
    public function process()
    {
        $entryId = $this->param('entry_id');

        if ($entryId === false || !is_numeric($entryId)) {
            return false;
        }

        /** @var Cartthrob_product $product */
        $product = ee()->cartthrob->get_product($this->param('entry_id'));

        if (!$product) {
            return false;
        }

        return $product->in_stock();
    }
}
