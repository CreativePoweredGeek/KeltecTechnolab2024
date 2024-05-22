<?php

namespace CartThrob\Tags;

use EE_Session;

class DeleteFromCartFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('form_builder');
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        $data = $this->globalVariables(true);

        ee()->form_builder->initialize([
            'form_data' => [
                'secure_return',
                'row_id',
                'return',
            ],
            'classname' => 'Cartthrob',
            'method' => 'delete_from_cart_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
        ]);

        return ee()->form_builder->form();
    }
}
