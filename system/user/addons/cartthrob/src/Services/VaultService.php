<?php

namespace CartThrob\Services;

use CartThrob\Model\Vault as VaultModel;
use Cartthrob_token;

class VaultService
{
    /**
     * @param VaultModel $vault
     * @return void
     */
    public function deleteVault(VaultModel $vault): void
    {
        if (ee()->extensions->active_hook('cartthrob_delete_vault_start') === true) {
            ee()->extensions->call('cartthrob_delete_vault_start', $vault);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        $vault_id = $vault->id;
        $vault->delete();

        if (ee()->extensions->active_hook('cartthrob_delete_vault_end') === true) {
            ee()->extensions->call('cartthrob_delete_vault_end', $vault_id);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }
    }

    /**
     * @param int $vault_id
     * @param int $member_id
     * @return VaultModel|null
     */
    public function getMemberVault(int $vault_id, int $member_id): ?VaultModel
    {
        return ee('Model')
            ->get('cartthrob:Vault')
            ->filter('id', $vault_id)
            ->filter('member_id', $member_id)
            ->first();
    }

    /**
     * @param VaultModel $vault
     * @return bool
     */
    public function updateVault(VaultModel $vault): ?bool
    {
        if (ee()->extensions->active_hook('cartthrob_update_vault_start') === true) {
            $vault = ee()->extensions->call('cartthrob_update_vault_start', $vault);
            if (ee()->extensions->end_script === true) {
                return true;
            }
        }

        $vault->modified = ee()->localize->now;
        if ($vault->primary == 1) {
            $this->resetMemberPrimaryVault($vault->member_id);
        }

        if ($vault->save() instanceof VaultModel) {
            if (ee()->extensions->active_hook('cartthrob_update_vault_end') === true) {
                ee()->extensions->call('cartthrob_update_vault_end', $vault);
            }

            return true;
        }

        return false;
    }

    /**
     * @param array $data
     * @return VaultModel|null
     */
    public function createToken(array $data): ?Cartthrob_token
    {
        if (ee()->extensions->active_hook('cartthrob_create_token_start') === true) {
            $data = ee()->extensions->call('cartthrob_create_token_start', $data);
            if (ee()->extensions->end_script === true) {
                return null;
            }
        }

        $token = ee()->cartthrob_payments->createToken('');
        if ($token instanceof Cartthrob_token) {
            if ($token->token() != '') {
                $data['token'] = $token->token();
                $data['member_id'] = ee()->session->userdata('member_id');
                $data['customer_id'] = $token->customer_id();
                $data['created_date'] = $data['modified'] = ee()->localize->now;
                $data['primary'] = $data['primary'] ?? 0;

                if (bool_string($data['primary'])) {
                    $this->resetMemberPrimaryVault(ee()->session->userdata('member_id'));
                }

                $vault = ee('Model')
                    ->make('cartthrob:Vault');
                $vault->set($data);
                $vault->save();

                if (ee()->extensions->active_hook('cartthrob_create_token_end') === true) {
                    ee()->extensions->call('cartthrob_create_token_end', $data, $token, $vault);
                    if (ee()->extensions->end_script === true) {
                        return $token;
                    }
                }
            }
        }

        return $token;
    }

    /**
     * @param int $member_id
     * @return void
     */
    public function resetMemberPrimaryVault(int $member_id): void
    {
        if (ee()->extensions->active_hook('cartthrob_reset_primary_vault_start') === true) {
            ee()->extensions->call('cartthrob_reset_primary_vault_start', $member_id);
            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        $what = ['primary' => 0];
        $where = ['member_id' => $member_id];
        ee()->db->update('cartthrob_vault', $what, $where);

        if (ee()->extensions->active_hook('cartthrob_reset_primary_vault_end') === true) {
            ee()->extensions->call('cartthrob_reset_primary_vault_end');
        }
    }
}
