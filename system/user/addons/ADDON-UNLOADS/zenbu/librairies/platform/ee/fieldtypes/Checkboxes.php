<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\View;

class Checkboxes {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			if(is_string($e))
			{
				$raw = explode('|', $e);
			}
			else
			{
				$raw = explode('|', $e->{'field_id_'.$f['field_id']});
			}

			if(isset($f['field_settings'], $f['field_settings']['value_label_pairs']))
			{
				$field_settings = $f['field_settings']['value_label_pairs'];

				$values = [];

				foreach($raw as $selected_item)
				{
					$values[] = isset($field_settings[$selected_item]) ? $field_settings[$selected_item] : $selected_item;
					$data['raw_field_id_'.$f['field_id']] = $raw;
				}

				$data['field_id_'.$f['field_id']] = implode(', ', $values);
			}
			else
			{
				$data['field_id_'.$f['field_id']] = $raw;
				$data['raw_field_id_'.$f['field_id']] = $raw;
			}
		}

		return $data;
	}
}