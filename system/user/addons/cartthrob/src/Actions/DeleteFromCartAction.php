<?php

namespace CartThrob\Actions;

class DeleteFromCartAction extends Action
{
    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_delete_from_cart_start') === true) {
            ee()->extensions->call('cartthrob_delete_from_cart_start');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        if (!ee()->form_builder->validate()) {
            return ee()->form_builder->action_complete();
        }

        ee()->cartthrob->save_customer_info();

        if ($this->request->has('row_id')) {
            ee()->cartthrob->cart->remove_item($this->request->input('row_id'));
        }

        if (ee()->extensions->active_hook('cartthrob_delete_from_cart_end') === true) {
            ee()->extensions->call('cartthrob_delete_from_cart_end');
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->form_builder
            ->set_success_callback([ee()->cartthrob, 'action_complete'])
            ->action_complete();
    }
}
