<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\Session;
use Zenbu\librairies\platform\ee\View;

class Relationship {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			$raw = $e->{'field_id_'.$f['field_id']};

			// Get the raw children entry_ids for this field

			$current_field_children_entry_ids = [];

			$wheres = [
				'parent_id'     => $e->entry_id,
				'field_id'      => $f['field_id'],
			];

			$rows = ee()->db
				->select('child_id, order')
				->from('relationships')
				->where($wheres)
				->order_by('order', 'asc')
				->get();

			if($rows->num_rows() > 0)
			{
				foreach($rows->result() as $row)
				{
					array_push($current_field_children_entry_ids, $row->child_id);
				}
			}

			// Get all children for this entry

			$all_children = $e->Children->getValues();

			// Filter to get children for this entry AND this field

			$current_field_children = $all_children ? array_filter($all_children, function($entry) use ($current_field_children_entry_ids) {
				return in_array($entry['entry_id'], $current_field_children_entry_ids);
			}) : [];
			
			//	----------------------------------------
			//	Display Settings for this field
			//	----------------------------------------

			if(Session::getCache('display_settings_for_field_id_'.$f['field_id']) !== false)
			{
				$setting_row = Session::getCache('display_settings_for_field_id_'.$f['field_id']);
			}
			else
			{
				$setting_row = isset($display_settings) ? array_values(array_filter($display_settings, function($ds) use ($f) {
					return $ds['fieldId'] == $f['field_id'];
				})) : null;

				Session::setCache('display_settings_for_field_id_'.$f['field_id'], $setting_row);
			}

			$data['field_id_'.$f['field_id']] = View::render('columns/fieldtypes/relationship.twig', ['children' => $current_field_children, 'settings' => ($setting_row ? $setting_row[0]['settings'] : [])]);
			$data['raw_field_id_'.$f['field_id']] = $raw;
		}

		return $data;
	}
}