<?php

namespace CartThrob\Tags;

use EE_Session;

class AddVaultFormTag extends Tag
{
    private ?string $formExtra = '';

    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['form_builder', 'languages', 'api/api_cartthrob_payment_gateways']);
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        if (!$this->param('id')) {
            $this->setParam('id', 'checkout_form');
        }

        if (!ee()->cartthrob->store->config('allow_gateway_selection')) {
            $this->clearParam('gateway');
        } elseif ($this->param('gateway')) {
            ee()->api_cartthrob_payment_gateways->set_gateway($this->param('gateway'));
        } elseif (ee()->cartthrob->cart->customer_info('gateway')) {
            ee()->api_cartthrob_payment_gateways->set_gateway(ee()->cartthrob->cart->customer_info('gateway'));
        }

        if (str_contains($this->tagdata(), '{vault_fields}')) {
            $this->setTagdata(str_replace('{vault_fields}', ee()->api_cartthrob_payment_gateways->gateway_fields(false, 'vault_fields'), $this->tagdata()));
        }

        if ($this->hasParam('required') && strncmp($this->param('required'), 'not ', 4) === 0) {
            $this->setParam('not_required', substr($this->param('required'), 4));
            $this->clearParam('required');
        }

        $this->addEncodedOptionVars($data);

        ee()->form_builder->initialize([
            'form_data' => [
                'action',
                'secure_return',
                'return',
                'language',
                'authorized_redirect',
                'failed_redirect',
                'declined_redirect',
                'processing_redirect',
                'member_id',
                'name',
            ],
            'encoded_form_data' => [
                'required' => 'REQ',
                'not_required' => 'NRQ',
                'gateway' => 'gateway',
            ],
            'encoded_numbers' => [],
            'encoded_bools' => [],
            'classname' => 'Cartthrob',
            'method' => 'add_vault_action',
            'params' => $this->params(),
            'action' => '',
        ]);

        if ($this->param('gateway_method')) {
            ee()->form_builder->set_hidden('gateway_method', $this->param('gateway_method'));
        }

        if (!$this->hasParam('custom_js') || $this->param('custom_js') !== true) {
            $gateway = ee()->api_cartthrob_payment_gateways->gateway();
            if ($gateway) {
                $gatewayInitialized = new $gateway['classname']();
                if (method_exists($gatewayInitialized, 'vault_form_extra')) {
                    $this->formExtra = @$gatewayInitialized->vault_form_extra();
                } else {
                    $this->formExtra = @$gatewayInitialized->form_extra();
                }
            }
        }

        $variables = $this->globalVariables(true);
        $variables['name'] = ee()->input->post('name');
        $variables['primary'] = ee()->input->post('primary');

        ee()->form_builder->set_content($this->parseVariablesRow($variables));

        return ee()->form_builder->form() . $this->formExtra;
    }
}
