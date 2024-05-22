<?php namespace Zenbu\librairies\platform\ee;

use Zenbu\librairies\platform\ee\ArrayHelper;
use Zenbu\librairies\platform\ee\fieldtypes;

class Field
{
	public $custom_fields;
	public $display_settings;

	public function __construct($custom_fields = null, $display_settings = null)
	{
		$this->custom_fields = $custom_fields;
		$this->display_settings = $display_settings;
	}


	/**
     * Process fields for display in Zenbu
     *
     * @param object $e The entry
     * @return array
     */
    public function processFieldData($e)
    {
        $data = [];

        $data['raw_channel'] = $e->Channel ? $e->Channel->getValues() : null;
        $data['raw_entry_date'] = $e->entry_date;
		$data['raw_edit_date'] = $e->edit_date;
        $data['raw_expiration_date'] = $e->expiration_date;

        //	----------------------------------------
        //	Entry Date
        //	----------------------------------------

		$date_settings = isset($this->display_settings) ? array_values(array_filter($this->display_settings, function($ds) {
			return $ds['fieldType'] == 'entry_date';
		})) : null;

		if(! $e->entry_date)
		{
			$data['entry_date'] = '';
		}
		else if(isset($date_settings[0], $date_settings[0]['settings'], $date_settings[0]['settings']['date_format']))
		{
        	$data['entry_date'] = Localize::format($date_settings[0]['settings']['date_format'], $e->entry_date);
		}
		else
		{
			$data['entry_date'] = Localize::human($e->entry_date);
		}

		//	----------------------------------------
		//	Edit Date
		//	----------------------------------------

		$date_settings = isset($this->display_settings) ? array_values(array_filter($this->display_settings, function($ds) {
			return $ds['fieldType'] == 'edit_date';
		})) : null;

		if(! $e->edit_date)
		{
			$data['edit_date'] = '';
		}
		else if(isset($date_settings[0], $date_settings[0]['settings'], $date_settings[0]['settings']['date_format']))
		{
			$data['edit_date'] = Localize::format($date_settings[0]['settings']['date_format'], $e->edit_date->format('U'));
		}
		else
		{
			$data['edit_date'] = Localize::human($e->edit_date->format('U'));
		}

		//	----------------------------------------
		//	Expiration Date
		//	----------------------------------------

		$date_settings = isset($this->display_settings) ? array_values(array_filter($this->display_settings, function($ds) {
			return $ds['fieldType'] == 'expiration_date';
		})) : null;

		if($e->expiration_date == 0)
		{
			$data['expiration_date'] = '';
		}
		else if(isset($date_settings[0], $date_settings[0]['settings'], $date_settings[0]['settings']['date_format']))
		{
			$data['expiration_date'] = Localize::format($date_settings[0]['settings']['date_format'], $e->expiration_date);
		}
		else
		{
			$data['expiration_date'] = Localize::human($e->expiration_date);
		}

		$data['title']          = '<a href="' . Url::cpEditEntryUrl($e) . '">' . $e->title . '</a>';
		$data['title_raw']      = $e->title;
		$data['preview']        = $e->Channel && $e->Channel->preview_url ? '<a href="' . Url::cpEditEntryUrl($e) . '&preview=y"><i class="fa fa-eye"></i></a>' : '<i class="fa fa-eye-slash" title="' . Lang::t('preview_not_enabled') . '"></i>';
		$data['categories']     = View::render('columns/standard/category.twig', ['categories' => $e->Categories->getValues()]);
		$data['categories_raw'] = $e->Categories->getValues();
		$data['channel_id']     = View::render('columns/standard/channel.twig', ['channel' => $e->Channel]);
		$data['status_id']      = View::render('columns/standard/status.twig', ['status' => Status::getStatusById($e->status_id)]);
		// $data['author_id']      = $e->Author->screen_name;
		
		//	----------------------------------------
		//	Author
		//	----------------------------------------
		
		$author_settings = isset($this->display_settings) ? array_values(array_filter($this->display_settings, function($ds) {
			return $ds['fieldType'] == 'author_id';
		})) : null;

		$author_settings = isset($author_settings[0], $author_settings[0]['settings']) ? $author_settings[0]['settings'] : null;

		$data['author_id'] = View::render('columns/standard/author.twig', ['author' => $e->Author, 'settings' => $author_settings]);
		
		//	----------------------------------------
		//	Sticky
		//	----------------------------------------
		
		$sticky_settings = isset($this->display_settings) ? array_values(array_filter($this->display_settings, function($ds) {
			return $ds['fieldType'] == 'sticky';
		})) : null;

		$sticky_settings = isset($sticky_settings[0], $sticky_settings[0]['settings']) ? $sticky_settings[0]['settings'] : null;

		$data['sticky'] = View::render('columns/standard/sticky.twig', ['value' => $e->sticky, 'settings' => $sticky_settings]);

        //    ----------------------------------------
        //    Grid fields
        //    ----------------------------------------

		$data = $data + $this->processDataForFieldtype(['grid', 'file_grid'], $e);

        //    ----------------------------------------
        //    File fields
        //    ----------------------------------------

		$data = $data + $this->processDataForFieldtype('file', $e);

		//	----------------------------------------
		//	Date fields
		//	----------------------------------------

		$data = $data + $this->processDataForFieldtype('date', $e);

		//    ----------------------------------------
		//    Input & Textarea fields
		//    ----------------------------------------

		$data = $data + $this->processDataForFieldtype(['text', 'textarea'], $e);

		//    ----------------------------------------
		//    Toggle fields
		//    ----------------------------------------

		$data = $data + $this->processDataForFieldtype('toggle', $e);

		//    ----------------------------------------
		//    Select fields
		//    ----------------------------------------

		$data = $data + $this->processDataForFieldtype('select', $e);

		//    ----------------------------------------
		//    Checkboxes fields
		//    ----------------------------------------

		$data = $data + $this->processDataForFieldtype('checkboxes', $e);

		//	----------------------------------------
		//	Relationship fields
		//	----------------------------------------

		$data = $data + $this->processDataForFieldtype('relationship', $e);

		//	----------------------------------------
		//	Fluid fields
		//	----------------------------------------

		$data = $data + $this->processDataForFieldtype('fluid_field', $e);

        // var_dump($customFields);

        return $data;
    } // END processFieldData()

