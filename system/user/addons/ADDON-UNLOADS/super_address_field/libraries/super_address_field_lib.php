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
* Amici Infotech - Super Address Field
*
* @package      superAddressField
* @author       Mufi
* @copyright    Copyright (c) 2019, Amici Infotech.
* @link         http://expressionengine.amiciinfotech.com/super-address-field
* @filesource   ./system/expressionengine/third_party/super_address_field/libraries/super_address_field_lib.php
*/

use EllisLab\ExpressionEngine\Library\CP\Table;

class Super_address_field_lib
{

    public $included = FALSE;
    public function __construct()
    {
        ee()->load->model('super_address_field_model', 'saf_model');
        ee()->lang->loadfile('super_address_field');
    }

    function generalSettings($vars)
    {

        $values = ee()->saf_model->getGeneralSettings();

        $vars['sections'] = array(
            array(
                array(
                    'title' => lang('api_key'),
                    'desc'  => lang('google_api_key'),
                    'fields' => array(
                        'api_key' => array(
                            'type'  => 'text',
                            'value' => $values['api_key'],
                        )
                    )
                )
            )
        );

        $vars += array(
            'base_url'              => ee('CP/URL', 'addons/settings/super_address_field/'),
            'cp_page_title'         => lang('general_settings'),
            'save_btn_text'         => lang('save'),
            'save_btn_text_working' => lang('saving')
        );

        return $vars;

    }

    /**
    *  Save data in Database
    *
    * @return  boolean value
    */
    function generalSettingsPost()
    {
        $values = array();
        foreach ($_POST as $key => $value)
        {
            $values[$key] = ee()->input->post($key, true);
        }
        
        // DB QUERIES to save data
        ee()->saf_model->generalSettingsPost($values);
        
        return TRUE;
    }

    /**
    *  create url for Setting Form
    *
    * @param array $params 
    * @param Function name 
    * @return  url
    */
    public function createURL($functionName = "index", $params = array())
    {
        $temp = "";
        if(count($params) > 0)
        {

            $temp = "/";
            foreach ($params as $key => $value)
            {
                $temp .= $value . "/";
            }
            rtrim($temp, "/");

        }

        return ee('CP/URL')->make('addons/settings/super_address_field/' . $functionName . $temp);
    }

