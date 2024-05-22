<?php

namespace Zenbu\models;

use EllisLab\ExpressionEngine\Service\Model\Model;

class Permission extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'zenbu_permissions';

	protected $id;
	protected $userId;
	protected $userGroupId;
	protected $setting;
	protected $value;

}