    // --------------------------------------------------------------------


	/**
	 * Add Settings for fields (Display Settings)
	 *
	 * @param $fields
	 *
	 * @return array $fields    An array of field data
	 */
	static public function addFieldSettings($fields)
	{
		// If a single field, make it an iterable array.

		if(isset($fields['field_id']))
		{
			$fields = [$fields];
		}

		$fields = array_map(function($f) {

			if(isset($f['field_type']) && in_array($f['field_type'], ['text', 'textarea']))
			{
				return $f + [
						'setting_fields' => [
							['field_type' => 'input', 'name' => 'limit_text', 'label' => Lang::t('limit_text'), 'default' => '50', 'placeholder' => 'eg. 50'],
							['field_type' => 'select', 'name' => 'display_style', 'label' => Lang::t('display_style'), 'default' => 'plain', 'value' => 'plain', 'options' => ['plain' => Lang::t('plain_text'), 'html' => Lang::t('html')]],
						]
					];
			}

			if(isset($f['field_type']) && in_array($f['field_type'], ['file']))
			{
				return $f + [
						'setting_fields' => [
							[
								'field_type' => 'select',
								'name'       => 'dimension_id',
								'label'      => Lang::t('use_dimension'),
								'default'    => 'default',
								'value'      => 'default',
								'options'    => [
										'default' => Lang::t('base_thumb')] + File::getAllDimensions()->filter(function($dimension) use ($f) {
											if(isset($f['field_settings'], $f['field_settings']['allowed_directories']))
											{
												return $dimension->upload_location_id == $f['field_settings']['allowed_directories'] || $f['field_settings']['allowed_directories'] == 'all';
											}

											return false;
									})->getDictionary('id', 'short_name'),
							],
						],
					];
			}

			if(in_array($f['field_id'], ['entry_date', 'expiration_date', 'edit_date']) || (isset($f['field_type']) && in_array($f['field_type'], ['date'])))
			{
				return $f + [
						'setting_fields' => [
							['field_type' => 'input', 'name' => 'date_format', 'label' => Lang::t('date_format'), 'default' => '', 'placeholder' => 'eg. %Y-%m-%d'],
						]
					];
			}

			if(in_array($f['field_id'], ['sticky']) || isset($f['field_type']) && in_array($f['field_type'], ['toggle']))
			{
				return $f + [
						'setting_fields' => [
							['field_type' => 'checkbox', 'name' => 'use_colored_labels', 'label' => Lang::t('use_colored_labels')],
							['field_type' => 'select', 'name' => 'display_style', 'label' => Lang::t('display_style'), 'default' => 'on_off', 'value' => 'on_off', 'options' => ['on_off' => Lang::t('on_off'), 'yes_no' => Lang::t('yes_no')]],
						]
					];
			}

			if(isset($f['field_type']) && in_array($f['field_type'], ['relationship']))
			{
				return $f + [
						'setting_fields' => [
							['field_type' => 'checkbox', 'name' => 'show_id', 'label' => Lang::t('show_entry_id')],
						]
					];
			}

			if(in_array($f['field_id'], ['author_id']))
			{
				return $f + [
						'setting_fields' => [
							['field_type' => 'checkbox', 'name' => 'link_to_profile', 'label' => Lang::t('link_to_profile')],
							['field_type' => 'select', 'name' => 'display_style', 'label' => Lang::t('display_style'), 'default' => 'screen_name', 'value' => 'screen_name', 'options' => ['screen_name' => Lang::t('screen_name'), 'username' => Lang::t('username'), 'member_id' => Lang::t('member_id')]],
						]
					];
			}

			return $f;
		}, $fields);

		return $fields;
	} // --------------------------------------------------------------------

