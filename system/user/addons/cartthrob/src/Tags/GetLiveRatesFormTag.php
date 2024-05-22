<?php

namespace CartThrob\Tags;

use EE_Session;

class GetLiveRatesFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['api', 'api/api_cartthrob_shipping_plugins', 'form_builder']);
    }

    /**
     * Outputs a quote request form
     */
    public function process()
    {
        $data = $this->globalVariables(true);
        $data['shipping_fields'] = $this->selected_shipping_fields();

        ee()->form_builder->initialize([
            'classname' => 'Cartthrob',
            'method' => 'update_live_rates_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
            'form_data' => [
                'return',
                'secure_return',
                'derive_country_code',
                'shipping_plugin',
                'shipping_option',
                'activate_plugin',
            ],
            'encoded_form_data' => [],
        ]);

        return ee()->form_builder->form();
    }

    /**
     * Returns data from the 'html' field of the currently selected shipping plugin
     *
     * @return string
     */
    private function selected_shipping_fields()
    {
        return ee()->api_cartthrob_shipping_plugins
            ->set_plugin($this->param('shipping_plugin'))
            ->html();
    }
}
