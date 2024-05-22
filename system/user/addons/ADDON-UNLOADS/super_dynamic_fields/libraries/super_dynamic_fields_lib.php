<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* The software is provided "as is", without warranty of any
* kind, express or implied, including but not limited to the
* warranties of merchantability, fitness for a particular
* purpose and noninfringement. in no event shall the authors
* or copyright holders be liable for any claim, damages or
* other liability, whether in an action of contract, tort or
* otherwise, arising from, out of or in connection with the
* software or the use or other dealings in the software.
* -----------------------------------------------------------
* Amici Infotech - Super Dynamic Fields
*
* @package      superDynamicFields
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-dynamic-fields
* @filesource   ./system/expressionengine/third_party/super_dynamic_fields/libraries/super_dynamic_fields_lib.php
*/

class Super_dynamic_fields_lib
{

	public function __construct()
	{
		ee()->load->library('Super_dynamic_fields_parsing', null, 'superDynamicFieldsParse');
	}

	/**
	*Decode the JSON
	*
	* @param   string $data Stored data for the field
	* @param   Defalut variables 
	* @return  JSON converted string
	*/
	function jsonValue($data, $fieldSettings)
	{

		if($fieldSettings['option_type'] == 'template_id')
		{
			$options = ee()->superDynamicFieldsParse->template_parser($fieldSettings['template']);
			$options = $options['msg_body'];
		}
		else
		{
			$options = $fieldSettings['body'];
		}

		$options = @json_decode(trim($options),TRUE);
		if(! is_array($options)) 
		{
			$options = array();
		}

		return $options;

	}

