<?php

namespace CartThrob\Tags;

use CartThrob\Model\Vault as VaultModel;
use EE_Session;

class UpdateVaultFormTag extends Tag
{
    public function __construct(EE_Session $session)
    {
        parent::__construct($session);

        ee()->load->library(['form_builder', 'languages', 'api/api_cartthrob_payment_gateways']);
    }

    public function process()
    {
        $this->guardLoggedOutRedirect();

        $vault_id = $this->param('vault_id');
        if (!$vault_id) {
            return;
        }

        $vault = ee('cartthrob:VaultService')->getMemberVault($vault_id, $this->getMemberId());
        if (!$vault instanceof VaultModel) {
            return;
        }

        if (!$this->param('id')) {
            $this->setParam('id', 'update_vault_form');
        }

        ee()->api_cartthrob_payment_gateways->set_gateway($vault->gateway);
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
            ],
            'encoded_form_data' => [
                'required' => 'REQ',
                'not_required' => 'NRQ',
                'gateway' => 'gateway',
            ],
            'encoded_numbers' => [],
            'encoded_bools' => [],
            'classname' => 'Cartthrob',
            'method' => 'update_vault_action',
            'params' => $this->params(),
            'action' => '',
        ]);

        if ($this->param('gateway_method')) {
            ee()->form_builder->set_hidden('gateway_method', $this->param('gateway_method'));
        }

        ee()->form_builder->set_hidden('vault_id', $vault_id);
        $variables = $this->globalVariables(true);
        $variables = array_merge($variables, $vault->toArray());

        ee()->form_builder->set_content($this->parseVariablesRow($variables));

        return ee()->form_builder->form();
    }
}
