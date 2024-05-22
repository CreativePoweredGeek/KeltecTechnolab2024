<?php

namespace Zenbu\controllers;

use Zenbu\helpers\FilterSetting;
use Zenbu\librairies\platform\ee\Lang;
use Zenbu\librairies\platform\ee\Request;
use Zenbu\librairies\platform\ee\View;
use Zenbu\librairies\platform\ee\Localize;
use Zenbu\librairies\platform\ee\Field;
use Zenbu\librairies\platform\ee\Pagination;
use Zenbu\librairies\platform\ee\Query;
use Zenbu\librairies\platform\ee\Session;
use Zenbu\librairies\platform\ee\Cache;
use Zenbu\librairies\platform\ee\Hook;

class MainController
{

	public function __construct()
	{
		$this->vars['language_array'] = Lang::getAllStrings('zenbu');
		Lang::load('content');
	}

	/**
	 * Main page
	 *
	 * @return string Rendered template
	 */
	public function index()
	{
		if(AJAX_REQUEST)
		{
			echo json_encode([__CLASS__.'@'.__FUNCTION__.': This method is not designed to be requested through Ajax']);
			die();
		}

		$channels = ee('Model')->get('Channel')->order('channel_title')->all();

		$this->vars['channel_dropdown_options'] = $channels->getDictionary('channel_id', 'channel_title');
		$this->vars['member_groups']            = ee('Model')->get('MemberGroup')->all()->getDictionary('group_id', 'group_title');
		$this->vars['debug_mode']               = isset(ee()->config->item('zenbu')['debug']) && ee()->config->item('zenbu')['debug'] !== false ? 'true' : 'false';
		$this->vars['limit_dropdown_options']   = $this->vars['debug_mode'] ? [1, 2, 5, 10, 15, 25, 50, 100, 200, 500] : [5, 10, 25, 50, 100, 200, 500];
		$this->vars['action_url']               = ee('CP/URL')->make('publish/edit');
		$this->vars['filters']                  = $filters = Cache::get('filters') ?: null;
		$this->vars['page']                     = Cache::get('page') ?: 1;
		$this->vars['permissions']              = array_flip(ee('Model')->get('zenbu:Permission')
			->filter('userGroupId', Session::user()->group_id)
			->filter('value', 'y')
			->all()
			->map(function ($p) {
				return $p->setting;
			}));

		//	----------------------------------------
		//	Starting Channel ID
		//	----------------------------------------

		if(isset($filters['channel_id']))
		{
			$this->vars['starting_channel_id'] = $channel_id = $filters['channel_id'] == '' ? '""' : $filters['channel_id'];
		}
		else
		{
			$this->vars['starting_channel_id'] = $channel_id = Request::param('channel_id', '""');
		}

		//	----------------------------------------
		//	Starting Limit
		//	----------------------------------------

		if(isset($filters['limit']))
		{
			$this->vars['starting_limit'] = $filters['limit'];
		}
		else
		{
			if(isset(ee()->config->item('zenbu')['starting_limit']) && is_numeric(ee()->config->item('zenbu')['starting_limit']))
			{
				$this->vars['starting_limit'] = (int) ee()->config->item('zenbu')['starting_limit'];
			}
			else if($starting_limit = FilterSetting::getSetting($channel_id, 'starting_limit'))
			{
				$this->vars['starting_limit'] = (int) $starting_limit;
			}
			else
			{
				$this->vars['starting_limit'] = 15;
			}
		}

		//	----------------------------------------
		//	Starting Order By
		//	----------------------------------------

		if(isset($filters['order_by']))
		{
			$this->vars['starting_order_by'] = $filters['order_by'];
		}
		else
		{
			if($starting_order_by = FilterSetting::getSetting($channel_id, 'starting_order_by'))
			{
				$this->vars['starting_order_by'] = $starting_order_by;
			}
			else
			{
				$this->vars['starting_order_by'] = 'entry_id';
			}
		}

		//	----------------------------------------
		//	Starting Sort
		//	----------------------------------------

		if(isset($filters['sort']))
		{
			$this->vars['starting_sort'] = $filters['sort'];
		}
		else
		{
			if($starting_sort = FilterSetting::getSetting($channel_id, 'starting_sort'))
			{
				$this->vars['starting_sort'] = $starting_sort;
			}
			else
			{
				$this->vars['starting_sort'] = 'desc';
			}
		}

		View::prepNativeBulkEditing();

		View::includeCss([
			'css/app.css',
		]);
		View::includeJs([
			'js/app.min.js',
		]);

		$this->vars['hook_zenbu_add_nav_content'] = Hook::call('zenbu_add_nav_content');
		$this->vars['hook_zenbu_main_content_end'] = Hook::call('zenbu_main_content_end');

		return [
			'body'       => View::render('main/index.twig', $this->vars),
			'breadcrumb' => [],
			'heading'    => Lang::t('entry_manager'),
		];
	} // END index()

