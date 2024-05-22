<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\Session;

class Text {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{

			if(is_string($e) && ! empty($e))
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

			$setting = isset($setting_row[0], $setting_row[0]['settings']) ? $setting_row[0]['settings'] : [];

			$text = $original_text = $content;

			if(isset($setting['display_style']))
			{
				if($setting['display_style'] == 'plain')
				{
					$text = strip_tags($text);
				}
			}

			if(isset($setting['limit_text']) && ! empty($setting['limit_text']))
			{
				$text_length = strlen($text);
				$text = substr($text, 0, (int) $setting['limit_text']);

				if($text_length > $setting['limit_text'])
				{
					$text .= '...';
				}
			}

			$data['field_id_'.$f['field_id']] = $text;
			$data['raw_field_id_'.$f['field_id']] = $original_text;
		}

		return $data;
	}
}