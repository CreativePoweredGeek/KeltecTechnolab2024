<?php
namespace Zenbu\controllers;

use Zenbu\librairies\platform\ee\Request;
use Zenbu\librairies\platform\ee\Session;

class SavedSearchesController
{
    var $permissions;

    public function __construct()
    {

    }

    public function fetch_saved_searches()
	{
		$saved_searches = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->order('order', 'asc')
			->all();

		return ['saved_searches' => $saved_searches->getValues(), 'total_results' => $saved_searches->count()];
	}

	public function fetch_saved_search_filters()
	{
		$saved_search = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->filter('id', Request::param('search_id'))
			->first();

		$filters = $saved_search->Filter;

		$out = [];

		foreach($filters as $filter)
		{
			switch($filter->filterAttribute1)
			{
				case 'channel_id':
					$out['channel_id'] = $filter->filterAttribute3;
				break;
				case 'limit':
					$out['limit'] = $filter->filterAttribute3;
				break;
				case 'order_by':
					$out['order_by'] = $filter->filterAttribute3;
				break;
				case 'sort':
					$out['sort'] = $filter->filterAttribute3;
				break;
				default:
					$out['rows'][] = [
						$filter->filterAttribute1,
						$filter->filterAttribute2,
						$filter->filterAttribute3,
					];
				break;
			}
		}

		return $out;
	}

    public function save_search()
	{
		$filters = [
			'channel_id' => Request::param('channel_id'),
			'rows'       => Request::param('rows'),
			'limit'      => Request::param('limit'),
			'order_by'   => Request::param('order_by', null),
			'sort'       => Request::param('sort', 'asc'),
		];

		$total_current_saved_searches = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->count();

		$search = ee('Model')->make('zenbu:SavedSearch');
		$search->userId = Session::user()->member_id;
		$search->site_id = Session::user()->site_id;
		$search->label = Request::param('label');
		$search->order = $total_current_saved_searches == 0  ? 0 : $total_current_saved_searches;

		$search->save();

		// Channel

		$search->Filter = ee('Model')->make('zenbu:SavedSearchFilter');
		$search->Filter->filterAttribute1 = 'channel_id';
		$search->Filter->filterAttribute2 = 'is';
		$search->Filter->filterAttribute3 = $filters['channel_id'];
		$search->Filter->order = 0;
		$search->Filter->save();

		// Filter Rows

		foreach($filters['rows'] as $key => $row)
		{
			$search->Filter = ee('Model')->make('zenbu:SavedSearchFilter');
			$search->Filter->filterAttribute1 = $row[0];
			$search->Filter->filterAttribute2 = $row[1];
			$search->Filter->filterAttribute3 = $row[2];
			$search->Filter->order = $key + 1;
			$search->Filter->save();
		}

		// Limit and Order/Sort

		foreach(['order_by', 'sort', 'limit'] as $key => $item)
		{
			$search->Filter = ee('Model')->make('zenbu:SavedSearchFilter');
			$search->Filter->filterAttribute1 = $item;
			// $search->Filter->filterAttribute2 = 'is';
			$search->Filter->filterAttribute3 = $filters[$item];
			$search->Filter->order = count($filters['rows']) + ($key + 1);
			$search->Filter->save();
		}

		$affected_row_ids[] = $search->getId();

		$saved_searches = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->order('order', 'asc')
			->all()
			->getValues();

		return ['success' => true, 'message' => 'Search Saved', 'label' => Request::param('label'), 'filters' => $filters, 'search' => $search, 'saved_searches' => $saved_searches];
	}

	public function update_saved_searches()
	{
		foreach(Request::param('saved_searches') as $key => $saved_search)
		{
			$iteration = ee('Model')->get('zenbu:SavedSearch')
				->filter('userId', Session::user()->member_id)
				->filter('site_id', Session::user()->site_id)
				->filter('id', $saved_search['id'])
				->first();

			$iteration->label = $saved_search['label'];
			$iteration->order = $key;
			$iteration->save();
		}

		$saved_searches = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->order('order', 'asc')
			->all();

		return ['saved_searches' => $saved_searches->getValues(), 'total_results' => $saved_searches->count()];
	}

	public function delete_saved_searches()
	{
		foreach(Request::param('selected_items') as $search_id)
		{
			ee('Model')->get('zenbu:SavedSearch')
				->filter('id', $search_id)
				->delete();
		}

		$saved_searches = ee('Model')->get('zenbu:SavedSearch')
			->filter('userId', Session::user()->member_id)
			->filter('site_id', Session::user()->site_id)
			->order('order', 'asc')
			->all();

		return ['success' => true, 'message' => 'Selected Saved Searches Deleted', 'saved_searches' => $saved_searches->getValues(), 'total_results' => $saved_searches->count()];
	}
}
