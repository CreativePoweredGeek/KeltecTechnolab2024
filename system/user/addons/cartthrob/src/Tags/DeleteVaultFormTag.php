<?php

namespace CartThrob\Tags;

use CartThrob\Model\Vault as VaultModel;

class DeleteVaultFormTag extends Tag
{
    public function process()
    {
        $vault_id = $this->param('vault_id');
        if (!$vault_id) {
            return;
        }

        $vault = ee('cartthrob:VaultService')->getMemberVault($vault_id, $this->getMemberId());
        if (!$vault instanceof VaultModel) {
            return;
        }

        $this->guardLoggedOutRedirect();

        $data = $this->globalVariables(true);
        $data = array_merge($vault->toArray(), $data);
        $data['vault_gateway'] = $vault->gateway;

        ee()->form_builder->initialize([
            'form_data' => [
                'secure_return',
                'row_id',
                'return',
            ],
            'classname' => 'Cartthrob',
            'method' => 'delete_vault_action',
            'params' => $this->params(),
            'content' => $this->parseVariablesRow($data),
        ]);

        ee()->form_builder->set_hidden('vault_id', $vault_id);

        return ee()->form_builder->form();
    }
}
