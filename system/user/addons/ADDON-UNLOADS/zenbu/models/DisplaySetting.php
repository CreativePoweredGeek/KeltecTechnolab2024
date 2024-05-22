<?php

namespace Zenbu\models;

use EllisLab\ExpressionEngine\Service\Model\Model;

class DisplaySetting extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'zenbu_display_settings';

	protected $id;
	protected $fieldType;
	protected $userId;
	protected $userGroupId;
	protected $fieldId;
	protected $sectionId;
	protected $show;
	protected $order;
	protected $settings;

	protected static $_relationships = [
		'Field' => [
			'model' => 'ee:ChannelField',
			'type' => 'BelongsTo',
			'from_key' => 'fieldId',
			'to_key' => 'field_id',
		],
		'Channel' => [
			'model' => 'ee:Channel',
			'type' => 'BelongsTo',
			'from_key' => 'sectionId',
			'to_key' => 'channel_id',
		],
	];

	protected static $_typed_columns = [
		'settings' => 'json',
		];

	protected function get__member_id()
	{
		return $this->userId;
	}

	protected function get__group_id()
	{
		return $this->userGroupId;
	}

	protected function get__field_id()
	{
		return $this->field_id;
	}

	protected function get__channel_id()
	{
		return $this->sectionId;
	}


}