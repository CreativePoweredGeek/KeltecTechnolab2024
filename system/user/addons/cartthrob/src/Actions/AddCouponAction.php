<?php

namespace CartThrob\Actions;

class AddCouponAction extends Action
{
    public function process()
    {
        if (!ee()->form_builder->validate()) {
            $this->setGlobalValues();

            ee()->form_builder->set_value('coupon_code');

            return ee()->form_builder->action_complete();
        }

        ee()->cartthrob->save_customer_info();

        ee()->cartthrob->cart->add_coupon_code($this->request->input('coupon_code'));

        ee()->form_builder
            ->set_errors(ee()->cartthrob->errors())
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }
}
