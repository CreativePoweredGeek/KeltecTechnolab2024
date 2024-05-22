<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;


use Zenbu\librairies\platform\ee\Localize;
use Zenbu\librairies\platform\ee\Session;

class Date {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			if(is_string($e) || is_numeric($e))
			{
				$raw = $e;
			}
			else
			{
				$raw = $e->{'field_id_'.$f['field_id']};
			}

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

			if(empty($raw))
			{
				$data['field_id_'.$f['field_id']] = '';
			}
			else if(isset($setting_row[0], $setting_row[0]['settings'], $setting_row[0]['settings']['date_format']))
			{
				$data['field_id_'.$f['field_id']] = Localize::format($setting_row[0]['settings']['date_format'], $raw);
			}
			else
			{
				$data['field_id_'.$f['field_id']] = Localize::human($raw);
			}

			$data['raw_field_id_'.$f['field_id']] = $raw;
		}

		return $data;
	}
}