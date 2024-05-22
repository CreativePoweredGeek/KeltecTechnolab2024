<?php
namespace Zenbu\controllers;

use Zenbu\librairies\platform\ee\Request;
use Zenbu\librairies\platform\ee\Session;

class PermissionsController
{
	var $permissions;

	public function __construct()
	{
	}

	public function fetch_permissions()
	{
		$member_group = Session::user()->group_id;

		$permissions = ee('Model')->get('zenbu:Permission')
			// ->filter('userId', Session::user()->member_id)
			// ->filter('sectionId', Request::param('channel_id'))
			// ->order('order', 'ASC')
			->all();

		$permission_array = [];

		foreach($permissions as $perm)
		{
			$permission_array[$perm->userGroupId] = [];
		}

		foreach($permissions as $perm)
		{
			if(isset($perm->value) && $perm->value == 'y')
			{
				array_push($permission_array[$perm->userGroupId], $perm->setting);
			}
		}

		$member_groups = ee('Model')->get('MemberGroup')->all();

		return ['member_group' => $member_group, 'permissions' => $permission_array, 'member_groups' => $member_groups->getValues()];
	}

	public function save_permissions()
	{
		$permission = ee('Model')->get('zenbu:Permission')
			->filter('userGroupId', Request::param('group_id'))
			->filter('setting', Request::param('permission'))
			->first();

		if(! $permission)
		{
			// No previous row found. Create a new row.

			$permission = ee('Model')->make('zenbu:Permission');
			$permission->userGroupId = Request::param('group_id');
			$permission->setting = Request::param('permission');
		}

		$permission->value = Request::param('enabled');
		$permission->save();

		$affected_row_id = $permission->getId();

		return ['success' => true, 'message' => 'Permission updated', 'permission' => Request::param('permission'), 'group_id' => Request::param('group_id'), 'enabled' => $permission->value, 'row_id' => $affected_row_id];
	}

}