	static public function addSavedValues($display_settings, $field)
	{
		if(isset($field['setting_fields']))
		{
			foreach($display_settings->settings as $key => $val)
			{
				foreach($field['setting_fields'] as $k => $s)
				{
					if($s['name'] == $key)
					{
						if($s['field_type'] == 'checkbox')
						{
							$field['setting_fields'][$k]['value'] = filter_var($val, FILTER_VALIDATE_BOOLEAN);
						}
						else
						{
							$field['setting_fields'][$k]['value'] = $val;
						}
					}
				}
			}
		}

		return $field;
	}

    static public function fetch_fields($channel_id = null)
	{
		$out = [];
		$field_ids = [];

		if($channel_id)
		{
			$channels = ee('Model')->get('Channel')->filter('channel_id', $channel_id)->all();
		}
		else
		{
			$channels = ee('Model')->get('Channel')->all();
		}

		foreach($channels as $channel)
		{
			$channel->FieldGroups->each(function($group) use (&$out, &$field_ids) {
				$group->ChannelFields->each(function($field) use (&$out, &$field_ids) {
					if(! in_array($field->field_id, $field_ids)) // We want unique items in $out
					{
						$field_ids[] = $field->field_id;
						$out[] = $field->getValues();
					}
				});
			});

			$channel->CustomFields->each(function($field) use (&$out, &$field_ids) {
				if(! in_array($field->field_id, $field_ids)) // We want unique items in $out
				{
					$field_ids[] = $field->field_id;
					$out[] = $field->getValues();
				}
			});
		}

		return $out;
    } // END fetch_fields()

    // --------------------------------------------------------------------


	/**
	 * Handle special ordering cases
	 *
	 * @param [type] $filters
	 * @return void
	 */
	static public function processFieldQuery($query, $custom_fields, $row)
	{
		$field = ArrayHelper::array_find('field_id', $row[0], $custom_fields);

		if(in_array($field['field_type'], ['relationship']))
		{
			if(! in_array($row[1], ['isEmpty', 'isNotEmpty']) && empty($row[2]))
			{
				return $query;
			}

			if($row[1] == 'isEmpty' || $row[1] == 'isNotEmpty')
			{
				// Get all entries that have child entries.
				// Looking for Children with entry_id of 0
				// should return all Parent entries (that have Children).
				$parent_entry_ids = ee('Model')->get('ChannelEntry')->with('Children')->filter('Children.entry_id', '!=', 0)->all()->pluck('entry_id');
				// Add a 0 for when $parent_entry_ids is empty
				$parent_entry_ids[] = 0;
				$in_not_in = $row[1] == 'isEmpty' ? 'NOT IN' : 'IN';
				return $query->filter('entry_id', $in_not_in, array_unique($parent_entry_ids));
			}
			else
			{
				$parent_entry_ids = ee('Model')->get('ChannelEntry')->with('Children')->filter('Children.title', 'LIKE', Query::whereValue($row))->all()->pluck('entry_id');
				// Add a 0 for when $parent_entry_ids is empty
				$parent_entry_ids[] = 0;
				$in_not_in = $row[1] == 'doesntContain' ? 'NOT IN' : 'IN';
				return $query->filter('entry_id', $in_not_in, array_unique($parent_entry_ids));
			}
		}

		if(in_array($field['field_type'], ['date']))
		{
			if($row[1] == 'betweenDates')
			{
				$dates = explode(' ~ ', $row[2]);

				if(isset($dates[0]) && ! empty($dates[0]))
				{
					$query = $query->filter('field_id_'.$row[0], '>=', Localize::format('%U', $dates[0] . ' 00:00:00'));
				}

				if(isset($dates[1]))
				{
					$query = $query->filter('field_id_'.$row[0], '<=', Localize::format('%U', $dates[1]) . ' 23:59:59');
				}
			}
			else
			{
				$query = $query->filter('field_id_'.$row[0], Query::whereDateOperator($row), Query::whereDateValue($row));
				$query = $query->filter('field_id_'.$row[0], Query::whereDateOperator($row) == '<=' ? '>=' : '<=', Localize::now());
			}

			return $query;
		}

		// The fallback is a simple direct search within field_id_X
		// Should cover textarea, text, url, email_address, rte, toggle, and other simple fields

		return $query->filter('field_id_'.$row[0], Query::whereOperator($row), Query::whereValue($row));

	} // processFieldQuery()

