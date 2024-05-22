<?php
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
* ZealousWeb - Smart Import Export
*
* @package      SmartImportExport
* @author       Himanshu
* @copyright    Copyright (c) 2020, ZealousWeb.
* @link         https://www.zealousweb.com/expression-engine/smart-import-export
* @filesource   ./system/expressionengine/third_party/smart_import_export/lib/sie_custom_validation.php
*
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Sie_custom_validation
{

    public $validator;
    
    /* Initialize constructor */
    public function __construct() {
        $this->validator = ee('Validation')->make();
        $this->callAllValidationMethods();
    }
    
    /**
    * All validation rule define here
    * @return true if Everythig is okay other wise return error
    **/
    function callAllValidationMethods()
    {
        //rule define for unique short name of filename
        $this->validator->defineRule('short_name_check', function($key, $value, $parameters)
        {
            return $this->shortNameCheck($value, $parameters);
        });

        //rule define for fixed type of data (css, js) 
        $this->validator->defineRule('allowedType', function($key, $value, $parameters)
        {
            if( ! in_array($value, $parameters) )
            {
                return 'only_valid_file_allowed';
            }
            
            return true; 
        });

        //rule define for folder exists 
        $this->validator->defineRule('file_exists_or_not', function($key, $value, $parameters)
        {
            $bpath = ee()->config->item('base_path');
            $value = str_replace("{base_path}",$bpath,$value);
            if(is_dir($value)){
                if(!isset($filename['extension']) || (isset($filename['extension']) && !in_array($filename['extension'], array('json','csv','xml','third_party_xml')))){
                    return "file_not_available";
                }
                if(file_exists($value)){
                    return true;
                }else{
                    return "file_not_available";
                }
            }elseif(file_exists($value)){
                if(file_exists($value)){
                    return true;
                }else{
                    return "file_not_available";
                }
            }elseif(filter_var($value, FILTER_VALIDATE_URL)){
                //V3.0.2
                if(@file_get_contents( $value ) != false){
                    return true;
                }else{
                    return "file_not_available";
                }
            }else{
                return "file_not_available";
            }
            
        });
        $this->validator->defineRule('checkBlank', function($key, $value, $parameters)      
        {       
            if($value == "")        
            {       
                return "required";      
            }       
        
           return true;        
        });
        //rule define for check paths 
        $this->validator->defineRule('folder_path_exits', function($key, $value, $parameters)
        {
            $bpath = ee()->config->item('base_path');
            $value = str_replace("{base_path}",$bpath,$value);
            
            if (!is_dir($value)) {
                return "path_invalid";
            }
            
            return true; 
        });

        //rule define for check files values 
        $this->validator->defineRule('file_value', function($key, $value, $parameters)
        {

            $cnt = count($value);  
            if($cnt == 1)
            { 
                if($value[0] == "")   
                {           
                    return "required";
                }
            }
            return true; 
        });

        //rule define for check paths 
        $this->validator->defineRule('check_css_js_file', function($key, $value, $parameters)
        {
            $c = 0;
            $filelist = array();
            if(isset($_POST['type']))
            {
                $listingFiles = ee()->SCL->findFiles($_POST['path'], $filelist, $_POST['type']);
            }
            if(isset($listingFiles))
            {
                foreach ($listingFiles as $key => $value) {
                    $ext = pathinfo($value['file'],PATHINFO_EXTENSION);
                    
                    if($_POST['type'] == 'css')
                    {
                        if($ext == 'css' || $ext == 'scss' || $ext == 'less')
                        {
                            $c++;
                        }
                    }
                    if($_POST['type'] == 'js')
                    {
                        if($ext == 'js')
                        {
                            $c++;
                        }
                    }
                }
                if($c == 0)
                {
                    if($_POST['type'] == 'css')
                    {
                        return "no_css_file_found";
                    }
                    elseif($_POST['type'] == 'js')
                    {
                        return "no_js_file_found";
                    }
                }
            }
            return true; 
        });
        
    }


    /**
    * @param $value         (Value of short name field in form)
    * @param $parameters    (Confirm for for is edit or add)
    * @return true if short name is valid else return error
    **/
    function shortNameCheck($value, $parameters)
    {

        ee()->lang->loadfile('admin_content');
        if (in_array($value, ee()->cp->invalid_custom_field_names()))
        {
            return 'reserved_word';
        }

        if (preg_match('/[^a-z0-9\_\-]/i', $value))
        {
            return 'invalid_characters';
        }

        //form variable to check edit or add
        $edit = $parameters[0]; 
        // Is this field name already taken?
        $ret = ee()->SCModel->checkShortName($value, $edit);
        if ($ret > 0)
        {
            return 'duplicate_field_name';
        }
        
        return true;

    }
}
