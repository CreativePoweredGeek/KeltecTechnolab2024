<?php

namespace CartThrob\Tags;

class UniqueItemsCountTag extends Tag
{
    public function process()
    {
        return ee()->cartthrob->cart->count();
    }
}
