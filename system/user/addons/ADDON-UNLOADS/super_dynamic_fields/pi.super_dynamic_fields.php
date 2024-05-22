<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Super_dynamic_fields
{
	
	function __construct()
	{
		ee()->load->library('super_dynamic_fields_lib', null, 'superDynamicFields');
		ee()->load->library('Super_dynamic_fields_parsing', null, 'superDynamicFieldsParse');
		ee()->lang->loadfile('super_dynamic_fields');
	}

	function parse()
	{
		
		$prefix = ee()->TMPL->fetch_param('prefix');
		if($prefix == "")
		{
			$prefix = "item";
		}

		$fieldId = ee()->TMPL->fetch_param('field_id');
		if($fieldId == "")
		{
			$fieldName = ee()->TMPL->fetch_param('field_name');
			if($fieldName == "")
			{
				return false;
			}
			$fields = ee('Model')->get('ChannelField')->filter('field_name', $fieldName)->first();
		}
		else
		{
			$fields = ee('Model')->get('ChannelField', $fieldId)->first();
		}

		if(! $fields)
		{
			return false;
		}

		$options = ee()->superDynamicFields->jsonValue("", $fields->field_settings);
		if(! is_array($options))
		{
			return false;
		}

		$ret = array();
		$total = count($options);
		
		foreach ($options as $key => $option)
		{
			$ret[] = array(
				$prefix . ":count" 			=> ($key+1),
				$prefix . ":option_name" 	=> html_entity_decode($option['label']),
				$prefix . ":option_value" 	=> $option['value'],
				$prefix . ":default"		=> (isset($option['default']) && $option['default'] == "yes") ? true : false,
				$prefix . ":total_results" 	=> $total,
			);
		}
		unset($options);
		unset($fields);

		return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $ret);

	}
}