	// --------------------------------------------------------------------


	/**
     * Handle special ordering cases
     *
     * @param [type] $filters
     * @return void
     */
    static public function processOrderBy($filters)
    {
    	// TODO: Try to find a way to order by category.
        if($filters['order_by'] == 'categories')
        {
            $filters['order_by'] = 'Categories.cat_id';
        }

		if($filters['order_by'] == 'preview')
		{
			$filters['order_by'] = 'entry_id';
		}

		if(is_numeric($filters['order_by']))
		{
			$filters['order_by'] = 'field_id_'.$filters['order_by'];
		}

        return $filters['order_by'];
    } // processOrderBy()

    // --------------------------------------------------------------------

	static public function getBaseFields()
	{
		return [
			['field_id' => 'entry_id', 'field_name' => 'entry_id', 'field_label' => 'ID'],
			['field_id' => 'title', 'field_name' => 'title', 'field_label' => Lang::t('title')],
			['field_id' => 'url_title', 'field_name' => 'url_title', 'field_label' => Lang::t('url_title')],
			['field_id' => 'status_id', 'field_name' => 'status_id', 'field_label' => Lang::t('status')],
			['field_id' => 'author_id', 'field_name' => 'author_id', 'field_label' => Lang::t('author')],
			['field_id' => 'categories', 'field_name' => 'categories', 'field_label' => Lang::t('categories')],
			['field_id' => 'channel_id', 'field_name' => 'channel_id', 'field_label' => Lang::t('channel')],
			['field_id' => 'entry_date', 'field_name' => 'entry_date', 'field_label' => Lang::t('entry_date')],
			['field_id' => 'edit_date', 'field_name' => 'edit_date', 'field_label' => Lang::t('edit_date')],
			['field_id' => 'expiration_date', 'field_name' => 'expiration_date', 'field_label' => Lang::t('expiration_date')],
			['field_id' => 'preview', 'field_name' => 'preview', 'field_label' => Lang::t('preview')],
			['field_id' => 'sticky', 'field_name' => 'sticky', 'field_label' => Lang::t('sticky')],
		];
	}

	public function processDataForFieldtype($fieldtypes, $e)
	{
		if(is_string($fieldtypes))
		{
			$fieldtypes = [$fieldtypes];
		}

		$fieldtype_rep = array_values(array_slice($fieldtypes, 0, 1))[0];

		if(Session::getCache('target_fields_'.$fieldtype_rep) !== false)
		{
			$target_fields = Session::getCache('target_fields_'.$fieldtype_rep);
		}
		else
		{
			$target_fields = array_filter($this->custom_fields, function($field) use ($fieldtypes) {
				return in_array($field['field_type'], $fieldtypes);
			});

			Session::setCache('target_fields_'.$fieldtype_rep, $target_fields);
		}


		$fieldtype_class = '\\'.ucfirst(reset($fieldtypes));

		if(in_array('grid', $fieldtypes) || in_array('file_grid', $fieldtypes))
		{
			return fieldtypes\Grid::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('file', $fieldtypes))
		{
			return fieldtypes\File::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('date', $fieldtypes))
		{
			return fieldtypes\Date::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('text', $fieldtypes))
		{
			return fieldtypes\Text::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('toggle', $fieldtypes))
		{
			return fieldtypes\Toggle::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('select', $fieldtypes))
		{
			return fieldtypes\Select::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('checkboxes', $fieldtypes))
		{
			return fieldtypes\Checkboxes::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('relationship', $fieldtypes))
		{
			return fieldtypes\Relationship::processData($e, $target_fields, $this->display_settings);
		}

		if(in_array('fluid_field', $fieldtypes))
		{
			return fieldtypes\Fluid::processData($e, $target_fields, $this->display_settings);
		}

		return [];
	}
}