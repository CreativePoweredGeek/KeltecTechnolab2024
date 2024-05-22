<?php

namespace CartThrob\Tags;

class TotalItemsCountTag extends Tag
{
    /**
     * Returns total number of ALL items (including indexes) in cart
     * If you have 4 of product A, and 5 of product B, this would return 9.
     * To get total individual items, use total unique items
     *
     * @return string
     */
    public function process()
    {
        return ee()->cartthrob->cart->count_all();
    }
}
