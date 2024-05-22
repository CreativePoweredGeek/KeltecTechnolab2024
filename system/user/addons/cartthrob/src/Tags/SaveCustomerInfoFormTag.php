<?php

namespace CartThrob\Tags;

use EE_Session;

class SaveCustomerInfoFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('form_builder');
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        $variables = $this->globalVariables(true);

        ee()->form_builder->initialize([
            'form_data' => [
                'return',
                'secure_return',
                'derive_country_code',
                'error_handling',
            ],
            'encoded_form_data' => [],
            'classname' => 'Cartthrob',
            'method' => 'save_customer_info_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($variables),
        ]);

        return ee()->form_builder->form();
    }
}
