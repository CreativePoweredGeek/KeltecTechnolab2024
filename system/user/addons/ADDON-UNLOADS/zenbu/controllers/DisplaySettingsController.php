<?php
namespace Zenbu\controllers;

use Zenbu\librairies\platform\ee\Request;
use Zenbu\librairies\platform\ee\Field;
use Zenbu\librairies\platform\ee\Session;

class DisplaySettingsController
{
	var $permissions;
	var $filter_settings;

	public function __construct()
	{
	}


	/**
	 * Display Settings
	 * @return string Rendered template
	 */
    public function fetch_display_settings()
    {
    	$base_fields = Field::getBaseFields();

		if(Request::param('channel_id') != 0)
		{
			$custom_fields = Field::fetch_fields(Request::param('channel_id'));
		}
		else
		{
			$custom_fields = Field::fetch_fields(null);
		}


		$all_fields = array_merge($base_fields, $custom_fields);

		// Add the setting form elements that go with some
		// of the default/custom fields.

		$output['fields'] = Field::addFieldSettings($all_fields);

		$output['selected_fields'] = [
			[
				'field_id' => 'entry_id', 'field_name' => 'entry_id', 'field_label' => 'ID', 'setting_fields' => [
					['field_type' => 'input', 'name' => 'text_limit', 'label' => 'Limit text', 'default' => '50', 'placeholder' => 'Value goes here'],
					['field_type' => 'select', 'name' => 'yes', 'label' => 'Yes or no', 'default' => 'n', 'options' => ['y' => 'Yes', 'n' => 'No']],
					['field_type' => 'checkbox', 'name' => 'like', 'label' => 'Do you like?', 'value' => 'y', 'checked' => true],
					['field_type' => 'checkbox', 'name' => 'like2', 'label' => 'Do you like?', 'value' => 'y'],
				]
			]
		];

		/**
		 * Load the saved Display Settings
		 */
		$display_settings = ee('Model')->get('zenbu:DisplaySetting')
			->with('Field')
			->filter('userId', Session::user()->member_id)
			->filter('sectionId', Request::param('channel_id'))
			->order('order', 'ASC')
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

		$selected_fields = [];

		foreach($display_settings as $ds)
		{
			$ds_array = $ds->getValues();

			if($ds->fieldType != 'field')
			{
				$base_field = array_filter($all_fields, function($f) use ($ds) {
					return $ds->fieldType == $f['field_id'];
				});

				$base_field = Field::addFieldSettings($base_field);
				$base_field = Field::addSavedValues($ds, array_shift($base_field));

				$selected_fields[] = $ds_array + ($base_field ?: []);

			}
			else
			{
				$field = array_filter($all_fields, function($f) use ($ds) {
					return $ds->fieldId == $f['field_id'];
				});

				if($field)
				{
					$field = Field::addFieldSettings($field);
					$field = Field::addSavedValues($ds, array_shift($field));
					$selected_fields[] = $ds_array + $field;
				}
			}
		};

		$output['selected_fields'] = $selected_fields;

		/**
		 * Load the Filter Settings
		 */
		$filter_settings = ee('Model')->get('zenbu:FilterSetting')
			->filter('userId', Session::user()->member_id)
			->filter('sectionId', Request::param('channel_id'))
			->all();

		//	----------------------------------------
		//	Fallback if no results are found for Display Settings:
		//  We try to look for any member group-level Display Settings.
		//	----------------------------------------

		if($filter_settings->count() === 0)
		{
			$filter_settings = ee('Model')->get('zenbu:FilterSetting')
				->filter('userGroupId', Session::user()->group_id)
				->filter('sectionId', Request::param('channel_id'))
				->all();
		}

		$output['filter_settings'] = [];

		foreach($filter_settings as $gs)
		{
			$output['filter_settings'][$gs->setting] = $gs->value;
		}

		return $output;

    } // END fetch_display_settings()

    // --------------------------------------------------------------------

	public function save_display_settings_for_group()
	{
		return $this->save_display_settings(Request::param('member_group_id'));
	}

	public function save_display_settings_for_user()
	{
		return $this->save_display_settings();
	}