    /**
    *Decode the JSON
    *
    * @param   string $data Stored data for the field
    * @param   Defalut variables 
    * @return  JSON converted string
    */
    public function jsonValue($data)
    {
        $options = @json_decode(trim($data), TRUE);
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
    public function displayField($data, $fieldSettings)
    {

        //load css and javascript
        $api_key = ee()->saf_model->apiKey();
        if($this->included === FALSE)
        {
            ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . 'super_address_field/css/settings.css" type="text/css" media="screen" />');
            ee()->cp->add_to_foot('<script type="text/javascript" src="' . URL_THIRD_THEMES . 'super_address_field/js/settings.js" ></script>');
            ee()->cp->add_to_foot('<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=' . $api_key . '" ></script>');
            $this->included = TRUE;
        }

        if($fieldSettings['fluid_field_data_id'] != '')
        {
            $options = $this->jsonValue($data);
        }
        else if($fieldSettings['field_id'] != '')
        {
            $options = ee()->saf_model->getData($fieldSettings);
        }
        else
        {
            $options = $this->jsonValue($data);
        }

        $field  = '';
        $temp   = '';
        $values = '';

        $allowedFields  = $fieldSettings['allowed_field'];
        $combineFields  = ['city', 'state', 'postal_code'];
        $mainWrapper    = "";
        $cnt            = 0;
        $chk            = 0;

        //create a custom Field
        foreach ($combineFields as $combineField)
        {
            if(in_array($combineField, $allowedFields))
            {
                $mainWrapper =  "flex-row full-row";
                $cnt++;
            }
        }

        foreach ($fieldSettings['allowed_field'] as $item)
        {

            if($item == "latitude_longitude")
            {

                /*if($fieldSettings['field_default_value'] == 1)
                {*/
                    if($api_key != '')
                    {
                        $field .= '<div class="flex-row full-row">';

                        foreach (['latitude', 'longitude'] as $item) 
                        {
                            if(empty($options))
                            {
                                $temp = array(
                                    'name'          => $fieldSettings['field_name'] . '[' . $item . ']',
                                    'value'         => $values,
                                    'placeholder'   => lang($item)
                                );
                            }
                            else
                            {

                                foreach ($options as $key => $value) 
                                {
                                    if($key == $item)
                                    {
                                        $values =$value;
                                    }

                                    $temp = array(
                                        'name'          => $fieldSettings['field_name'] . '[' . $item . ']',
                                        'value'         => $values,
                                        'placeholder'   => lang($item)
                                    );
                                }

                            }
                            $field .= '<div class="' . $item . '">' . form_input($temp) . '</div>';
                        }

                        $field .= '<div class="button-wrapper"> <button type="button" name="plot_location" class="btn action">'.lang('plot_location').'</button></div></div>';
                    }
                    /* }*/
                }

                else
                {

                    if(in_array($item, $combineFields) && $mainWrapper != "")
                    {
                        if($chk == 0)
                        {
                            $field .= '<div class="' . $mainWrapper . '">';
                        }
                        $chk ++;
                    }

                    $extraClass = "";
                    if(! in_array($item, $combineFields))
                    {
                        $extraClass = " full-row";
                    }
                    if(empty($options))
                    {

                        $temp = array(
                            'name'          => $fieldSettings['field_name'] . '[' . $item . ']',
                            'value'         => $values,
                            'placeholder'   => lang($item)
                        );
                    }
                    else
                    {

                        foreach ($options as $key => $value) 
                        {
                            if($key == $item)
                            {
                                $values =$value;
                            }
                            $temp = array(
                                'name'          => $fieldSettings['field_name'] . '[' . $item . ']',
                                'value'         => $values,
                                'placeholder'   => lang($item)
                            );

                        }
                    }
                    $field .= '<div class="' . $item . $extraClass . '">' . form_input($temp) . '</div>';

                    if(in_array($item, $combineFields) && $mainWrapper != "" && $chk == $cnt)
                    {
                        $field .= '</div>';
                    }
                }

            }

            return '<div class="saf-wrapper">' . $field . '</div>';

        }

    /**
    * This function is display toggle button in display settings
    *
    * @param   string $data Stored data for the field
    * @param   Defalut variables 
    * @return  toggle button   
    */
    /*private function _display_field($data, $fieldSettings)
    {
        if (REQ == 'CP')
        {
            return ee('View')->make('ee:_shared/form/fields/toggle')->render(array(
                'field_name'    => $fieldSettings['field_name'],
                'value'         => $data,
                'disabled'      => $fieldSettings['disable'],
            ));
        }
    }*/

    function validate($data, $fieldSettings)
    {

        if($fieldSettings['field_reqiure'] === false)
        {
            return true;
        }
        
        $ret = "required";
        foreach ($data as $value)
        {
            if($value != "")
            {
                $ret = true;
                break;
            }
        }

        return $ret;

    }

    /**
    *   The data you return will be saved and returned to your field on
    *   display on the frontend and when editing the field.
    *
    * @param     string $data Stored data for the field
    * @param     Defalut variables 
    * @return    string data to store
    */
    public function save($data, $fieldSettings)
    {

        //save field data in custom table  
        if($fieldSettings['field_id'] == '' || $fieldSettings['fluid_field_data_id'] != '')
        {
            $data = json_encode($data);
            return $data;
        }
        else if($fieldSettings['entry_id'] != '')
        {

            ee()->saf_model->saveData($data, $fieldSettings);
            if($data['latitude'] != '' && $data['longitude'] != '')
            {
                $latitude   = $data['latitude'];
                $longitude  = $data['longitude'];

                return  $latitude . ', ' . $longitude;
            }
            else
            {
                return json_encode($data);
            }

        }
        else
        {
            return json_encode($data);
        }

    }

    /**
    *  This method implement to fetch newly created field content ID of the fieldtype
    *
    * @param   string $data Stored data for the field
    * @return  string data to store
    */
    function postSave($data, $fieldSettings)
    {
        if ($fieldSettings['field_id'] != '') 
        {
            if(is_array($data))
            {
                //store data in custom table
                ee()->saf_model->saveData($data, $fieldSettings);

                if($data['latitude'] != '' || $data['longitude'] != '')
                {
                    $latitude   = $data['latitude'];
                    $longitude  = $data['longitude'];
                    $data       = $latitude . ', ' . $longitude;
                }

                ee()->saf_model->updateData($data, $fieldSettings);
            }
        }
    }