	// --------------------------------------------------------------------

	public function search()
	{
		$raw_results = [];
		$results = [];

		$filters = [
			'channel_id' => Request::param('channel_id'),
			'rows'       => Request::param('rows'),
			'limit'      => Request::param('limit'),
			'order_by'   => Request::param('order_by'),
			'sort'       => Request::param('sort'),
		];

		Cache::set('filters', $filters, 300);
		Cache::set('page', Request::param('page'), 300);

		// Would have loved to eager load using ->with('Categories'),
		// but if I use ->limit() later to limit Entry results,
		// ->limit() _also_ limits the returned Categories. Makes no sense. Bug?

		$query = ee('Model')->get('ChannelEntry')->with('Channel');

		if(! empty($filters['channel_id']))
		{
			$query = $query->filter('channel_id', $filters['channel_id']);
			$custom_fields = Field::fetch_fields($filters['channel_id']);
		}
		else
		{
			$custom_fields = Field::fetch_fields(null);
		}


		//	----------------------------------------
		//	Loop through each filter row and add to
		//  the query based on the row's selections
		//	----------------------------------------

		if(is_array($filters['rows']))
		{
			foreach($filters['rows'] as $row)
			{
				if($row[0])
				{
					if(is_numeric($row[0]))
					{
						$query = Field::processFieldQuery($query, $custom_fields, $row);
					}
					else if(in_array($row[0], ['category_id']))
					{
						// Unfortunately directly searching in Categories.cat_id won't work,
						// unless you eager-load, but then using a final limit() on your query
						// will also limit the returned Categories on each entry. We therefore
						// need to make a separate query in Categories, get the ChannelEntries Ids,
						// and use those in the main entry query.

						$category = ee('Model')->get('Category')->filter('cat_id', '=', $row[2])->first();

						if($category)
						{
							$entry_ids = $category->ChannelEntries->pluck('entry_id');

							if($row[1] == 'isNot')
							{
								$query = $query->filter('entry_id', 'NOT IN', $entry_ids);
							}
							else
							{
								$query = $query->filter('entry_id', 'IN', $entry_ids);
							}
						}
					}
					else if(in_array($row[0], ['entry_date', 'edit_date', 'expiration_date']))
					{
						if($row[1] == 'betweenDates')
						{
							$dates = explode(' ~ ', $row[2]);

							if(isset($dates[0]) && ! empty($dates[0]))
							{
								$query = $query->filter($row[0], '>=', Localize::format('%U', $dates[0] . ' 00:00:00'));
							}

							if(isset($dates[1]))
							{
								$query = $query->filter($row[0], '<=', Localize::format('%U', $dates[1]) . ' 23:59:59');
							}
						}
						else
						{
							$query = $query->filter($row[0], Query::whereDateOperator($row), Query::whereDateValue($row));
							$query = $query->filter($row[0], Query::whereDateOperator($row) == '<=' ? '>=' : '<=', Localize::now());
						}
					}
					else if(in_array($row[0], ['sticky']))
					{
						$query = $query->filter($row[0], Query::whereOperator($row), Query::whereValue($row));
					}
					else
					{
						if(! empty($row[2]))
						{
							$query = $query->filter($row[0], Query::whereOperator($row), Query::whereValue($row));
						}
					}
				}
			}
		}

		//    ----------------------------------------
		//    Get total results before using limit
		//    ----------------------------------------

		$total_results = $query->count();

		if(isset($filters['limit']))
		{
			$page = Request::param('page');
			$start = $filters['limit'] * ($page - 1); // this ensures page 1 starts at 0
			$query = $query->limit($filters['limit']);
			$query = $query->offset($start);
		}

		//	----------------------------------------
		//	Ordering and sorting
		//	----------------------------------------

		$filters['order_by'] = Field::processOrderBy($filters);

		if($filters['order_by'])
		{
			$query = $query->order($filters['order_by'], strtoupper($filters['sort']));
		}

		//	----------------------------------------
		//	Hook zenbu_modify_query
		//  Modify the query the way you want.
		//	----------------------------------------

		$query = Hook::call('zenbu_modify_query', $query, $custom_fields, $filters);

		$query = $query->all();

		//	----------------------------------------
		//	Display Settings
		//	----------------------------------------

		$display_settings = ee('Model')->get('zenbu:DisplaySetting')
			->with('Field')
			->filter('userId', Session::user()->member_id)
			->filter('sectionId', Request::param('channel_id'))
			->order('order', 'asc')
			->all();

		//	----------------------------------------
		//	Fallback if no results are found for Display Settings:
		//  We try to look for any member group-level Display Settings.
		//	----------------------------------------

		if($display_settings->count() === 0)
		{
			$display_settings = ee('Model')->get('zenbu:DisplaySetting')
				->with('Field')
				->filter('userGroupId', Session::user()->group_id)
				->filter('sectionId', Request::param('channel_id'))
				->order('order', 'ASC')
				->all();
		}

		/**
		 * Make an array of related "Field" data.
		 * We'll add this to the Display Settings array later.
		 * Doing this because I can't set a property on a model that isn't
		 * explicitly listed in the Model's description file.
		 * (eg. Can't do $model->foo = 'bar' if I didn't list "foo" in the model's file)
		 *
		 * *Sigh* This is so much simpler in Laravel (or just Eloquent).
		 */
		$ds_with_field_data = [];

		foreach($display_settings as $ds)
		{
			if($ds->Field)
			{
				$ds_with_field_data[$ds->id] = $ds->Field->getValues();
			}
		}

		$display_settings = $display_settings->getValues();

		/**
		 * Mix the related "Field" data with the Display Settings
		 */
		if($display_settings)
		{
			$display_settings = array_map(function ($ds) use ($ds_with_field_data) {
				if(array_key_exists($ds['id'], $ds_with_field_data))
				{
					return array_merge($ds, $ds_with_field_data[$ds['id']]);
				}

				return $ds;
			}, $display_settings);
		}

		$field_helper = new Field($custom_fields, $display_settings);

		foreach($query as $e)
		{
			$entry_values = $e->getValues();
			$raw_results[] = $entry_values;
			$results[] = array_merge($entry_values, $field_helper->processFieldData($e));
		}

		$output = [
			'raw_data'         => $raw_results,
			'data'             => $results,
			'pagination_data'  => Pagination::getPagination($total_results, $filters['limit']),
			'display_settings' => $display_settings,
		];

		$output = Hook::call('zenbu_modify_results', $output);

		return $output;
	}

