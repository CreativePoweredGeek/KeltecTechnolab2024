<?php

namespace CartThrob\Actions;

use CartThrob\Model\Vault as VaultModel;

class DeleteVaultAction extends Action
{
    /**
     * @return void
     */
    public function process()
    {
        $vault_id = (int)ee()->input->post('vault_id');
        $vault = ee('cartthrob:VaultService')->getMemberVault($vault_id, ee()->session->userdata('member_id'));
        if ($vault instanceof VaultModel) {
            ee('cartthrob:VaultService')->deleteVault($vault);
        }

        ee()->form_builder->action_complete();
    }
}
