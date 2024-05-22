<?php

namespace Zenbu\models;

use EllisLab\ExpressionEngine\Service\Model\Model;

class FilterSetting extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'zenbu_filter_settings';

	protected $id;
	protected $userId;
	protected $userGroupId;
	protected $sectionId;
	protected $setting;
	protected $value;

	protected static $_relationships = [
		'Channel' => [
			'model' => 'ee:Channel',
			'type' => 'BelongsTo',
			'from_key' => 'sectionId',
			'to_key' => 'channel_id',
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

	protected function get__channel_id()
	{
		return $this->sectionId;
	}
}