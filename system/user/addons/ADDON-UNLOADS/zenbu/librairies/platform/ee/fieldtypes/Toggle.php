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

class Toggle {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			if(is_string($e))
			{
				$content = $e;
			}
			else
			{
				$content = $e->{'field_id_'.$f['field_id']};
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

			$data['field_id_'.$f['field_id']] = $setting_row ? View::render('columns/fieldtypes/toggle.twig', ['value' => $content, 'settings' => $setting_row[0]['settings'] ]) : $content;
			$data['raw_field_id_'.$f['field_id']] = $content;
		}

		return $data;
	}
}