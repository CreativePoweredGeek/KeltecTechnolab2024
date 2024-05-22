<?php

namespace CartThrob\Actions;

class CartAction extends Action
{
    public function process()
    {
        ee()->cartthrob->save_customer_info();
    }
}