    /**
    * Display Field Settings
    *
    * @param    array   $data Currently saved settings for this field
    * @return   string  Settings form display
    */
    function displaySettings($data, $fieldSettings)
    {

        $defaults = array(
            'field_default_value' => 0
        );

        foreach ($defaults as $setting => $value)
        {
            $data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
        }

        $fieldSettings['field_name']    = 'field_default_value';
        $allowedFields                  = (isset($data['allowed_field'])) ? $data['allowed_field'] : '';

        $settings = array(
           /* array(
                'title'     => lang('enable_map'),
                'desc'      => lang('latitude_longitude'),
                'fields'    => array(
                    'field_default_value' => array(
                        'type'      => 'html',
                        'content'   => $this->_display_field($data['field_default_value'], $fieldSettings) 
                    )
                )
            ),*/
            array(
                'title'     => lang('allowed_field'),
                'fields'    => array(
                    'allowed_field' =>array(
                        'type'      => 'checkbox',
                        'choices'   => array('address_1' => 'Address 1', 'address_2' => 'Address 2', 'city' => 'City', 'state' => 'State', 'postal_code' => 'Postal code', 'latitude_longitude' => 'Latitude/Logitude'),
                        'value'     => $allowedFields,
                    )
                )
            )
        );

        return array('field_options_super_address_field' => array(
            'label'     => 'field_options',
            'group'     => 'super_address_field',
            'settings'  => $settings
        ));

    }

