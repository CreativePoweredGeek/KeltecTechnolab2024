<?php

namespace Zenbu\models;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SavedSearch extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'zenbu_saved_searches';

	protected $id;
	protected $label;
	protected $userId;
	protected $userGroupId;
	protected $order;
	protected $site_id;

	protected static $_relationships = [
		'Filter' => [
			'model'    => 'SavedSearchFilter',
			'type'     => 'HasMany',
			'from_key' => 'id',
			'to_key'   => 'searchId',
		]
	];

	protected function get__member_id()
	{
		return $this->userId;
	}

	protected function get__group_id()
	{
		return $this->userGroupId;
	}


}