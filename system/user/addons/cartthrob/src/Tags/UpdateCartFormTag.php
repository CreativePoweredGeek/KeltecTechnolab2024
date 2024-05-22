<?php

namespace CartThrob\Tags;

use EE_Session;

class UpdateCartFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library('form_builder');
        ee()->load->model('subscription_model');
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        $variables = $this->compileVariables();

        ee()->form_builder->initialize([
            'form_data' => [
                'secure_return',
                'return',
            ],
            'encoded_form_data' => ee()->subscription_model->encoded_form_data(),
            'encoded_numbers' => ee()->subscription_model->encoded_numbers(),
            'encoded_bools' => ee()->subscription_model->encoded_bools(),
            'classname' => 'Cartthrob',
            'method' => 'update_cart_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($variables),
        ]);

        if (ee()->has('coilpack')) {
            foreach ($variables as $key => $data) {
                ee()->TMPL->add_data($data, $key);
            }
        }

        return ee()->form_builder->form();
    }

    /**
     * @return mixed
     */
    private function compileVariables()
    {
        $variables = $this->globalVariables(true);

        foreach ($this->getVarSingle() as $key) {
            if (!isset($variables[$key]) && strpos($key, 'custom_data:') === 0) {
                $variables[$key] = '';
            }
        }

        return $variables;
    }
}