	public function fetch_fields()
	{
		$out = [];
		if(Request::param('channel_id'))
		{
			$channels = ee('Model')->get('Channel')->filter('channel_id', Request::param('channel_id', null))->all();
		}
		else
		{
			$channels = ee('Model')->get('Channel')->all();
		}

		foreach($channels as $channel)
		{
			$channel->FieldGroups->each(function ($group) use (&$out) {
				$group->ChannelFields->each(function ($field) use (&$out) {
					$out[] = $field->getValues();
				});
			});
		}

		return $out;
	} // END fetch_fields()

	public function fetch_statuses()
	{
		$out = [];
		if(Request::param('channel_id'))
		{
			$channels = ee('Model')->get('Channel')->with('Statuses')->filter('channel_id', Request::param('channel_id', null))->all();
		}
		else
		{
			$channels = ee('Model')->get('Channel')->with('Statuses')->all();
		}

		foreach($channels as $channel)
		{
			$channel->Statuses->each(function ($status) use (&$out) {
				$status_values = $status->getValues();
				$out[$status_values['status_id']] = $status_values;
			});
		}

		return $out;
	} // END fetch_statuses

	public function fetch_authors()
	{
		$out = [];

		$members = ee('Model')->get('Member')->filter('total_entries', '>', 0)->order('screen_name', 'ASC')->all();

		foreach($members as $member)
		{
			$out[] = $member->getValues();
		}

		return $out;
	} // END fetch_authors

	public function fetch_categories()
	{
		$out = [];
		if(Request::param('channel_id'))
		{
			$channels = ee('Model')->get('Channel')->filter('channel_id', Request::param('channel_id', null))->all();
		}
		else
		{
			$channels = ee('Model')->get('Channel')->all();
		}

		foreach($channels as $channel)
		{
			$cat_groups = $channel->getCategoryGroups();

			$cat_groups->each(function ($group) use (&$out) {
				$out[] = [
					'group_id'   => $group->group_id,
					'group_name' => $group->group_name,
					'tree'       => $group->buildCategoryOptionsTree(),
				];
			});
		}

		return $out;
	} // END fetch_categories

	public function forget_filters()
	{
		Cache::delete('filters');
		Cache::delete('page');

		return ['success' => true, 'message' => 'Memorized Filters deleted'];
	}

	public function clear_zenbu_cache()
	{
		ee()->cache->delete('/zenbu/');

		return 'Zenbu caches deleted';
	}
}
