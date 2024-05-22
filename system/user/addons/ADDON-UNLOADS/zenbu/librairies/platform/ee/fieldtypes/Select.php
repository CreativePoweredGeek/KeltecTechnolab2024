<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\View;

class Select {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			if(is_string($e))
			{
				$raw = $e;
			}
			else
			{
				$raw = $e->{'field_id_'.$f['field_id']};
			}

			if(isset($f['field_settings'], $f['field_settings']['value_label_pairs']))
			{
				$field_settings = $f['field_settings']['value_label_pairs'];
				$data['field_id_'.$f['field_id']] = isset($field_settings[$raw]) ? $field_settings[$raw] : $raw;
				$data['raw_field_id_'.$f['field_id']] = $raw;
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