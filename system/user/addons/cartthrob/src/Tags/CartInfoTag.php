<?php

namespace CartThrob\Tags;

class CartInfoTag extends Tag
{
    /**
     * Template tag that outputs generic cart info & conditionals related to totals and shipping
     */
    public function process()
    {
        $this->setTagdata(ee()->functions->prep_conditionals($this->tagdata(), ee()->cartthrob->cart->info(false)));

        return $this->parseVariablesRow($this->globalVariables());
    }
}
