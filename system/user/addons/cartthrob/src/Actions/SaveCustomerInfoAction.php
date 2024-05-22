<?php

namespace CartThrob\Actions;

class SaveCustomerInfoAction extends Action
{
    public function process()
    {
        if (ee()->extensions->active_hook('cartthrob_save_customer_info_start') === true) {
            ee()->extensions->call('cartthrob_save_customer_info_start');
        }

        if (ee()->form_builder->validate()) {
            ee()->cartthrob->save_customer_info();
        } else {
            $this->setGlobalValues();
        }

        if (ee()->form_builder->has_errors()) {
            ee()->form_builder
                ->set_error_callback([ee()->cartthrob, 'action_complete'])
                ->action_complete();
        } else {
            if (ee()->extensions->active_hook('cartthrob_save_customer_info_end') === true) {
                ee()->extensions->call('cartthrob_save_customer_info_end');
            }

            ee()->form_builder
                ->set_success_callback([ee()->cartthrob, 'action_complete'])
                ->action_complete();
        }
    }
}