	/**
	*Displays the field for the CP or Frontend, and accounts for grid
	*
	* @param   string $data Stored data for the field
	* @param   Defalut variables 
	* @param   string $container What type of container is this field in, 'fieldset' or 'grid'?
	* @return  string Field display
	*/
	function displayField($data, $fieldSettings,  $grid = 'fieldset')
	{
		
		$options 			= $this->jsonValue($data, $fieldSettings);
		$defaultSelected 	= array();
		
		//javascript for display FluidField or grid
		if (REQ == 'CP')
		{
			ee()->javascript->output('
				FluidField.on("super_dynamic_fields", "add", function(element){
					Dropdown.renderFields(element);
					SelectField.renderFields(element);
				});

				Grid.bind("super_dynamic_fields", "display", function(element){
					Dropdown.renderFields(element);
					SelectField.renderFields(element);
				});
			');
		}

		$choices = array();
		foreach ($options as  $option) 
		{
			$choices[$option['value']] =  html_entity_decode($option['label']);
			
			if(isset($option['default']) && strtolower($option['default']) == "yes")
			{
				$defaultSelected[] = $option['value'];
			}

		}

		if($fieldSettings['type'] == 'dropdown')
		{

			if((! (isset($_POST) && count($_POST) > 0)) && count($defaultSelected) && $fieldSettings['entry_id'] == "")
			{
				$data = $defaultSelected[0];
			}

			if (REQ == 'CP' && $fieldSettings['content'] !== 'grid')
			{
				return ee('View')->make('ee:_shared/form/fields/dropdown')->render([
					'field_name'     => $fieldSettings['field_name'],
					'choices'        => $choices,
					'value'          => $data,
				]);
			}

			$field = form_dropdown(
				$fieldSettings['field_name'],
				$choices,
				$data
			);

			return $field;

		}
		elseif($fieldSettings['type'] == 'radio')
		{

			if((! (isset($_POST) && count($_POST) > 0)) && count($defaultSelected) && $fieldSettings['entry_id'] == "")
			{
				$data = $defaultSelected[0];
			}

			if (REQ == 'CP')
			{

				return ee('View')->make('ee:_shared/form/fields/select')->render([
					'field_name' => $fieldSettings['field_name'],
					'choices'    => $choices,
					'value'      => $data,
					'multi'      => FALSE,
				]);

			}

			$selected = $data;

			$r = '';
			$class = 'choice mr';
			foreach($choices as $key => $value)
			{
				$selected = ($key == $data);
				$r .= '<label>' . form_radio($fieldSettings['field_name'], $key, $selected) . NBS . $value . '</label>';
			}

			switch ($grid)
			{
				case 'grid':
				$r =  $fieldSettings['gird_container'];
				break;

				default:
				$r = form_fieldset('') . $r . form_fieldset_close();
				break;

			}

			return $r;

		}

		elseif($fieldSettings['type'] == 'checkboxes')
		{
			if((! (isset($_POST) && count($_POST) > 0)) && count($defaultSelected) && $fieldSettings['entry_id'] == "")
			{
				$data = $defaultSelected;
			}
			elseif(! is_array($data))
			{
				$data = explode('|', $data);
			}
			
			if (REQ == 'CP')
			{

				return ee('View')->make('ee:_shared/form/fields/select')->render([
					'field_name'  		=> $fieldSettings['field_name'],
					'choices'     		=> $choices,
					'value'       		=> $data,
					'multi'       		=> TRUE,
					'nested'            => TRUE,
					'nestable_reorder'  => TRUE,
				]);

			}

			$r = '<div class="scroll-wrap pr">';

			$r .= $this->displayNestedForm($choices, $data, $fieldSettings);

			$r .= '</div>';

			switch ($grid)
			{
				case 'grid':
				$r = $fieldSettings['gird_container'];
				break;

				default:
				$r = form_fieldset('').$r.form_fieldset_close();
				break;
			}

			return $r;

		}

	}

	/**
	* This function is display checkbox in channel form
	*
	* @param   array $choices stored data
	* @param   string $data Stored data for the field
	* @param   Defalut variables 
	* @return  grid field   
	*/
	protected function displayNestedForm($choices, $data, $fieldSettings)
	{
		$out      = '';
		$disabled = ($fieldSettings['field_disable']) ? 'disabled' : '';
		foreach ($choices as $key => $value)
		{
			$checked = (in_array(form_prep($key), $data)) ? TRUE : FALSE;
			if (is_array($value))
			{
				$out .= '<label>'.form_checkbox($fieldSettings['field_name'].'[]', $key, $checked, $disabled).NBS.$value['name'].'</label>';
				$out .= $this->displayNestedForm($value['children'], $data, TRUE);
			}
			else
			{
				$out .= '<label>'.form_checkbox($fieldSettings['field_name'].'[]', $key, $checked, $disabled).NBS.$value.'</label>';
			}
		}

		return $out;

	}

	/**
	*	The data you return will be saved and returned to your field on
	* 	display on the frontend and when editing the field.
	*
	* @param   	 string $data Stored data for the field
	* @return    string data to store
	*/
	function save($data)
	{

		if(is_array($data))
		{
			ee()->load->helper('custom_field');
			$data = encode_multi_field($data);
		}
		
		return $data;

	}

	/**
	*	Validate the field in frontend
	*
	* @param   string $data Stored data for the field
	* @param   Defalut variables 
	*/
	public function validate($data, $fieldSettings)
	{
		
		$options = $this->jsonValue($data, $fieldSettings);

		$choices = array();
		foreach ($options as $value)
		{
			if(isset($value['label']) && isset($value['value']))
			{
				$choices[$value['value']] = $value['label'];
			}
		}

		if(empty($choices))
		{
			return true;
		}

		if($data == "" || (is_array($data) && count($data) == 1 && $data[0] == ""))
		{

			if($fieldSettings['field_reqiure'])
			{
				return 'required';
			}
			else
			{
				return true;
			}

		}
		
		if(is_array($data))
		{

			foreach ($data as $value)
			{

				if($value != "")
				{
					if(! isset($choices[$value]))
					{
						return 'invalid_selection';
					}
				}

			}

		}
		else
		{

			if($data != "")
			{
				if(! isset($choices[$data]))
				{
					return 'invalid_selection';
				}
			}

		}

		return true;

	}

	/**
	* Display Field Settings
	*
	* @param	array   $data Currently saved settings for this field
	* @return	string  Settings form display
	*/
	public function displaySettings($data, $grid = 'fieldset')
	{

		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$defaults = array(
			'field_fmt' 	=> 'none',
			'option_type' 	=> 'template_id',
			'body' 			=> '',
			'template' 		=> '',
			'type'			=> ''
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}
		
		$settings = array(
			array(
				'title'		=> 'Text Format',
				'fields' 	=> array(
					'field_fmt' 	=> array(
						'type' 		=> 'radio',
						'choices' 	=> $format_options,
						'value' 	=> $data['field_fmt'],
					)
				)
			),
			array(
				'title'     => 'Type',
				'fields'    => array(
					'type' 	=> array(
						'type'      => 'inline_radio',
						'choices'   => array('dropdown' => 'Dropdown', 'radio' => 'Radio Buttons', 'checkboxes' => 'Checkboxes'),
						'value'     => (isset($data['type'])) ? $data['type'] : "dropdown",
						'required'  => TRUE
					)
				)
			),
			array(
				'fields' => array(
					'field_pre_populate_n'	=> array(
						'type' 			=> 'radio',
						'name' 			=> 'option_type',
						'choices' 		=> array(
							'template_id' 	=> lang('json_template_id'),
						),
						'value' 		=> $data['option_type']
					),
					'template' => array(
						'type' 			=> 'radio',
						'margin_left' 	=> TRUE,
						'choices' 		=> $this->templateListSearch($grid),
						'value'			=> $data['template']
					)
				)
			),
			array(
				'fields' => array(
					'field_pre_populate_n' => array(
						'type' 		=> 'radio',
						'name' 		=> 'option_type',
						'choices' 	=> array(
							'custom_json' => lang('json_custom'),
						),
						'value' => $data['option_type']
					),
					'body' => array(
						'type' 			=> 'textarea',
						'margin_left' 	=> TRUE,
						'placeholder' 	=> "{'label': 'Large' , 'value' : 'lg' , 'default' : 'yes'},\n{'label': 'Medium' , 'value' : 'md'}",
						'value' 		=> $data['body']
					)
				)
			)
		);
		
		return array('field_options_super_dynamic_fields' => array(
			'label' => 'field_options',
			'group' => 'super_dynamic_fields',
			'settings' => $settings
		));

	}

	function gridDisplayFields($data, $grid = 'grid')
	{

		$format_options = ee()->addons_model->get_plugin_formatting(TRUE);

		$defaults = array(
			'field_fmt' 	=> 'none',
			'option_type' 	=> 'template_id',
			'body' 			=> '',
			'template' 		=> '',
			'type'			=> ''
		);

		foreach ($defaults as $setting => $value)
		{
			$data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
		}

		/*ee()->javascript->output('
			$(document).on("change", "input[type=hidden]", function(){
				if($(this).val().trim() == "super_dynamic_fields")
				{
					setTimeout(function() {SelectField.renderFields();}, 100);
				}
			});
		');*/
		ee()->javascript->output('
			Grid.bind("super_dynamic_fields", "displaySettings", function(element){
				SelectField.renderFields(element);
			});
		');

		return array(
			'field_options' => array(
				array(
					'title'		=> 'Text Format',
					'fields' 	=> array(
						'field_fmt' 	=> array(
							'type' 		=> 'radio',
							'choices' 	=> $format_options,
							'value' 	=> $data['field_fmt'],
						)
					)
				),
				array(
					'title'     => 'Type',
					'fields'    => array(
						'type' 	=> array(
							'type'      => 'inline_radio',
							'choices'   => array('dropdown' => 'Dropdown', 'radio' => 'Radio Buttons', 'checkboxes' => 'Checkboxes'),
							'value'     => (isset($data['type'])) ? $data['type'] : "dropdown",
							'required'  => TRUE
						)
					)
				),
				array(
					'fields' => array(
						'field_pre_populate_n'	=> array(
							'type' 			=> 'radio',
							'name' 			=> 'option_type',
							'choices' 		=> array(
								'template_id' 	=> lang('json_template_id'),
							),
							'value' 		=> $data['option_type']
						),
						'template' => array(
							'type' 			=> 'radio',
							'margin_left' 	=> TRUE,
							'choices' 		=> $this->templateListSearch($grid),
							'value'			=> $data['template']
						)
					)
				),
				array(
					'fields' => array(
						'field_pre_populate_n' => array(
							'type' 		=> 'radio',
							'name' 		=> 'option_type',
							'choices' 	=> array(
								'custom_json' => lang('json_custom'),
							),
							'value' => $data['option_type']
						),
						'body' => array(
							'type' 			=> 'textarea',
							'margin_left' 	=> TRUE,
							'placeholder' 	=> "{'label': 'Large' , 'value' : 'lg' , 'default' : 'yes'},\n{'label': 'Medium' , 'value' : 'md'}",
							'value' 		=> $data['body']
						)
					)
				)
			)
		);
		
	}

	/**
	* Validate the settings
	*
	* This is called before the settings are fully saved
	*
	* @param mixed   settings data
	* @return mixed  validation result
	*/
	public function validateSettings($data)
	{
		
		if (AJAX_REQUEST)
		{
			return TRUE;
		}
		
		$validator = ee('Validation')->make();

		//validate JSON for body 
		$validator->defineRule('json', function($key, $value, $parameters) use ($data)
		{

			$value = trim($value);
			if(! is_array(json_decode($value,TRUE))) 
			{
				return FALSE;
			}

			return true;

		});

		//validate JSON for template 
		$validator->defineRule('template_json', function($key, $value, $parameters) use ($data)
		{

			$value = ee()->superDynamicFieldsParse->template_parser($data['template']);
			$value = $value['msg_body'];
			$value = trim($value);
			
			if(! is_array(json_decode($value,TRUE))) 
			{
				return FALSE;
			}

			return true;

		});

		if($data['option_type'] == 'template_id')
		{

			$validator->setRules(array(
				'option_type' 	=> 'required|enum[template_id,custom_json]',
				'template'  	=> 'required|template_json',
				'body' 			=> 'json',
			));

		}
		else
		{
			$validator->setRules(array(
				'option_type' 	=> 'required|enum[template_id,custom_json]',
				'template'  	=> 'template_json',
				'body' 			=> 'required|json'
			));
		}

		return $validator->validate($data);
		
	}

	/**
	* Saves settings for a field that allows its options to be specified in
	* a field setting
	*
	* @return save display settings
	*/
	public function saveSettings($data)
	{

		$defaults = array(
			'option_type' 	=> isset($data['option_type']) ? $data['option_type'] : "json",
			'template' 	 	=> $data['template'],
			'body' 			=> $data['body'],
			'type' 			=> $data['type']
		);
		
		$all = array_merge($defaults, $data);
		
		return array_intersect_key($all, $defaults);

	}

	/**
	* Default replace_tag implementation
	* @param   Defalut variables 
	*/
	function replaceTag($data, $params, $tagdata, $fieldSettings)
	{

		ee()->load->helper('custom_field');
		$data 		= decode_multi_field($data);
		$temp 		= $this->jsonValue($data, $fieldSettings);
		$options 	= array();

		if(is_array($temp) && count($temp))
		{
			foreach ($temp as $value)
			{
				if(isset($value['value']) && isset($value['label']))
				{
					$options[$value['value']] = $value['label'];
				}
			}
		}
		unset($temp);

		if ($tagdata)
		{
			return $this->parseMulti($data, $options, $params, $tagdata, $fieldSettings);
		}
		else
		{
			return $this->parseSingle($data, $options, $params, $fieldSettings);
		}

	}

	/**
	* Process text through default typography options
	*
	* @param	string	$string	String to process
	* @param   Defalut variables 
	* @return	Processed string
	*/
	protected function processTypograpghy($string, $fieldSettings)
	{
		ee()->load->library('typography');

		return ee()->typography->parse_type(
			ee()->functions->encode_ee_tags($string),
			array(
				'text_format'	=> $fieldSettings['get_format'],
				'html_format'	=> $fieldSettings['row'],
				'auto_links'	=> $fieldSettings['row_1'],
				'allow_img_url' => $fieldSettings['row_2'],
			)
		);
	}

	/**
	* Parses a multi-selection field as a single variable
	*
	* @param	string	$data	Entry field data
	* @param	array	$params	Params passed to the field via the template
	* @return	Parsed template string
	*/
	protected function parseSingle($data, $options, $params, $fieldSettings)
	{
		
		if (isset($params['limit']))
		{
			$limit = intval($params['limit']);

			if (count($data) > $limit)
			{
				$data = array_slice($data, 0, $limit);
			}
		}

		foreach ($data as $key => $value)
		{
			if (isset($pairs[$value]))
			{
				$data[$key] = $pairs[$value];
			}
		}
		

		if (isset($params['markup']) && ($params['markup'] == 'ol' OR $params['markup'] == 'ul'))
		{
			$entry = '<'.$params['markup'].'>';

			foreach($data as $dv)
			{
				$entry .= '<li>';
				$entry .= $dv;
				$entry .= '</li>';
			}

			$entry .= '</'.$params['markup'].'>';
		}
		else
		{
			$entry = implode(', ', $data);
		}

		return $this->processTypograpghy($entry, $fieldSettings);

	}

	/**
 	* Parses a multi-selection field as a variable pair
 	*
 	* @param	string	$data		Entry field data
 	* @param	array	$params		Params passed to the field via the template
 	* @param	string	$tagdata	String between the variable pair
 	* @return	Parsed template string
 	*/
 	protected function parseMulti($data, $options, $params, $tagdata, $fieldSettings)
 	{
 		if(! is_array($params))
 		{
 			$params = array();
 		}

 		$chunk 		= '';
 		$raw_chunk 	= '';
 		$limit 		= (isset($params['limit']) && is_numeric($params['limit'])) ? $params['limit'] : FALSE;
 		$prefix 	= (isset($params['prefix'])) ? $params['prefix'] : 'item';
 		$total 		= ($limit && count($data) >= $limit) ? $limit : count($data);
 		$absolute_results = count($data);
 		
 		foreach($data as $key => $item)
 		{

 			if ( ! $limit OR $key < $limit)
 			{
 				$vars[$prefix] 				= $item;
 				$vars[$prefix . ':label'] 	= $item;
 				$vars[$prefix . ':value'] 	= $item;
 				$vars[$prefix . ':count'] 				= $key + 1;
 				$vars[$prefix . ':total_results'] 		= $total;
 				$vars[$prefix . ':absolute_results'] 	= $absolute_results;

 				if (isset($options[$item]))
 				{
 					$vars[$prefix . ':label'] = $options[$item];
 				}

 				$tmp = ee()->functions->prep_conditionals($tagdata, $vars);
 				$raw_chunk .= ee()->functions->var_swap($tmp, $vars);
 				$chunk .= ee()->TMPL->parse_variables_row($tmp, $vars);
 			}
 			else
 			{
 				break;
 			}

 		}

 		if (isset($params['backspace']))
 		{
 			$chunk 		= substr($chunk, 0, - $params['backspace']);
 			$raw_chunk 	= substr($raw_chunk, 0, - $params['backspace']);
 		}

 		return $chunk;

 	}

	/**
 	* fetch template file for  JSON strings
 	*
 	* @return	Parsed template string
 	*/
 	private function templateListSearch($container = 'fieldset')
 	{

 		$templates = ee('Model')->get('Template')
 		->with('TemplateGroup')
 		->filter('site_id', ee()->config->item('site_id'))
 		->order('TemplateGroup.group_name')
 		->order('Template.template_name');
 		
 		$templates = $templates->limit(null)->all();
 		$results = [];

 		foreach ($templates as $template)
 		{
 			if ($container == 'grid')
 			{
 				$results[$template->getId()] = $template->getPath();
 			}
 			else
 			{
 				$results[$template->getTemplateGroup()->group_name][$template->getId()] = $template->getPath();
 			}
 		}

 		return $results;

 	}

 }

//EOF
