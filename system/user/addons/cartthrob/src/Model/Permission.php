<?php

namespace CartThrob\Model;

/**
 * Permission Model
 */
class Permission extends AbstractModel
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_permissions';

    protected static $_validation_rules = [
        'member_id' => 'required|integer|validateMemberExists',
        'permission' => 'required|integer|validatePermissionExists|validateUniqueToMember',
    ];

    protected $id;
    protected $member_id;
    protected $order_id;
    protected $item_id;
    protected $permission;
    protected $sub_id;

    protected static $_relationships = [
        'Member' => [
            'type' => 'BelongsTo',
            'model' => 'ee:Member',
            'from_key' => 'member_id',
            'to_key' => 'member_id',
        ],
        'Entry' => [
            'type' => 'BelongsTo',
            'model' => 'ee:ChannelEntry',
            'from_key' => 'order_id',
            'to_key' => 'entry_id',
        ],
        'PermissionItem' => [
            'type' => 'BelongsTo',
            'model' => 'PermissionItem',
            'from_key' => 'permission',
            'to_key' => 'id',
        ],
    ];

    /**
     * Rule to ensure the Permission exists
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validatePermissionExists($name, $value, $params, $object)
    {
        $permission = ee('Model')
            ->get('cartthrob:PermissionItem')
            ->filter('id', $value)
            ->first();

        if ($permission instanceof \CartThrob\Model\PermissionItem) {
            return true;
        }

        return 'ct.error.invalid_permission';
    }

    /**
     * Validates that the provided Permission isn't already assigned to the member
     * @param $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateUniqueToMember($name, $value, $params, $object)
    {
        $permission = ee('Model')
            ->get('cartthrob:Permission')
            ->filter('member_id', $this->member_id)
            ->filter('permission', $value)
            ->first();

        if ($permission instanceof \CartThrob\Model\Permission) {
            return 'ct.error.duplicate_permission_for_member';
        }

        return true;
    }
}