    /**
    * Display Grid Field Settings
    *
    * @param    array   $data Currently saved settings for this field
    * @return   string  Settings form display
    */
    function gridDisplaySettings($data, $fieldSettings)
    {

        $defaults = array(
            'field_default_value' => 0
        );

        foreach ($defaults as $setting => $value)
        {
            $data[$setting] = isset($data[$setting]) ? $data[$setting] : $value;
        }

        $fieldSettings['field_name']    = 'field_default_value';
        $allowedFields                  = (isset($data['allowed_field'])) ? $data['allowed_field'] : '';

        ee()->javascript->output('
            Grid.bind("super_address_field", "displaySettings", function(element){
                SelectField.renderFields(element);
                });
                ');

        return array(
            'field_options' =>array(
               /* array(
                    'title'     => lang('enable_map'),
                    'desc'      => lang('latitude_longitude'),
                    'fields'    => array(
                        'field_default_value' => array(
                            'type'      => 'html',
                            'content'   => $this->_display_field($data['field_default_value'],  $fieldSettings) 

                        )
                    )
                ),*/
                array(
                    'title'  => lang('allowed_field'),
                    'fields'  =>array(
                        'allowed_field' =>array(
                            'type'      => 'checkbox',
                            'choices'   => array('address_1' => 'Address 1', 'address_2' => 'Address 2', 'city' => 'City', 'state' => 'State', 'postal_code' => 'Postal code', 'latitude_longitude' => 'Latitude/Logitude'),
                            'value'     => $allowedFields
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
        /*if (AJAX_REQUEST)
        {
            return TRUE;
        }

        $validator = ee('Validation')->make();
        
        $validator->setRules(array(
            'allowed_field'     => 'required'
        ));

        return $validator->validate($data);*/
        return TRUE;
    }

    /**
    * Saves settings for a field that allows its options to be specified in
    * a field setting
    *
    * @return save display settings
    */
    function saveSettings($data)
    {
        
        if(isset($data['allowed_field']) === false)
        {
            return ;
        }

        foreach ($data['allowed_field'] as $key => $value) 
        {
            if($value == '')
            {
                if(is_null($value) || $value == '')
                    unset($data['allowed_field'][$key]);
            }    
        }

        if(count($data['allowed_field']) == 1 && $data['allowed_field'][0] == 'latitude_longitude')
        {
            $data['allowed_field'] = array('address_1','latitude_longitude');
        }
        
        if(empty($data['allowed_field']))
        {
            $data['allowed_field'] = array('address_1');
        }

        $defaults = array(
            'allowed_field'         => $data['allowed_field'],
            // 'field_default_value'   => $data['field_default_value']
        );

        $all = array_merge($defaults, $data);

        return array_intersect_key($all, $defaults);

    }

    /**
    *  replace_tag for display data to frontend 
    *
    * @param   Defalut variables 
    */
    function replaceTag($data, $params, $tagdata, $fieldSettings)
    {   
        if($fieldSettings['field_id'] == '' || $fieldSettings['fluid_field_data_id'] != '')
        {
            $data = $this->jsonValue($data);
        }
        else
        {
            $data = ee()->saf_model->getData($fieldSettings);
        }
        
        if ($tagdata)
        {
            return $this->parseMulti($data, $params, $tagdata, $fieldSettings);
        }
        else
        {
            return $this->parseSingle($data, $params, $fieldSettings);
        }
    }

    /**
    * Process text through default typography options
    *
    * @param    string  $string String to process
    * @param   Defalut variables 
    * @return   Processed string
    */
    protected function processTypograpghy($string, $fieldSettings)
    {

        ee()->load->library('typography');

        return ee()->typography->parse_type(
            ee()->functions->encode_ee_tags($string),
            array(
                'text_format'       => $fieldSettings['get_format'],
                'html_format'       => $fieldSettings['row'],
                'auto_links'        => $fieldSettings['row_1'],
                'allow_img_url'     => $fieldSettings['row_2'],
            )
        );
    }

    /**
    * Parses a multi-selection field as a single variable
    *
    * @param    string  $data   Entry field data
    * @param    array   $params Params passed to the field via the template
    * @return   Parsed template string
    */
    protected function parseSingle($data, $params, $fieldSettings)
    {
        
        foreach ($data as $key => $value)
        {
            if($value == "")
            {
                unset($data[$key]);
            }
        }

        $separator = ", ";
        if(isset($params['separator']))
        {
            $separator = $params['separator'];
        }

        $entry = implode($separator, $data);
        return $this->processTypograpghy($entry, $fieldSettings);
        
    }

    /**
    * Parses a multi-selection field as a variable pair
    *
    * @param    string  $data       Entry field data
    * @param    array   $params     Params passed to the field via the template
    * @param    string  $tagdata    String between the variable pair
    * @return   Parsed template string
    */
    protected function parseMulti($data, $params, $tagdata, $fieldSettings)
    {
        if(! is_array($params))
        {
            $params = array();
        }

        $prefix     = (isset($params['prefix'])) ? $params['prefix'] : 'item';
        $parse = array();
        
        foreach ($data as $key => $value)
        {
            $parse[$prefix . ':' . $key] = $value;
        }

        return ee()->TMPL->parse_variables_row($tagdata, $parse);
        
    }

    /**
    * fetch data for Attribute
    *
    * @param    string  $data Entry field data
    * @param   Defalut variables
    * @return   filter data 
    */
    function dataFetch($data, $fieldSettings)
    {
        if(! $fieldSettings['field_id'] == '')
        {
            $data = ee()->saf_model->getData($fieldSettings);
        }
        else
        {
            $data = $this->jsonValue($data);
        }

        return $data;
    }

    /**
    * fetch data for all subfields
    *
    * @param   string  $data Entry field data
    * @param   Defalut variables 
    * @param   $key (subfield)
    * @return  $key (Data of subfield)
    */
    function replaceSubField($data, $fieldSettings, $key)
    {
        $data = $this->dataFetch($data, $fieldSettings);
        return isset($data[$key]) ? $data[$key] : "";
    }

    /**
    * fetch data for Latitude and Longitude Attribute
    *
    * @param   string  $data Entry field data
    * @param   Defalut variables 
    * @return  latitude and longitude 
    */
    function replaceLatLong($data, $fieldSettings)
    {

        $data   = $this->dataFetch($data, $fieldSettings);
        $lat    = isset($data['latitude']) ? $data['latitude'] : "";
        $long   = isset($data['longitude']) ? $data['longitude'] : "";

        return $lat . ', ' . $long;

    }

}

// EOF