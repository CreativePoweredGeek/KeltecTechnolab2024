<?php
/**
 * Created by PhpStorm.
 * User: nicolasbottari
 * Date: 2019-03-13
 * Time: 15:20
 */

namespace Zenbu\librairies\platform\ee\fieldtypes;

use Zenbu\librairies\platform\ee\Field;
use Zenbu\librairies\platform\ee\Lang;
use Zenbu\librairies\platform\ee\Session;
use Zenbu\librairies\platform\ee\Url;
use Zenbu\librairies\platform\ee\View;

class Fluid {

	public static function processData($e, $target_fields, $display_settings)
	{
		$data = [];

		foreach($target_fields as $f)
		{
			$fluid_data = ee('Model')->get('fluid_field:FluidField')
				->filter('fluid_field_id', $f['field_id'])
				->filter('entry_id', $e->entry_id)
				->order('order', 'ASC')
				->all();

			$rows = $fluid_data;

			$row_data = [];

			foreach($rows as $row)
			{
				$row_data_model = $row->getFieldData();
				$field_data = $row_data_model->getValues();
				$field_content = $row->getField($row_data_model)->getData();
				$field_type = $row->getField($row_data_model)->getType();
				$field_id = $row->getField($row_data_model)->getId();

				$row_target_fields = [
					[
						'field_id' => $field_id,
						'field_type' => $field_type,
						'field_settings' => ee('Model')->get('ChannelField')->filter('field_id', $field_id)->first()->field_settings
					]
				];

				if(in_array($field_type, ['text', 'textarea']))
				{
					$field_content = Text::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['file']))
				{
					$field_content = File::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['select']))
				{
					$field_content = Select::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['checkboxes']))
				{
					$field_content = Checkboxes::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['date']))
				{
					$field_content = Date::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['toggle']))
				{
					$field_content = Toggle::processData($field_content, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['relationship']))
				{
					$field_content = Relationship::processData($e, $row_target_fields, $display_settings)['field_id_'.$field_id];
				}

				if(in_array($field_type, ['grid']))
				{
					$field_content = '<a href="' . Url::cpEditEntryUrl($e) . '">' . Lang::t('open_entry_to_view_grid') . '</a>'; // Getting a little crazy here.
				}


				$row_data[] = [
					'field_data' => $field_data,
					'content' => $field_content,
					'field_type' => $field_type
				];
			}

			$data['field_id_'.$f['field_id']] = View::render('columns/fieldtypes/fluid.twig', ['rows' => $row_data]);
			$data['raw_field_id_'.$f['field_id']] = $e->{'field_id_'.$f['field_id']};
		}


		return $data;
	}
}