	protected function save_display_settings($member_group_id = null)
	{
		$affected_row_ids = [];

		if($member_group_id)
		{
			$userColumn = 'userGroupId';
			$userData = $member_group_id;
		}
		else
		{
			$userColumn = 'userId';
			$userData = Session::user()->member_id;
		}

		if(Request::param('data'))
		{
			foreach(Request::param('data') as $order => $data)
			{
				// Get the field's settings

				$settings_data = null;

				if(isset($data['setting_fields']))
				{
					foreach($data['setting_fields'] as $item)
					{
						$settings_data[$item['name']] = isset($item['value']) && $item['value'] ? $item['value'] : null;
					}
				}

				$field_type = is_numeric($data['field_id']) ? 'field' : $data['field_id'];

				$field_id = is_numeric($data['field_id']) ? $data['field_id'] : null;

				$setting = ee('Model')->get('zenbu:DisplaySetting')
					->filter($userColumn, $userData)
					->filter('sectionId', Request::param('channel_id'))
					->filter('fieldType', $field_type)
					->filter('fieldId', $field_id)
					->first();

				if(! $setting)
				{
					// No previous row found. Create a new row.

					$setting = ee('Model')->make('zenbu:DisplaySetting');
					$setting->{$userColumn} = $userData;
					$setting->sectionId = Request::param('channel_id');
					$setting->fieldType = $field_type;
					$setting->fieldId = $field_id;
				}

				// The if statement is to not include $setting->settings
				// during save if there's no setting data.
				// Should default to NULL in that case.

				if($settings_data)
				{
					$setting->settings = $settings_data;
				}

				$setting->order= $order;
				$setting->save();

				$affected_row_ids[] = $setting->getId();
			}
		}

		// Delete other rows that shouldn't be there anymore

		if(empty($affected_row_ids))
		{
			ee('Model')->get('zenbu:DisplaySetting')
				->filter($userColumn, $userData)
				->filter('sectionId', Request::param('channel_id'))
				->delete();
		}
		else
		{
			ee('Model')->get('zenbu:DisplaySetting')
				->filter($userColumn, $userData)
				->filter('sectionId', Request::param('channel_id'))
				->filter('id', 'NOT IN', $affected_row_ids)
				->delete();
		}
		
		//	----------------------------------------
		//	Save Filter Settings
		//	----------------------------------------

		foreach(['starting_limit', 'starting_order_by', 'starting_sort'] as $setting_name)
		{
			if(Request::param('filter_settings') && isset(Request::param('filter_settings')[$setting_name]))
			{
				$g_setting = ee('Model')->get('zenbu:FilterSetting')
					->filter($userColumn, $userData)
					->filter('sectionId', Request::param('channel_id'))
					->filter('setting', $setting_name)
					->all();

				$default_limit_setting_row = $g_setting->filter(function($gs) use ($setting_name) {
					return $gs->setting == $setting_name;
				});

				if($default_limit_setting_row->count() > 1)
				{
					// More than one of the same setting found.
					// This isn't right. Clear and create a new setting.

					ee('Model')->get('zenbu:FilterSetting')
						->filter($userColumn, $userData)
						->filter('sectionId', Request::param('channel_id'))
						->filter('setting', $setting_name)
						->delete();

					$g_setting                = ee('Model')->make('zenbu:FilterSetting');
					$g_setting->{$userColumn} = $userData;
					$g_setting->sectionId     = Request::param('channel_id');
					$g_setting->setting       = $setting_name;
					$g_setting->value         = Request::param('filter_settings')[$setting_name];
					$g_setting->save();
				}
				else if($default_limit_setting_row->count() === 1)
				{
					// There's a previous row about this setting. Update it.
					$default_limit_setting_row->value = Request::param('filter_settings')[$setting_name];
					$default_limit_setting_row->save();
				}
				else
				{
					// Create the setting

					$g_setting                = ee('Model')->make('zenbu:FilterSetting');
					$g_setting->{$userColumn} = $userData;
					$g_setting->sectionId     = Request::param('channel_id');
					$g_setting->setting       = $setting_name;
					$g_setting->value         = Request::param('filter_settings')[$setting_name];
					$g_setting->save();
				}

				$affected_row_ids[] = $g_setting->getId();
			}
		}

		return ['success' => true, 'message' => 'Display Settings saving complete', 'data' => Request::param('data'), 'channel_id' => Request::param('channel_id'), 'saved_in_column' => $userColumn, 'saved_user_or_group_id' => $userData];

	} // END save_display_settings()
}
