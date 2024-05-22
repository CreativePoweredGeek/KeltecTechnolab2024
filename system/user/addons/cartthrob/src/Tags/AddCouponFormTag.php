<?php

namespace CartThrob\Tags;

use EE_Session;

class AddCouponFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('form_builder');
    }

    /**
     * Prints a coupon code form.
     */
    public function process()
    {
        $this->guardLoggedOutRedirect();

        $data = $this->globalVariables(true);
        $data['allowed'] = 1;

        if (ee()->cartthrob->store->config('global_coupon_limit') && count(ee()->cartthrob->cart->coupon_codes()) >= ee()->cartthrob->store->config('global_coupon_limit')) {
            $data['allowed'] = 0;
        }

        ee()->form_builder->initialize([
            'classname' => 'Cartthrob',
            'method' => 'add_coupon_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
            'form_data' => [
                'action',
                'secure_return',
                'return',
                'language',
            ],
            'encoded_form_data' => [],
            'encoded_numbers' => [],
            'encoded_bools' => [
                'json' => 'JSN',
            ],
        ]);

        return ee()->form_builder->form();
    }
}
