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
use Zenbu\librairies\platform\ee\File as FileHelper;

class File {

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

			if(Session::getCache('display_settings_for_field_id_' . $f['field_id']) !== false)
			{
				$setting_row = Session::getCache('display_settings_for_field_id_' . $f['field_id']);
			}
			else
			{
				$setting_row = isset($display_settings) ? array_values(array_filter($display_settings, function ($ds) use ($f) {
					return $ds['fieldId'] == $f['field_id'];
				})) : null;

				Session::setCache('display_settings_for_field_id_' . $f['field_id'], $setting_row);
			}

			$setting = isset($setting_row[0], $setting_row[0]['settings']) ? $setting_row[0]['settings'] : [];

			$dimension_name = 'thumbs';

			if(isset($setting['dimension_id']))
			{
				$file_dimension = FileHelper::getDimensionById($setting['dimension_id']);
				if($file_dimension)
				{
					$dimension_name = $file_dimension['short_name'];
				}
			}

			$file                                   = FileHelper::getFileModel($content);
			$filepath                               = FileHelper::get_thumb_path($content, $dimension_name);
			$data['field_id_' . $f['field_id']]     = View::render('columns/fieldtypes/file.twig', [
				'filepath' => $filepath,
				'is_image' => $file ? $file->isImage() : false,
				'file'     => $file,
				'entry'    => $e,
				'field_id' => $f['field_id'],
			]);
			$data['raw_field_id_' . $f['field_id']] = $content;
		}

		return $data;
	}

}