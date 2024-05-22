<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

abstract class AbstractModel extends Model
{
    /**
     * Rule to ensure the member_id actually exists in the system
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberExists($name, $value, $params, $object)
    {
        $member = ee('Model')
            ->get('Member')
            ->filter('member_id', $value)
            ->first();

        if ($member instanceof \ExpressionEngine\Model\Member\Member) {
            return true;
        }

        return 'ct.error.invalid_member_id';
    }

    /**
     * Rule to ensure a Vault exists
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     */
    public function validateVaultExists($name, $value, $params, $object)
    {
        if (!$value) {
            return true;
        }

        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('id', $value)
            ->first();

        if (!$vault) {
            return 'ct.error.vault_not_exist';
        }

        return true;
    }

    /**
     * Rule to ensure the set vault is owned by the set member
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateVaultOwnedByMember($name, $value, $params, $object)
    {
        if (!$value) {
            return true;
        }

        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('member_id', $this->member_id)
            ->filter('id', $value)
            ->first();

        if (!$vault) {
            return 'ct.error.vault_not_owned_by_member';
        }

        return true;
    }

    /**
     * Checks to ensure a member has a vault that is active
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateMemberHasActiveVault($name, $value, $params, $object)
    {
        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('member_id', $value)
            ->first();
        if (!$vault) {
            return 'ct.error.member_not_have_active_vault';
        }

        return true;
    }
}
