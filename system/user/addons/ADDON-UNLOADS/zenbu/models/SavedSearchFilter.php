<?php

namespace Zenbu\models;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SavedSearchFilter extends Model {

	protected static $_primary_key = 'id';
	protected static $_table_name = 'zenbu_saved_search_filters';

	protected $id;
	protected $searchId;
	protected $filterAttribute1;
	protected $filterAttribute2;
	protected $filterAttribute3;
	protected $order;

	protected static $_relationships = [
		'SavedSearch' => [
			'model' => 'SavedSearch',
			'type' => 'BelongsTo',
			'from_key' => 'searchId',
			'to_key' => 'id',
		]
	];

}