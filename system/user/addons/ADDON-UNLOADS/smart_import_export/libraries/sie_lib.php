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
* @copyright    Copyright (c) 2021, ZealousWeb.
* @link         https://www.zealousweb.com/expression-engine/smart-import-export
* @filesource   ./system/expressionengine/third_party/smart_import_export/lib/sie_lib.php
*
*/

use EllisLab\ExpressionEngine\Library\CP\Table;

class Sie_lib
{

    /* Important globel variables */ 
    public $site_id;
    public $member_id;
    public $group_id;
    public $delim       = ",";
    public $newline     = "\n";
    public $enclosure   = '"';
    public $exportData;
    public $importData;
    public $errors;
    public $type;
    public $search;
    public $replace;
    public $items;

    public $patharray  = array();

    /* Constructor */
    public function __construct()
    {

        $this->module_name   = 'Smart_import_export';

        /*Logged in member ID, group ID and site ID*/
        $this->site_id      = ee()->config->item("site_id");
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata('group_id');
        $exportData         = array();

        /* Neeful model classes */
        if(! class_exists('sie_model'))
        {
            ee()->load->model('sie_model','sieModel');
        }
        ee()->load->library('sie_custom_validation','sie_custom_validation');
        $this->search   = array('"', "{base_url}", "{base_path}");
        $this->replace  = array("\"", ee()->config->item('base_url'), ee()->config->item('base_path'));
        ee()->lang->loadfile('smart_import_export');

    }

    /**
    * Create URL by given parameters
    * 
    * @param $method (Set method in URL. Default index method)
    * @param $parameters (array of arguments pass in URL via get parameters)
    * @return Backend URL
    */
    function url($method="index", $parameters = array())
    {

        $url = 'addons/settings/smart_import_export/'.$method;
        if(is_array($parameters) && count($parameters) > 0)
        {
            foreach ($parameters as $key => $value)
            {
                $url .= "/" . $value;
            }
        }
        
        return ee('CP/URL')->make($url);

    }

    /**
    * Function to fetch all fields group from Group ID
    * @param $group_id (Channel field group ID to get Channel group Fields)
    * @return $result (Final Array of channel fields with sub array of GRID fields)
    **/
    function getAllFieldsFromGroupId($group_id)
    {

        /*Fetch channel fields*/
        $result = ee()->sieModel->getChannelFields( array('group_id' => $group_id));
        
        if(isset($result) && is_array($result) && count($result) > 0)
        {

            for ($i=0; $i < count($result); $i++)
            {

                /*Check whether field is GRID type*/
                if($result[$i]['field_type'] == "grid")
                {

                    /*Relationships inside GRID*/
                    $q2 = ee()->sieModel->relationshipsInsideGrid($result[$i]['field_id']);

                    /*if Empty Grid*/
                    if($q2 === false)
                    {
                        $result[$i]['grid_rel'] = "NA";
                    }
                    else
                    {
                        foreach ($q2 as $key => $value)
                        {
                            $result[$i]['grid_rel'][$key] = $value;
                        }
                    }

                }
                elseif($result[$i]['field_type'] == "matrix")
                {
                    /*Relationships inside MATRIX*/
                    $q2 = ee()->sieModel->relationshipsInsideMatrix($result[$i]['field_id']);
                    
                    /*if Empty Grid*/
                    if($q2 === false)
                    {
                        $result[$i]['matrix_rel'] = "NA";
                    }
                    else
                    {
                        foreach ($q2 as $key => $value)
                        {
                            $result[$i]['matrix_rel'][$key] = $value;
                        }
                    }

                }
                else
                {
                    $result[$i]['matrix_rel'] = "NA";
                }

            }

        }
       
        return $result;

    }

    function getAllChannelFields($channelID)
    {

        /*Fetch channel fields*/
        $result = ee()->sieModel->getAllChannelFields($channelID);
        
        if(isset($result) && is_array($result) && count($result) > 0)
        {

            for ($i=0; $i < count($result); $i++)
            {

                /*Check whether field is GRID type*/
                if($result[$i]['field_type'] == "grid")
                {

                    /*Relationships inside GRID*/
                    $q2 = ee()->sieModel->relationshipsInsideGrid($result[$i]['field_id']);

                    /*if Empty Grid*/
                    if($q2 === false)
                    {
                        $result[$i]['grid_rel'] = "NA";
                    }
                    else
                    {
                        foreach ($q2 as $key => $value)
                        {
                            $result[$i]['grid_rel'][$key] = $value;
                        }
                    }

                }
                elseif($result[$i]['field_type'] == "matrix")
                {

                    /*Relationships inside MATRIX*/
                    $q2 = ee()->sieModel->relationshipsInsideMatrix($result[$i]['field_id']);
                    
                    /*if Empty Grid*/
                    if($q2 === false)
                    {
                        $result[$i]['matrix_rel'] = "NA";
                    }
                    else
                    {
                        foreach ($q2 as $key => $value)
                        {
                            $result[$i]['matrix_rel'][$key] = $value;
                        }
                    }

                }
                elseif($result[$i]['field_type'] == "fluid_field")
                {
                    
                    $result[$i]['field_settings'] = unserialize(base64_decode($result[$i]['field_settings']));
                    if(isset($result[$i]['field_settings']['field_channel_fields']) && is_array($result[$i]['field_settings']['field_channel_fields']) && count($result[$i]['field_settings']['field_channel_fields']) > 0)
                    {

                        $result[$i]['rel'] = array();
                        $fluideResults = ee()->sieModel->getAllChannelFields($channelID, $result[$i]['field_settings']['field_channel_fields']);
                        if(isset($fluideResults) && is_array($fluideResults) && count($fluideResults) > 0)
                        {

                            for ($j = 0; $j < count($fluideResults); $j++)
                            {
                                
                                if($fluideResults[$j]['field_type'] == "relationship")
                                {
                                    $result[$i]['rel']['relationship'][] =  $fluideResults[$j];
                                }
                                elseif($fluideResults[$j]['field_type'] == "playa")
                                {
                                    $result[$i]['rel']['playa'][] =  $fluideResults[$j];
                                }
                                elseif($fluideResults[$j]['field_type'] == "grid")
                                {

                                    /*Relationships inside GRID*/
                                    $q2 = ee()->sieModel->relationshipsInsideGrid($fluideResults[$j]['field_id']);

                                    /*if Empty Grid*/
                                    if($q2 !== false)
                                    {
                                        foreach ($q2 as $key => $value)
                                        {
                                            $result[$i]['rel']['grid_rel'][] = $value;
                                        }
                                    }

                                }
                                elseif($fluideResults[$j]['field_type'] == "matrix")
                                {

                                    /*Relationships inside MATRIX*/
                                    $q2 = ee()->sieModel->relationshipsInsideMatrix($fluideResults[$j]['field_id']);
                                    
                                    /*if Empty Grid*/
                                    if($q2 !== false)
                                    {
                                        foreach ($q2 as $key => $value)
                                        {
                                            $result[$i]['rel']['matrix_rel'][] = $value;
                                        }
                                    }

                                }

                            }

                        }

                    }

                }

                unset($result[$i]['field_settings']);

            }

        }

        return $result;

    }

    /**
    * Generate export function. Setup all exportSettings and get all Data from channel and throw to other function create a file (csv or xml)
    * @param $exportSettings (Array of all fields saved by user in backend export form.)
    * @return download export file or return base64 data of given exported query result if AJAX
    **/
    function generateExport($exportSettings = array(), $type = "")
    {

        $this->type = $type;
        $this->exportData['exportQuery']        = "";
        $this->exportData['exportSettings']     = $exportSettings;
        
        $temp                                   = ee()->sieModel->getGeneralSettings();

        $this->exportData['generalSettings']    = $temp['settings'];
        if(!isset($this->exportData['exportSettings']['settings']['default_fields']) || count($this->exportData['exportSettings']['settings']['default_fields']) < 2){
            show_error(lang('select_alteast_title_urltitle_default_field')."<br/><a href='".$this->url('export_form/'.$this->exportData['exportSettings']['token'])."'>Edit your export setting and then export again</a>");
        }
        if(!in_array('title',$this->exportData['exportSettings']['settings']['default_fields']) || !in_array('url_title',$this->exportData['exportSettings']['settings']['default_fields'])){
            show_error(lang('select_both_title_urltitle_default_field')."<br/><a href='".$this->url('export_form/'.$this->exportData['exportSettings']['token'])."'>Edit your export setting and then export again</a>");
        }
        unset($temp);
        unset($exportSettings);
        
        /* We need offset and limit to break down exports in parts if triggered by AJAX */
        if($this->type == "ajax")
        {

            $this->exportData['exportSettings']['settings']['procedure'] = "ajax";
            
            /* Set Offset */
            if(ee()->input->get('offset') != "")
            {
                $this->exportData['offset'] = ee()->input->get_post('offset', true);
                if(! is_numeric($this->exportData['offset']))
                {
                    $this->exportData['offset'] = 0;
                }
            }
            else
            {
                $this->exportData['offset'] = 0;
            }

            /* Set limit */
            if(isset($this->exportData['exportSettings']['settings']['batches']))
            {
                $this->exportData['limit']  = is_numeric($this->exportData['exportSettings']['settings']['batches']) ? $this->exportData['exportSettings']['settings']['batches'] : 50;
            }
            else
            {
                $this->exportData['limit'] = 50;
            }

        }
        
        /* Get all default fields selected in export form */
        if(isset($this->exportData['exportSettings']['settings']['default_fields']) && is_array($this->exportData['exportSettings']['settings']['default_fields']))
        {
            $this->exportData['exportQuery'] = "ct." . implode(", ct.", $this->exportData['exportSettings']['settings']['default_fields']) . ", ";
        }

        /* Get all Dynamic fields selected in export form */
        if(isset($this->exportData['exportSettings']['settings']['custom_fields']) && is_array($this->exportData['exportSettings']['settings']['custom_fields']))
        {
            $this->exportData['customFields'] = ee()->sieModel->getCustomFieldNames($this->exportData['exportSettings']['settings']['custom_fields']);
            if($this->exportData['customFields'] !== false)
            {
                $this->exportData['exportQuery'] .= $this->exportData['customFields']['select'];
                unset($this->exportData['customFields']['select']);
                $this->exportData['customFields'] = $this->exportData['customFields']['data'];
            }
        }
        
        /* Get all file directories to convert {filedir_x} to given file URL */
        $this->exportData['parseFiles'] = ee()->sieModel->getFieldDirectories();

        /* Find total rows of particular channel only if triggered by AJAX */
        if($this->type == "ajax")
        {
            $this->exportData['totalRows']  = ee()->sieModel->getChannelData($this->exportData, true);
        }

        $this->exportData['data']       = ee()->sieModel->getChannelData($this->exportData);

        
        $pages                          = ee()->sieModel->checkModuleInstalled('Pages');
        $low_variables                  = ee()->sieModel->checkModuleInstalled('Low_variables');
        $siteID                         = ee()->sieModel->getSiteID($this->exportData['exportSettings']['settings']['channel_id']);

        /* Get all pages (custom URIs defined in pages module) */
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['pages']) && $this->exportData['exportSettings']['settings']['general_settings']['pages'] == "yes" && $pages === true)
        {
            $this->exportData['pagesData'] = ee()->sieModel->getSitePages($siteID);
            $this->exportData['pagesData'] = $this->exportData['pagesData'][1];
        }

        /* Get all category groups of given channel */
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['categories']) && $this->exportData['exportSettings']['settings']['general_settings']['categories'] == "yes")
        {

            $this->exportData['categoryGroups']   = ee()->sieModel->getCategoryGroups($this->exportData['exportSettings']['settings']['channel_id']);
            if(isset($this->exportData['categoryGroups']) && is_array($this->exportData['categoryGroups']) && count($this->exportData['categoryGroups']) > 0)
            {
                for ($j = 0; $j < count($this->exportData['categoryGroups']); $j++)
                {
                    $this->exportData['categoryGroups'][$j]['short_name'] = $this->sanitize($this->exportData['categoryGroups'][$j]['group_name']);
                }
            }
            
        }

        $includeSeoLite = false;
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['seo_lite']) && $this->exportData['exportSettings']['settings']['general_settings']['seo_lite'] == "yes")
        {
            $includeSeoLite = ee()->sieModel->checkModuleInstalled('Seo_lite');
        }

        $includeSmartSeo = false;
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['smart_seo']) && $this->exportData['exportSettings']['settings']['general_settings']['smart_seo'] == "yes")
        {
            $includeSmartSeo = ee()->sieModel->checkModuleInstalled('Smart_seo');
            if($includeSmartSeo){
                ee()->load->add_package_path(PATH_THIRD . 'smart_seo');
                require_once PATH_THIRD.'smart_seo/tab.smart_seo.php';
                $smart_seo_tab = new Smart_seo_tab();
            }
        }

        $includeSEEO = false;
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['seeo']) && $this->exportData['exportSettings']['settings']['general_settings']['seeo'] == "yes")
        {
            $includeSEEO = ee()->sieModel->checkModuleInstalled('seeo');
            if($includeSEEO){
                ee()->load->add_package_path(PATH_THIRD . 'seeo');
                require_once PATH_THIRD.'seeo/tab.seeo.php';
                $seeo_tab = new Seeo_tab();
            }
        }


        $includeStructure = false;
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['structure']) && $this->exportData['exportSettings']['settings']['general_settings']['structure'] == "yes")
        {
            $includeStructure = ee()->sieModel->checkModuleInstalled('Structure');
            if($includeStructure){
                ee()->load->add_package_path(PATH_THIRD . 'structure');
                require_once PATH_THIRD.'structure/tab.structure.php';
                $structure_tab = new Structure_tab();
            }
        }

        $includeTranscribe = false;
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['transcribe']) && $this->exportData['exportSettings']['settings']['general_settings']['transcribe'] == "yes")
        {
            $includeTranscribe = ee()->sieModel->checkModuleInstalled('Transcribe');
            if($includeTranscribe){
                ee()->load->add_package_path(PATH_THIRD . 'transcribe');
                require_once PATH_THIRD.'transcribe/tab.transcribe.php';
                $transcribe_tab = new Transcribe_tab();
            }

        }


        /* Process data if we found any entries in given channel */
        if(isset($this->exportData['data']) && is_array($this->exportData['data']) && count($this->exportData['data']) > 0)
        {
            for ($i = 0; $i < count($this->exportData['data']); $i++)
            {

                $entry_id = $this->exportData['data'][$i]['ne_entry_id'];
                unset($this->exportData['data'][$i]['ne_entry_id']);
                foreach ($this->exportData['data'][$i] as $key => $value)
                {
                    
                    if(isset($this->exportData['customFields'][$key]['field_type']))
                    {

                        $fieldType = $this->exportData['customFields'][$key]['field_type'];
                        
                        /* Check field type and process that field accordingly.*/
                        if($fieldType == "assets")
                        {

                            if($value != "")
                            {
                                $where = array(
                                    'entry_id' => $entry_id, 
                                    'field_id' => $this->exportData['customFields'][$key]['field_id']
                                );
                                $ret = ee()->sieModel->getAssetsData($where, $this->exportData['parseFiles']);
                                $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                            }

                        }
                        elseif($fieldType == "channel_files")
                        {
                            $ret = ee()->sieModel->getChannelImagesAndFilesData($entry_id, $this->exportData['customFields'][$key], $this->exportData['parseFiles'], "channel_files");
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                        }
                        elseif($fieldType == "channel_images")
                        {
                            $ret = ee()->sieModel->getChannelImagesAndFilesData($entry_id, $this->exportData['customFields'][$key], $this->exportData['parseFiles']);
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                        }
                        elseif($fieldType == "channel_videos")
                        {

                        }
                        elseif($fieldType == "file_grid")
                        {
                            $this->exportData['data'][$i][$key] = ee()->sieModel->getChannelGridData($this->exportData['customFields'][$key], $entry_id);
                            if(isset($this->exportData['data'][$i][$key]) && is_array($this->exportData['data'][$i][$key]) && count($this->exportData['data'][$i][$key]) > 0)
                            {

                                for ($gridCount = 0; $gridCount < count($this->exportData['data'][$i][$key]); $gridCount++)
                                {
                                    
                                    foreach ($this->exportData['data'][$i][$key][$gridCount] as $gridKey => $gridValue)
                                    {

                                        if(isset($this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type']))
                                        {

                                            $gridFieldType = $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type'];
                                            if($gridFieldType == "assets")
                                            {

                                                if($gridValue != "")
                                                {
                                                    $where = array(
                                                        'entry_id'  => $entry_id, 
                                                        'field_id'  => $this->exportData['customFields'][$key]['field_id'],
                                                        'row_id'    => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'col_id'    => $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']
                                                    );
                                                    
                                                    $ret = ee()->sieModel->getAssetsData($where, $this->exportData['parseFiles']);
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;
                                                }

                                            }
                                            elseif($gridFieldType == "file")
                                            {
                                                if($gridValue != ""){
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = $this->replaceFileDir($this->exportData['parseFiles'], $gridValue);
                                                }
                                            }
                                            elseif($gridFieldType == "relationship")
                                            {
                                                
                                                $fieldRet = $this->exportData['exportSettings']['settings']['grid_relationship'][$this->exportData['customFields'][$key]['field_id']][$this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']];
                                                $where = array(
                                                        'parent_id'     => $entry_id, 
                                                        'grid_field_id' => $this->exportData['customFields'][$key]['field_id'],
                                                        'grid_row_id'   => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'grid_col_id'   => $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']
                                                    );
                                                $ret = ee()->sieModel->getRelationshipsData($where, $fieldRet);
                                                $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;

                                            }

                                        }

                                    }

                                    unset($this->exportData['data'][$i][$key][$gridCount]['row_id']);
                                }

                            }

                        }
                        elseif($fieldType == "grid")
                        {

                            $this->exportData['data'][$i][$key] = ee()->sieModel->getChannelGridData($this->exportData['customFields'][$key], $entry_id);
                            if(isset($this->exportData['data'][$i][$key]) && is_array($this->exportData['data'][$i][$key]) && count($this->exportData['data'][$i][$key]) > 0)
                            {

                                for ($gridCount = 0; $gridCount < count($this->exportData['data'][$i][$key]); $gridCount++)
                                {
                                    
                                    foreach ($this->exportData['data'][$i][$key][$gridCount] as $gridKey => $gridValue)
                                    {

                                        if(isset($this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type']))
                                        {

                                            $gridFieldType = $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type'];
                                            if($gridFieldType == "assets")
                                            {

                                                if($gridValue != "")
                                                {
                                                    $where = array(
                                                        'entry_id'  => $entry_id, 
                                                        'field_id'  => $this->exportData['customFields'][$key]['field_id'],
                                                        'row_id'    => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'col_id'    => $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']
                                                    );
                                                    
                                                    $ret = ee()->sieModel->getAssetsData($where, $this->exportData['parseFiles']);
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;
                                                }

                                            }
                                            elseif($gridFieldType == "file")
                                            {
                                                if($gridValue != ""){
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = $this->replaceFileDir($this->exportData['parseFiles'], $gridValue);
                                                }
                                            }
                                            elseif($gridFieldType == "relationship")
                                            {
                                                
                                                $fieldRet = $this->exportData['exportSettings']['settings']['grid_relationship'][$this->exportData['customFields'][$key]['field_id']][$this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']];
                                                $where = array(
                                                        'parent_id'     => $entry_id, 
                                                        'grid_field_id' => $this->exportData['customFields'][$key]['field_id'],
                                                        'grid_row_id'   => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'grid_col_id'   => $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_id']
                                                    );
                                                $ret = ee()->sieModel->getRelationshipsData($where, $fieldRet);
                                                $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;

                                            }

                                        }

                                    }

                                    unset($this->exportData['data'][$i][$key][$gridCount]['row_id']);
                                }

                            }

                        }
                        elseif($fieldType == "matrix")
                        {

                            $this->exportData['data'][$i][$key] = ee()->sieModel->getChannelMatrixData($this->exportData['customFields'][$key], $entry_id);

                            if(isset($this->exportData['data'][$i][$key]) && is_array($this->exportData['data'][$i][$key]) && count($this->exportData['data'][$i][$key]) > 0)
                            {

                                for ($gridCount = 0; $gridCount < count($this->exportData['data'][$i][$key]); $gridCount++)
                                {
                                    
                                    foreach ($this->exportData['data'][$i][$key][$gridCount] as $gridKey => $gridValue)
                                    {

                                        if(isset($this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_type']))
                                        {

                                            $gridFieldType = $this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_type'];
                                            if($gridFieldType == "assets")
                                            {

                                                if($gridValue != "")
                                                {
                                                    $where = array(
                                                        'entry_id'  => $entry_id, 
                                                        'field_id'  => $this->exportData['customFields'][$key]['field_id'],
                                                        'row_id'    => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'col_id'    => $this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_id']
                                                    );
                                                    $ret = ee()->sieModel->getAssetsData($where, $this->exportData['parseFiles']);
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;
                                                }

                                            }
                                            elseif($gridFieldType == "file")
                                            {
                                                if($gridValue != ""){
                                                    $this->exportData['data'][$i][$key][$gridCount][$gridKey] = $this->replaceFileDir($this->exportData['parseFiles'], $gridValue);
                                                }
                                            }
                                            elseif($gridFieldType == "playa")
                                            {
                                                
                                                $fieldRet = $this->exportData['exportSettings']['settings']['matrix_playa'][$this->exportData['customFields'][$key]['field_id']][$this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_id']];
                                                $where = array(
                                                        'parent_entry_id'   => $entry_id, 
                                                        'parent_field_id'   => $this->exportData['customFields'][$key]['field_id'],
                                                        'parent_row_id'     => $this->exportData['data'][$i][$key][$gridCount]['row_id'],
                                                        'parent_col_id'     => $this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_id']
                                                    );
                                                
                                                $ret = ee()->sieModel->getPlayaData($where, $fieldRet);
                                                $this->exportData['data'][$i][$key][$gridCount][$gridKey] = ($ret === false) ? "" : $ret;

                                            }

                                        }

                                    }

                                    unset($this->exportData['data'][$i][$key][$gridCount]['row_id']);
                                }

                            }

                        }
                        elseif($fieldType == "fluid_field")
                        {

                            $field_id   = $this->exportData['customFields'][$key]['field_id'];
                            $fluidData  = ee()->sieModel->getFluidData($field_id, $entry_id);
                            if($fluidData !== false && is_array($fluidData) && count($fluidData) > 0)
                            {
                                
                                $app = array();
                                for ($j = 0; $j < count($fluidData); $j++)
                                {
                                    
                                    if(isset($this->exportData['customFields'][$key]['fluidFields']['data'][$fluidData[$j]['field_name']]))
                                    {

                                        $fluidSubFieldData = $this->exportData['customFields'][$key]['fluidFields']['data'][$fluidData[$j]['field_name']];
                                        
                                        if($fluidSubFieldData['field_type'] == "file_grid" || $fluidSubFieldData['field_type'] == "grid")
                                        {

                                            $temp = ee()->sieModel->getChannelGridData($fluidSubFieldData, $entry_id, $fluidData[$j]);

                                            if(isset($temp) && is_array($temp) && count($temp) > 0)
                                            {

                                                for ($gridCount = 0; $gridCount < count($temp); $gridCount++)
                                                {
                                                    
                                                    foreach ($temp[$gridCount] as $gridKey => $gridValue)
                                                    {

                                                        if(isset($fluidSubFieldData['gridFields']['data'][$gridKey]['col_type']))
                                                        {

                                                            $gridFieldType = $fluidSubFieldData['gridFields']['data'][$gridKey]['col_type'];
                                                            if($gridFieldType == "assets")
                                                            {

                                                                if($gridValue != "")
                                                                {
                                                                    $where = array(
                                                                        'entry_id'  => $entry_id, 
                                                                        'field_id'  => $fluidData[$j]['field_id'],
                                                                        'row_id'    => $temp[$gridCount]['row_id'],
                                                                        'col_id'    => $fluidSubFieldData['gridFields']['data'][$gridKey]['col_id']
                                                                    );
                                                                    
                                                                    $ret = ee()->sieModel->getAssetsData($where, $this->exportData['parseFiles']);
                                                                    $temp[$gridCount][$gridKey] = ($ret === false) ? "" : $ret;
                                                                }

                                                            }
                                                            elseif($gridFieldType == "file")
                                                            {
                                                                if($gridValue != ""){
                                                                    $temp[$gridCount][$gridKey] = $this->replaceFileDir($this->exportData['parseFiles'], $gridValue);
                                                                }
                                                            }
                                                            elseif($gridFieldType == "relationship")
                                                            {

                                                                if(isset($this->exportData['exportSettings']['settings']['fluid_field'][$this->exportData['customFields'][$key]['field_id']][$fluidSubFieldData['gridFields']['data'][$gridKey]['col_id']]))
                                                                {
                                                                    $fieldRet = $this->exportData['exportSettings']['settings']['fluid_field'][$this->exportData['customFields'][$key]['field_id']][$fluidSubFieldData['gridFields']['data'][$gridKey]['col_id']];
                                                                }
                                                                else
                                                                {
                                                                    $fieldRet = "title";
                                                                }

                                                                $where = array(
                                                                        'parent_id'     => $entry_id,
                                                                        'grid_field_id' => $fluidData[$j]['field_id'],
                                                                        'grid_row_id'   => $temp[$gridCount]['row_id'],
                                                                        'grid_col_id'   => $fluidSubFieldData['gridFields']['data'][$gridKey]['col_id']
                                                                    );
                                                                $ret = ee()->sieModel->getRelationshipsData($where, $fieldRet, $fluidData[$j]);
                                                                $temp[$gridCount][$gridKey] = ($ret === false) ? "" : $ret;

                                                            }

                                                        }

                                                    }

                                                    unset($temp[$gridCount]['row_id']);
                                                }

                                            }
                                            else
                                            {
                                                $temp = "";
                                            }
                                            
                                            $app[] = array($fluidData[$j]['field_name'] => $temp);

                                        }
                                        elseif($fluidSubFieldData['field_type'] == "relationship")
                                        {

                                            if(isset($this->exportData['exportSettings']['settings']['fluid_field'][$this->exportData['customFields'][$key]['field_id']][$fluidSubFieldData['field_id']]))
                                            {
                                                $fieldRet = $this->exportData['exportSettings']['settings']['fluid_field'][$this->exportData['customFields'][$key]['field_id']][$fluidSubFieldData['field_id']];
                                            }
                                            else
                                            {
                                                $fieldRet = "title";
                                            }

                                            $where = array(
                                                'parent_id' => $entry_id,
                                                'field_id'  => $fluidData[$j]['field_id']
                                            );
                                            $ret = ee()->sieModel->getRelationshipsData($where, $fieldRet, $fluidData[$j]);
                                            $app[] = array($fluidData[$j]['field_name'] => ($ret === false) ? "" : $ret);

                                        }
                                        else
                                        {

                                            $temp = ee()->sieModel->getNormalFluidFieldData($fluidData[$j], $fluidSubFieldData);
                                            if($fluidSubFieldData['field_type'] == "file" && is_array($temp) && count($temp) > 0)
                                            {
                                                foreach ($temp as $fileKey => $fileValue)
                                                {
                                                    $temp[$fileKey] = $this->replaceFileDir($this->exportData['parseFiles'], $fileValue);
                                                }
                                            }
                                            $app[] = $temp;

                                        }

                                    }

                                }
                                
                                $this->exportData['data'][$i][$key] = $app;
                            }
                            else
                            {
                                $this->exportData['data'][$i][$key] = "";
                            }

                        }
                        elseif($fieldType == "relationship")
                        {

                            $where = array(
                                'parent_id' => $entry_id,
                                'field_id' => $this->exportData['customFields'][$key]['field_id']
                            );
                            $fieldRet = $this->exportData['exportSettings']['settings']['relationship_field'][$this->exportData['customFields'][$key]['field_id']];
                            $ret = ee()->sieModel->getRelationshipsData($where, $fieldRet);
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;

                        }
                        elseif($fieldType == "playa")
                        {

                            $where = array(
                                'parent_entry_id' => $entry_id,
                                'parent_field_id' => $this->exportData['customFields'][$key]['field_id']
                            );
                            $fieldRet = $this->exportData['exportSettings']['settings']['playa_field'][$this->exportData['customFields'][$key]['field_id']];
                            
                            $ret = ee()->sieModel->getPlayaData($where, $fieldRet);
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;

                        }
                        elseif($fieldType == "file")
                        {
                            if($value != ""){
                                $this->exportData['data'][$i][$key] = $this->replaceFileDir($this->exportData['parseFiles'], $value);
                            }
                        }

                    }

                }

                /* Add categories (Group wise) in export */
                if(isset($this->exportData['categoryGroups']) && is_array($this->exportData['categoryGroups']) && count($this->exportData['categoryGroups']) > 0)
                {

                    for ($j = 0; $j < count($this->exportData['categoryGroups']); $j++)
                    {
                        
                        $temp = ee()->sieModel->getAllCategories($entry_id, $this->exportData['categoryGroups'][$j]['group_id']);
                        if($temp !== false && is_array($temp) && count($temp) > 0)
                        {
                            // $temp = $this->setupCategories($temp);
                            $temp = $this->getStringCats($temp);
                            $this->exportData['data'][$i]['category_'.$this->exportData['categoryGroups'][$j]['short_name']] = $temp;
                        }
                        else
                        {
                            $this->exportData['data'][$i]['category_'.$this->exportData['categoryGroups'][$j]['short_name']] = "";
                        }

                    }

                }

                if($includeSeoLite === true)
                {
                    $selLiteFields = ee()->sieModel->getSeoLiteData($entry_id);
                    
                    if(isset($selLiteFields) && is_array($selLiteFields) && count($selLiteFields) > 0){
                        foreach ($selLiteFields as $seoKey => $seoValue) {
                            $this->exportData['data'][$i][$seoKey] = $seoValue;
                        }
                    }else{
                        $this->exportData['data'][$i]['seo_lite_title']         = "";
                        $this->exportData['data'][$i]['seo_lite_keywords']      = "";
                        $this->exportData['data'][$i]['seo_lite_description']   = "";
                    }
                }

                if($includeSmartSeo === true){
                    $smart_seo_tab_data = $smart_seo_tab->display($this->exportData['exportSettings']['settings']['channel_id'], $entry_id);
                    foreach($smart_seo_tab_data as $smart_seo_field_data){
                        $this->exportData['data'][$i]['smart_seo_'.$smart_seo_field_data['field_id']] = $smart_seo_field_data['field_data'];
                    }
                }

                if($includeSEEO === true){
                    $seeo_tab_data = $seeo_tab->display($this->exportData['exportSettings']['settings']['channel_id'], $entry_id);
                    foreach($seeo_tab_data as $seeo_field_data){
                        $this->exportData['data'][$i]['seeo_'.$seeo_field_data['field_id']] = $seeo_field_data['field_data'];
                    }
   
                }

                if($includeStructure === true){
                    $structure_tab_data = $structure_tab->display($this->exportData['exportSettings']['settings']['channel_id'], $entry_id);
                    foreach($structure_tab_data as $structure_field_data){
                        $this->exportData['data'][$i]['structure_'.$structure_field_data['field_id']] = is_array($structure_field_data['field_data'])?json_encode($structure_field_data['field_data']):$structure_field_data['field_data'];
                    }
   
                }

                if($includeTranscribe === true){
                    $transcribe_tab_data = $transcribe_tab->display($this->exportData['exportSettings']['settings']['channel_id'], $entry_id);
                    foreach($transcribe_tab_data as $transcribe_field_data){
                        $this->exportData['data'][$i][$transcribe_field_data['field_id']] = $transcribe_field_data['field_data'];
                    }
   
                }



                /* Add pages URI in export */
                if(isset($this->exportData['pagesData']['uris']) && is_array($this->exportData['pagesData']['uris']) && isset($this->exportData['pagesData']['templates']) && is_array($this->exportData['pagesData']['templates']))
                {

                    if(isset($this->exportData['pagesData']['uris'][$entry_id]) && isset($this->exportData['pagesData']['templates'][$entry_id]))
                    {
                        $this->exportData['data'][$i]['pages_uri']      = $this->exportData['pagesData']['uris'][$entry_id];
                        $this->exportData['data'][$i]['pages_template'] = $this->exportData['pagesData']['templates'][$entry_id];
                    }
                    else
                    {
                        $this->exportData['data'][$i]['pages_uri']      = "";
                        $this->exportData['data'][$i]['pages_template'] = "";
                    }

                }

            }

        }
        else
        {
            if( ! isset($this->exportData['generalSettings']['general']['force_export_file']) || (isset($this->exportData['generalSettings']['general']['force_export_file']) && $this->exportData['generalSettings']['general']['force_export_file'] == "n"))
            {
                return false;
            }
        }
        /* Check what user wants in export file. */
        if(strtolower($this->exportData['exportSettings']['format']) == "xml")
        {
            return $this->generateXML();
        }
        else if(strtolower($this->exportData['exportSettings']['format']) == "json")
        {
            return $this->generateJSON();
        }
        else
        {
            return $this->generateCSV();
        }

    }

    /**
    * Generate CSV file
    * @param $delim Default delim
    * @param $newline Default newline
    * @param $enclosure Default enclosure
    * @return download export file or return base64 data of given exported query result if AJAX
    **/
    function generateCSV($delim = ",", $newline = "\n", $enclosure = '"')
    {

        //V3.1.5 : Solve issue of non encode characters
        if( ! isset($this->exportData['generalSettings']['general']['disable_ob_function']) || (isset($this->exportData['generalSettings']['general']['disable_ob_function']) && $this->exportData['generalSettings']['general']['disable_ob_function'] == "n"))
        {
            @ob_clean();
            @ob_start();
        }

        $this->delim        = $delim;
        $this->newline      = $newline;
        $this->enclosure    = $enclosure;
        $out                = '';

        if($this->type != "ajax" || (isset($this->exportData['offset']) && $this->exportData['offset'] == 0)){
            $keys = array_keys($this->exportData['data'][0]);
            foreach ($keys as $name)
            {
                $out .= $this->enclose($name);
            }
            $out .= $newline;
            unset($keys);
        }
        
        $search     = array('"');
        $replace    = array("\"");
        if(is_array($this->exportData['data']) && count($this->exportData['data']) > 0)
        {
            for ($i = 0; $i < count($this->exportData['data']); $i++)
            {

                foreach ($this->exportData['data'][$i] as $key => $value)
                {

                    if(isset($this->exportData['generalSettings']['general']['encode_content']) && $this->exportData['generalSettings']['general']['encode_content'] != "")
                    {
                        if($this->exportData['generalSettings']['general']['encode_content'] == "encode_utf_8")
                        {
                            $value = $this->encodeUTF8($value);
                        }
                        elseif($this->exportData['generalSettings']['general']['encode_content'] == "decode_utf_8")
                        {
                            $value = $this->decodeUTF8($value);
                        }
                    }

                    if(is_array($value))
                    {
                        array_walk_recursive($value, array($this, 'findAndChangeTimestamp'));
                    }
                    else
                    {
                        $this->findAndChangeTimestamp($value);
                    }
                    
                    if(isset($this->exportData['customFields'][$key]['field_type']))
                    {
                        $fieldType = $this->exportData['customFields'][$key]['field_type'];
                        
                        if($fieldType == "file_grid" || $fieldType == "grid" || $fieldType == "matrix" || $fieldType == "fluid_field")
                        {
                            if(is_array($value) && count($value) > 0)
                            {

                                $tempSearch     = array('"', "\n", '\n');
                                $tempReplace    = array("\"", "", "");
                                if(isset($this->exportData['generalSettings']['csv']['encode_for_array']) && $this->exportData['generalSettings']['csv']['encode_for_array'] != "")
                                {

                                    switch ($this->exportData['generalSettings']['csv']['encode_for_array'])
                                    {
                                        case "serialize":
                                            $value = serialize(json_decode(str_replace($tempSearch, $tempReplace, json_encode($value)), true));
                                            break;

                                        case "json_base64":
                                            $value = base64_encode(str_replace($tempSearch, $tempReplace, json_encode($value)));
                                            break;

                                        case "serialize_base64":
                                            $value = base64_encode(serialize(json_decode(str_replace($tempSearch, $tempReplace, json_encode($value)), true)));
                                            break;
                                        
                                        case "json":
                                        default:
                                            $value = str_replace($tempSearch, $tempReplace, json_encode($value));
                                            break;
                                    }

                                }
                                else
                                {
                                    $value = str_replace($tempSearch, $tempReplace, json_encode($value));
                                }
                            }
                        }
                        else
                        {
                            if(is_array($value))
                            {
                                
                                if(count($value) > 0)
                                {
                                    if($fieldType == "channel_files" || $fieldType == "channel_images" || $fieldType == "channel_videos")
                                    {
                                        $tempArray = array();
                                        for ($j = 0; $j < count($value); $j++)
                                        {
                                            $tempArray[] = $value[$j]['filename'];
                                        }

                                        if(isset($this->exportData['generalSettings']['csv']['separator_for_array_entities']) && $this->exportData['generalSettings']['csv']['separator_for_array_entities'] != "")
                                        {
                                            $value = implode($this->exportData['generalSettings']['csv']['separator_for_array_entities'], $tempArray);
                                        }
                                        else
                                        {
                                            $value = implode(",", $tempArray);
                                        }
                                    }
                                    else
                                    {
                                        if(is_array($value) && count($value) > 0)
                                        {
                                            if(isset($this->exportData['generalSettings']['csv']['separator_for_array_entities']) && $this->exportData['generalSettings']['csv']['separator_for_array_entities'] != "")
                                            {
                                                $value = implode($this->exportData['generalSettings']['csv']['separator_for_array_entities'], $value);
                                            }
                                            else
                                            {
                                                $value = implode(",", $value);
                                            }
                                        }
                                    }

                                }
                                else
                                {
                                    $value = "";
                                }

                            }
                            else
                            {
                                $value = str_replace($search, $replace, $value);
                            }
                        }
                    }
                    else
                    {
                        $value = str_replace($search, $replace, $value);
                    }
                    
                    $out .= $this->enclose($value);

                }

                $out = rtrim($out);
                $out .= $newline;

            }
        }

        if($this->type == "ajax")
        {
            return $this->ajaxFileHandler("csv", $out);
            exit();
        }
        else
        {
            $now = gmdate("D, d M Y H:i:s");
            header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
            header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
            header("Last-Modified: {$now} GMT");

            // force download
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');

            // disposition / encoding on response body
            header('Content-Disposition: attachment;filename=smart_import_export_'.$this->exportData['exportSettings']['id'].'.csv');
            header('Content-Transfer-Encoding: binary');
            echo $out;

            exit(ob_get_clean());
            
        }

    }

    function encodeUTF8($array)
    {

        if(is_array($array))
        {
            foreach($array as $key => $value)
            {
                if(is_array($value))
                {
                    $array[$key] = $this->encodeUTF8($value);
                }
                else
                {
                    $array[$key] = mb_convert_encoding($value, 'HTML-ENTITIES', "UTF-8");
                }
            }
        }
        else
        {
            /*return mb_convert_encoding($array, 'UTF-8', "auto");*/
            $array = mb_convert_encoding($array, 'HTML-ENTITIES', "UTF-8");
        }

        return $array;
    }

    function decodeUTF8($array)
    {

        if(is_array($array))
        {
            foreach($array as $key => $value)
            {
                if(is_array($value))
                {
                    $array[$key] = $this->decodeUTF8($value);
                }
                else
                {
                    $array[$key] = html_entity_decode(preg_replace("/U\+([0-9A-F]{4})/", "&#x\\1;", $value), ENT_NOQUOTES, 'UTF-8');
                }
            }
        }
        else
        {
            $array = html_entity_decode(preg_replace("/U\+([0-9A-F]{4})/", "&#x\\1;", $array), ENT_NOQUOTES, 'UTF-8');
        }

        return $array;
    }

    function findAndChangeTimestamp(&$item, $key="")
    {
        if (strlen($item) === 10 && ((string) (int) $item === $item) && ($item <= PHP_INT_MAX) && ($item >= ~PHP_INT_MAX) && isset($this->exportData['generalSettings']['general']['convert_all_dates']) && $this->exportData['generalSettings']['general']['convert_all_dates'] != "")
        {
            $item = date($this->exportData['generalSettings']['general']['convert_all_dates'], $item);
        }
        if(isset($this->exportData['generalSettings']['general']['covert_html_entities']) && $this->exportData['generalSettings']['general']['covert_html_entities'] == "y")
        {
            $item = htmlentities($item);
        }

        $item = str_replace($this->search, $this->replace, $item);
    }

    function handleGeneralSettingsForm($vars)
    {

        $vars['data'] = ee()->sieModel->getGeneralSettings();
        $vars['sections'] = array(
            array(
                array(
                    'fields' => array(
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => $vars['data']['id'],
                            'required'  => TRUE
                        )
                    ),
                    'attrs' => array(
                        'class' => 'last hidden',
                    ),
                ),
            ),

            'general_settings' => array(
                array(
                    'title'     => 'encode_content_label',
                    'desc'      => 'encode_content_desc',
                    'fields' => array(
                        'general-encode_content' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'n'              => lang('no_encode_decode'),
                                'encode_utf_8'  => lang('encode_utf_8'),
                                'decode_utf_8'  => lang('decode_utf_8'),
                            ),
                            'value' => (isset($vars['data']['settings']['general']['encode_content'])) ? $vars['data']['settings']['general']['encode_content'] : "",
                        )
                    )
                ),
                array(
                    'title'     => 'convert_all_dates_label',
                    'desc'      => 'convert_all_dates_desc',
                    'fields' => array(
                        'general-convert_all_dates' => array(
                            'type' => 'text',
                            'value' => (isset($vars['data']['settings']['general']['convert_all_dates'])) ? $vars['data']['settings']['general']['convert_all_dates'] : "",
                        )
                    )
                ),
                array(
                    'title'     => 'covert_html_entities_label',
                    'desc'      => 'covert_html_entities_desc',
                    'fields' => array(
                        'general-covert_html_entities' => array(
                            'type' => 'yes_no',
                            'value' => (isset($vars['data']['settings']['general']['covert_html_entities'])) ? $vars['data']['settings']['general']['covert_html_entities'] : "",
                        )
                    )
                ),
                array(
                    'title'     => 'force_export_file_label',
                    'desc'      => 'force_export_file_desc',
                    'fields' => array(
                        'general-force_export_file' => array(
                            'type' => 'yes_no',
                            'value' => (isset($vars['data']['settings']['general']['force_export_file'])) ? $vars['data']['settings']['general']['force_export_file'] : "",
                        )
                    )
                ),
                array(
                    'title'     => 'disable_ob_function_label',
                    'desc'      => 'disable_ob_function_desc',
                    'fields' => array(
                        'general-disable_ob_function' => array(
                            'type' => 'yes_no',
                            'value' => (isset($vars['data']['settings']['general']['disable_ob_function'])) ? $vars['data']['settings']['general']['disable_ob_function'] : "",
                        )
                    )
                ),
            ),
            'csv_settings' => array(
                array(
                    'title'     => 'separator_for_array_entities_label',
                    'desc'      => 'separator_for_array_entities_desc',
                    'fields' => array(
                        'csv-separator_for_array_entities' => array(
                            'type' => 'text',
                            'value' => (isset($vars['data']['settings']['csv']['separator_for_array_entities'])) ? $vars['data']['settings']['csv']['separator_for_array_entities'] : ", ",
                        )
                    )
                ),
                array(
                    'title'     => 'encode_for_array_label',
                    'desc'      => 'encode_for_array_desc',
                    'fields' => array(
                        'csv-encode_for_array' => array(
                            'type'      => 'inline_radio',
                            "choices"   => array(
                                'json'              => "JSON",
                                'serialize'         => "Serialize",
                                'json_base64'       => "JSON + Base64 Encode",
                                'serialize_base64'  => "Serialize + Base64 Encode",
                            ),
                            'value'     => (isset($vars['data']['settings']['csv']['encode_for_array'])) ? $vars['data']['settings']['csv']['encode_for_array'] : "json",
                        )
                    )
                ),
            ),
            'xml_settings' => array(
                array(
                    'title'     => 'root_tag_name_label',
                    'desc'      => 'root_tag_name_desc',
                    'fields' => array(
                        'xml-root_tag_name' => array(
                            'type' => 'text',
                            'value' => (isset($vars['data']['settings']['xml']['root_tag_name'])) ? $vars['data']['settings']['xml']['root_tag_name'] : "root",
                        )
                    )
                ),
                array(
                    'title'     => 'element_tags_name_label',
                    'desc'      => 'element_tags_name_desc',
                    'fields' => array(
                        'xml-element_tags_name' => array(
                            'type' => 'text',
                            'value' => (isset($vars['data']['settings']['xml']['element_tags_name'])) ? $vars['data']['settings']['xml']['element_tags_name'] : "root",
                        )
                    )
                ),
            ),
        );
        $vars += array(
            'base_url' => $this->url('export_general_settings'),
            'cp_page_title' => lang('general_settings'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return $vars;

    }

    function handleGeneralSettingsFormPost()
    {

        $rules = array(
            'general-encode_content'           => 'required',
            'general-covert_html_entities'     => 'required',
            'csv-separator_for_array_entities' => 'required',
            'csv-encode_for_array'             => 'required',
            'xml-root_tag_name'                => 'required',
            'xml-element_tags_name'            => 'required',
        );

        $result = ee('Validation')->make($rules)->validate($_POST);

        if (! $result->isValid())
        {
            return $result;
        }

        $temp = array();
        $data = array();
        foreach ($_POST as $key => $value)
        {
            if(strpos($key, "-"))
            {
                $keys = explode('-', $key);
                $temp[$keys[0]][$keys[1]] = $value;
            }
        }

        $data['id']         = $_POST['id'];
        $data['settings']   = base64_encode(serialize($temp));
        ee()->sieModel->saveGeneralSettings($data);
        
        return true;

    }

    /**
    * This function handles ajax responses. To return wither pagination or file URL to download given exported file.
    * @param $fileType XML or CSV
    * @param $out String to save in that file
    * @return base64 data of given exported query result if AJAX
    **/
    function ajaxFileHandler($fileType, $out)
    {
        $moduleThemeUrl     = URL_THIRD_THEMES . "smart_import_export/temp";
        $moduleThemePath    = PATH_THIRD_THEMES . "smart_import_export/temp";

        if (!is_dir($moduleThemePath)) {
            mkdir($moduleThemePath, 0777, TRUE);
        }
        
        $filename = "smart_import_export_".$this->exportData['exportSettings']['id'] . "." . $fileType;
        if($this->exportData['offset'] == 0){
            @unlink($moduleThemePath . "/" . $filename);
        }

        if ($fileType == 'json')
        {
            $operation = 'c';
        }
        else{
            $operation = 'a';
        }

        $handle = fopen($moduleThemePath . "/" . $filename, $operation) or die('Cannot open file:  '.$filename);
        
        if ($fileType == 'json')
        {
            fseek($handle, 0, SEEK_END);
        }

        if ($fileType == 'json' && ftell($handle) > 0)
        {
            // move back a byte
            fseek($handle, -1, SEEK_END);

            // add the trailing comma
            fwrite($handle, ',', 1);

            // add the new json string
            fwrite($handle, substr($out, '1'));
        }
        else{
            fwrite($handle, $out);
        }

        fclose($handle);
        @chmod($moduleThemePath . "/" . $filename, 0777);

        $ret = array(
            'status'    => 'pending',
            'offset'    => $this->exportData['offset'] + $this->exportData['limit'],
            'limit'     => $this->exportData['limit'],
            'totalrows' => $this->exportData['totalRows']
        );

        if(($this->exportData['offset'] + $this->exportData['limit']) >= $this->exportData['totalRows']){
            $ret['status']  = "completed";
            $ret['url']     = $moduleThemeUrl . "/" . $filename;
        }else{
            $query = $_GET;
            $query['offset'] = $ret['offset'];
            $query_result = http_build_query($query);
            $ret['next_batch'] = $_SERVER['PHP_SELF'] ."?". $query_result;
        }
        
        unset($this->exportData);
        return base64_encode(json_encode($ret));
        exit();

    }

    /**
    * Generate XML file
    * @return download export file or return base64 data of given exported query result if AJAX
    **/
    function generateXML()
    {
        
        //V3.1.5 : Solve issue of non encode characters
        if( ! isset($this->exportData['generalSettings']['general']['disable_ob_function']) || (isset($this->exportData['generalSettings']['general']['disable_ob_function']) && $this->exportData['generalSettings']['general']['disable_ob_function'] == "n"))
        {
            @ob_clean();
            @ob_start();
        }

        // Set our default values
        foreach (array('root' => 'root', 'element' => 'element', 'newline' => "\n", 'tab' => "\t") as $key => $val)
        {
            if ( ! isset($params[$key]))
            {
                $params[$key] = $val;
            }
        }

        // Create variables for convenience
        extract($params);
        if(isset($this->exportData['generalSettings']['xml']['root_tag_name']) && $this->exportData['generalSettings']['xml']['root_tag_name'] != "")
        {
            $root = $this->exportData['generalSettings']['xml']['root_tag_name'];
        }
        if(isset($this->exportData['generalSettings']['xml']['element_tags_name']) && $this->exportData['generalSettings']['xml']['element_tags_name'] != "")
        {
            $element = $this->exportData['generalSettings']['xml']['element_tags_name'];
        }
        $xml = "";
        
        if($this->type != "ajax" || (isset($this->exportData['offset']) && $this->exportData['offset'] == 0)){
            $xml .= '<?xml version="1.0"?>'.$newline;
            $xml .= "<{$root}>" . $newline;
        }
        
        if(is_array($this->exportData['data']) && count($this->exportData['data']) > 0)
        {
            for ($i = 0; $i < count($this->exportData['data']); $i++)
            {

                $xml .= $tab."<{$element}>" . $newline;
                foreach ($this->exportData['data'][$i] as $key => $value)
                {

                    if(isset($this->exportData['generalSettings']['general']['encode_content']) && $this->exportData['generalSettings']['general']['encode_content'] != "")
                    {
                        if($this->exportData['generalSettings']['general']['encode_content'] == "encode_utf_8")
                        {
                            $value = $this->encodeUTF8($value);
                        }
                        elseif($this->exportData['generalSettings']['general']['encode_content'] == "decode_utf_8")
                        {
                            $value = $this->decodeUTF8($value);
                        }
                    }

                    if(is_array($value))
                    {
                        array_walk_recursive($value, array($this, 'findAndChangeTimestamp'));
                    }
                    else
                    {
                        $this->findAndChangeTimestamp($value);
                    }

                    if(isset($this->exportData['customFields'][$key]['field_type']))
                    {
                        $fieldType = $this->exportData['customFields'][$key]['field_type'];
                        
                        if($fieldType == "grid" || $fieldType == "matrix")
                        {
                            
                            if(isset($value) && is_array($value) && count($value) > 0)
                            {

                                $xml .= $tab . $tab . "<{$key}>" . $newline;
                                for ($gridCount = 0; $gridCount < count($value); $gridCount++)
                                {
                                    
                                    $xml .= $tab . $tab . $tab . "<item>" . $newline;
                                    foreach ($value[$gridCount] as $gridKey => $gridValue)
                                    {

                                        if(isset($this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type']) || isset($this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_type']))
                                        {

                                            if($fieldType == "grid")
                                            {
                                                $gridFieldType = $this->exportData['customFields'][$key]['gridFields']['data'][$gridKey]['col_type'];
                                            }
                                            else
                                            {
                                                $gridFieldType = $this->exportData['customFields'][$key]['matrixFields']['data'][$gridKey]['col_type'];
                                            }

                                            if($gridFieldType == "assets" || $gridFieldType == "relationship" || $gridFieldType == "playa")
                                            {

                                                if(isset($gridValue) && is_array($gridValue))
                                                {

                                                    $xml .= $tab . $tab . $tab . $tab . "<{$gridKey}>" . $newline;
                                                    for ($j = 0; $j < count($gridValue); $j++)
                                                    {

                                                        if(is_array($gridValue[$j]))
                                                        {

                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<item>" . $newline;
                                                            foreach ($gridValue[$j] as $key1 => $value1)
                                                            {
                                                                $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                                            }
                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "</item>" . $newline;

                                                        }
                                                        else
                                                        {
                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<item><![CDATA[" . $gridValue[$j] . "]]></item>" . $newline;
                                                        }

                                                    }
                                                    $xml .= $tab . $tab . $tab . $tab . "</{$gridKey}>" . $newline;

                                                }
                                                else
                                                {
                                                    $xml .= $tab . $tab . $tab . $tab . "<{$gridKey}><![CDATA[" . $gridValue . "]]></{$gridKey}>" . $newline;
                                                }
                                            }
                                            else
                                            {
                                                $xml .= $tab . $tab . $tab . $tab . "<{$gridKey}><![CDATA[" . $gridValue . "]]></{$gridKey}>" . $newline;
                                            }
                                        }

                                    }
                                    $xml .= $tab . $tab . $tab . "</item>" . $newline;

                                }
                                $xml .= $tab . $tab . "</{$key}>" . $newline;

                            }
                            else
                            {
                                $xml .= $tab . $tab . "<{$key}><![CDATA[" . $value . "]]></{$key}>" . $newline;
                            }

                        }
                        elseif($fieldType == "fluid_field")
                        {

                            $xml .= $tab . $tab . "<{$key}>";
                            
                            if(is_array($value) && count($value) > 0)
                            {

                                $xml .= $newline;
                                
                                for ($k = 0; $k < count($value); $k++)
                                {
                                    
                                    if(isset($value[$k]))
                                    {

                                        foreach ($value[$k] as $key1 => $value1)
                                        {

                                            if(is_array($value1))
                                            {

                                                $fieldType = $this->exportData['customFields'][$key]['fluidFields']['data'][$key1]['field_type'];
                                                if($fieldType == "grid" || $fieldType == "matrix")
                                                {

                                                    if(isset($value1) && is_array($value1) && count($value1) > 0)
                                                    {

                                                        $xml .= $tab . $tab . $tab . "<{$key1}>" . $newline;
                                                        for ($gridCount = 0; $gridCount < count($value1); $gridCount++)
                                                        {
                                                            
                                                            $xml .= $tab . $tab . $tab . $tab . "<item>" . $newline;
                                                            foreach ($value1[$gridCount] as $gridKey => $gridValue)
                                                            {

                                                                if(isset($this->exportData['customFields'][$key1]['gridFields']['data'][$gridKey]['col_type']) || isset($this->exportData['customFields'][$key1]['matrixFields']['data'][$gridKey]['col_type']))
                                                                {

                                                                    if($fieldType == "grid")
                                                                    {
                                                                        $gridFieldType = $this->exportData['customFields'][$key1]['gridFields']['data'][$gridKey]['col_type'];
                                                                    }
                                                                    else
                                                                    {
                                                                        $gridFieldType = $this->exportData['customFields'][$key1]['matrixFields']['data'][$gridKey]['col_type'];
                                                                    }

                                                                    if($gridFieldType == "assets" || $gridFieldType == "relationship" || $gridFieldType == "playa")
                                                                    {

                                                                        if(isset($gridValue) && is_array($gridValue))
                                                                        {

                                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<{$gridKey}>" . $newline;
                                                                            for ($j = 0; $j < count($gridValue); $j++)
                                                                            {

                                                                                if(is_array($gridValue[$j]))
                                                                                {

                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<item>" . $newline;
                                                                                    foreach ($gridValue[$j] as $key2 => $value2)
                                                                                    {
                                                                                        $xml .= $tab . $tab . $tab . $tab . $tab . $tab . $tab . "<{$key2}><![CDATA[" . $value2 . "]]></{$key2}>" . $newline;
                                                                                    }
                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "</item>" . $newline;

                                                                                }
                                                                                else
                                                                                {
                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<item><![CDATA[" . $gridValue[$j] . "]]></item>" . $newline;
                                                                                }

                                                                            }
                                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "</{$gridKey}>" . $newline;

                                                                        }
                                                                        else
                                                                        {
                                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<{$gridKey}><![CDATA[" . $gridValue . "]]></{$gridKey}>" . $newline;
                                                                        }
                                                                    }
                                                                    else
                                                                    {
                                                                        $xml .= $tab . $tab . $tab . $tab . $tab . "<{$gridKey}><![CDATA[" . $gridValue . "]]></{$gridKey}>" . $newline;
                                                                    }
                                                                }

                                                            }
                                                            $xml .= $tab . $tab . $tab . $tab . "</item>" . $newline;

                                                        }
                                                        $xml .= $tab . $tab . $tab . "</{$key1}>" . $newline;

                                                    }
                                                    else
                                                    {
                                                        $xml .= $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                                    }
                                                }
                                                else
                                                {

                                                    $xml .= $tab . $tab . $tab . "<{$key1}>";
                                                    if(count($value1) > 0)
                                                    {

                                                        $xml .= $newline;
                                                        for ($j = 0; $j < count($value1); $j++)
                                                        {

                                                            if(is_array($value1[$j]))
                                                            {

                                                                $xml .= $tab . $tab . $tab . $tab . "<item>" . $newline;
                                                                foreach ($value1[$j] as $key2 => $value2)
                                                                {
                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . "<{$key2}><![CDATA[" . $value2 . "]]></{$key2}>" . $newline;
                                                                }
                                                                $xml .= $tab . $tab . $tab . $tab . "</item>" . $newline;

                                                            }
                                                            else
                                                            {
                                                                $xml .= $tab . $tab . $tab . $tab . "<item><![CDATA[" . $value1[$j] . "]]></item>" . $newline;
                                                            }

                                                        }

                                                        $xml .= $tab . $tab . $tab;
                                                    }
                                                    $xml .= "</{$key1}>" . $newline;

                                                }

                                            }
                                            else
                                            {
                                                $xml .= $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                            }

                                        }

                                    }

                                }
                                $xml .= $tab . $tab;

                            }
                            $xml .= "</{$key}>" . $newline;

                        }
                        elseif(is_array($value))
                        {

                            $xml .= $tab . $tab . "<{$key}>";
                            if(count($value) > 0)
                            {

                                $xml .= $newline;
                                for ($j = 0; $j < count($value); $j++)
                                {

                                    if(is_array($value[$j]))
                                    {

                                        $xml .= $tab . $tab . $tab . "<item>" . $newline;
                                        foreach ($value[$j] as $key1 => $value1)
                                        {
                                            $xml .= $tab . $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                        }
                                        $xml .= $tab . $tab . $tab . "</item>" . $newline;

                                    }
                                    else
                                    {
                                        $xml .= $tab . $tab . $tab . "<item><![CDATA[" . $value[$j] . "]]></item>" . $newline;
                                    }

                                }
                                
                                $xml .= $tab . $tab;
                            }
                            $xml .= "</{$key}>" . $newline;

                        }
                        else
                        {
                            $xml .= $tab . $tab . "<{$key}><![CDATA[" . $value . "]]></{$key}>" . $newline;
                        }
                    }
                    else
                    {
                            $xml .= $tab . $tab . "<{$key}><![CDATA[" . $value . "]]></{$key}>" . $newline;
                    }
                }
                $xml .= $tab."</{$element}>" . $newline;

            }
        }

        if($this->type == "ajax"){
            if(($this->exportData['offset'] + $this->exportData['limit']) >= $this->exportData['totalRows']){
                $xml .= "</{$root}>" . $newline;
            }
        }else{
            $xml .= "</{$root}>" . $newline;
        }

        if($this->type == "ajax")
        {
            return $this->ajaxFileHandler("xml", $xml);
            exit();
        }
        else
        {
            $now = gmdate("D, d M Y H:i:s");
            
            header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
            header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
            header("Last-Modified: {$now} GMT");

            header("Content-type: text/xml");

            // force download
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');

            // disposition / encoding on response body
            header('Content-Disposition: attachment;filename=smart_import_export_'.$this->exportData['exportSettings']['id'].'.xml');
            header('Content-Transfer-Encoding: binary');
            echo $xml;
            exit(ob_get_clean());
        }

    }

    /**
    * Generate JSON file
    * @return download export file or return base64 data of given exported query result if AJAX
    **/
    function generateJSON(){
        
        //V3.1.5 : Solve issue of non encode characters
        if( ! isset($this->exportData['generalSettings']['general']['disable_ob_function']) || (isset($this->exportData['generalSettings']['general']['disable_ob_function']) && $this->exportData['generalSettings']['general']['disable_ob_function'] == "n"))
        {
            @ob_clean();
            @ob_start();
        }


        $json       = array();

        $search     = array('"');
        $replace    = array("\"");
        
        if(is_array($this->exportData['data']) && count($this->exportData['data']) > 0)
        {
            for ($i = 0; $i < count($this->exportData['data']); $i++)
            {
                
                // $json_inner = array();

                foreach ($this->exportData['data'][$i] as $key => $value)
                {

                    if(isset($this->exportData['generalSettings']['general']['encode_content']) && $this->exportData['generalSettings']['general']['encode_content'] != "")
                    {
                        if($this->exportData['generalSettings']['general']['encode_content'] == "encode_utf_8")
                        {
                            $value = $this->encodeUTF8($value);
                        }
                        elseif($this->exportData['generalSettings']['general']['encode_content'] == "decode_utf_8")
                        {
                            $value = $this->decodeUTF8($value);
                        }
                    }

                    if(is_array($value))
                    {
                        array_walk_recursive($value, array($this, 'findAndChangeTimestamp'));
                    }
                    else
                    {
                        $this->findAndChangeTimestamp($value);
                    }
                    
                    if(isset($this->exportData['customFields'][$key]['field_type']))
                    {
                    
                        $fieldType = $this->exportData['customFields'][$key]['field_type'];
                        
                        if($fieldType == "file_grid" || $fieldType == "grid" || $fieldType == "matrix" || $fieldType == "fluid_field")
                        {
                            if(is_array($value) && count($value) > 0)
                            {

                                $tempSearch     = array('"', "\n", '\n');
                                $tempReplace    = array("\"", "", "");
                              
                                /*$value = str_replace($tempSearch, $tempReplace, json_encode($value));*/
                            }
                        }
                        else
                        {
                            if(is_array($value))
                            {
                                
                                if(count($value) > 0)
                                {
                                    if($fieldType == "channel_files" || $fieldType == "channel_images" || $fieldType == "channel_videos")
                                    {
                                        $tempArray = array();
                                        for ($j = 0; $j < count($value); $j++)
                                        {
                                            $tempArray[] = $value[$j]['filename'];
                                        }

                                        $value = implode(",", $tempArray);
                                    }
                                    else
                                    {
                                        if(is_array($value) && count($value) > 0)
                                        { 
                                            $value = implode(",", $value);
                                        }
                                    }

                                }
                                else
                                {
                                    $value = "";
                                }

                            }
                            else
                            {
                                $value = str_replace($search, $replace, $value);
                            }
                        }
                    }
                    else
                    {
                        $value = str_replace($search, $replace, $value);
                    }
                    
                    // $json_inner[] = array($key => $value);
                    $this->exportData['data'][$i][$key] = $value;

                }

                $json[] = $this->exportData['data'][$i];

            }
        }
        $json = array('entries' => $json);
        $json_string = json_encode($json); 

        if($this->type == "ajax")
        {
            return $this->ajaxFileHandler("json", $json_string);
            exit();
        }
        else
        {
            $now = gmdate("D, d M Y H:i:s");
            header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
            header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
            header("Last-Modified: {$now} GMT");

            // force download
            header('Content-Type: application/force-download');
            header('Content-Type: application/octet-stream');
            header('Content-Type: application/download');

            // disposition / encoding on response body
            header('Content-Disposition: attachment;filename=smart_import_export_'.$this->exportData['exportSettings']['id'].'.json');
            header('Content-Transfer-Encoding: binary');
            echo $json_string;

            exit(ob_get_clean());
            
        }
        
    }


    /**
    * Conver string array to comma separated string
    * @return String of given categories
    **/
    function getStringCats($temp)
    {
        $ret = "";
        foreach ($temp as $key => $value) {
            $ret .= $value['cat_name'] . ', ';
        }
        $ret = rtrim($ret, ', ');
        return $ret;
    }

    /**
    * Enclose CSV string to " ".
    * @param $data (String to enclose )
    * @return String of given parameter data
    **/
    function enclose($data)
    {
        return $this->enclosure.str_replace($this->enclosure, $this->enclosure.$this->enclosure, $data).$this->enclosure.$this->delim;
    }

    /**
    * Save add/edit export form setting
    * @return True or false
    **/
    function handleExportFormPost()
    {

        /* Create post array with xss cleaning */
        $data = array();
        foreach($_POST as $key => $value)
        {
            $data[$key] = ee()->input->post($key, true);
        }
        unset($data['submit']);
        unset($data['XID']);
        unset($data['csrf_token']);
        
        if(isset($data['settings']['filters']['start_date']))
        {   
            $data['settings']['filters']['start_date'] = ee()->localize->string_to_timestamp(str_replace("/", "-", date("d-m-Y", strtotime($data['settings']['filters']['start_date']))));
        }
        if(isset($data['settings']['filters']['end_date']))
        {
            $data['settings']['filters']['end_date'] = ee()->localize->string_to_timestamp(str_replace("/", "-", date("d-m-Y", strtotime($data['settings']['filters']['end_date']))));
        }
        $data['settings']       = base64_encode(serialize($data['settings']));

        $token = ee()->input->get_post('token', true);
        if($token == "")
        {

            $data['created_date']   = ee()->localize->now;
            $data['last_modified']  = ee()->localize->now;
            $data['member_id']      = $this->member_id;
            $data['status']         = "active";
            $data['export_counts']  = 0;
            $data['token']          = strtolower(ee()->functions->random('md5',10));
            
            $export_id = ee()->sieModel->saveExport($data);

            $token = $data['token'];

            if(empty($data['name'])){
                unset($data);
                $data = array();
                $data['name'] = "export_".$export_id;

                ee()->db->where('token', $token);
                ee()->db->update('sie_exports', $data);
            }

            ee()->session->set_flashdata('return_id', $data['token']);
            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_saved_successfully'))->defer();
        }
        else
        {
            /* Update existing entry */
            $data['last_modified']  = ee()->localize->now;
            ee()->sieModel->updateExport($data, $token);
            ee()->session->set_flashdata('return_id', $token);
            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_updated_successfully'))->defer();
        }

    }

    /**
    * Create EE table of export lists
    * @return Export table EE obj
    **/
    function createExportTable($vars, $perPage)
    {

        ee()->load->library('pagination');

        /* remove confirm popup*/
        ee()->javascript->set_global('lang.remove_confirm', lang('export_list') . ': <b>### ' . lang('export_list') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        /*$vars['export_form'] = ee('CP/URL')->make('addons/settings/smart_import_export/export_form');*/
        $vars['export_form'] = ee()->sie->url('export_form');
        $vars['delete_export'] = ee()->sie->url('delete_export');
        ee()->cp->set_right_nav(array(lang('export_form_title')  => $vars['export_form']));
        
        /* Make table for displaying export listing */
        $table = ee('CP/Table', array(
            'sortable'  => FALSE,
            'reorder'   => false
        ));

        /* Make table columns headings for displaying export listing */
        $table->setColumns(
            array(
                'id'            => array('encode' => FALSE, 'class' => 'field-table-id'),
                'member_id'     => array('encode' => FALSE, 'class' => 'field-table-member_id'),
                'name'          => array('encode' => FALSE, 'class' => 'field-table-name'),
                'created_date'  => array('encode' => FALSE, 'class' => 'field-table-created_date'),
                'last_modified' => array('encode' => FALSE, 'class' => 'field-table-last_modified'),
                'export_counts' => array('encode' => FALSE, 'class' => 'field-table-export_counts'),
                'type'          => array('encode' => FALSE, 'class' => 'field-table-type'),
                'format'        => array('encode' => FALSE, 'class' => 'field-table-format'),
                'manage'        => array(
                    'type'  => Table::COL_TOOLBAR
                ),
                array(
                    'type'  => Table::COL_CHECKBOX
                )
            )
        );

        /* Set no result text if no data found */
        $table->setNoResultsText(
            sprintf(lang('no_found'), lang('exports')),
            'create_new',
            $vars['export_form']
        );

        /*Default Settings*/
        $total          = ee()->sieModel->getExportList("", $this->group_id, $perPage);
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $perPage; // Offset is 0 indexed

        $vars['export_list']    = ee()->sieModel->getExportList($offset, $this->group_id, $perPage);
        $vars['method']         = "smart_import_export";
        $vars['title']          = lang('export_list');

        $fieldData = array();
        if(isset($vars['export_list']) && is_array($vars['export_list']) && $vars['export_list'] > 0)
        {

            $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perPage)
            ->currentPage($currentpage)
            ->render(ee()->sie->url());

            for ($i=0; $i < count($vars['export_list']); $i++)
            { 

                $vars['export_list'][$i]['settings'] = unserialize(base64_decode($vars['export_list'][$i]['settings']));
                $procedure  =  isset($vars['export_list'][$i]['settings']['procedure']) ? $vars['export_list'][$i]['settings']['procedure'] : "normal";
                $subClass = "";
                if($procedure == "ajax"){
                    $subClass = "ajax-download";
                }
                
                $columns = array(
                    'id'            => $vars['export_list'][$i]['id'],
                    'member_id'     => $vars['export_list'][$i]['member_id'],
                    'name'          => $vars['export_list'][$i]['name'],
                    'created_date'  => date('m/d/Y', $vars['export_list'][$i]['created_date']),
                    'last_modified' => date('m/d/Y', $vars['export_list'][$i]['last_modified']),
                    'export_counts' => $vars['export_list'][$i]['export_counts'],
                    'type'          => $vars['export_list'][$i]['type'],
                    'format'        => $vars['export_list'][$i]['format'],
                    array('toolbar_items' => array(
                        'edit' => array(
                            'href'      => ee()->sie->url('export_form', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('edit'))
                        ),
                        'upload' => array(
                            'href'      => ee()->sie->url('download_export', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('export')),
                            'class'     => "download-export $subClass"
                        ),
                        'rte-link' => array(
                            'href'     => 'javascript:void(0);',
                            'title'     => strtolower(lang('url')),
                            'class'     => 'passkey',
                            'copy-link'      => ee()->functions->create_url("?ACT=".ee()->sieModel->getActionID("sie_export").AMP.'token='.$vars['export_list'][$i]['token'] . (($procedure == "ajax") ? AMP . 'type=ajax': '')),
                        ),
                    )),
                    array(
                        'name'  => 'selection[]',
                        'value' => $vars['export_list'][$i]['id'],
                        'data'  => array(
                            'confirm' => lang('export') . ': <b>' . htmlentities($vars['export_list'][$i]['name'], ENT_QUOTES, 'UTF-8') . '</b>'
                        )
                    )
                );
                unset($vars['export_list'][$i]['settings']);

                $attrs = array();
                if (ee()->session->flashdata('return_id') == $vars['export_list'][$i]['token'])
                {
                    $attrs = array('class' => 'selected');
                }

                $fieldData[] = array(
                    'attrs' => $attrs,
                    'columns' => $columns
                );
            }
            
        }

        unset($vars['export_list']);
        $table->setData($fieldData);

        $vars['table'] = $table->viewData(ee()->sie->url());
        return $vars;
    
    }

    /**
    * Replace EE file tag (filedir_x) to specific URL
    * @param File URL to be convert into actual URL
    * @return File URL
    **/
    function replaceFileDir($parseFiles, $filename)
    {
        $search = array();
        $replace = array();
        foreach ($parseFiles as $key => $value) {
            $search[]   = '{filedir_' . $key . '}';
            $replace[]  = $value;
        }

        return str_replace($search, $replace, $filename);
    }

    /**
    * sanitize function to convert string to remove extra spaces and unwanted special characters
    * @param $title (string to be sanitize)
    * @return sanitized string
    **/
    function sanitize($title)
    {

        $title = strip_tags($title);
        
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        if ($this->seems_utf8($title))
        {

            if (function_exists('mb_strtolower'))
            {
                $title = mb_strtolower($title, 'UTF-8');
            }

            $title = $this->utf8_uri_encode($title, 200);

        }

        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = str_replace('.', '_', $title);
        $title = str_replace('-', '_', $title);

        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '_', $title);
        $title = preg_replace('|_+|', '_', $title);

        $title = trim($title, '_');

        return $title;

    }

    /*Genterate string in utf8. dependent function of sanitize*/
    function utf8_uri_encode( $utf8_string, $length = 0 )
    {

        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;
        $string_length = strlen( $utf8_string );

        for ($i = 0; $i < $string_length; $i++ )
        {

            $value = ord( $utf8_string[ $i ] );

            if ( $value < 128 )
            {

                if ( $length && ( $unicode_length >= $length ) )
                {
                    break;
                }

                $unicode .= chr($value);
                $unicode_length++;

            }
            else
            {

                if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

                $values[] = $value;

                if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                {
                    break;
                }

                if ( count( $values ) == $num_octets )
                {

                    if ($num_octets == 3)
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    }
                    else
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;

                }

            }

        }

        return $unicode;
        
    }

    /*dependent function of utf8_uri_encode*/
    function seems_utf8($str)
    {

        $length = strlen($str);

        for ($i=0; $i < $length; $i++)
        {

            $c = ord($str[$i]);

            if ($c < 0x80) $n = 0; /*0bbbbbbb*/
            elseif (($c & 0xE0) == 0xC0) $n=1; /*110bbbbb*/
            elseif (($c & 0xF0) == 0xE0) $n=2; /*1110bbbb*/
            elseif (($c & 0xF8) == 0xF0) $n=3; /*11110bbb*/
            elseif (($c & 0xFC) == 0xF8) $n=4; /*111110bb*/
            elseif (($c & 0xFE) == 0xFC) $n=5; /*1111110b*/
            else return false; /*Does not match any model*/
            
            for ($j=0; $j<$n; $j++)
            { 

                /*n bytes matching 10bbbbbb follow ?*/
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                {
                    return false;
                }

            }

        }

        return true;

    }

    function setupCategories($categories, $parentID = 0)
    {

        $ret = array();
        foreach ($categories as $key => $value)
        {
            if($value['parent_id'] == $parentID)
            {
                $children = $this->setupCategories($categories, $value['cat_id']);
                if ($children) {
                    $categories[$key]['children'] = $children;
                }
                $ret[$value['cat_id']] = $categories[$key];
            }
        }        
        
        return $ret;
    }

    /**
    * Function to handle module errors.
    * @param $source (mode of error [in case we have more in future])
    * @return error output in EE gray screen
    **/
    function handlingMODErrors($source = "", $errors = array())
    {

        /*show error in EE default style if error reporting is not set to inline*/
        if($source == "export")
        {

            $message = "<ul>";
            foreach ($errors as $key => $value)
            {
                $message .= "<li>".$value."</li>"; // solved for ee6
            }
            $message .= "<ul>";

            $data = array(  
                'title'     => ee()->lang->line('submission_error'),
                'heading'   => ee()->lang->line('submission_error'),
                'content'   => $message
                );
            
            return ee()->output->show_message($data, FALSE);

        }

    }

    /* load datepicker library */
    function loadDatePicker()
    {

        ee()->lang->loadfile('calendar');
        ee()->javascript->set_global('date.date_format', ee()->localize->get_date_format());
        ee()->javascript->set_global('lang.date.months.full', array(
            lang('cal_january'),
            lang('cal_february'),
            lang('cal_march'),
            lang('cal_april'),
            lang('cal_may'),
            lang('cal_june'),
            lang('cal_july'),
            lang('cal_august'),
            lang('cal_september'),
            lang('cal_october'),
            lang('cal_november'),
            lang('cal_december')
        ));
        ee()->javascript->set_global('lang.date.months.abbreviated', array(
            lang('cal_jan'),
            lang('cal_feb'),
            lang('cal_mar'),
            lang('cal_apr'),
            lang('cal_may'),
            lang('cal_june'),
            lang('cal_july'),
            lang('cal_aug'),
            lang('cal_sep'),
            lang('cal_oct'),
            lang('cal_nov'),
            lang('cal_dec')
        ));
        ee()->javascript->set_global('lang.date.days', array(
            lang('cal_su'),
            lang('cal_mo'),
            lang('cal_tu'),
            lang('cal_we'),
            lang('cal_th'),
            lang('cal_fr'),
            lang('cal_sa'),
        ));
        ee()->cp->add_js_script(array(
            'file' => array('cp/date_picker'),
        ));

    }

    /* handle import form */ 
    function handleAddNewImport($vars)
    {
        if (isset($vars['import_id']) && $vars['import_id'] != NULL && $vars['import_id'] > 0)
        {
            
            $importData = ee()->sieModel->getImportData($vars['import_id']);
            if(empty($importData)){
                show_error(lang('no_import_setting_data_found')."<br/><a href='".$this->url('import_index/')."'>Go Back</a>");
            }
            $importData = $importData[0];
            if(!isset($importData['settings'])){
                show_error(lang('no_import_setting_data_found')."<br/><a href='".$this->url('import_index/')."'>Go Back</a>");
            }
            $importDataSettings = unserialize(base64_decode($importData['settings']));
            $importDataSettings = $importDataSettings['setting'];
            $baseURL = $this->url('import_form/'.$vars['import_id']);
            $cpPageTitle = lang('edit_import');
        }
        else
        {
            @session_start();
            if(isset($_SESSION['Smart_import_export'])){
                if(!isset($_SESSION['Smart_import_export']['setting'])){
                    unset($_SESSION['Smart_import_export']);
                    show_error(lang('no_import_setting_data_found')."<br/><a href='".$this->url('import_index/')."'>Go Back/a>");
                }
                $importData['id'] = $_SESSION['Smart_import_export']['setting']['import'];
                $importDataSettings = $_SESSION['Smart_import_export']['setting'];
                $saveBtnText = sprintf(lang('btn_save'), lang('action'));
                $cpPageTitle = lang('create_new_action');
            }
            $baseURL = $this->url('import_form');
            $cpPageTitle = lang('create_new_import');

        }

        $saveBtnText = sprintf(lang('btn_save'), lang('action'));

        ee()->lang->loadfile('smart_import_export');

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));

        $allChannels = ee('Model')->get('Channel')->filter('site_id', $this->site_id)->order('channel_title', 'ASC')->all();
        $allChannelsCombi = $allChannels->getDictionary('channel_id', 'channel_title');


        $vars['sections'] = array(
          array(
            array(
                'fields' => array(
                    'import' => array(
                        'type'      => 'hidden',
                        'value'     => isset($importData['id'])?$importData['id']:$vars['import_id'],
                        'required'  => TRUE
                    )
                ),
                'attrs' => array(
                    'class' => 'last',
                    'style' => 'position: absolute;'
                ),
            ),
            array(
                'title' => 'import_file_type',
                'desc' => 'import_file_type_desc',
                'fields' => array(
                    'import_file_type' => array(
                        'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                        'choices' => array(
                            'csv' => 'CSV',
                            'xml' => 'XML',
                            'json' => 'JSON',
                            'third_party_xml' => 'XML - Third Party',
                        ),
                        'group_toggle' => array(
                            'csv' => 'csv',
                            'xml' => 'xml',
                            'json' => 'json',
                            'third_party_xml' => 'third_party_xml',
                        ),
                        'value' => isset($importDataSettings['import_file_type'])?$importDataSettings['import_file_type']:'',
                    )
                )
            ),
            array(
                'title' => 'import_channel',
                'desc' => 'import_channel_desc',
                'fields' => array(
                    'import_channel' => array(
                        'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                        'choices' => $allChannelsCombi,
                        'value' => isset($importDataSettings['import_channel'])?$importDataSettings['import_channel']:'',
                    )
                )
            ),
        ),
        'csv' => array(
            'group' => 'csv',
            'settings' => array(array(
                'title' => 'import_csv_file_name',
                'desc' => 'import_file_desc',
                'fields' => array(
                    'file_source_csv' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_source_csv'])?$importDataSettings['file_source_csv']:'',
                    )
                )
            ),
            array(
                'title' => 'import_csv_delimiter',
                'desc' => 'import_csv_delimiter_desc',
                'fields' => array(
                    'file_delimiter_csv' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_delimiter_csv'])?$importDataSettings['file_delimiter_csv']:'',
                    )
                )
            ),
            array(
                'title' => 'import_csv_encloser',
                'desc' => 'import_csv_encloser_desc',
                'fields' => array(
                    'file_encloser_csv' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_encloser_csv'])?$importDataSettings['file_encloser_csv']:'',
                    )
                )
            ),
            array(
                'title' => 'import_csv_encloser_not',
                'desc' => 'import_csv_encloser_not_desc',
                'fields' => array(
                    'file_no_encloser_csv' => array(
                        'type' => 'yes_no',
                        'value' => isset($importDataSettings['file_no_encloser_csv'])?$importDataSettings['file_no_encloser_csv']:'n',
                    )
                )
            ),
            array(
                'title' => 'import_csv_first_row',
                'desc' => 'import_csv_first_row_desc',
                'fields' => array(
                    'file_first_row_csv' => array(
                        'type' => 'yes_no',
                        'value' => isset($importDataSettings['file_first_row_csv'])?$importDataSettings['file_first_row_csv']:'y',
                    )
                )
            )
        )
        ),
        'xml' => array(
            'group' => 'xml',
            'settings' => array(
            array(
                'title' => 'import_xml_file_name',
                'desc' => 'import_file_desc',
                'fields' => array(
                    'file_source_xml' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_source_xml'])?$importDataSettings['file_source_xml']:'',
                    )
                )
            ),
            array(
                'title' => 'import_xml_path',
                'desc' => 'import_xml_path_desc',
                'fields' => array(
                    'path_xml' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['path_xml'])?$importDataSettings['path_xml']:'',
                    )
                )
            )

        )
        ),
        'json' => array(
            'group' => 'json',
            'settings' => array(
            array(
                'title' => 'import_json_file_name',
                'desc' => 'import_file_desc',
                'fields' => array(
                    'file_source_json' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_source_json'])?$importDataSettings['file_source_json']:'',
                    )
                )
            ),
            array(
                'title' => 'import_json_path',
                'desc' => 'import_json_path_desc',
                'fields' => array(
                    'path_json' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['path_json'])?$importDataSettings['path_json']:'',
                    )
                )
            )

        )
        ),
        'third_party_xml' => array(
            'group' => 'third_party_xml',
            'settings' => array(
            array(
                'title' => 'import_xml_file_name',
                'desc' => 'import_file_desc',
                'fields' => array(
                    'file_source_xml' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['file_source_xml'])?$importDataSettings['file_source_xml']:'',
                    )
                )
            ),
            array(
                'title' => 'import_xml_path',
                'desc' => 'import_xml_path_desc',
                'fields' => array(
                    'path_xml' => array(
                        'type' => 'text',
                        'value' => isset($importDataSettings['path_xml'])?$importDataSettings['path_xml']:'',
                    )
                )
            )

        )
        ),
        );



        // $vars['ajax_validate'] = TRUE;
        $vars['save_btn_text_working'] = 'btn_saving';

        ee()->javascript->output('$(document).ready(function () {
            EE.cp.fieldToggleDisable(null, "import_file_type");
        });');

        $vars += array(
            'base_url'              => $baseURL,
            'cp_page_title'         => $cpPageTitle,
            'save_btn_text'         => $saveBtnText,
            'save_btn_text_working' => 'btn_saving'
        );
        return $vars;
    }

    /* handle import form post */
    function handleImportFormPost($importID)
    {
        //rule define for short name must be unique
        $label = false;
        $is_edit =  (ee()->input->post('import', true)) == 0 ? "n" : "y";
        $rules = array();
        if(isset($_POST))
        {
            $rules = array(
                'import_file_type'      => 'required|xss|allowedType[csv,xml,third_party_xml,json]',
                'import_channel'        => 'required|xss|numeric',
            );
            if($_POST['import_file_type'] == 'csv')
            {
                $rules += array(
                    // 'import_file_type'      => 'required|xss|allowedType[csv,xml,json]',
                    // 'import_channel'        => 'required|xss|numeric',
                    'file_source_csv'      => 'required|xss|file_exists_or_not',
                    'file_delimiter_csv'   => 'required|xss',
                    'file_encloser_csv'    => 'xss',
                );
            }
            if($_POST['import_file_type'] == 'xml')
            {
                $rules += array(
                    // 'import_file_type'      => 'required|xss|allowedType[csv,xml,json]',
                    // 'import_channel'        => 'required|xss|numeric',
                    'file_source_xml'      => 'required|xss|file_exists_or_not',
                    'path_xml'             => 'required|xss',
                );
            }
            if($_POST['import_file_type'] == 'third_party_xml')
            {
                $rules += array(
                    // 'import_file_type'      => 'required|xss|allowedType[csv,xml,json]',
                    // 'import_channel'        => 'required|xss|numeric',
                    'file_source_xml'      => 'required|xss|file_exists_or_not',
                    'path_xml'             => 'required|xss',
                );
            }
            if($_POST['import_file_type'] == 'json')
            {
                $rules = array(
                    'file_source_json'             => 'required|xss|file_exists_or_not',
                );
            }
        }
        
        ee()->sie_custom_validation->validator->setRules($rules);
        $result = ee()->sie_custom_validation->validator->validate($_POST);
        if ($result->isValid())
        {
            if($importID == 0){
                $this->_setSettings('setting', $_POST);
            }else{
                ee()->sieModel->saveImportForm($importID);
            }
            return true;
        }
        else
        {
            return $result;
        }
    }

    function _getSettings($key, $keep = FALSE){
        @session_start();  
        if( isset( $_SESSION[ $this->module_name ] ) ) {
            if( isset( $_SESSION[ $this->module_name ][ $key ] ) ) {
                $data = $_SESSION[ $this->module_name ][ $key ];
                if ( $keep != TRUE ) {
                unset($_SESSION[ $this->module_name ][ $key ]); 
                unset($_SESSION[ $this->module_name ]); 
                }
                return( $data );
            }
        }
        return "";
    }

    function _setSettings($key, $data){
        @session_start();
        if ( !isset( $_SESSION[ $this->module_name ] ) ) {
            $_SESSION[ $this->module_name ] = array();
        }
        $_SESSION[ $this->module_name ][ $key ] = $data;
    }

    function _remove($key) {
        //$this->_setSettings( 'settings', serialize( array() ) ); // due to get error 29 april
        unset($_SESSION[$this->module_name][$key]);
    }

    /* get all fluid fileds */
    function getAllFluidFields($settings){
        $allChannels = ee('Model')->get('ChannelField')->filter('field_id', 'IN', $settings['field_channel_fields'])->all();
        ee()->load->model('grid_model');
        $flist = array();
        foreach($allChannels as $field){
                 $flist[$field->getId()] = array(
                    'field_label' => $field->field_label,
                    'field_id' => $field->getId(),
                    'field_name' => $field->field_name,
                    'field_type' => $field->field_type,
                    //'content_type' => $field->content_type,
                    'field_settings' => $field->field_settings,
                    'field_data' => ($field->field_type == 'grid') ? ee()->grid_model->get_columns_for_field($field->getId(), 'channel') : array()
                );
        }
        return $flist;
    }

    /* check json */
    function isJSON($string){
       return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    function array2xml($array, $xml = false){

        if($xml === false){
            $xml = new SimpleXMLElement('<result/>');
        }
    
        foreach($array as $key => $value){
            if(is_array($value)){
                $this->array2xml($value, $xml->addChild($key));
            } else {
                $xml->addChild($key, $value);
            }
        }
    
        return $xml->asXML();
    }

    // function defination to convert array to xml
    function arrayToXml($array, $rootElement = null, $xml = null) {
        $_xml = $xml;
          
        // If there is no Root Element then insert root
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }
          
        // Visit all key value pair
        foreach ($array as $k => $v) {
              
            // If there is nested array then
            if (is_array($v)) { 
                  
                // Call function for nested array
                $this->arrayToXml($v, $k, $_xml->addChild($k));
            }
                  
            else {
                  
                // Simply add child element. 
                $_xml->addChild($k, $v);
            }
        }
          
        return $_xml->asXML();
    }

    /* get json header*/
    function jsonToArrayHeader($data = array(), $only_header = "no")
    {
        $filename  = isset($data['file_source_json']) ? $data['file_source_json'] : "";

        if(filter_var($filename, FILTER_VALIDATE_URL))
        {
            if(! $this->checkURL($filename))
            {
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                return FALSE;
            }
        }
       
        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if($ext != "json")
        {
            // return FALSE;
        }

        $header = NULL;
        // $data = array();
        $headerRow = array();

        $file = @file_get_contents($filename);
        $file_data = json_decode($file, true);

		$json_array = json_decode( $file, true );
		// $json_array = (array)$json_obj;

        $path_json = $data['path_json'];

		if( $path_json != "" ) {
			$this->items = $json_array[$path_json];			
		} else {
			$this->items = $json_array;
		}

        $columns = $this->next();

		while( $item = $this->next() ) {
			$columns = array_merge( $columns, $item );
		}

		// $titles = array();
        $header["0"] = 'select'; 
		$count = 0;
		foreach( $columns as $idx => $title ) {
			if( substr( $idx, -1, 1) != "#" ) {
				if ( strlen( $title ) > 32 ) {
					$title = substr( htmlspecialchars($title), 0, 32 ) . "...";
				}
				$header[ $idx ] = $idx ;
			}
		}
        $header = array_filter($header);
        return $header;

        
        if(isset($data['path_json']) && !empty($data['path_json'])){
            $first_occurence[0] = isset($file_data[$data['path_json']][0]) ? $file_data[$data['path_json']][0] : array();
        }else{
            
            $first_occurence[0] = isset($file_data[0]) ? $file_data[0] : array();  
        }


        if(isset($first_occurence[0])){
            foreach ($first_occurence as $fo_key => $fo_value) {
                foreach ($fo_value as $foi_key => $foi_value) {
                    $headerRow[0][] = $foi_key;
                    $headerRow[1][] = $foi_value;
                }
            }
        }else{
            foreach ($first_occurence as $foi_key => $foi_value) {
                $headerRow[0][] = $foi_key;
                $headerRow[1][] = $foi_value;
            }
        }
                              
        $header["0"] = 'select'; 
        for ($i=0; $i < count($headerRow[0]); $i++)
            {
                if(isset($headerRow[0])){
                    trim($headerRow[0][$i]);
                    
                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i])))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i])));
                }
                if(isset($headerRow[1])){
                    if(is_array($headerRow[1][$i])){
                        $jsonArray = $headerRow[1][$i];
                        foreach($jsonArray as $js){
                            if(is_array($js)){
                                foreach($js as $k=>$v){
                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k)));
                                    if(is_array($v)){
                                        foreach($v as $kk=>$vv){
                                            if(!is_numeric($kk)){
                                                $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk)));

                                                if(is_array($vv)){
                                                    foreach($vv as $kkk=>$vvv){
                                                        if(!is_numeric($kkk)){
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk.' -> '.$kkk)));
                                                        }
                                                    }
                                                }

                                            }elseif(is_numeric($kk)){
                                                if(is_array($vv)){
                                                    foreach($vv as $kkk=>$vvv){
                                                        if(!is_numeric($kkk)){
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kkk)));
                                                        }
                                                    }
                                                }
                                            }   
                                        }
                                    }
                                }
                            }else{
                            }
                        }
                        
                    }
                    
                }


            }
        
        $header = array_filter($header);
        return $header;

    }

	function _parse_json_into_array( $json_obj, &$result, $prefix="" ) {

		$json_array = (array)$json_obj;

		foreach( $json_array as $key => $value ) {
            if(!is_numeric($key)){ 
			    $newkey = $prefix." -> ".$key;
            }elseif(is_numeric($key) && is_string($json_array[$key])){ 
			    $newkey = $prefix." -> ".$key;
            }else{
                $newkey = $prefix;
            }
			$newkey = trim( $newkey, " -> " );
			if( is_object( $value ) ){
				$result[ $newkey ] = $this->_parse_json_into_array( $value, $result, $newkey );
			} else {
                if ( is_array( $value ) ) {
					$result[ $newkey ] = $this->_parse_json_into_array( $value, $result, $newkey );
				} else {
					$result[ $newkey ] = $value;
				}
			}
		}

		// return $json_array;
	}

	function next() {

		$item = current( $this->items );
		next( $this->items );

		if ( $item == FALSE ) {
			return FALSE;
		}

		$new = array();
		$item = $this->_parse_json_into_array( $item, $new );

		return $new;
	}

    /* get json data */
    function jsonToArray($data = array(), $only_header = "no")
    {
        $filename  = isset($data['file_source_json']) ? $data['file_source_json'] : "";

        if(filter_var($filename, FILTER_VALIDATE_URL))
        {
            if(! $this->checkURL($filename))
            {
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                return FALSE;
            }
        }
       
        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if($ext != "json")
        {
            // return FALSE;
        }

        $header = NULL;
        // $data = array();

        $file = @file_get_contents($filename);
        $file_data = json_decode($file, true);
        $main_array = array();
        $key_array = array();

        $data['path_json'] = ltrim($data['path_json'], '/');
        $data['path_json'] = rtrim($data['path_json'], '/');
        foreach(explode('/', $data['path_json']) as $key){
            if(isset($file_data[$key])){
                $file_data = $file_data[$key];
            }
        }
        $final_data = array();
        return $file_data;


        foreach ($file_data as $key => $value) {
            $inner_array = array();
            foreach ($value as $key1 => $value2) {
                if(is_array($file_data[$key][$key1])){
                    foreach ($value2 as $key2 => $value2) { 
                        if($key == 0){
                            // $key_array[] = $key2; 
                        }   
                        $inner_array[] = is_array($value2) ? json_encode($value2) : $value2;
                    }
                }else{
                    // if($key == 0){
                    //     $key_array[] = $value; 
                    // } 
                    if($key == 0 && empty($key_array)){  
                        foreach($file_data as $keyheader => $valueheader){
                            // $key_array[] = $keyheader;
                        }
                    }
                    $inner_array[] = is_array($value2) ? json_encode($value2) : $value2;
                }
            }
            $main_array[] = $inner_array;
        }

        $key_array = array($key_array);
        $main_array = array_merge($key_array, $main_array);
        $count = -1;
        foreach ($main_array as $key => $row) {

            $count = $count + 1 ; 
            if(!$header)
            {

                $header = array();
                for ($i=0; $i < count($row); $i++)
                {
                    trim($row[$i]);
                    // $header[0] = 'select'; 
                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])));
                }

                if($only_header == "yes")
                {
                    return $header;
                }

            }
            else
            {
                // unset($header[0]);
                if(count($header) == count($row))
                {
                    // if($count >= $offset && $count <= ($offset+$betches)){
                    //     $data[] = array_combine($header, $row);
                    // }
                    $final_data[] = array_combine($header, $row);
                }
            }

        }
        
        unset($header);
        unset($handle);
        return $file_data;

    }


    function checkURL($filename){
        if(file_get_contents( $filename ) != false){
            return true;
        }else{
            return false;
        }
    }

    /* get csv header*/
    function csvToArrayHeader($data = array(), $only_header = "no")
    {
        $filename  = isset($data['setting']['file_source_csv']) ? $data['setting']['file_source_csv'] : "";
        $delimiter  = isset($data['setting']['file_delimiter_csv']) ? $data['setting']['file_delimiter_csv'] : ",";
        if(isset($data['setting']['file_no_encloser_csv']) && $data['setting']['file_no_encloser_csv'] == 'y'){
            $encloser  = chr(0);
        }else{
            $encloser  = isset($data['setting']['file_encloser_csv']) ? $data['setting']['file_encloser_csv'] : '"';
        }
        $file_first_row_csv = isset($data['setting']['file_first_row_csv']) ? $data['setting']['file_first_row_csv'] : "n";

        if(filter_var($filename, FILTER_VALIDATE_URL))
        {
            if(! $this->checkURL($filename))
            {
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
       
        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if($ext != "csv")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }

        $header = NULL;
        $data = array();
        $headerRow = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            $rowNo = 0;
            while (($row = fgetcsv($handle, 0, $delimiter, $encloser,'"')) !== FALSE)
            {
                
                if($rowNo <= 1)
                {
                    $headerRow[] = $row;
                    $rowNo = $rowNo + 1;
                }

            }

            $header["0"] = 'select'; 
            for ($i=0; $i < count($headerRow[0]); $i++)
            {
                if(isset($headerRow[0])){
                    trim($headerRow[0][$i]);
                    
                    //blank column scenario
                    /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i])))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i])));*/
                    if(empty($headerRow[0][$i])){
                        $header['Column - '.$i] = 'Column - '.$i;
                    }else{
                        $header['Column - '.$i] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i])));
                    }
                }
                
                if(isset($headerRow[1])){
                    if($this->isJSON($headerRow[1][$i])){
                        
                        //blank column scenario
                        //change the name to this column because of the json
                        $header_json_key = ($file_first_row_csv == 'y' && !$this->isJSON($headerRow[0][$i]) ) ? $headerRow[0][$i] : 'Column - '.$i;
                        $header['Column - '.$i] =  $header_json_key;

                        $jsonArray = json_decode($headerRow[1][$i], true);
                        foreach($jsonArray as $js){
                            if(!$this->isJSON($js)){
                                foreach($js as $k=>$v){

                                    /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k)));*/
                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k)));
                                    if(is_array($v)){
                                        foreach($v as $kk=>$vv){
                                            if(!is_numeric($kk)){
                                                /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk)));*/
                                                $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kk)));

                                                if(is_array($vv)){
                                                    foreach($vv as $kkk=>$vvv){
                                                        if(!is_numeric($kkk)){
                                                            /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kk.' -> '.$kkk)));*/
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kk.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kk.' -> '.$kkk)));
                                                        }
                                                    }
                                                }

                                            }elseif(is_numeric($kk)){
                                                if(is_array($vv)){
                                                    foreach($vv as $kkk=>$vvv){
                                                        if(!is_numeric($kkk)){
                                                            /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$k.' -> '.$kkk)));*/
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $header_json_key.' -> '.$k.' -> '.$kkk)));
                                                        }
                                                    }
                                                }
                                            }   
                                        }
                                    }
                                }
                            }else{
                                // foreach($jsonArray[0][0] as $kk=>$vv){
                                //     $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$jsonArray[0][0].' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $headerRow[0][$i].' -> '.$jsonArray[0][0].' -> '.$kk)));
                                // }
                            }
                        }
                        
                    }
                    
                }


            }
            fclose($handle);

        }

        // unset($header);
        // unset($handle);
        
        return $header;

    }

    /* get csv data */
    function csvToArray($data = array(), $only_header = "no")
    {

        $filename  = isset($data['setting']['file_source_csv']) ? $data['setting']['file_source_csv'] : "";
        $delimiter  = isset($data['setting']['file_delimiter_csv']) ? $data['setting']['file_delimiter_csv'] : ",";
        if(isset($data['setting']['file_no_encloser_csv']) && $data['setting']['file_no_encloser_csv'] == 'y'){
            $encloser  = chr(0);
        }else{
            $encloser  = isset($data['setting']['file_encloser_csv']) ? $data['setting']['file_encloser_csv'] : '"';
        }
        $file_first_row_csv = isset($data['setting']['file_first_row_csv']) ? $data['setting']['file_first_row_csv'] : "n";
        

        if(filter_var($filename, FILTER_VALIDATE_URL))
        {
            if(! $this->checkURL($filename))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
       
        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if($ext != "csv")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            $count = -1;
            while (($row = fgetcsv($handle, 0, $delimiter, $encloser)) !== FALSE)
            {

                $count = $count + 1 ; 
                if(!$header)
                {

                    $header = array();
                    for ($i=0; $i < count($row); $i++)
                    {
                        trim($row[$i]);
                        // $header[0] = 'select'; 

                        //blank column scenario
                        /*$header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])));*/
                        if(empty($row[$i])){
                            $header['Column - '.$i] = 'Column - '.$i;
                        }else{
                            $header['Column - '.$i] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])));
                        }
                    }

                    if($only_header == "yes")
                    {
                        return $header;
                    }

                    //consider the first row as data.
                    if($file_first_row_csv == 'n'){

                        if(count($header) == count($row))
                        {
                            $row_count = 0;
                            foreach($header as $key => $value){
                                $data[($count)][$key] = $row[$row_count++];
                            }

                        }
                    }


                }
                else
                {
                    // unset($header[0]);
                    if(count($header) == count($row))
                    {
                        // if($count >= $offset && $count <= ($offset+$betches)){
                        //     $data[] = array_combine($header, $row);
                        // }
                        
                        //blank column scenario
                        //$data[] = array_combine($header, $row);
                        $row_count = 0;
                        foreach($header as $key => $value){
                            $data[$file_first_row_csv == 'n' ? $count : ($count-1)][$key] = $row[$row_count++];
                        }

                    }
                }

            }

            fclose($handle);

        }

        unset($header);
        unset($handle);
        return $data;

    }

    /* get xml data */
    function xmlToArray($data = array(), $only_header = "no")
    {

        $filename = $data['setting']['file_source_xml'];

        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($filename, FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($filename))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
        $file = @file_get_contents($filename);
        if($file == "" || $file == NULL || $file == false)
        {
            show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }
        
        /*Convert string data to xml*/
        $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
        unset($file);
        
        $xml = json_encode($xml);

        $xml = str_replace("se--","",$xml);


        $xml = json_decode($xml,TRUE);
        $data['setting']['path_xml'] = ltrim($data['setting']['path_xml'], '/');
        $data['setting']['path_xml'] = rtrim($data['setting']['path_xml'], '/');
        foreach(explode('/', $data['setting']['path_xml']) as $key){
            if(isset($xml[$key])){
                $xml = $xml[$key];
            }
        }
        $items = $xml;
        return $items;


    }

    /* get xml header */
    function xmlToArrayHeader($data = array(), $only_header = "no")
    {
        $filename = $data['setting']['file_source_xml'];

        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($filename, FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($filename))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
        $file = @file_get_contents($filename);
        if($file == "" || $file == NULL || $file == false)
        {
            show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }
        
        /*Convert string data to xml*/
        $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
        unset($file);
        
        $xml = json_encode($xml);
        $xml = str_replace("se--","",$xml);
        $xml = json_decode($xml,TRUE);
        
        /*Set the final array to be use*/
        foreach ($xml as $key => $value)
        {

            /*Return header value of only header required*/
            if($only_header == "yes")
            {

                if(isset($value[0]) && is_array($value[0]) && count($value[0]) > 0){
                    $value = $value[0];
                }
                
                if(isset($value) && is_array($value) && count($value) > 0)
                {
                    $header = array();
                    $header[0] = 'selectqqq'; 
                    foreach($value as $key => $val)    
                    {

                        trim($key);
                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key)));
                        if(is_array($value[$key])){
                            foreach($value[$key] as $ks=>$js){
                                if(is_array($js)){
                                    if(!is_numeric($ks)){
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)));
                                    }
                                    foreach($js as $k=>$v){
                                        if(!is_numeric($k)){
                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)));

                                            if(is_array($v)){

                                                foreach($v as $kk=>$vv){

                                                    if(!is_numeric($kk)){
                                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk)));

                                                        if(is_array($vv)){
                                                                    
                                                            foreach($vv as $kkk=>$vvv){
                                                                if(!is_numeric($kkk)){
                                                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $$key.' -> '.$k.' -> '.$kk.' -> '.$kkk)));
                                                                }else{
                                                                    foreach($vvv as $kkkk=>$vvvv){
                                                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $$key.' -> '.$k.' -> '.$kk.' -> '.$kkkk)));
                                                                    }
                                                                }
                                                            }
                                                        }

                                                    }elseif(is_numeric($kk)){
                                                                    
                                                        if(is_array($vv)){
                                                            foreach($vv as $kkk=>$vvv){

                                                                if(!is_numeric($kkk)){

                                                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kkk)));
                                                                }
                                                                else{

                                                                }
                                                            }
                                                        }
                                                    }   
                                                }
                                            }
                                        }
                                        else{
                                            
                                            if(is_array($v)){
                                                foreach($v as $kk=>$vv){
                                                    if(is_numeric($kk)){
                                                        foreach($vv as $kkk=>$vvv){
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kkk)));
                                                        }
                                                    }else{
                                                        //V3.1.1
                                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kk)));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(!is_numeric($ks)){
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)));
                                    }
                                }
                            }
                        }else{
                            // foreach($value[$key] as $k=>$v){
                            //     $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)));
                            // }
                        }
                    }
                    $header[0] = 'select'; 
                    
                    return $header;

                }
                else
                {
                    return false;
                }

            }
            else
            {
                return $value;
            }

        }

    }


    /* get xml data */
    function thirdPartyXmlToArray($data = array(), $only_header = "no")
    {

        // $filename = $data['setting']['file_source_xml'];

        /*Get extension of file*/
        $ext = pathinfo($data['setting']['file_source_xml'], PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($data['setting']['file_source_xml'], FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($data['setting']['file_source_xml']))
            {
                show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($data['setting']['file_source_xml']) && is_readable($data['setting']['file_source_xml'])))
            {
                show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($data['setting']['file_source_xml'], "SimpleXMLElement", LIBXML_NOCDATA);
        $xml_string = @file_get_contents($data['setting']['file_source_xml']);
        if($xml_string == "" || $xml_string == NULL || $xml_string == false)
        {
            show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }

        $xml = $this->xml2array($xml_string);

        $data['setting']['path_xml'] = ltrim($data['setting']['path_xml'], '/');
        $data['setting']['path_xml'] = rtrim($data['setting']['path_xml'], '/');
        foreach(explode('/', $data['setting']['path_xml']) as $key){
            if(isset($xml[$key])){
                $xml = $xml[$key];
            }
        }
        return $xml;
        exit;


        $filename = $data['setting']['file_source_xml'];

        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($filename, FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($filename))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
        $file = @file_get_contents($filename);
        if($file == "" || $file == NULL || $file == false)
        {
            show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }
        
        /*Convert string data to xml*/
        $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
        unset($file);
        
        $xml = json_encode($xml);

        $xml = str_replace("se--","",$xml);


        $xml = json_decode($xml,TRUE);

        $data['setting']['path_xml'] = ltrim($data['setting']['path_xml'], '/');
        $data['setting']['path_xml'] = rtrim($data['setting']['path_xml'], '/');
        foreach(explode('/', $data['setting']['path_xml']) as $key){
            if(isset($xml[$key])){
                $xml = $xml[$key];
            }
        }
        $items = $xml;
        return $items;

    }


    function xml2array(&$string) {
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $string, $vals, $index);
        xml_parser_free($parser);

        $xml_array=array();
        $temp_array=&$xml_array;
        foreach ($vals as $r) {
            $t=$r['tag'];
            if ($r['type']=='open') {
                if (isset($temp_array[$t])) {
                    if (isset($temp_array[$t][0])) $temp_array[$t][]=array(); else $temp_array[$t]=array($temp_array[$t], array());
                    $cv=&$temp_array[$t][count($temp_array[$t])-1];
                } else $cv=&$temp_array[$t];
                if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_attribute'][$k]=$v;}
                $cv=array();
                $cv['_p']=&$temp_array;
                $temp_array=&$cv;

            } elseif ($r['type']=='complete') {
                if (isset($temp_array[$t])) { // same as open
                    if (isset($temp_array[$t][0])) $temp_array[$t][]=array(); else $temp_array[$t]=array($temp_array[$t], array());
                    $cv=&$temp_array[$t][count($temp_array[$t])-1];
                } else $cv=&$temp_array[$t];
                if (isset($r['attributes'])) {foreach ($r['attributes'] as $k=>$v) $cv['_attribute'][$k]=$v;}
                $cv['_value']=(isset($r['value']) ? $r['value'] : '');

            } elseif ($r['type']=='close') {
                $temp_array=&$temp_array['_p'];
            }
        }    
        
        $this->_del_p($xml_array);
        return $xml_array;
    }

    // _Internal: Remove recursion in result array
    function _del_p(&$ary) {
        foreach ($ary as $k=>$v) {
            if ($k==='_p') unset($ary[$k]);
            elseif (is_array($ary[$k])) $this->_del_p($ary[$k]);
        }
    }


    public function arrayToPath($arr, $path='',$indexed=false){
        if (is_array($arr)){
            $current    = array();
            foreach ($arr AS $key=>$val){
                if (is_array($val) && (array_keys($val) !== range(0, count($val) - 1))){
                    $nextpath   = ltrim(($indexed==false)?$path." -> $key":$path,' -> ');
                    if(count($val) > 1 && strpos($nextpath, ' -> ') !== false){
                        $this->patharray[ltrim("$nextpath",' -> ')]  = $nextpath;
                    }elseif(count($val) > 1 && strpos($nextpath, ' -> ') !== true){
                        $this->patharray[ltrim("$nextpath",' -> ')]  = $nextpath;
                    }
                    // $this->patharray[$nextpath] = $nextpath;
                    $current[]  = $this->arrayToPath($val, $nextpath);
                }elseif (is_array($val) && (array_keys($val) == range(0, count($val) - 1))){
                    $nextpath   = ltrim($path." -> $key",' -> ');
                    $this->patharray[ltrim("$path -> $key",' -> ')]  = "$path -> $key";
                    // $this->patharray[$nextpath] = $nextpath;
                    $current[]  = $this->arrayToPath($val, $nextpath, true);
                } else {
                    // $this->patharray['path'][]  = "$path->$key";
                    // $this->patharray['val'][]   = $val;
                    // $pathkey = ltrim('->',$path->$key);
                    if(strpos($path, ' -> ') !== false){
                        $this->patharray[$path]  = $path;
                    }
                    $this->patharray["$path -> $key"]  = "$path -> $key";
                }
            }
            // $this->patharray['select'] = '0';
            return $this->patharray;
        } else {
            return FALSE;
        }
    }    


    /* get xml header */
    function thirdPartyXmlToArrayHeader($data = array(), $only_header = "no")
    {

        // $filename = $data['setting']['file_source_xml'];

        /*Get extension of file*/
        $ext = pathinfo($data['setting']['file_source_xml'], PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($data['setting']['file_source_xml'], FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($data['setting']['file_source_xml']))
            {
                show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($data['setting']['file_source_xml']) && is_readable($data['setting']['file_source_xml'])))
            {
                show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($data['setting']['file_source_xml'], "SimpleXMLElement", LIBXML_NOCDATA);
        $xml_string = @file_get_contents($data['setting']['file_source_xml']);
        if($xml_string == "" || $xml_string == NULL || $xml_string == false)
        {
            show_error($data['setting']['file_source_xml']." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }
        
        $xml=$this->xml2array($xml_string);

        $data['setting']['path_xml'] = ltrim($data['setting']['path_xml'], '/');
        $data['setting']['path_xml'] = rtrim($data['setting']['path_xml'], '/');
        foreach(explode('/', $data['setting']['path_xml']) as $key){
            if(isset($xml[$key])){
                $xml = $xml[$key];
            }
            $root = $key;
        }
        $items = $xml;
        if(isset($items[0])){
            $items = $this->arrayToPath($items[0]);
        }else{
            $items = $this->arrayToPath($items);
        }
        $items = array('0' => 'Select') + $items;
        return $items;
        exit; 


        $filename = $data['setting']['file_source_xml'];
        $xml = @file_get_contents($filename);
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $xml, $tags);
        xml_parser_free($parser);
        $elements = array();  // the currently filling [child] XmlElement array
        $stack = array();
        // $xml = json_encode($tags);
        // $xml = str_replace("se--","",$xml);
        // $xml = json_decode($xml,TRUE);
        foreach ($tags as $tag) {
            $index = count($elements);
            if ($tag['type'] == "complete" || $tag['type'] == "open") {
              $elements[$index] = new XmlElement;
              $elements[$index]->name = isset($tag['tag'])?$tag['tag']:"";
              $elements[$index]->attributes = isset($tag['attributes'])?$tag['attributes']:"";
              $elements[$index]->content = isset($tag['value'])?$tag['value']:"";
              if ($tag['type'] == "open") {  // push
                $elements[$index]->children = array();
                $stack[count($stack)] = &$elements;
                $elements = &$elements[$index]->children;
              }
            }
            if ($tag['type'] == "close") {  // pop
              $elements = &$stack[count($stack) - 1];
              unset($stack[count($stack) - 1]);
            }
        }

        $elements = $elements[0];

        // $xml = json_encode($elements);
        // $xml = str_replace("se--","",$xml);
        // $xml = json_decode($xml,TRUE);



        $data['setting']['path_xml'] = "";
        if( $data['setting']['path_xml'] == "" ) {
            if( $elements->name == "feed") {
                // ATOM feed
                $data['setting']['path_xml'] = '/feed/entry';
            } elseif( $elements->name == "rss") {
                // RSS feed
                $data['setting']['path_xml'] = '/rss/channel/item';
            }
        }

        $items = array();
        $xml = json_encode($elements);
        $xml = str_replace("se--","",$xml);
        $xml = json_decode($xml,TRUE);
        $this->_fetch_xml( $xml, $data['setting']['path_xml'], $items );
        exit;


        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            // show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            // return FALSE;
        }
        
        if(filter_var($filename, FILTER_VALIDATE_URL))
        { 
            if(! $this->checkURL($filename))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        else
        {
            if(! (file_exists($filename) && is_readable($filename)))
            {
                show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
                return FALSE;
            }
        }
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
        $file = @file_get_contents($filename);
        if($file == "" || $file == NULL || $file == false)
        {
            show_error($filename." - ".lang('file_does_not_exist_or_readable')."<br/><a href='".$this->url('import_form/'.$data['configure']['import'])."'>Edit your setting and then configure again</a>");
            return false;
        }
        
        /*Convert string data to xml*/
        $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
        unset($file);
        
        $xml = json_encode($xml);
        $xml = str_replace("se--","",$xml);
        $xml = json_decode($xml,TRUE);

        $data['setting']['path_xml'] = ltrim($data['setting']['path_xml'], '/');
        $data['setting']['path_xml'] = rtrim($data['setting']['path_xml'], '/');
        foreach(explode('/', $data['setting']['path_xml']) as $key){
            if(isset($xml[$key])){
                $xml = $xml[$key];
            }
        }
        $items = $xml;
        
        /*Set the final array to be use*/
        foreach ($xml as $key => $value)
        {
            /*Return header value of only header required*/
            if($only_header == "yes")
            {

                if(isset($value[0]) && is_array($value[0]) && count($value[0]) > 0){
                    $value = $value[0];
                }

                // $datas = $this->array_keys_multi($value);

                if(isset($value) && is_array($value) && count($value) > 0)
                {
                    $header = array();
                    $header[0] = 'selectqqq'; 
                    foreach($value as $key => $val)    
                    {

                        trim($key);
                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key)));
                        if(is_array($value[$key])){
                            foreach($value[$key] as $ks=>$js){
                                if(is_array($js)){
                                    if(!is_numeric($ks)){
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)));
                                    }else{
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)));
                                    }
                                    foreach($js as $k=>$v){
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$k)));
                                        if(!is_numeric($k)){
                                            // $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)));
                                            if(is_array($v)){
                                                foreach($v as $kk=>$vv){
                                                    if(!is_numeric($kk)){
                                                        // $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk)));
                                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$k.' -> '.$kk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$k.' -> '.$kk)));
                                                        if(is_array($vv)){
                                                            foreach($vv as $kkk=>$vvv){
                                                                if(!is_numeric($kkk)){
                                                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkk)));
                                                                }else{
                                                                    foreach($vvv as $kkkk=>$vvvv){
                                                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kk.' -> '.$kkkk)));
                                                                    }
                                                                }
                                                            }
                                                        }

                                                    }elseif(is_numeric($kk)){
                                                        if(is_array($vv)){
                                                            foreach($vv as $kkk=>$vvv){
                                                                if(!is_numeric($kkk)){
                                                                    $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k.' -> '.$kkk)));
                                                                }
                                                                else{

                                                                }
                                                            }
                                                        }
                                                    }   
                                                }
                                            }
                                        }
                                        else{
                                            if(is_array($v)){
                                                foreach($v as $kk=>$vv){
                                                    if(is_numeric($kk)){
                                                        foreach($vv as $kkk=>$vvv){
                                                            $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kkk)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks.' -> '.$kkk)));
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }else{
                                    if(!is_numeric($ks)){
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)));
                                    }else{
                                        $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$ks))); 
                                    }
                                }
                            }
                        }else{
                            foreach($value[$key] as $k=>$v){
                                $header[trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)))] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $key.' -> '.$k)));
                            }
                        }
                    }
                    $header[0] = 'select'; 
                    return $header;

                }
                else
                {
                    // return false;
                }

            }
            else
            {
                return $value;
            }

        }

    }



    function _fetch_children(&$nodes, &$child, &$items){
        if(!empty($child['content'])){
            $items[$child['name']]['data'] = $child['content'];
        }
        if(is_array($child['attributes'])){
            foreach ( $child['attributes'] as $attr_key => $attr_value ) {
                $items[$child['name']]['@attributes'][ $attr_key ] = $attr_value;
            }
        }
        if(is_array($child['children'])){
            foreach($child['children'] as $sub_child){
                $items[$child['name']] = $this->_fetch_children($child, $sub_child, $items);
            }
        }
        return $items;
    }

    function _fetch_xml( $x, $search, &$items, $path="", $element=0, $in_element=false, $subpath="" ) {
        $temp = array();
        $search = array('feed', 'entry');
        foreach($search as $key){
            if(isset($x['name']) && in_array($x['name'], $search)){
                $x = $x['children'];
                // $items = $x['children'];
            }else{
                foreach($x as $nodes){
                    if(in_array($nodes['name'], $search)){
                        $temp[] = $nodes; 
                    }
                }
            }
        }
        foreach($temp as $children){
            foreach($children as $nodes){
                foreach($nodes as $child){
                    if(!empty($child['content'])){
                        $items[$child['name']]['data'] = $child['content'];
                    }
                    if(is_array($child['attributes'])){
                        foreach ( $child['attributes'] as $attr_key => $attr_value ) {
                            $items[$child['name']]['@attributes'][ $attr_key ] = $attr_value;
                        }
                    }
                    if(is_array($child['children'])){
                        // $items[$child['name']]['content'] = $this->_fetch_children($nodes, $child, $items);
                    }
                }
            }
        }
        // http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html


        if ( $path == $search ) {
            $element++;
            $items[ $element ] = array();       
            $subpath = "";
            if ( is_array( $x->attributes ) ) {
                $items[ $element ][ $subpath ] = array();
                $items[ $element ][ $subpath ]['@attributes'] = array();
                foreach ( $x->attributes as $attr_key => $attr_value ) {
                    // $items[ $element ][ $subpath . "->@" . $attr_key ] = $attr_value;
                    $items[ $element ][ $subpath ]['@attributes'][ $attr_key ] = $attr_value;
                }
            }
            $in_element = true;

        } elseif ( $str = strstr( $path, $search ) ) {
            $subpath = substr( $str, strlen( $search )+1 );

            $result = $items;
            $temp =& $result;
            if(strpos($subpath, '/') !== false){
                foreach(explode('/', $subpath) as $key) {
                    $temp =& $temp[$key];
                }
                $temp = $x->content;
                $items =& $result;
            }else{
                // $items = $x->content;
                $temp = $x->content;
                // $items =& $result;
            }
            // $items[$element][$subpath] =& $temp;
            // if(strpos($subpath, '/') !== false){
            //     $tempArr = explode('/', $subpath);
            //     foreach ($tempArr as $no=>$key) {
            //         if($no == 0){
            //             $tempArr[$key] = array();
            //         }elseif($no != 0 && (count($tempArr)-1) != $no){
            //             $tempArr[$tempArr[$no-1]][$tempArr[$no]]=array();
            //         }elseif($no != 0 ){
            //             $tempArr[$tempArr[$no-1]][$tempArr[$no]]=$key;
            //         }else{
            //             // $tempArr[$key] = array();
            //         }
            //         // $tempArr[] = $tempArr[$key];
            //     }
            //     $items[] = $tempArr;
            // }

            // if ( ! isset( $items[ $element ][ $subpath . "#" ] ) ) {
            //     $items[ $element ][ $subpath . "#" ] = 0;
            //     // $items[ $element ][ $subpath ] = "";
            // }
            // $count = $items[ $element ][ $subpath . "#" ]++;

            // if ( isset( $items[ $element ][ $subpath ] ) ) {
            //     $subpath .= "#" . ( $count + 1);
            // }
        } else {
            $in_element = false;
        }

        if ( gettype($x->children) != NULL && ( $x->children ) == 0 ) {

            if ( $in_element ) {
                $items[ $element ][ $subpath ] = $x->content;
            }
            
        } else {

            foreach ( $x->children as $key => $value ) {
                $element = $this->_fetch_xml( $value, $search, $items, $path, $element, $in_element, $subpath );
            }

        }

        if( $in_element ) {
            if ( is_array( $x->attributes ) ) {
                if(!empty($x->content)){
                    $items[ $element ][ $subpath ]["content"][] = array('content'=> $x->content);
                    // $items[ $element ][ $subpath ]["content"] = $x->content;
                }
                // $items[ $element ][ $subpath ] = array();
                // $items[ $element ][ $subpath ]['@attributes'] = array();
                foreach ( $x->attributes as $attr_key => $attr_value ) {
                    // $items[ $element ][ $subpath . "->@" . $attr_key ] = $attr_value;
                    $items[ $element ][ $subpath ]['@attributes'][ $attr_key ] = $attr_value;
                }
            }
        }

        return $element;
    }


    function _curl_fetch($url)
    {
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

        $data = curl_exec($ch);

        curl_close($ch);

        return $data;
    }



    /* create a listing of the imports */
    function createImportTable($vars, $perPage)
    {
        // $perPage = 100;
        ee()->load->library('pagination');

        /* remove confirm popup*/
        ee()->javascript->set_global('lang.remove_confirm', lang('export_list') . ': <b>### ' . lang('export_list') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        /*$vars['export_form'] = ee('CP/URL')->make('addons/settings/smart_import_export/export_form');*/
        $vars['import_form'] = ee()->sie->url('import_form');
        $vars['import_index'] = ee()->sie->url('import_index');
        ee()->cp->set_right_nav(array(lang('import_form_title')  => $vars['import_form']));
        
        /* Make table for displaying export listing */
        $table = ee('CP/Table', array(
            'sortable'  => FALSE,
            'reorder'   => false
        ));

        /* Make table columns headings for displaying export listing */
        $table->setColumns(
            array(
                'id'            => array('encode' => FALSE, 'class' => 'field-table-id'),
                // 'member_id'     => array('encode' => FALSE, 'class' => 'field-table-member_id'),
                'name'          => array('encode' => FALSE, 'class' => 'field-table-name'),
                'created_date'  => array('encode' => FALSE, 'class' => 'field-table-created_date'),
                'last_modified' => array('encode' => FALSE, 'class' => 'field-table-last_modified'),
                // 'import_counts' => array('encode' => FALSE, 'class' => 'field-table-import_counts'),
                // 'type'          => array('encode' => FALSE, 'class' => 'field-table-type'),
                'format'        => array('encode' => FALSE, 'class' => 'field-table-format'),
                'manage'        => array(
                    'type'  => Table::COL_TOOLBAR
                ),
                array(
                    'type'  => Table::COL_CHECKBOX
                )
            )
        );

        /* Set no result text if no data found */
        $table->setNoResultsText(
            sprintf(lang('no_found'), lang('imports')),
            'create_new',
            $vars['import_form']
        );

        /*Default Settings*/
        $total          = ee()->sieModel->getImportList("", $this->group_id, $perPage);
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $perPage; // Offset is 0 indexed

        $vars['import_list']    = ee()->sieModel->getImportList($offset, $this->group_id, $perPage);
        $vars['method']         = "smart_import_export";
        $vars['title']          = lang('import_list');

        $fieldData = array();
        if(isset($vars['import_list']) && is_array($vars['import_list']) && $vars['import_list'] > 0)
        {

            $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perPage)
            ->currentPage($currentpage)
            ->render(ee()->sie->url('import_index'));

            for ($i=0; $i < count($vars['import_list']); $i++)
            { 
                $vars['import_list'][$i]['settings'] = unserialize(base64_decode($vars['import_list'][$i]['settings']));
                $procedure  =  isset($vars['import_list'][$i]['settings']['procedure']) ? $vars['import_list'][$i]['settings']['procedure'] : "normal";
                $subClass = "";
                if($procedure == "ajax"){
                    $subClass = "ajax-download";
                }
                
                $columns = array(
                    'id'            => $vars['import_list'][$i]['id'],
                    // 'member_id'     => $vars['import_list'][$i]['member_id'],
                    'name'          => $vars['import_list'][$i]['name'],
                    'created_date'  => date('m/d/Y', $vars['import_list'][$i]['created_date']),
                    'last_modified' => date('m/d/Y', $vars['import_list'][$i]['last_modified']),
                    // 'import_counts' => $vars['import_list'][$i]['import_counts'],
                    // 'type'          => $vars['import_list'][$i]['type'],
                    'format'        => lang($vars['import_list'][$i]['format']),
                    array('toolbar_items' => array(
                        'edit' => array(
                            'href'      => ee()->sie->url('import_configure', array('token' => $vars['import_list'][$i]['id'])),
                            'title'     => strtolower(lang('edit'))
                        ),
                        'download' => array(
                            'href'      => ee()->sie->url('make_import', array('id' => $vars['import_list'][$i]['id'], 'token' => $vars['import_list'][$i]['token'], 'batches' => $vars['import_list'][$i]['settings']['configure']['import_settings']['import_ajax_batch'])),
                            'title'     => strtolower(lang('import')),
                            'class'     => "download-export $subClass"
                        ),
                        // cron feature
                        'rte-link' => array(
                            'href'     => 'javascript:void(0);',
                            'title'     => strtolower(lang('import_cron_url')),
                            'class'     => 'passkey',
                            'copy-link'      => ee()->functions->create_url("?ACT=".ee()->sieModel->getActionID("sie_import").AMP.'token='.$vars['import_list'][$i]['token']),
                        ),
                    )),
                    array(
                        'name'  => 'selection[]',
                        'value' => $vars['import_list'][$i]['id'],
                        'data'  => array(
                            'confirm' => lang('export') . ': <b>' . htmlentities($vars['import_list'][$i]['name'], ENT_QUOTES, 'UTF-8') . '</b>'
                        )
                    )
                );
                unset($vars['import_list'][$i]['settings']);

                $attrs = array();
                if (ee()->session->flashdata('return_id') == $vars['import_list'][$i]['token'])
                {
                    $attrs = array('class' => 'selected');
                }

                $fieldData[] = array(
                    'attrs' => $attrs,
                    'columns' => $columns
                );
            }
            
        }

        unset($vars['import_list']);
        $table->setData($fieldData);

        $vars['table'] = $table->viewData(ee()->sie->url());
        return $vars;
    
    }

    /* handle configuration settings */
    function handleImportConfigure($vars){

        @session_start();

        if(isset($_SESSION['Smart_import_export']['setting']) && $_SESSION['Smart_import_export']['setting'] != NULL){

            $settingString = "<input type='hidden' name='import' value=''>";
            $settingString .= "<table>";
            foreach($_SESSION['Smart_import_export']['setting'] as $set_name=>$set_data){
                $input_field = "<input type='hidden' name='setting[".$set_name."]' value='".$set_data."'>";
                if($set_name == 'import_channel'){
                    $channel = ee('Model')->get('Channel')->filter('channel_id',$set_data)->filter('site_id', $this->site_id)->first();
                    $set_data = $channel->channel_title;
                }
                // $input_field = "<input type='hidden' name='setting[".$set_name."]' value='".$set_data."'>";
                $settingString .= "<tr><td><label>".lang($set_name)."</label></td><td>".($set_data.$input_field)."</td></tr>";
            }
            $settingString .= "</table>";
            $settingString .= "<a class='btn' href='".ee()->sie->url('import_form',array($vars['import_id']))."'><b>Go to setting</b></a>";

            $settingFields = array(
                'title'     => "Import Setting",
                'fields' => array(
                    'setting_fields' => array(
                        'type' => 'html', 
                        'content' =>$settingString,
                    )
                )
            );
        }elseif(isset($vars['import_id']) && $vars['import_id'] !== '0'){
            unset($_SESSION['Smart_import_export']);
            $importData = ee()->sieModel->getImportData($vars['import_id']);
            if(empty($importData)){
                show_error(lang('no_import_configuration_data_found'));
            }
            $importData = $importData[0];
            if(isset($importData['settings']) && !empty($importData['settings'])){
                $importDataSettingsFull = unserialize(base64_decode($importData['settings']));
                if(isset($importDataSettingsFull['setting']) && !empty($importDataSettingsFull['setting'])){
                    $importDataSettings = $importDataSettingsFull['setting'];
                    $_POST['setting'] = $importDataSettingsFull['setting'];
                }else{
                    $importDataSettings = array();
                }
            }else{
                $importDataSettings = array();
            }


            $settingString = "<input type='hidden' name='import' value='".$vars['import_id']."'>";
            $settingString .= "<table>";
            foreach($importDataSettings as $set_name=>$set_data){
                $input_field = "<input type='hidden' name='setting[".$set_name."]' value='".$set_data."'>";
                $settingString .= "<tr><td><label>".lang($set_name)."</label></td><td>".($set_name=='import_file_type'?lang($set_data):$set_data).$input_field."</td></tr>";
            }
            $settingString .= "</table>";
            $settingString .= "<a class='btn' href='".ee()->sie->url('import_form',array($vars['import_id']))."'><b>Go to setting</b></a>";

            $settingFields = array(
                'title'     => "Import Setting",
                'fields' => array(
                    'setting_fields' => array(
                        'type' => 'html', 
                        'content' =>$settingString,
                    )
                )
            );
        }else{
            show_error(lang('no_import_configuration_data_found'));
        }

        if(isset($_SESSION['Smart_import_export'])){
            if($_SESSION['Smart_import_export']['setting']['import_file_type'] == 'csv'){
                $importFieldsHeader = $this->csvToArrayHeader($_SESSION['Smart_import_export'], "yes");
                // $importFieldsFull = $this->csvToArray($_SESSION['Smart_import_export'], "no");
            }elseif($_SESSION['Smart_import_export']['setting']['import_file_type'] == 'xml'){
                $importFieldsHeader = $this->xmlToArrayHeader($_SESSION['Smart_import_export'], "yes");
                // $importFieldsFull = $this->xmlToArray($_SESSION['Smart_import_export'], "no");
            }elseif($_SESSION['Smart_import_export']['setting']['import_file_type'] == 'third_party_xml'){
                $importFieldsHeader = $this->thirdPartyXmlToArrayHeader($_SESSION['Smart_import_export'], "yes");
                // $importFieldsFull = $this->thirdPartyXmlToArray($_SESSION['Smart_import_export'], "no");
            }
            elseif($_SESSION['Smart_import_export']['setting']['import_file_type'] == 'json'){
                $importFieldsHeader = $this->jsonToArrayHeader($_SESSION['Smart_import_export']['setting'], "yes");
                // $importFieldsFull = $this->jsonToArray($_SESSION['Smart_import_export']['setting'], "no");
            }
            $channelID = $_SESSION[$this->module_name]['setting']['import_channel'];
            $cpPageTitle = lang('create_new_import');
        }elseif(isset($importDataSettings['import_file_type'])){
            if($importDataSettings['import_file_type'] == 'csv'){
                $importFieldsHeader = $this->csvToArrayHeader($importDataSettingsFull, "yes");
                // $importFieldsFull = $this->csvToArray($importDataSettingsFull, "no");
            }elseif($importDataSettings['import_file_type'] == 'xml'){
                $importFieldsHeader = $this->xmlToArrayHeader($importDataSettingsFull, "yes");
                // $importFieldsFull = $this->xmlToArray($importDataSettingsFull, "no");
            }elseif($importDataSettings['import_file_type'] == 'third_party_xml'){
                $importFieldsHeader = $this->thirdPartyXmlToArrayHeader($importDataSettingsFull, "yes");
                // $importFieldsFull = $this->thirdPartyXmlToArray($importDataSettingsFull, "no");
            }elseif($importDataSettings['import_file_type'] == 'json'){
                $importFieldsHeader = $this->jsonToArrayHeader($importDataSettingsFull['setting'], "yes");
                // $importFieldsFull = $this->jsonToArray($importDataSettingsFull['setting'], "no");
            }
            $channelID = $importDataSettingsFull['setting']['import_channel'];
            $cpPageTitle = lang('edit_import');
        }
        $ChannelFields = $this->getChannelFields($channelID);
        unset($_SESSION['Smart_import_export']['fields']);
        $checkForDuplicatFields = array(
            "" => "select",
            "title"=>"title",
            "url_title"=>"url_title"
        );

        $allowedFieldType = array('text', 'textarea', 'url', 'toggle', 'rte', 'radio', 'duration', 'checkboxes', 'zeal_color_picker', 'email_address');

        $allDirs = ee('Model')->get('UploadDestination')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('module_id', 0)
            ->all();
        $allDir = "";    
        foreach($allDirs as $Dir){  
            $allDir .= "<option value='".$Dir->id."'>".$Dir->name."</option>";
        }
        $upldActionString = "<option value='y'>Yes</option><option value='n'>No</option>";
        $defaultFields = array();
        $defaultFieldsNames = array('title', 'url_title', 'entry_date', 'expiry_date', 'author', 'system_authors', 'status');
        // $defaultFields = array();
        $individualFields = array();
        $catGroups = array();
        $importFieldsHeaderStringDefault = "";
        $importFieldsHeaderString = "";
        $duplicateHeader = array();
        $duplicateHeader[0] = '';
        $duplicateHeader[1] = 'title';
        $duplicateHeader[2] = 'url_title';


        // if($importFieldsHeader !== false){
        foreach($importFieldsHeader as $k=>$v){
            $importFieldsHeaderStringDefault .= '<option value="'.$k.'">'.$v.'</option>';
        }

        foreach($defaultFieldsNames as $dfields){

            if($dfields == 'system_authors'){
                if(SIE_APP_VER_L6){
                    $authors = ee('Model')->get('Member')->filter('group_id', 1)->all()->getDictionary('member_id', 'username');
                }else{
                    $authors = ee('Model')->get('Member')->filter('role_id', 1)->all()->getDictionary('member_id', 'username');
                }
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure'][$dfields])){
                    foreach($authors as $k=>$v){
                        if($k==$importDataSettingsFull['configure'][$dfields]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    foreach($authors as $k=>$v){
                        $sel_string = '';
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }

                $fields = "<select name='".$dfields."' class=''>";
                $fields .= $importFieldsHeaderString;
                $fields .= '</select>';

                $defaultFields[] = array(
                    'title'     => $dfields.'_label',
                    'desc'      => $dfields.'_desc',
                    'fields' => array(
                        'default_fields['.$dfields.']' => array(
                            'type' => 'html', 
                            'content' =>$fields,
                        )
                    )
                );

            }else{

                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure'][$dfields])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure'][$dfields]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }

                $fields = "<select name='".$dfields."' class=''>";
                $fields .= $importFieldsHeaderString;
                $fields .= '</select>';

                $defaultFields[] = array(
                    'title'     => $dfields.'_label',
                    'desc'      => $dfields.'_desc',
                    'fields' => array(
                        'default_fields['.$dfields.']' => array(
                            'type' => 'html', 
                            'content' =>$fields,
                        )
                    )
                );

            }


        }

        if(isset($ChannelFields['group_fields'])){

            foreach($ChannelFields['group_fields'] as $GFields){
                if($GFields['field_type'] == 'grid'){
                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }

                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' )</label></td><td><select value="about_image" name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';

                    $grid_action_unique_fields = '';
                    $grid_action_unique_select = isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['action']) ? $importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['action'] : 0;

                    foreach($GFields['field_data'] as $gridColumn){
                        $file_field = "";
                        $importFieldsHeaderString = "";
                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }


                        $fields .= '<tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                            $importFileLocationString = "";
                            $importFileLocationActionString = "";
                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                foreach($allDirs as $dir){
                                    if($dir->id==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                }
                            }else{
                                $importFileLocationString = $allDir;
                            }

                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                foreach($upldAction as $k=>$v){
                                    if($v==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                }
                            }else{
                                $importFileLocationActionString = $upldActionString;
                            }


                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                        }
                        $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';

                        //V3.1.0 : GRID OPTIONS FEATURE
                        $grid_action_check_str = '';
                        if($gridColumn['col_id'] == $grid_action_unique_select){
                            $grid_action_check_str = 'selected';
                        }
                        $grid_action_unique_fields .= '<option '.$grid_action_check_str.'  value="'.$gridColumn['col_id'].'">'.$gridColumn['col_label'].'</option>';
                    }  

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_check_str_0 = '';
                    $grid_action_check_str_m1 = '';
                    if(0 == $grid_action_unique_select){
                        $grid_action_check_str_0 = 'selected';
                    }
                    if(-1 == $grid_action_unique_select){
                        $grid_action_check_str_m1 = 'selected';
                    }
                    $fields .= '<tr><td><label>Action to take when an entry is updated:</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][action]"><option '.$grid_action_check_str_0.' value="0">Delete all existing rows and add new</option><option '.$grid_action_check_str_m1.' value="-1">Keep existing rows and append new</option><optgroup label="Update the row if this column matches:">'.$grid_action_unique_fields.'</optgroup>';

                    $fields .= '</table>';      

                    $groupFields[] = array(
                        'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'group_fields['.$channelID.']['.$GFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($GFields['field_type'] == 'file_grid'){
                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }

                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' )</label></td><td><select value="about_image" name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';

                    $grid_action_unique_fields = '';
                    $grid_action_unique_select = isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['action']) ? $importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['action'] : 0;

                    foreach($GFields['field_data'] as $gridColumn){
                        $file_field = "";
                        $importFieldsHeaderString = "";
                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }


                        $fields .= '<tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                            $importFileLocationString = "";
                            $importFileLocationActionString = "";
                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                foreach($allDirs as $dir){
                                    if($dir->id==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                }
                            }else{
                                $importFileLocationString = $allDir;
                            }

                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                foreach($upldAction as $k=>$v){
                                    if($v==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                }
                            }else{
                                $importFileLocationActionString = $upldActionString;
                            }


                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                        }
                        $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';

                        //V3.1.0 : GRID OPTIONS FEATURE
                        $grid_action_check_str = '';
                        if($gridColumn['col_id'] == $grid_action_unique_select){
                            $grid_action_check_str = 'selected';
                        }
                        $grid_action_unique_fields .= '<option '.$grid_action_check_str.'  value="'.$gridColumn['col_id'].'">'.$gridColumn['col_label'].'</option>';
                    }  

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_check_str_0 = '';
                    $grid_action_check_str_m1 = '';
                    if(0 == $grid_action_unique_select){
                        $grid_action_check_str_0 = 'selected';
                    }
                    if(-1 == $grid_action_unique_select){
                        $grid_action_check_str_m1 = 'selected';
                    }
                    $fields .= '<tr><td><label>Action to take when an entry is updated:</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][action]"><option '.$grid_action_check_str_0.' value="0">Delete all existing rows and add new</option><option '.$grid_action_check_str_m1.' value="-1">Keep existing rows and append new</option><optgroup label="Update the row if this column matches:">'.$grid_action_unique_fields.'</optgroup>';

                    $fields .= '</table>';      

                    $groupFields[] = array(
                        'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'group_fields['.$channelID.']['.$GFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($GFields['field_type'] == 'matrix'){

                    if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }

                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' )</label></td><td><select value="about_image" name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                    foreach($GFields['field_data'] as $gridColumn){
                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']][$gridColumn['col_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']][$gridColumn['col_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }


                        $fields .= '<tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                        if(in_array($gridColumn['col_type'], array('file'))){
                            $file_field = "<div class='extra_fields_box_cp'><label>Upload Dir.</label><select><option>select</option>".$allDir."</select></div><div class='extra_fields_box_cp'><label>Want to fetch ?</label><select><option>select</option></select>";
                        }
                        $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';
                    }  

                    $fields .= '</table>';      

                    $groupFields[] = array(
                        'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'group_fields['.$channelID.']['.$GFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($GFields['field_type'] == 'fluid_field'){


                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }



                    $fields = "<table>";

                    $fields .= '<tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></tr>';

                    foreach($GFields['field_data'] as $fluidFields){

                        if($fluidFields['field_type'] == 'grid'){

                            $importFieldsHeaderString = "";
                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main'])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' ) >> '.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][main]">';
                            $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                            foreach($fluidFields['field_data'] as $gridColumn){

                                $importFieldsHeaderString = "";
                                if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main'])){
                                    foreach($importFieldsHeader as $k=>$v){
                                        if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id']]){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                    }
                                }else{
                                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                                }

                                $fields .= '<tr><td><label>'.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' ) >> '.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                                $fields .= $importFieldsHeaderString.'</select>';

                                //V3.1.3 - add file other option for the fluid field 
                                if($gridColumn['col_type'] == "file" || $gridColumn['col_type'] == "assets"){
                                    // $fields .= '<table>';
                                        $importFieldsHeaderString = "";
                                        $file_field = "";
                                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']])){
                                            foreach($importFieldsHeader as $k=>$v){
                                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                            }
                                        }else{
                                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                                        }
                                        // $fields .= '<td>';
                                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                                            $importFileLocationString = "";
                                            $importFileLocationActionString = "";
                                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                                foreach($allDirs as $dir){
                                                    if($dir->id==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                                        $sel_string = 'selected';
                                                    }else{
                                                        $sel_string = '';
                                                    }
                                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                                }
                                            }else{
                                                $importFileLocationString = $allDir;
                                            }
                                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                                foreach($upldAction as $k=>$v){
                                                    if($v==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                                        $sel_string = 'selected';
                                                    }else{
                                                        $sel_string = '';
                                                    }
                                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                                }
                                            }else{
                                                $importFileLocationActionString = $upldActionString;
                                            }
                                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                                        }
                                        $fields .= $file_field.'</td>';
                                    // }  
                                    // $fields .= '</table>';      
                                }
                                $fields .="<br>"; 
                            }  

                        }else{

                            $importFieldsHeaderString = "";
                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].']">';
                            $fields .= $importFieldsHeaderString.'</select>';

                            //V3.1.3 - missing some file field setting for fluid
                            if($fluidFields['field_type'] == "file" || $fluidFields['field_type'] == "assets"){
                                    if(in_array($fluidFields['field_type'], array('file', 'assets'))){
                                        $importFileLocationString = "";
                                        $importFileLocationActionString = "";
                                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload'])){
                                            foreach($allDirs as $dir){
                                                if($dir->id==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload']){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                            }
                                        }else{
                                            $importFileLocationString = $allDir;
                                        }
                                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload_action'])){
                                            $upldAction = array('Yes'=>'y', 'No'=>'n');
                                            foreach($upldAction as $k=>$v){
                                                if($v==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload_action']){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                            }
                                        }else{
                                            $importFileLocationActionString = $upldActionString;
                                        }
                                        $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'-file_setting][upload]"><option value="0">select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                                    }
                                    $fields .= $file_field.'</td>';
                            }else{
                            }
                            $fields .= "</tr>";

                        }

                    }
                    $fields .= "</table>";

                    $groupFields[] = array(
                        'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'group_fields['.$channelID.']['.$GFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' => $fields,
                            )
                        )
                    );
                }else{

                    if(in_array($GFields['field_type'], $allowedFieldType)){
                        $checkForDuplicatFields["group_fields-".$GFields['field_type']."-".$GFields['field_id']] = $GFields['field_label'];
                    }

                    if($GFields['field_type'] == "file" || $GFields['field_type'] == "assets"){
                        $importFieldsHeaderString = "";
                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }

                        $fields = "";
                        $fields .= '<table>';
                            $importFieldsHeaderString = "";
                            $file_field = "";
                            if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$GFields['field_label'].' ( '.$GFields['field_type'].' )</label></td><td><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].']">';
                            if(in_array($GFields['field_type'], array('file', 'assets'))){
                                $importFileLocationString = "";
                                $importFileLocationActionString = "";
                                if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id'].'-file_setting']['upload'])){
                                    foreach($allDirs as $dir){
                                        if($dir->id==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id'].'-file_setting']['upload']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                    }
                                }else{
                                    $importFileLocationString = $allDir;
                                }

                                if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id'].'-file_setting']['upload_action'])){
                                    $upldAction = array('Yes'=>'y', 'No'=>'n');
                                    foreach($upldAction as $k=>$v){
                                        if($v==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id'].'-file_setting']['upload_action']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                    }
                                }else{
                                    $importFileLocationActionString = $upldActionString;
                                }


                                $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                            }
                            $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';
                        // }  

                        $fields .= '</table>';      
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        $groupFields[] = array(
                            'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                            'desc'      => '',
                            'fields' => array(
                                'group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].']' => array(
                                    'type' => 'html', 
                                    'content' =>$fields,
                                )
                            )
                        );
                    }else{
                        $importFieldsHeaderString = "";
                        if(isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }

                        $fields = "<select name='group_fields[channel][".$channelID."][fields][".$GFields['field_type']."][".$GFields['field_id']."]' class=''>";
                        $fields .= $importFieldsHeaderString;
                        $fields .= '</select>';

                        $groupFields[] = array(
                            'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                            'desc'      => '',
                            'fields' => array(
                                'group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].']' => array(
                                    'type' => 'html', 
                                    'content' =>$fields,
                                )
                            )
                        );

                        // $groupFields[] = array(
                        //     'title'     => $GFields['field_label']." (".$GFields['field_type'].")",
                        //     'desc'      => '',
                        //     'fields' => array(
                        //         'group_fields[channel]['.$channelID.'][fields]['.$GFields['field_type'].']['.$GFields['field_id'].']' => array(
                        //             'type' => 'select', 
                        //             'choices' =>$importFieldsHeader,
                        //             'value' => isset($importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']])?$importDataSettingsFull['configure']['group_fields']['channel'][$channelID]['fields'][$GFields['field_type']][$GFields['field_id']]:"",
                        //         )
                        //     )
                        // );

                    }
                }
            }
        }
        if(isset($ChannelFields['individual_fields'])){
            foreach($ChannelFields['individual_fields'] as $IFields){
                if($IFields['field_type'] == 'grid'){
                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }


                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                    // $importFieldsHeaderString = $importFieldsHeaderStringDefault;

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_unique_fields = '';
                    $grid_action_unique_select = isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['action']) ? $importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['action'] : 0;

                    foreach($IFields['field_data'] as $gridColumn){
                        $importFieldsHeaderString = "";
                        $file_field = "";
                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }


                        $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                            $importFileLocationString = "";
                            $importFileLocationActionString = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                foreach($allDirs as $dir){
                                    if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                }
                            }else{
                                $importFileLocationString = $allDir;
                            }

                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                foreach($upldAction as $k=>$v){
                                    if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                }
                            }else{
                                $importFileLocationActionString = $upldActionString;
                            }


                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                        }
                        $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';

                        //V3.1.0 : GRID OPTIONS FEATURE
                        $grid_action_check_str = '';
                        if($gridColumn['col_id'] == $grid_action_unique_select){
                            $grid_action_check_str = 'selected';
                        }
                        $grid_action_unique_fields .= '<option '.$grid_action_check_str.'  value="'.$gridColumn['col_id'].'">'.$gridColumn['col_label'].'</option>';
                    }  

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_check_str_0 = '';
                    $grid_action_check_str_m1 = '';
                    if(0 == $grid_action_unique_select){
                        $grid_action_check_str_0 = 'selected';
                    }
                    if(-1 == $grid_action_unique_select){
                        $grid_action_check_str_m1 = 'selected';
                    }
                    $fields .= '<tr><td><label>Action to take when an entry is updated:</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][action]"><option '.$grid_action_check_str_0.' value="0">Delete all existing rows and add new</option><option '.$grid_action_check_str_m1.' value="-1">Keep existing rows and append new</option><optgroup label="Update the row if this column matches:">'.$grid_action_unique_fields.'</optgroup>';

                    $fields .= '</table>';      
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    $individualFields[] = array(
                        'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($IFields['field_type'] == 'file_grid'){
                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }


                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                    // $importFieldsHeaderString = $importFieldsHeaderStringDefault;

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_unique_fields = '';
                    $grid_action_unique_select = isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['action']) ? $importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['action'] : 0;

                    foreach($IFields['field_data'] as $gridColumn){
                        $importFieldsHeaderString = "";
                        $file_field = "";
                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }


                        $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                            $importFileLocationString = "";
                            $importFileLocationActionString = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                foreach($allDirs as $dir){
                                    if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                }
                            }else{
                                $importFileLocationString = $allDir;
                            }

                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                foreach($upldAction as $k=>$v){
                                    if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                }
                            }else{
                                $importFileLocationActionString = $upldActionString;
                            }


                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                        }
                        $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';

                        //V3.1.0 : GRID OPTIONS FEATURE
                        $grid_action_check_str = '';
                        if($gridColumn['col_id'] == $grid_action_unique_select){
                            $grid_action_check_str = 'selected';
                        }
                        $grid_action_unique_fields .= '<option '.$grid_action_check_str.'  value="'.$gridColumn['col_id'].'">'.$gridColumn['col_label'].'</option>';
                    }  

                    //V3.1.0 : GRID OPTIONS FEATURE
                    $grid_action_check_str_0 = '';
                    $grid_action_check_str_m1 = '';
                    if(0 == $grid_action_unique_select){
                        $grid_action_check_str_0 = 'selected';
                    }
                    if(-1 == $grid_action_unique_select){
                        $grid_action_check_str_m1 = 'selected';
                    }
                    $fields .= '<tr><td><label>Action to take when an entry is updated:</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][action]"><option '.$grid_action_check_str_0.' value="0">Delete all existing rows and add new</option><option '.$grid_action_check_str_m1.' value="-1">Keep existing rows and append new</option><optgroup label="Update the row if this column matches:">'.$grid_action_unique_fields.'</optgroup>';

                    $fields .= '</table>';      
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    $individualFields[] = array(
                        'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($IFields['field_type'] == 'matrix'){
                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }


                    $fields = "";
                    $fields .= '<table><tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                    // $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    if(isset($IFields['field_data']) && is_array($IFields['field_data'])){
                        foreach($IFields['field_data'] as $gridColumn){
                            $importFieldsHeaderString = "";
                            $file_field = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id']]){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                            if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                                $importFileLocationString = "";
                                $importFileLocationActionString = "";
                                if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                    foreach($allDirs as $dir){
                                        if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                    }
                                }else{
                                    $importFileLocationString = $allDir;
                                }

                                if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                    $upldAction = array('Yes'=>'y', 'No'=>'n');
                                    foreach($upldAction as $k=>$v){
                                        if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                    }
                                }else{
                                    $importFileLocationActionString = $upldActionString;
                                }


                                $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                            }
                            $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';
                        }  
                    }

                    $fields .= '</table>';      
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    $individualFields[] = array(
                        'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' =>$fields,
                            )
                        )
                    );
                }elseif($IFields['field_type'] == 'fluid_field'){

                    $importFieldsHeaderString = "";
                    if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main'])){
                        foreach($importFieldsHeader as $k=>$v){
                            if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['main']){
                                $sel_string = 'selected';
                            }else{
                                $sel_string = '';
                            }
                            $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                        }
                    }else{
                        $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                    }



                    $fields = "<table>";

                    $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][main]">';
                    $fields .= $importFieldsHeaderString.'</select></td></tr>';

                    foreach($IFields['field_data'] as $fluidFields){

                        if($fluidFields['field_type'] == 'grid'){

                            $importFieldsHeaderString = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main'])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main']){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' ) >> '.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][main]">';
                            $fields .= $importFieldsHeaderString.'</select></td></td></tr>';
                            foreach($fluidFields['field_data'] as $gridColumn){

                                $importFieldsHeaderString = "";
                                if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['main'])){
                                    foreach($importFieldsHeader as $k=>$v){
                                        if($k==@$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id']]){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                    }
                                }else{
                                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                                }

                                $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' ) >> '.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' ) >> '.$gridColumn['col_label'].' ( '.$gridColumn['col_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].']">';
                                $fields .= $importFieldsHeaderString.'</select>';


                                if($gridColumn['col_type'] == "file" || $gridColumn['col_type'] == "assets"){

                                    // $fields .= '<table>';


                                        $importFieldsHeaderString = "";
                                        $file_field = "";
                                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']])){
                                            foreach($importFieldsHeader as $k=>$v){
                                                if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                            }
                                        }else{
                                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                                        }


                                        // $fields .= '<td>';
                                        if(in_array($gridColumn['col_type'], array('file', 'assets'))){
                                            $importFileLocationString = "";
                                            $importFileLocationActionString = "";
                                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload'])){
                                                foreach($allDirs as $dir){
                                                    if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload']){
                                                        $sel_string = 'selected';
                                                    }else{
                                                        $sel_string = '';
                                                    }
                                                    $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                                }
                                            }else{
                                                $importFileLocationString = $allDir;
                                            }

                                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action'])){
                                                $upldAction = array('Yes'=>'y', 'No'=>'n');
                                                foreach($upldAction as $k=>$v){
                                                    if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]['cols'][$gridColumn['col_id'].'-file_setting']['upload_action']){
                                                        $sel_string = 'selected';
                                                    }else{
                                                        $sel_string = '';
                                                    }
                                                    $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                                }
                                            }else{
                                                $importFileLocationActionString = $upldActionString;
                                            }


                                            $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'][cols]['.$gridColumn['col_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                                        }
                                        $fields .= $file_field.'</td>';
                                    // }  

                                    // $fields .= '</table>';      


                                }

                                $fields .="<br>"; 

                            }  
                        }else{

                            $importFieldsHeaderString = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id']]){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$fluidFields['field_label'].' ( '.$fluidFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].']">';
                            $fields .= $importFieldsHeaderString.'</select>';


                            if($fluidFields['field_type'] == "file" || $fluidFields['field_type'] == "assets"){

                                    if(in_array($fluidFields['field_type'], array('file', 'assets'))){
                                        $importFileLocationString = "";
                                        $importFileLocationActionString = "";
                                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload'])){
                                            foreach($allDirs as $dir){
                                                if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload']){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                            }
                                        }else{
                                            $importFileLocationString = $allDir;
                                        }

                                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload_action'])){
                                            $upldAction = array('Yes'=>'y', 'No'=>'n');
                                            foreach($upldAction as $k=>$v){
                                                if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]['fields'][$fluidFields['field_type']][$fluidFields['field_id'].'-file_setting']['upload_action']){
                                                    $sel_string = 'selected';
                                                }else{
                                                    $sel_string = '';
                                                }
                                                $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                            }
                                        }else{
                                            $importFileLocationActionString = $upldActionString;
                                        }


                                        $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'-file_setting][upload]"><option value="0">select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'][fields]['.$fluidFields['field_type'].']['.$fluidFields['field_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                                    }
                                    $fields .= $file_field.'</td>';

                            }else{
                            }
                            $fields .= "</tr>";
                        }

                    }
                    $fields .= "</table>";

                    $individualFields[] = array(
                        'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                        'desc'      => '',
                        'fields' => array(
                            'individual_fields['.$channelID.']['.$IFields['field_id'].']' => array(
                                'type' => 'html', 
                                'content' => $fields,
                            )
                        )
                    );
                }
                else{

                    if(in_array($IFields['field_type'], $allowedFieldType)){
                        $checkForDuplicatFields["individual_fields-".$IFields['field_type']."-".$IFields['field_id']] = $IFields['field_label'];
                    }


                    if($IFields['field_type'] == "file" || $IFields['field_type'] == "assets"){


                        $fields = "";
                        $fields .= '<table>';
                            $importFieldsHeaderString = "";
                            $file_field = "";
                            if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']])){
                                foreach($importFieldsHeader as $k=>$v){
                                    if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]){
                                        $sel_string = 'selected';
                                    }else{
                                        $sel_string = '';
                                    }
                                    $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                                }
                            }else{
                                $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                            }


                            $fields .= '<tr><td><label>'.$IFields['field_label'].' ( '.$IFields['field_type'].' )</label></td><td><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']">';
                            if(in_array($IFields['field_type'], array('file', 'assets'))){
                                $importFileLocationString = "";
                                $importFileLocationActionString = "";
                                if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id'].'-file_setting']['upload'])){
                                    foreach($allDirs as $dir){
                                        if($dir->id==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id'].'-file_setting']['upload']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationString .= '<option value="'.$dir->id.'" '.$sel_string.'>'.$dir->name.'</option>';
                                    }
                                }else{
                                    $importFileLocationString = $allDir;
                                }

                                if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id'].'-file_setting']['upload_action'])){
                                    $upldAction = array('Yes'=>'y', 'No'=>'n');
                                    foreach($upldAction as $k=>$v){
                                        if($v==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id'].'-file_setting']['upload_action']){
                                            $sel_string = 'selected';
                                        }else{
                                            $sel_string = '';
                                        }
                                        $importFileLocationActionString .= '<option value="'.$v.'" '.$sel_string.'>'.$k.'</option>';
                                    }
                                }else{
                                    $importFileLocationActionString = $upldActionString;
                                }


                                $file_field = '<div class="extra_fields_box_cp"><label>Upload Dir.</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'-file_setting][upload]"><option>select</option>'.$importFileLocationString.'</select></div><div class="extra_fields_box_cp"><label>Want to fetch ?</label><select name="individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].'-file_setting][upload_action]"><option value="0">select</option>'.$importFileLocationActionString.'</select></div>';
                            }
                            $fields .= $importFieldsHeaderString.'</select>'.$file_field.'</td></tr>';
                        // }  

                        $fields .= '</table>';      
                        // $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        $individualFields[] = array(
                            'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                            'desc'      => '',
                            'fields' => array(
                                'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                                    'type' => 'html', 
                                    'content' =>$fields,
                                )
                            )
                        );
                    }else{


                        $importFieldsHeaderString = "";
                        if(isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']])){
                            foreach($importFieldsHeader as $k=>$v){
                                if($k==$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]){
                                    $sel_string = 'selected';
                                }else{
                                    $sel_string = '';
                                }
                                $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                            }
                        }else{
                            $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                        }

                        $fields = "<select name='individual_fields[channel][".$channelID."][fields][".$IFields['field_type']."][".$IFields['field_id']."]' class=''>";
                        $fields .= $importFieldsHeaderString;
                        $fields .= '</select>';

                        $individualFields[] = array(
                            'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                            'desc'      => '',
                            'fields' => array(
                                'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                                    'type' => 'html', 
                                    'content' =>$fields,
                                )
                            )
                        );






                        // $individualFields[] = array(
                        //     'title'     => $IFields['field_label']." (".$IFields['field_type'].")",
                        //     'desc'      => '',
                        //     'fields' => array(
                        //         'individual_fields[channel]['.$channelID.'][fields]['.$IFields['field_type'].']['.$IFields['field_id'].']' => array(
                        //             'type' => 'select', 
                        //             'choices' =>$importFieldsHeader,
                        //             'value' => isset($importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']])?$importDataSettingsFull['configure']['individual_fields']['channel'][$channelID]['fields'][$IFields['field_type']][$IFields['field_id']]:"",
                        //         )
                        //     )
                        // );



                    }
                }
            }
        }

        if(isset($ChannelFields['cat_groups'])){
            foreach($ChannelFields['cat_groups'] as $catGrp){

                $cat_group_dropdown_string = "";
                $category_dropdown_string = "";

                if(isset($importDataSettingsFull['configure']['cat_group']['channel'][$channelID][$catGrp['group_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['cat_group']['channel'][$channelID][$catGrp['group_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $cat_group_dropdown_string .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    foreach($importFieldsHeader as $k=>$v){
                        $cat_group_dropdown_string .= '<option value="'.$k.'">'.$v.'</option>';
                    }
                }

                if(isset($importDataSettingsFull['configure']['category']['channel'][$channelID][$catGrp['group_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['category']['channel'][$channelID][$catGrp['group_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $category_dropdown_string .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    foreach($importFieldsHeader as $k=>$v){
                        $category_dropdown_string .= '<option value="'.$k.'">'.$v.'</option>';
                    }
                }

                $default_value_text = '<table><tr><td><label>Category Default value</label></td><td><input type="text" value="'.(isset($importDataSettingsFull['configure']['cat_group_default_value']['channel'][$channelID][$catGrp['group_id']]) ? $importDataSettingsFull['configure']['cat_group_default_value']['channel'][$channelID][$catGrp['group_id']] : "").'" name="cat_group_default_value[channel]['.$channelID.']['.$catGrp['group_id'].']"></td></tr>';

                foreach($importFieldsHeader as $k=>$v){
                    $cat_group_dropdown_string .= '<option value="'.$k.'">'.$v.'</option>';
                }

                $cat_group_dropdown = '<tr><td><label>Category Group</label></td><td><select name="cat_group[channel]['.$channelID.']['.$catGrp['group_id'].']">'.$cat_group_dropdown_string.'</select></td></tr>';
                // $cat_group_dropdown .= '</select></td></tr>';

                $cat_dropdown = '<tr><td><label>Category</label></td><td><select name="category[channel]['.$channelID.']['.$catGrp['group_id'].']">'.$category_dropdown_string.'</select></td></tr>';
                // $cat_group_dropdown .= '</select></td></tr>';

                $delimiter_value_text = '<tr><td><label>Category Delimiter</label></td><td><input type="text" value="'.(isset($importDataSettingsFull['configure']['cat_group_delimiter']['channel'][$channelID][$catGrp['group_id']])?$importDataSettingsFull['configure']['cat_group_delimiter']['channel'][$channelID][$catGrp['group_id']]:"").'" name="cat_group_delimiter[channel]['.$channelID.']['.$catGrp['group_id'].']"></td></tr>';

                //V3.1.4 - provide option to select parent child category delimiter
                $parent_child_delimiter_value_text = '<tr><td><label>Parent Child Category Delimiter</label></td><td><input type="text" value="'.(isset($importDataSettingsFull['configure']['parent_child_cat_group_delimiter']['channel'][$channelID][$catGrp['group_id']])?$importDataSettingsFull['configure']['parent_child_cat_group_delimiter']['channel'][$channelID][$catGrp['group_id']]:"").'" name="parent_child_cat_group_delimiter[channel]['.$channelID.']['.$catGrp['group_id'].']"></td></tr>';
                //V3.1.4 - provide option to create category if it does not exist
                $create_category_if_not = '<tr><td><label>Create category if it does not exist</label></td><td><input type="checkbox" value="1" '.(isset($importDataSettingsFull['configure']['create_category_if_not']['channel'][$channelID][$catGrp['group_id']])? "checked" :"").' name="create_category_if_not[channel]['.$channelID.']['.$catGrp['group_id'].']"></td>';
                //V3.1.4 - Force exact categories - To find the category in exact category level.
                $force_exact_categories = '<tr><td><label>Force exact categories</label><em>To find the category in exact level. </em></td><td><input type="checkbox" value="1" '.(isset($importDataSettingsFull['configure']['force_exact_categories']['channel'][$channelID][$catGrp['group_id']])? "checked" :"").' name="force_exact_categories[channel]['.$channelID.']['.$catGrp['group_id'].']"></td></tr></table>';

                $catGroups[] = 
                    array(
                        'title'     => $catGrp['group_name'],
                        'desc'     => $catGrp['group_name'],
                        'fields' => array(
                            'cat_group_default_value' => array(
                                'type' => 'html',
                                'content' => $default_value_text.$cat_group_dropdown.$cat_dropdown.$delimiter_value_text.$parent_child_delimiter_value_text.$create_category_if_not.$force_exact_categories,
                            )
                        )
                    );
            }
        }


        $smart_seo = ee()->sieModel->checkModuleInstalled('Smart_seo');
        if($smart_seo){
            $fields = ee('Model')->get('smart_seo:SsField')->filter("ss_site_id", ee()->config->item("site_id"))->order('ss_field_order');
            $ss_fields = $fields->all();

                
            $fields_str = "";

            $fields_str .= '<table><tr><td><label>Smart SEO</label></td><td></td></td></tr>';


            foreach($fields->all() as $ss_fields){
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure']['smart_seo_fields']['channel'][$channelID]['fields'][$ss_fields->ss_field_name])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['smart_seo_fields']['channel'][$channelID]['fields'][$ss_fields->ss_field_name]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }


                $fields_str .= '<tr><td><label>'.$ss_fields->ss_field_label.'</label></td><td><select name="smart_seo_fields[channel]['.$channelID.'][fields]['.$ss_fields->ss_field_name.']">';
                $fields_str .= $importFieldsHeaderString.'</select></td></tr>';
            }  


            $fields_str .= '</table>';      

            $smartSEOFields[] = array(
                'title'     => "Smart SEO",
                'desc'      => '',
                'fields' => array(
                    'smart_seo_fields' => array(
                        'type' => 'html', 
                        'content' =>$fields_str,
                    )
                )
            );
        }


        $seeo = ee()->sieModel->checkModuleInstalled('Seeo');
        if($seeo){

            ee()->load->add_package_path(PATH_THIRD . 'seeo');
            ee()->load->library(array('seeo_settings'));
            ee()->lang->loadfile('seeo');

            $seeo_meta_fields = new EEHarbor\Seeo\Helpers\MetaFields;

                
            $fields_str = "";

            $fields_str .= '<table><tr><td><label>SEEO</label></td><td></td></td></tr>';

            foreach($seeo_meta_fields->getTabFields('all', $seeo_meta_fields->default_entry_meta) as $seeo_fields){
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure']['seeo_fields']['channel'][$channelID]['fields'][$seeo_fields['field_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['seeo_fields']['channel'][$channelID]['fields'][$seeo_fields['field_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }

                $fields_str .= '<tr><td><label>'.$seeo_fields['field_label'].'</label></td><td><select name="seeo_fields[channel]['.$channelID.'][fields]['.$seeo_fields['field_id'].']">';
                $fields_str .= $importFieldsHeaderString.'</select></td></tr>';
            }  


            $fields_str .= '</table>';      

            $SEEOFields[] = array(
                'title'     => "SEEO",
                'desc'      => '',
                'fields' => array(
                    'seeo_fields' => array(
                        'type' => 'html', 
                        'content' =>$fields_str,
                    )
                )
            );
        }

        $seo_lite = ee()->sieModel->checkModuleInstalled('Seo_lite');
        if($seo_lite){
            ee()->lang->loadfile('seo_lite');

            $seo_lite_fields = array();

            $seo_lite_fields['seo_lite_title'] = array(
                'field_id' => 'seo_lite_title',
                'field_label' => lang('seotitle'),
            );
     
            if(ee()->config->item('seolite_show_keywords_field') != 'n') {
    
                $seo_lite_fields['seo_lite_keywords'] = array(
                'field_id' => 'seo_lite_keywords',
                'field_label' => lang('seokeywords'),
            );
            }
     
             $seo_lite_fields['seo_lite_description'] = array(
                'field_id' => 'seo_lite_description',
                'field_label' => lang('seodescription'),
     
            );
                     
            $fields_str = "";

            $fields_str .= '<table><tr><td><label>SEO Lite</label></td><td></td></td></tr>';

            foreach($seo_lite_fields as $seo_lite_fields){
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure']['seo_lite_fields']['channel'][$channelID]['fields'][$seo_lite_fields['field_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['seo_lite_fields']['channel'][$channelID]['fields'][$seo_lite_fields['field_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }


                $fields_str .= '<tr><td><label>'.$seo_lite_fields['field_label'].'</label></td><td><select name="seo_lite_fields[channel]['.$channelID.'][fields]['.$seo_lite_fields['field_id'].']">';
                $fields_str .= $importFieldsHeaderString.'</select></td></tr>';
            }  


            $fields_str .= '</table>';      

            $SEOLiteFields[] = array(
                'title'     => "SEO Lite",
                'desc'      => '',
                'fields' => array(
                    'seo_lite_fields' => array(
                        'type' => 'html', 
                        'content' =>$fields_str,
                    )
                )
            );
        }

        $structure = ee()->sieModel->checkModuleInstalled('Structure');
        if($structure){

            ee()->load->add_package_path(PATH_THIRD . 'structure');
            require_once PATH_THIRD.'structure/tab.structure.php';
            $structure_tab = new Structure_tab();
                
            $fields_str = "";

            $fields_str .= '<table><tr><td><label>Structure</label></td><td></td></td></tr>';

            foreach($structure_tab->publish_tabs($channelID) as $structure_fields){
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure']['structure_fields']['channel'][$channelID]['fields'][$structure_fields['field_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['structure_fields']['channel'][$channelID]['fields'][$structure_fields['field_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }

                $fields_str .= '<tr><td><label>'.$structure_fields['field_label'].'</label></td><td><select name="structure_fields[channel]['.$channelID.'][fields]['.$structure_fields['field_id'].']">';
                $fields_str .= $importFieldsHeaderString.'</select></td></tr>';
            }  


            $fields_str .= '</table>';      

            $structureFields[] = array(
                'title'     => "Structure",
                'desc'      => '',
                'fields' => array(
                    'seeo_fields' => array(
                        'type' => 'html', 
                        'content' =>$fields_str,
                    )
                )
            );
        }
        
        $transcribe = ee()->sieModel->checkModuleInstalled('Transcribe');
        if($transcribe){

            ee()->load->add_package_path(PATH_THIRD . 'transcribe');
            require_once PATH_THIRD.'transcribe/tab.transcribe.php';
            $transcribe_tab = new Transcribe_tab();
                
            $fields_str = "";

            $fields_str .= '<table><tr><td><label>Transcribe</label></td><td></td></td></tr>';

            foreach($transcribe_tab->display($channelID) as $transcribe_fields){
                $importFieldsHeaderString = "";
                if(isset($importDataSettingsFull['configure']['transcribe_fields']['channel'][$channelID]['fields'][$transcribe_fields['field_id']])){
                    foreach($importFieldsHeader as $k=>$v){
                        if($k==$importDataSettingsFull['configure']['transcribe_fields']['channel'][$channelID]['fields'][$transcribe_fields['field_id']]){
                            $sel_string = 'selected';
                        }else{
                            $sel_string = '';
                        }
                        $importFieldsHeaderString .= '<option value="'.$k.'" '.$sel_string.'>'.$v.'</option>';
                    }
                }else{
                    $importFieldsHeaderString = $importFieldsHeaderStringDefault;
                }

                $fields_str .= '<tr><td><label>'.$transcribe_fields['field_label'].'</label></td><td><select name="transcribe_fields[channel]['.$channelID.'][fields]['.$transcribe_fields['field_id'].']">';
                $fields_str .= $importFieldsHeaderString.'</select></td></tr>';
            }  


            $fields_str .= '</table>';      

            $transcribeFields[] = array(
                'title'     => "Transcribe",
                'desc'      => '',
                'fields' => array(
                    'seeo_fields' => array(
                        'type' => 'html', 
                        'content' =>$fields_str,
                    )
                )
            );
        }
        
        
        $vars['sections'] = array(
            'setting_disply' => isset($settingFields) ? array($settingFields) : array(),
            // 'default_fields' => array(
            //     array(
            //         'title'     => 'title_label',
            //         'desc'      => '',
            //         'fields' => array(
            //             'title' => array(
            //                 'type' => 'select', 
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['title'])?$importDataSettingsFull['configure']['title']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'url_title_label',
            //         'desc'      => 'url_title_desc',
            //         'fields' => array(
            //             'url_title' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['url_title'])?$importDataSettingsFull['configure']['url_title']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'date_label',
            //         'desc'      => 'date_desc',
            //         'fields' => array(
            //             'entry_date' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['entry_date'])?$importDataSettingsFull['configure']['entry_date']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'expiry_date_label',
            //         'desc'      => 'expiry_date_desc',
            //         'fields' => array(
            //             'expiry_date' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['expiry_date'])?$importDataSettingsFull['configure']['expiry_date']:"",
            //             )
            //         )
            //     ),
            //     /* from version 3.0.1 */
            //     array(
            //         'title'     => 'author_label',
            //         'desc'      => 'author_desc',
            //         'fields' => array(
            //             'author' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['author'])?$importDataSettingsFull['configure']['author']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'status_label',
            //         'desc'      => 'status_desc',
            //         'fields' => array(
            //             'status' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['status'])?$importDataSettingsFull['configure']['status']:"",
            //             )
            //         )
            //     ),
            // ),
            'default_fields' => $defaultFields,
            'group_fields' => isset($groupFields) ? $groupFields : array(),
            'individual_fields' => isset($individualFields) ? $individualFields : array() ,
            'smart_seo_fields' => isset($smartSEOFields) ? $smartSEOFields : array() ,
            'seeo_fields' => isset($SEEOFields) ? $SEEOFields : array() ,
            'seo_lite_fields' => isset($SEOLiteFields) ? $SEOLiteFields : array() ,
            'structure_fields' => isset($structureFields) ? $structureFields : array() ,
            'transcribe_fields' => isset($transcribeFields) ? $transcribeFields : array() ,
            // 'pages' => array(
            //     array(
            //         'title'     => 'add_as_page_label',
            //         'desc'      => 'add_as_page_desc',
            //         'fields' => array(
            //             'pages' => array(
            //                 'type' => 'yes_no',
            //                 'value' => isset($importDataSettingsFull['configure']['pages'])?$importDataSettingsFull['configure']['pages']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'page_url_label',
            //         'desc'      => 'page_url_desc',
            //         'fields' => array(
            //             'page_url' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['page_url'])?$importDataSettingsFull['configure']['page_url']:"",
            //             )
            //         )
            //     ),
            //     array(
            //         'title'     => 'page_template_label',
            //         'desc'      => 'page_template_desc',
            //         'fields' => array(
            //             'page_template' => array(
            //                 'type' => 'select',
            //                 'choices' =>$importFieldsHeader,
            //                 'value' => isset($importDataSettingsFull['configure']['page_template'])?$importDataSettingsFull['configure']['page_template']:"",
            //             )
            //         )   
            //     ),
            // ),
            'Check for duplicate entries' => array(
                array(
                    'title'     => 'filed_to_check_label',
                    'desc'      => 'filed_to_check_desc',
                    'fields' => array(
                        'duplicate_check_with' => array(
                            'type' => 'select',
                            'choices' =>$checkForDuplicatFields,
                            'value' => (isset($importDataSettingsFull['configure']['duplicate_check_with']) && $importDataSettingsFull['configure']['duplicate_check_with'] != "")?$importDataSettingsFull['configure']['duplicate_check_with']:"",
                        )
                    )
                ),
                array(
                    'title'     => 'duplicate_action_label',
                    'desc'      => 'duplicate_action_desc',
                    'fields' => array(
                        'duplicate_action' => array(
                            'type' => 'select',
                            // 'choices' =>array(0=>"Insert New Row", 1=>"Update", 2=>"Delete Old and Insert New", 3=>"Do Nothing"),
                            'choices' =>array(0=>"Insert New Row", 1=>"Update", 3=>"Do Nothing"),
                            'value' => isset($importDataSettingsFull['configure']['duplicate_action'])?$importDataSettingsFull['configure']['duplicate_action']:0,
                        )
                    )
                ),
                //delete existing entries
                array(
                    'title'     => 'delete_existing_entries',
                    'desc'      => 'delete_existing_entries_desc',
                    'fields' => array(
                        'delete_existing_entries' => array(
                            'type' => 'radio',
                            'choices' => array( 0 => "No", 1 => "Yes" ),
                            'value' => isset($importDataSettingsFull['configure']['delete_existing_entries'])?$importDataSettingsFull['configure']['delete_existing_entries']:0,
                        )
                    )
                )
            ),
            'categories' => $catGroups,  
            'import_settings' => array(
                array(
                    'title'     => 'name_label',
                    'desc'      => 'name_desc',
                    'fields' => array(
                        'import_settings[import_name]' => array(
                            'type' => 'text', 
                            'value' => isset($importDataSettingsFull['configure']['import_settings']['import_name']) ? $importDataSettingsFull['configure']['import_settings']['import_name'] : "",
                        )
                    )
                ),
                // array(
                //     'title'     => 'access_import_without_login_label',
                //     'desc'      => 'access_import_without_login_desc',
                //     'fields' => array(
                //         'import_settings[import_without_login]' => array(
                //             'type' => 'yes_no',
                //             'value' => isset($importDataSettingsFull['configure']['import_settings']['import_without_login']) ? $importDataSettingsFull['configure']['import_settings']['import_without_login'] : "y",
                //         )
                //     )
                // ),
                // array(
                //     'title'     => 'import_type_label',
                //     'desc'      => 'import_type_desc',
                //     'fields' => array(
                //         'import_settings[import_type]' => array(
                //             'type' => 'radio',
                //             'choices' => array(
                //                 'private'  => lang('private'),
                //                 'public' => lang('public'),
                //             ),
                //             'value' => isset($importDataSettingsFull['configure']['import_settings']['import_type']) ? $importDataSettingsFull['configure']['import_settings']['import_type'] : "private",
                //         )
                //     )
                // ),
                array(
                    'title'     => 'import_comment_label',
                    'desc'      => 'import_comment_desc',
                    'fields' => array(
                        'import_settings[import_comment]' => array(
                            'type' => 'textarea',
                            'value' => isset($importDataSettingsFull['configure']['import_settings']['import_comment']) ? $importDataSettingsFull['configure']['import_settings']['import_comment'] : "",
                        )
                    )
                ),
                // array(
                //     'title'     => 'import_procedure_label',
                //     'desc'      => 'import_procedure_desc',
                //     'fields' => array(
                //         'import_settings[import_procedure]' => array(
                //             'type' => 'select',
                //             'choices' => array(
                //                 'normal'  => lang('normal'),
                //                 'ajax' => lang('ajax'),
                //             ),
                //             'value' => isset($importDataSettingsFull['configure']['import_settings']['import_procedure']) ? $importDataSettingsFull['configure']['import_settings']['import_procedure'] : "",
                //         )
                //     )
                // ),
                array(
                    'title'     => 'batches_label',
                    'desc'      => 'import_batches_desc',
                    'fields' => array(
                        'import_settings[import_ajax_batch]' => array(
                            'type' => 'select',
                            'choices' => array(
                                1  => 1,
                                2  => 2,
                                5  => 5,
                                10 => 10,
                                20 => 20,
                                50 => 50,
                                75 => 75,
                                100 => 100,
                                150 => 150,
                                200 => 200,
                            ),
                            'value' => isset($importDataSettingsFull['configure']['import_settings']['import_ajax_batch']) ? $importDataSettingsFull['configure']['import_settings']['import_ajax_batch'] : 2,
                        )
                    )
                ),
            ),
      
        );
        // }

        $vars['save_btn_text_working'] = 'btn_saving';

        // ee()->javascript->output('$(document).ready(function () {
        //     EE.cp.fieldToggleDisable(null, "import_file_type");
        // });');

        $saveBtnText = sprintf(lang('Save Configuration'), lang('action'));
        // $cpPageTitle = lang('create_new_import');
        $baseURL = isset($vars['import_id']) ? $this->url('import_configure/'.$vars['import_id']) : $this->url('import_configure');

        $vars += array(
            'base_url'              => $baseURL,
            'cp_page_title'         => $cpPageTitle,
            // 'save_btn_text'         => $saveBtnText,
            // 'save_btn_text_working' => 'btn_saving'
            'buttons' => [
                            [
                                'name' => 'submit',
                                'type' => 'submit',
                                'value' => 'save',
                                'text' => 'save',
                                'working' => 'btn_saving'
                            ],

                        ],        
        );
        return $vars;
    }

    function getChannelFields($channelID){
        @session_start();

        if(isset($channelID) && !empty($channelID)){
            $allChannels = ee('Model')->get('Channel')->with('FieldGroups', 'CustomFields')->filter('channel_id',$channelID)->filter('site_id', $this->site_id)->order('channel_title', 'ASC')->all();
            ee()->load->model('grid_model');
            foreach($allChannels as $channel){
                $individualFields = array();
                $individualFields = $channel->CustomFields->sortBy('field_id')->map(function($field) {
                    return [
                        'field_label' => $field->field_label,
                        'field_id' => $field->getId(),
                        'field_name' => $field->field_name,
                        'field_type' => $field->field_type,
                        //'content_type' => $field->content_type,
                        'field_settings' => $field->field_settings,
                        'field_data' => ($field->field_type == 'grid' || $field->field_type == 'file_grid') ? ee()->grid_model->get_columns_for_field($field->getId(), 'channel') : (($field->field_type == 'fluid_field') ? $this->getAllFluidFields($field->field_settings) : (($field->field_type == 'matrix') ? $this->get_columns_for_matrix($field->getId()) : array()))
                    ];
                });

                $groupFields = array();
                $nos = 0;
                foreach($channel->FieldGroups as $fg) {
                    $fields = $fg->ChannelFields->sortBy('field_id')->asArray();
                    foreach($fields as $field){
                        $groupFields[$nos]['field_id'] = $field->field_id;
                        $groupFields[$nos]['field_name'] = $field->field_name;
                        $groupFields[$nos]['field_label'] = $field->field_name;
                        $groupFields[$nos]['field_type'] = $field->field_type;
                        $groupFields[$nos]['field_settings'] = $field->field_settings;
                        $groupFields[$nos]['field_data'] = ($field->field_type == 'grid' || $field->field_type == 'file_grid') ? ee()->grid_model->get_columns_for_field($field->getId(), 'channel') : (($field->field_type == 'fluid_field') ? $this->getAllFluidFields($field->field_settings) : (($field->field_type == 'matrix') ? $this->get_columns_for_matrix($field->getId()) : array()));
                        $nos++;
                    }
                }

                $channelCatGroups = ee('Model')->get('CategoryGroup')->filter('group_id', "IN", explode("|", $channel->cat_group))->filter('site_id', $this->site_id)->all();
                $CatGroups = array();
                foreach($channelCatGroups as $group){
                    $CatGroups[] = array('group_id'=>$group->group_id, 'group_name'=>$group->group_name);
                }
                return array('group_fields'=>$groupFields, 'individual_fields'=>$individualFields, 'cat_groups'=>$CatGroups);
            }
        }
    }

    /* hanlde import configuration */
    public function handleImportConfigureFormPostFinal($data){
        @session_start();


        // $rules = array();
        $rules = array(
            'import_settings[import_name]'      => 'required|xss',
            // 'title'                             => 'required|xss',
            /*'url_title'                         => 'required|xss',*/
        );
        if($_POST['title'] === "0"){
            $_POST['title'] = "";
        }
/*        if($_POST['url_title'] === "0"){
            $_POST['url_title'] = "";
        }
*/        $_POST['import_settings[import_name]'] = $_POST['import_settings']['import_name'];
        ee()->sie_custom_validation->validator->setRules($rules);
        $result = ee()->sie_custom_validation->validator->validate($_POST);


        // $result = true;
        if ($result->isValid())
        {
            if(isset($_SESSION['Smart_import_export']) && isset($_SESSION['Smart_import_export']['setting']['import'])){
                $this->_setSettings('configure', $data);
                ee()->sieModel->saveImport();
            }else{
        
                if($_POST['import'] != 0 && $_POST['import'] != ''){
                    ee()->sieModel->saveImport();
                }
            }
            unset($_SESSION['Smart_import_export']);
            return true;
        }
        else
        {
            return $result;
        }
    }

    function setSession($name, $data)
    {
        if(!isset($_SESSION))
        {
             session_start();
        }

        $_SESSION['sm'][$name] = $data;
    }

    function session($name)
    {
        if(!isset($_SESSION))
        {
             session_start();
        }

        if(isset($_SESSION['sm'][$name]))
        {
            return $_SESSION['sm'][$name];
        }

        return false;
    }

    function unsetSession($name = "")
    {

        if(!isset($_SESSION))
        {
             session_start();
        }

        if($name == "")
        {
            unset($_SESSION['sm'][$name]);
        }
        else
        {
            unset($_SESSION['sm']);
        }
    }

    /* handle import process */
    function handleImportSuccess($vars)
    {
        /*Define hepful variables*/
        if($vars['import_id'] == "")
        {
            $vars['import_id']  = ee()->input->get_post('import_id');
        }
        if($vars['token'] == "")
        {
            $vars['token']  = ee()->input->get_post('token');
        }
        if($vars['batch'] == "")
        {
            $vars['batch']  = ee()->input->get_post('batch');
        }
        if($vars['status'] == "")
        {
            $vars['status'] = ee()->input->get_post('status');
        }
        if($vars['offset'] == "")
        {
            $vars['offset'] = ee()->input->get_post('offset');
        }


        /*Load helpful classes*/
        ee()->load->library('table');

        //cron feature
        if(!isset($vars['use_in_cron'])){
            /*Append css and js files for beeter view of page*/
            ee()->cp->add_to_head('<link rel="stylesheet" href="'.URL_THIRD_THEMES.'smart_import_export/css/jquery.dataTables.min.css" type="text/css" media="screen" />');
            ee()->cp->add_to_foot('<script src="'.URL_THIRD_THEMES.'smart_import_export/js/jquery.dataTables.min.js"></script>');
        }

        /*reload the page with another batch if all data isnt exported*/
        if($vars['status'] == "pending" && $vars['batch'] !== '0')
        {
            $vars['redirect_import']  = ee()->sie->url('make_import', array('import_id' => $vars['import_id'], 'token' => $vars['token'], 'status' => $vars['status'], 'batches' => $vars['batch'], 'offset' => $vars['offset']));
        }
        else
        {
            $vars['redirect_import'] = false;
        }

        /*Setup fields to be show in table*/
        $vars['loading_image']        = URL_THIRD_THEMES."smart_import_export/images/indicator.gif";
        $vars['total_entries']        = $this->session('total_entries_' . $vars['token']);
        $vars['imported_entries']     = $this->session('imported_entries_' . $vars['token']);
        $vars['updated_entries']      = $this->session('updated_entries_' . $vars['token']);
        $vars['recreated_entries']    = $this->session('recreated_entries_' . $vars['token']);
        // delete existing entries
        $vars['deleted_existing_entries']  = $this->session('deleted_existing_entries_' . $vars['token']);
        $vars['skipped_entries']      = $this->session('skipped_entries_' . $vars['token']);
        $vars['memory_usage']         = $this->session('memory_usage_' . $vars['token']);
        $vars['total_memory_usage']   = $this->session('total_memory_usage_' . $vars['token']);
        $vars['time_taken']           = $this->session('time_taken_' . $vars['token']);
        $vars['total_time_taken']     = $this->session('total_time_taken_' . $vars['token']);
        $vars['data']                 = unserialize($this->session('ret_' . $vars['token']));

        /*Set column header of table*/
        $columns = array(
            'import_id'     => array('header' => lang('member_id')),
            );

        /*Data of insert table*/
        if(isset($vars['data']['insert']))
        {
            ee()->table->set_columns($columns);
            ee()->table->set_data($vars['data']['insert']);
            $vars['insert_data'] = ee()->table->generate();
        }

        /*Data of update table*/
        if(isset($vars['data']['update']))
        {
            ee()->table->set_columns($columns);
            ee()->table->set_data($vars['data']['update']);
            $vars['update_data'] = ee()->table->generate();
        }

        /*Data of recreated member table*/
        if(isset($vars['data']['delete']))
        {
            ee()->table->set_columns($columns);
            ee()->table->set_data($vars['data']['delete']);
            $vars['delete_data'] = ee()->table->generate();
        }

        /*Unset all the data to save memory*/
        unset($data);

        $js = "$('.insert_data table, .update_data table, .delete_data table').DataTable({
                    aLengthMenu: [
                    [10, 50, 100, 200, 400, -1],
                    [10, 50, 100, 200, 400, 'All']
                    ],
                });
                $('table').wrap('<div class=\"table-responsive\"></div>');
                $('table').addClass('table table-bordred table-striped'); \n";
        
        if($vars['redirect_import'] !== false)
        {
            $vars['redirect_import'] = str_replace("&amp;", "&", $vars['redirect_import']);
            $js .= 'setTimeout(function() {
                        window.location.href = "'.$vars['redirect_import'].'";
                    }, 3000);';
        }

        //cron feature
        if(!isset($vars['use_in_cron'])){
            ee()->javascript->output($js);
        }

        return $vars;
    }

    /* handle import process */ 
    public function handleMakeImport($importID, $token="", $batches=2, $status="", $offset=0){

        @session_start();
        $this->importData['process']['token'] = $token ; 
        if($offset == 0)
        {
            $this->setSession('total_entries_'.$token, 0);
            $this->setSession('imported_entries_'.$token, 0);
            $this->setSession('deleted_entries_'.$token, 0);
            $this->setSession('updated_entries_'.$token, 0);
            $this->setSession('recreated_entries_'.$token, 0);
            $this->setSession('skipped_entries_'.$token, 0);
            $this->setSession('total_memory_usage_'.$token, 0);
            $this->setSession('total_time_taken_'.$token, 0);
            //delete existing entry_id  
            $this->setSession('processed_entries_'.$token, array());    
            $this->setSession('deleted_existing_entries_'.$token, 0);
        }

        $this->setSession('memory_usage_'.$token, memory_get_usage());
        $this->setSession('time_taken_'.$token, time());


        $data = ee()->sieModel->getImportData($importID)[0];

        $settings = unserialize(base64_decode($data['settings']));

        $this->importData['process']['batches'] = $batches !== 0 ? $settings['configure']['import_settings']['import_ajax_batch'] : "CRON";
        // $this->importData['process']['batches'] = $batches;
        $this->importData['process']['offset'] = $offset;

        $channel_id = $settings['setting']['import_channel'];

        if($data['format'] == 'csv'){
            $importFieldsFull = $this->csvToArray($settings, "no");
            $this->importData['process']['total'] = count($importFieldsFull);
            $this->setSession('total_entries_'.$token, $this->importData['process']['total']);
        }elseif($data['format'] == 'xml'){
            $importFieldsFull = $this->xmlToArray($settings, "no");

            //hack for one entry issue (2.0.1)
            if(count($importFieldsFull) > 0 && !isset($importFieldsFull[0])){
                $tempp = $importFieldsFull;
                unset($importFieldsFull);
                $importFieldsFull[0] = $tempp;
            }
            //end of the hack
            $this->importData['process']['total'] = count($importFieldsFull);
            $this->setSession('total_entries_'.$token, $this->importData['process']['total']);
        }elseif($data['format'] == 'third_party_xml'){
            $importFieldsFull = $this->thirdPartyXmlToArray($settings, "no");

            //hack for one entry issue (2.0.1)
            if(count($importFieldsFull) > 0 && !isset($importFieldsFull[0])){
                $tempp = $importFieldsFull;
                unset($importFieldsFull);
                $importFieldsFull[0] = $tempp;
            }
            //end of the hack
            $this->importData['process']['total'] = count($importFieldsFull);
            $this->setSession('total_entries_'.$token, $this->importData['process']['total']);
        }elseif($data['format'] == 'json'){
            $importFieldsFull = $this->jsonToArray($settings['setting'], "no");
            if(isset($importFieldsFull['entries'])){
                $importFieldsFull = $importFieldsFull['entries'];
            }
            $this->importData['process']['total'] = count($importFieldsFull);
            $this->setSession('total_entries_'.$token, $this->importData['process']['total']);
        }


        //cron feature
        if($this->importData['process']['batches']  == 'CRON'){
            //set the batch limit by total records
            $this->importData['process']['batches'] = $this->importData['process']['total'];
        }
        $selectedData['configure'] = $settings['configure'];


        $smart_seo = ee()->sieModel->checkModuleInstalled('Smart_seo');
        if($smart_seo){
            $smartSEOFields = ee('Model')->get('smart_seo:SsField')->filter("ss_site_id", ee()->config->item("site_id"))->order('ss_field_order');
        }

        $seeo = ee()->sieModel->checkModuleInstalled('Seeo');
        if($seeo){
            ee()->router->set_class('cp');
            ee()->load->library('cp');
            ee()->load->add_package_path(PATH_THIRD . 'seeo');
            ee()->load->library(array('seeo_settings'));
            $seeo_meta_fields = new EEHarbor\Seeo\Helpers\MetaFields;
            $SEEOFields = $seeo_meta_fields->getTabFields('all', $seeo_meta_fields->default_entry_meta);
        }

        $seo_lite = ee()->sieModel->checkModuleInstalled('Seo_lite');
        if($seo_lite){

            $seo_lite_fields = array();

            $seo_lite_fields[] = array(
                'field_id' => 'seo_lite_title',
            );
     
            if(ee()->config->item('seolite_show_keywords_field') != 'n') {
                $seo_lite_fields[] = array(
                'field_id' => 'seo_lite_keywords',
            );
            }
     
             $seo_lite_fields[] = array(
                'field_id' => 'seo_lite_description',
            );


        }

        $structure = ee()->sieModel->checkModuleInstalled('Structure');
        if($structure){
            ee()->load->add_package_path(PATH_THIRD . 'structure');
            require_once PATH_THIRD.'structure/tab.structure.php';
            $structure_tab = new Structure_tab();
            $structure_fields = $structure_tab->publish_tabs($channel_id);
        }

        $transcribe = ee()->sieModel->checkModuleInstalled('Transcribe');
        if($transcribe){
            ee()->load->add_package_path(PATH_THIRD . 'transcribe');
            require_once PATH_THIRD.'transcribe/tab.transcribe.php';
            $transcribe_tab = new Transcribe_tab();
            $transcribe_fields = $transcribe_tab->display($channel_id);
        }

        $wygwam = ee()->sieModel->checkModuleInstalled('Wygwam');

        $editor = ee()->sieModel->checkModuleInstalled('Editor');

        $polls = ee()->sieModel->checkModuleInstalled('Polls');

        $maps = ee()->sieModel->checkModuleInstalled('Maps');

        $low_variables = ee()->sieModel->checkModuleInstalled('Low_variables');

        $low_events = ee()->sieModel->checkModuleInstalled('Low_events');


        $channelFields = $this->getChannelFields($channel_id);

        $statuses = ee('Model')->get('Status')
            ->order('status_order')
            ->all();

        $statuses_ids = $statuses->getDictionary('status_id', 'status_id'); 
        $statuses = $statuses->getDictionary('status_id', 'status'); 


        $this->setSession('memory_usage_'.$token, memory_get_usage());

        //delete existing entries
        $this->importData['delete_existing_entries'] = isset($settings['configure']['delete_existing_entries']) ? $settings['configure']['delete_existing_entries'] : 0;

        $this->setSession('time_taken_'.$token, time());


        $channel = ee('Model')->get('Channel', $channel_id)
            // ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        $channelCategoryData = ee('Model')->get('Channel', $channel_id)
            // ->filter('site_id', ee()->config->item('site_id'))
            ->all()->getDictionary('channel_id','cat_group'); 

        if ( ! $channel)
        {
            show_error(lang('no_channel_exists'));
        }


        $count = 0;
        if(isset($importFieldsFull) && count($importFieldsFull) > 0){
            foreach($importFieldsFull as $importDataRow){
                $count = $count + 1;
                if($this->importData['process']['offset'] < $count && $count <= ($this->importData['process']['offset']+$this->importData['process']['batches'])){
                    $update = 0;
                    $delete = 0;
                    $do_nothing = 0;
                    $insert_new = 0;
                    if(isset($selectedData['configure']['duplicate_check_with']) && $selectedData['configure']['duplicate_check_with'] != ""){
                        if(!in_array($selectedData['configure']['duplicate_check_with'], array('title', 'url_title')) && $selectedData['configure']['duplicate_check_with'] !== '0'){
                            $filed_data = explode("-", $selectedData['configure']['duplicate_check_with']);

                            if(strpos($selectedData['configure'][$filed_data[0]]['channel'][$channel_id]['fields'][$filed_data[1]][$filed_data[2]], ' -> ') !== false){
                                $result = $importDataRow;
                                $temp =& $result;

                                foreach(explode(' -> ', $selectedData['configure'][$filed_data[0]]['channel'][$channel_id]['fields'][$filed_data[1]][$filed_data[2]]) as $key) {
                                    $temp = $temp[$key];
                                }
                                $duplicate_check_with = $temp;
                            }else{
                                $duplicate_check_with = $importDataRow[$selectedData['configure'][$filed_data[0]]['channel'][$channel_id]['fields'][$filed_data[1]][$filed_data[2]]];
                            }

                            $entries = ee('Model')->get('ChannelEntry')
                            ->filter('channel_id', $channel_id)
                            ->filter('field_id_'.$filed_data[2], $duplicate_check_with)
                            ->filter('site_id', $this->site_id)->first(); 
                            if(isset($entries)){
                                if($selectedData['configure']['duplicate_action'] == 3){
                                    $do_nothing = 1;
                                    $this->setSession('skipped_entries_'.$token, $this->session('skipped_entries_'.$token) + 1);
                                }elseif($selectedData['configure']['duplicate_action'] == 1){
                                    $update = 1;
                                }elseif($selectedData['configure']['duplicate_action'] == 2){
                                    // $delete = 1;
                                    // $entriesToBeDelete = ee('Model')->get('ChannelEntry')
                                    // ->filter('channel_id', $channel_id)
                                    // ->fields('entry_id', $entries->entry_id)
                                    // ->filter('site_id', $this->site_id);    
                                    // $entriesToBeDelete->delete();
                                    // $insert_new = 1;
                                    // if($delete == 1){
                                    //     $this->setSession('deleted_entries_'.$token, $this->session('deleted_entries_'.$token) + 1);
                                    // }
                                }elseif($selectedData['configure']['duplicate_action'] == 0){
                                    $insert_new = 1;
                                }  
                            }
                        }elseif(in_array($selectedData['configure']['duplicate_check_with'], array('title', 'url_title')) && $selectedData['configure']['duplicate_check_with'] !== '0'){

                            $result = $importDataRow;
                            $temp =& $result;
                            if(strpos($selectedData['configure'][$selectedData['configure']['duplicate_check_with']], ' -> ') !== false){
	                            foreach(explode(' -> ', $selectedData['configure'][$selectedData['configure']['duplicate_check_with']]) as $key) {
	                                $temp = $temp[$key];
	                            }
	                            $duplicate_check_with = $temp;
                        	}else{
                        		$duplicate_check_with = $importDataRow[$selectedData['configure'][$selectedData['configure']['duplicate_check_with']]];
                        	}
                            // $temp = $value;
                            $entries = ee('Model')->get('ChannelEntry')
                            ->filter('channel_id', $channel_id)
                            ->filter($selectedData['configure']['duplicate_check_with'], 
                            	utf8_encode($duplicate_check_with))
                            ->filter('site_id', $this->site_id)->first(); 
                            if(isset($entries)){
                                if($selectedData['configure']['duplicate_action'] == 3){
                                    $do_nothing = 1;
                                    $this->setSession('skipped_entries_'.$token, $this->session('skipped_entries_'.$token) + 1);
                                }elseif($selectedData['configure']['duplicate_action'] == 1){
                                    $update = 1;
                                }elseif($selectedData['configure']['duplicate_action'] == 2){
                                    // $delete = 1;
                                    // $entriesToBeDelete = ee('Model')->get('ChannelEntry')
                                    // ->filter('channel_id', $channel_id)
                                    // ->fields('entry_id', $entries->entry_id)
                                    // ->filter('site_id', $this->site_id);    
                                    // $entriesToBeDelete->delete();
                                    // $insert_new = 1;
                                    // if($delete == 1){
                                    //     $this->setSession('deleted_entries_'.$token, $this->session('deleted_entries_'.$token) + 1);
                                    // }
                                }elseif($selectedData['configure']['duplicate_action'] == 0){
                                    $insert_new = 1;
                                }  
                            }
                        }elseif($selectedData['configure']['duplicate_check_with'] !== '0'){
                            $update = 0;
                            $delete = 0;
                            $do_nothing = 0;
                            $insert_new = 1;
                            if($selectedData['configure']['duplicate_action'] == 3){
                                $do_nothing = 1;
                                $this->setSession('skipped_entries_'.$token, $this->session('skipped_entries_'.$token) + 1);
                            }else{
                                $do_nothing = 0;
                            }

                        }
                    }else{
                            $update = 0;
                            $delete = 0;
                            $do_nothing = 0;
                            $insert_new = 1;
                            $entries = array();
                            if($selectedData['configure']['duplicate_action'] == 3){
                                $do_nothing = 1;
                                $this->setSession('skipped_entries_'.$token, $this->session('skipped_entries_'.$token) + 1);
                            }else{
                                $do_nothing = 0;
                            }
                    }

                    if($do_nothing == 0 || $insert_new == 1 || $update == 1){
                        $entryFieldArray = array();
                        if(!empty($channelFields['individual_fields'])){
                            foreach($channelFields['individual_fields'] as $field){
                                if($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                    if($field['field_type'] == 'grid'){
                                        $entryFieldArray['field_id_'.$field['field_id']]['rows'] = array();
                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] !== '0'){
                                            	if($this->isJson($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                            		if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    	$colArray = $this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]);
                                                	}
                                            		$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] = json_decode($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']], true);
                                            	}
                                                if(array_keys($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) !== range(0, count($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                    $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] = array();
                                                    $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']][0] = $temp;
                                                    // unset($temp);
                                                }else{
                                                    //
                                                }


                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    // $colArray = $this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('individual_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData); 
                                                // for($i=1;$i<=count($colArray);$i++){
                                                //     $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_'.$i] = $this->insertIntoGrid($field, $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']], $colArray[$i-1],$importDataRow); 
                                                // }   

                                            }else{
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(' -> ', $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']) as $key) {
                                                    $temp = $temp[$key];
                                                }
                                                if(array_keys($result) !== range(0, count($result) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp1 = $result;
                                                    $result = array();
                                                    $result[0] = $temp1;
                                                    unset($temp1);
                                                }else{
                                                    //
                                                }
                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $colArray = $this->getColArray($result);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $result;
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $result;
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('individual_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData);
                                            }
                                            
                                        }                  
                                    }elseif($field['field_type'] == 'file_grid'){
                                        $entryFieldArray['field_id_'.$field['field_id']]['rows'] = array();
                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] !== '0'){
                                            	if($this->isJson($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) === true){
                                            		if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    	$colArray = $this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]);
                                                	}
                                            		$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] = json_decode($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']], true);
                                            	}else{

                                                }
                                                if(array_keys($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) !== range(0, count($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) === true){
                                                    $temp = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                    $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] = array();
                                                    $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']][0] = $temp;
                                                    // unset($temp);
                                                }else{
                                                    //
                                                }


                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    // $colArray = $this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('individual_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData); 
                                                // for($i=1;$i<=count($colArray);$i++){
                                                //     $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_'.$i] = $this->insertIntoGrid($field, $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']], $colArray[$i-1],$importDataRow); 
                                                // }   

                                            }else{
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(' -> ', $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']) as $key) {
                                                    $temp = $temp[$key];
                                                }
                                                if(array_keys($result) !== range(0, count($result) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp1 = $result;
                                                    $result = array();
                                                    $result[0] = $temp1;
                                                    unset($temp1);
                                                }else{
                                                    //
                                                }
                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $colArray = $this->getColArray($result);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $result;
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $result;
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('individual_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData);
                                            }
                                            
                                        }                  
                                    }elseif($field['field_type'] == 'fluid_field'){

                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']] !== '0'){
                                                $entryFieldArray['field_id_'.$field['field_id']]['fields'] = $this->insertIntoFluid($field['field_id'],$field,$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']],$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']],$importDataRow,$settings['setting']['import_file_type']);

                                                $sub_entryFieldArray = $entryFieldArray['field_id_'.$field['field_id']]['fields'];
                                                ksort($sub_entryFieldArray);
                                                $entryFieldArray['field_id_'.$field['field_id']]['fields'] = $sub_entryFieldArray;
                                            }
                                        }
                                    }elseif($field['field_type'] == 'assets'){
                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]] !== '0'){

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $assetsArr = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }

                                                if(is_array($assetsArr) && count($assetsArr) > 0){
                                                    foreach($assetsArr as $no=>$assetImgUrl){
                                                        if($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                            $assetData = $this->imgUpload($assetImgUrl, $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "assets");
                                                             $assetsArr[$no] = ($assetData != false) ? $assetData['file_id'] : "" ;
                                                        }else{
                                                            $assetsArr[$no] = "";
                                                        }

                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $assetsArr;
                                                }
                                            }{

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $assetsArr = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];

                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(' -> ', $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]) as $key) {
                                                        $temp = $temp[$key];
                                                    }
                                                    if(array_keys($result) !== range(0, count($result) - 1)){
                                                    // if($this->isAssoc($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                        $temp1 = $result;
                                                        $result = array();
                                                        $result[0] = $temp1;
                                                        unset($temp1);
                                                    }else{
                                                        //
                                                    }

                                                    $assetsArr = $result;

                                                    if(count($assetsArr) > 0){
                                                        foreach($assetsArr as $no=>$assetImgUrl){
                                                            if($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                                $fileData = $this->imgUpload($assetImgUrl['_value'], $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "assets");
                                                                $assetsArr[$no] = ($fileData != false) ? $fileData['file_id'] : "";
                                                            }else{
                                                                $assetsArr[$no] = "";
                                                            }

                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $assetsArr;
                                                }

                                            }
                                        }
                                    }elseif($field['field_type'] == 'matrix'){
                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $colArray = $this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']];
                                                }
                                                for($i=1;$i<=count($colArray);$i++){
                                                    $entryFieldArray['field_id_'.$field['field_id']]['row_order'][($i-1)] = 'row_new_'.$i;
                                                    $entryFieldArray['field_id_'.$field['field_id']]['row_new_'.$i] = $this->generateMatrixData($field,$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']],$colArray[$i-1],$importDataRow);
                                                }
                                            }
                                        }
                                    }elseif($field['field_type'] == 'playa'){


                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = array();
                                                    }
                                                }
                                                $playArray = $temp;

                                                $playaArray = array();
                                                foreach($playArray as $row){
                                                    if(is_numeric($row)){
                                                        $playaArray[] = $row;
                                                    }elseif(substr_count($row, '-') > 0){
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('url_title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $playaArray[] = $ent->entry_id;
                                                        }
                                                    }else{
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $playaArray[] = $ent->entry_id;
                                                        }
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;



                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    // $playArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
    
                                                    if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                        $playArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
                                                    }elseif($settings['setting']['import_file_type'] == "xml"){
                                                        $playArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                        $playArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }
    
                                                    $playaArray = array();
                                                    foreach($playArray as $row){
                                                        if(is_numeric($row)){
                                                            $playaArray[] = $row;
                                                        }elseif(substr_count($row, '-') > 0){
                                                            $ent = ee('Model')->get('ChannelEntry')
                                                            ->filter('url_title', $row)
                                                            ->filter('site_id', $this->site_id)->first();    
                                                            if(isset($ent->entry_id)){
                                                                $playaArray[] = $ent->entry_id;
                                                            }
                                                        }else{
                                                            $ent = ee('Model')->get('ChannelEntry')
                                                            ->filter('title', $row)
                                                            ->filter('site_id', $this->site_id)->first();    
                                                            if(isset($ent->entry_id)){
                                                                $playaArray[] = $ent->entry_id;
                                                            }
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;
                                                }
                                                    
                                            }
                                        }

                                        // if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                        //     if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                        //         // $playArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);

                                        //         if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                        //             $playArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
                                        //         }elseif($settings['setting']['import_file_type'] == "xml"){
                                        //             $playArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                        //         }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                        //             $playArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                        //         }

                                        //         $playaArray = array();
                                        //         foreach($playArray as $row){
                                        //             if(is_numeric($row)){
                                        //                 $playaArray[] = $row;
                                        //             }elseif(substr_count($row, '-') > 0){
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('url_title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $playaArray[] = $ent->entry_id;
                                        //                 }
                                        //             }else{
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $playaArray[] = $ent->entry_id;
                                        //                 }
                                        //             }
                                        //         }
                                        //         $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;
                                        //     }
                                        // }
                                    }elseif($field['field_type'] == 'tag'){

                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }
                                        }
                                    }elseif($field['field_type'] == 'smart_members_field'){

                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['data'] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']]['data'] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }
                                        }
                                    }elseif($field['field_type'] == 'channel_files'){
                                    }elseif($field['field_type'] == 'channel_images'){
                                    }elseif($field['field_type'] == 'low_variables'){
                                        if($low_variables){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
                                            }

                                        }
                                    }elseif($field['field_type'] == 'low_events'){
                                        if($low_events){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = array();
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = json_decode($temp, true);
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }

                                        }
                                    }elseif($field['field_type'] == 'channel_videos'){
                                    }elseif($field['field_type'] == 'relationship'){


                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                
                                                //solved issue of relationship field not worked in the group field for xml in 3.0.9
                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $relArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $relArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $relArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                }

                                                $relsArray = array();
                                                foreach($relArray as $row){
                                                    if(is_numeric($row)){
                                                        $relsArray[] = $row;
                                                    }elseif(substr_count($row, '-') > 0){
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('url_title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $relsArray[] = $ent->entry_id;
                                                        }
                                                    }else{
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $relsArray[] = $ent->entry_id;
                                                        }
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['data'] = $relsArray;
                                            }
                                        }


                                        
                                        // if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                        //     if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){

                                        //         if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                        //             $relArray = explode(',',$importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['relationship'][$field['field_id']]]);
                                        //         }elseif($settings['setting']['import_file_type'] == "xml"){
                                        //             $relArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['relationship'][$field['field_id']]];
                                        //         }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                        //             $relArray = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields']['relationship'][$field['field_id']]];
                                        //         }
                                        //         $relsArray = array();
                                        //         foreach($relArray as $row){
                                        //             if(is_numeric($row)){
                                        //                 $relsArray[] = $row;
                                        //             }elseif(substr_count($row, '-') > 0){
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('url_title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $relsArray[] = $ent->entry_id;
                                        //                 }
                                        //             }else{
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $relsArray[] = $ent->entry_id;
                                        //                 }
                                        //             }
                                        //         }
                                        //         $entryFieldArray['field_id_'.$field['field_id']]['data'] = $relsArray;



                                        //     }
                                        // }
                                    }elseif($field['field_type'] == 'file'){
                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){

                                                if($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){

                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
    

                                                    if($temp != ""){
                                                        $fileData = $this->imgUpload($temp, $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "file");
                                                        $entryFieldArray['field_id_'.$field['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                                                    }else{
                                                        $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                    }
                                                }else{
                                                    $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                }

                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                        $fileData = $this->imgUpload($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]], $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "file");
                                                        $entryFieldArray['field_id_'.$field['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                                                    }else{
                                                        $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                    }
                                                }
                                            }

                                        }
                                    }elseif($field['field_type'] == 'maps'){
                                        if($maps){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }
    

                                        }
                                    }elseif($field['field_type'] == 'wygwam'){
                                        if($wygwam){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }
    

                                        }
                                    }elseif($field['field_type'] == 'editor'){
                                        if($editor){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }
                                                
                                        }
                                    }elseif($field['field_type'] == 'polls'){
                                        if($polls){

                                            if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }
    

                                        }
                                    }else{


                                        if(isset($selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            elseif($settings['setting']['import_file_type'] == "json"){
                                                $str = $selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['individual_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }

                                        }

                                    }
                                }
                            }
                        }

                        if(!empty($channelFields['group_fields'])){
                            foreach($channelFields['group_fields'] as $field){

                                if($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                    if($field['field_type'] == 'grid'){
                                        
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main'] !== '0'){
                                            $entryFieldArray['field_id_'.$field['field_id']]['rows'] = array();
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] !== '0'){
                                            	if($this->isJson($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                            		if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    	$colArray = $this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]);
                                                	}
                                            		$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] = json_decode($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']], true);
                                            	}
                                            	// $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] = json_decode($importDataRow['Column - 27'], true);
                                                if(array_keys($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) !== range(0, count($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                    $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']] = array();
                                                    $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']][0] = $temp;
                                                    // unset($temp);
                                                }else{
                                                    //
                                                }

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    // $colArray = $this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]);
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];


                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']];
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('group_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData); 
                                                // for($i=1;$i<=count($colArray);$i++){
                                                //     $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_'.$i] = $this->insertIntoGrid($field, $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']], $colArray[$i-1],$importDataRow); 
                                                // }   

                                            }else{
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(' -> ', $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }
                                                }
                                                if(array_keys($result) !== range(0, count($result) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp1 = $result;
                                                    $result = array();
                                                    $result[0] = $temp1;
                                                    unset($temp1);
                                                }else{
                                                    //
                                                }

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    if(is_array($result)){
                                                        $colArray = $result;
                                                    }else{
                                                        $colArray = $this->getColArray($result);
                                                    }
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $result;
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $result;
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('group_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData);
                                            }
                                            
                                        }                  
                                    }elseif($field['field_type'] == 'file_grid'){
                                        $entryFieldArray['field_id_'.$field['field_id']]['rows'] = array();
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] !== '0'){
                                            	if($this->isJson($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) === true){
                                            		if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    	$colArray = $this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]);
                                                	}
                                            		$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] = json_decode($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']], true);
                                            	}
                                            	// $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] = json_decode($importDataRow['Column - 27'], true);

                                                if(array_keys($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) !== range(0, count($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]) === true){
                                                    $temp = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                    $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']] = array();
                                                    $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']][0] = $temp;
                                                    // unset($temp);
                                                }else{
                                                    //
                                                }

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    // $colArray = $this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']];
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('group_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData);
                                                // for($i=1;$i<=count($colArray);$i++){
                                                //     $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_'.$i] = $this->insertIntoGrid($field, $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']], $colArray[$i-1],$importDataRow); 
                                                // }   

                                            }else{
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(' -> ', $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['file_grid'][$field['field_id']]['main']) as $key) {
                                                    $temp = $temp[$key];
                                                }
                                                if(array_keys($result) !== range(0, count($result) - 1)){
                                                // if($this->isAssoc($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                    $temp1 = $result;
                                                    $result = array();
                                                    $result[0] = $temp1;
                                                    unset($temp1);
                                                }else{
                                                    //
                                                }

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $colArray = $this->getColArray($result);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $result;
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $colArray = $result;
                                                }
                                                //V3.0.1
                                                $setGridImportData = $this->setGridImportData('group_fields', $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow);
                                                $entryFieldArray = array_merge($entryFieldArray, $setGridImportData);
                                            }
                                            
                                        } 
                                    }elseif($field['field_type'] == 'fluid_field'){
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main'] !== '0'){
                                                $entryFieldArray['field_id_'.$field['field_id']]['fields'] = $this->insertIntoFluid($field['field_id'],$field,$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']]['main']],$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['fluid_field'][$field['field_id']],$importDataRow,$settings['setting']['import_file_type']);
                                                //V3.1.1 Fluid for the XML
                                                $sub_entryFieldArray = $entryFieldArray['field_id_'.$field['field_id']]['fields'];
                                                ksort($sub_entryFieldArray);
                                                $entryFieldArray['field_id_'.$field['field_id']]['fields'] = $sub_entryFieldArray;
                                            }
                                        }
                                    }elseif($field['field_type'] == 'assets'){
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]] !== '0'){

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $assetsArr = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }


                                                if(count($assetsArr) > 0){
                                                    foreach($assetsArr as $no=>$assetImgUrl){
                                                        if($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                            $fileData = $this->imgUpload($assetImgUrl, $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "assets");
                                                            $assetsArr[$no] = ($fileData != false) ? $fileData['file_id'] : "";
                                                        }else{
                                                            $assetsArr[$no] = "";
                                                        }

                                                    }
                                                }

                                                $entryFieldArray['field_id_'.$field['field_id']] = $assetsArr;
                                            }else{

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $assetsArr = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $assetsArr = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    // $assetsArr = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]];

                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(' -> ', $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['assets'][$field['field_id']]) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }
                                                    }
                                                    if(array_keys($result) !== range(0, count($result) - 1)){
                                                    // if($this->isAssoc($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['grid'][$field['field_id']]['main']]) === true){
                                                        $temp1 = $result;
                                                        $result = array();
                                                        $result[0] = $temp1;
                                                        unset($temp1);
                                                    }else{
                                                        //
                                                    }

                                                    $assetsArr = $result;

                                                    if(count($assetsArr) > 0){
                                                        foreach($assetsArr as $no=>$assetImgUrl){
                                                            if($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                                $fileData = $this->imgUpload($assetImgUrl['_value'], $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "assets");
                                                                $assetsArr[$no] = ($fileData != false) ? $fileData['file_id'] : "";
                                                            }else{
                                                                $assetsArr[$no] = "";
                                                            }

                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $assetsArr;
                                                }

                                            }
                                        }
                                    }elseif($field['field_type'] == 'matrix'){
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main'] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $colArray = $this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $colArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']]['main']];
                                                }

                                                for($i=1;$i<=count($colArray);$i++){
                                                    $entryFieldArray['field_id_'.$field['field_id']]['row_order'][($i-1)] = 'row_new_'.$i;
                                                    $entryFieldArray['field_id_'.$field['field_id']]['row_new_'.$i] = $this->generateMatrixData($field,$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['matrix'][$field['field_id']],$colArray[$i-1],$importDataRow);
                                                }
                                            }
                                        }
                                    }elseif($field['field_type'] == 'playa'){


                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = array();
                                                    }
                                                }
                                                $playArray = $temp;

                                                $playaArray = array();
                                                foreach($playArray as $row){
                                                    if(is_numeric($row)){
                                                        $playaArray[] = $row;
                                                    }elseif(substr_count($row, '-') > 0){
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('url_title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $playaArray[] = $ent->entry_id;
                                                        }
                                                    }else{
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $playaArray[] = $ent->entry_id;
                                                        }
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;



                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    // $playArray = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
    
                                                    if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                        $playArray = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
                                                    }elseif($settings['setting']['import_file_type'] == "xml"){
                                                        $playArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                        $playArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }
    
                                                    $playaArray = array();
                                                    foreach($playArray as $row){
                                                        if(is_numeric($row)){
                                                            $playaArray[] = $row;
                                                        }elseif(substr_count($row, '-') > 0){
                                                            $ent = ee('Model')->get('ChannelEntry')
                                                            ->filter('url_title', $row)
                                                            ->filter('site_id', $this->site_id)->first();    
                                                            if(isset($ent->entry_id)){
                                                                $playaArray[] = $ent->entry_id;
                                                            }
                                                        }else{
                                                            $ent = ee('Model')->get('ChannelEntry')
                                                            ->filter('title', $row)
                                                            ->filter('site_id', $this->site_id)->first();    
                                                            if(isset($ent->entry_id)){
                                                                $playaArray[] = $ent->entry_id;
                                                            }
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;
                                                }
                                                    
                                            }
                                        }



                                        // if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']] !== '0'){
                                        //     if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]] !== '0'){

                                        //         if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                        //             $playArray = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]]);
                                        //         }elseif($settings['setting']['import_file_type'] == "xml"){
                                        //             $playArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]];
                                        //         }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                        //             $playArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['playa'][$field['field_id']]];
                                        //         }

                                        //         $playaArray = array();
                                        //         foreach($playArray as $row){
                                        //             if(is_numeric($row)){
                                        //                 $playaArray[] = $row;
                                        //             }elseif(substr_count($row, '-') > 0){
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('url_title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $playaArray[] = $ent->entry_id;
                                        //                 }
                                        //             }else{
                                        //                 $ent = ee('Model')->get('ChannelEntry')
                                        //                 ->filter('title', $row)
                                        //                 ->filter('site_id', $this->site_id)->first();    
                                        //                 if(isset($ent->entry_id)){
                                        //                     $playaArray[] = $ent->entry_id;
                                        //                 }
                                        //             }
                                        //         }

                                        //         $entryFieldArray['field_id_'.$field['field_id']]['selections'] = $playaArray;
                                        //     }
                                        // }
                                    }elseif($field['field_type'] == 'tag'){

                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }
                                        }
                                    }elseif($field['field_type'] == 'smart_members_field'){
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['data'] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']]['data'] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }

                                        }
                                    }elseif($field['field_type'] == 'channel_files'){
                                    }elseif($field['field_type'] == 'channel_images'){
                                    }elseif($field['field_type'] == 'low_variables'){
                                        if($low_variables){

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }
    

                                        }
                                    }elseif($field['field_type'] == 'low_events'){
                                        if($low_events){

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = array();
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = json_decode($temp, true);
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
    
                                            }

                                        }
                                    }elseif($field['field_type'] == 'channel_videos'){
                                    }elseif($field['field_type'] == 'relationship'){
                                        
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){
                                            if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                
                                                //solved issue of relationship field not worked in the group field for xml in 3.0.9
                                                if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                    $relArray = explode(',',$importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]);
                                                }elseif($settings['setting']['import_file_type'] == "xml"){
                                                    $relArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                }elseif($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $relArray = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                }

                                                $relsArray = array();
                                                foreach($relArray as $row){
                                                    if(is_numeric($row)){
                                                        $relsArray[] = $row;
                                                    }elseif(substr_count($row, '-') > 0){
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('url_title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $relsArray[] = $ent->entry_id;
                                                        }
                                                    }else{
                                                        $ent = ee('Model')->get('ChannelEntry')
                                                        ->filter('title', $row)
                                                        ->filter('site_id', $this->site_id)->first();    
                                                        if(isset($ent->entry_id)){
                                                            $relsArray[] = $ent->entry_id;
                                                        }
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']]['data'] = $relsArray;
                                            }
                                        }

                                    }elseif($field['field_type'] == 'file'){

                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){

                                                if($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){

                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
    

                                                    if($temp != ""){
                                                        $fileData = $this->imgUpload($temp, $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "file");
                                                        $entryFieldArray['field_id_'.$field['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                                                    }else{
                                                        $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                    }
                                                }else{
                                                    $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                }

                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                        $fileData = $this->imgUpload($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]], $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id'].'-file_setting'], "file");
                                                        $entryFieldArray['field_id_'.$field['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                                                    }else{
                                                        $entryFieldArray['field_id_'.$field['field_id']] = "";
                                                    }
                                                }
                                            }

                                        }


                                    }elseif($field['field_type'] == 'maps'){
                                        if($maps){
                                            // $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['maps'][$field['field_id']]];

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
                                            }
    
    


                                        }
                                    }elseif($field['field_type'] == 'wygwam'){
                                        if($wygwam){
                                            // $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['wygwam'][$field['field_id']]];

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
                                            }
    
    


                                        }
                                    }elseif($field['field_type'] == 'editor'){
                                        if($editor){
                                            // $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['editor'][$field['field_id']]];

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
                                            }
    
    

                                        }
                                    }elseif($field['field_type'] == 'polls'){
                                        if($polls){
                                            // $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields']['polls'][$field['field_id']]];

                                            if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                                if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                    $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                    $result = $importDataRow;
                                                    $temp =& $result;
                                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                        if(isset($temp[$key])){
                                                            $temp = $temp[$key];
                                                        }else{
                                                            $temp = '';
                                                        }
                                                    }
                                                    $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                                }
                                                else{
    
                                                    if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                        if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                            $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                        }else{
    
                                                        }
                                                    }
                                                }
                                            }
    
    

                                        }
                                    }else{
                                        if(isset($selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]) && $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']] !== '0'){

                                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                                                $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            elseif($settings['setting']['import_file_type'] == "json"){
                                                $str = $selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']];
                                                $result = $importDataRow;
                                                $temp =& $result;
                                                foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                                                    if(isset($temp[$key])){
                                                        $temp = $temp[$key];
                                                    }else{
                                                        $temp = '';
                                                    }
                                                }
                                                $entryFieldArray['field_id_'.$field['field_id']] = $temp;
                                            }
                                            else{

                                                if(isset($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]) && $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]] !== '0'){
                                                    if(!is_array($this->getColArray($importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]]))) {
                                                        $entryFieldArray['field_id_'.$field['field_id']] = $importDataRow[$selectedData['configure']['group_fields']['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]];
                                                    }else{

                                                    }
                                                }
                                            }

                                        }

                                    }
                                }
                            }
                        }


                        if($smart_seo){
                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                            	if(isset($selectedData['configure']['smart_seo_fields'])){
	                                foreach($smartSEOFields->all() as $ss_fields){
	                                    $str = $selectedData['configure']['smart_seo_fields']['channel'][$channel_id]['fields'][$ss_fields->ss_field_name];
	                                    $result = $importDataRow;
	                                    $temp =& $result;
	                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
	                                        if(isset($temp[$key])){
	                                            $temp = $temp[$key];
	                                        }else{
	                                            $temp = '';
	                                        }
	                                    }
	                                    $entryFieldArray['smart_seo__'.$ss_fields->ss_field_name] = $_POST['smart_seo__'.$ss_fields->ss_field_name] = $temp;
	                                }
	                            }

                            }
                            else{
                            	if(isset($selectedData['configure']['smart_seo_fields'])){
	                                foreach($smartSEOFields->all() as $ss_fields){
	                                    if(isset($importDataRow[$selectedData['configure']['smart_seo_fields']['channel'][$channel_id]['fields'][$ss_fields->ss_field_name]]) && $importDataRow[$selectedData['configure']['smart_seo_fields']['channel'][$channel_id]['fields'][$ss_fields->ss_field_name]] !== '0'){
	                                        $entryFieldArray['smart_seo__'.$ss_fields->ss_field_name] = $_POST['smart_seo__'.$ss_fields->ss_field_name] = $importDataRow[$selectedData['configure']['smart_seo_fields']['channel'][$channel_id]['fields'][$ss_fields->ss_field_name]];
	                                    }
	                                }
	                            }
                                    
                            }

                        }

                        if($seeo){
                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                            	if(isset($selectedData['configure']['seeo_fields'])){
	                                foreach($SEEOFields as $seeo_fields){
	                                    $str = $selectedData['configure']['seeo_fields']['channel'][$channel_id]['fields'][$seeo_fields['field_id']];
	                                    $result = $importDataRow;
	                                    $temp =& $result;
	                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
	                                        if(isset($temp[$key])){
	                                            $temp = $temp[$key];
	                                        }else{
	                                            $temp = '';
	                                        }
	                                    }
	                                    $entryFieldArray['seeo__'.$seeo_fields['field_id']] = $_POST['seeo__'.$seeo_fields['field_id']] = $temp;
	                                }
                            	}

                            }else{
                            	if(isset($selectedData['configure']['seeo_fields'])){
	                                foreach($SEEOFields as $seeo_fields){
	                                    if(isset($importDataRow[$selectedData['configure']['seeo_fields']['channel'][$channel_id]['fields'][$seeo_fields['field_id']]]) && $importDataRow[$selectedData['configure']['seeo_fields']['channel'][$channel_id]['fields'][$seeo_fields['field_id']]] !== '0'){
	                                        if(isset($seeo_fields)){
	                                            $entryFieldArray['seeo__'.$seeo_fields['field_id']] = $_POST['seeo__'.$seeo_fields['field_id']] = $importDataRow[$selectedData['configure']['seeo_fields']['channel'][$channel_id]['fields'][$seeo_fields['field_id']]];
	                                        }
	                                    }
	                                }
                            	}
                            }

                        }

                        
                        if($seo_lite){

                            if($settings['setting']['import_file_type'] == "third_party_xml"){
                            	if(isset($selectedData['configure']['seo_lite_fields'])){
	                                foreach($seo_lite_fields as $seo_lite_field){
	                                    $str = $selectedData['configure']['seo_lite_fields']['channel'][$channel_id]['fields'][$seo_lite_field['field_id']];
	                                    $result = $importDataRow;
	                                    $temp =& $result;
	                                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
	                                        if(isset($temp[$key])){
	                                            $temp = $temp[$key];
	                                        }else{
	                                            $temp = '';
	                                        }
	                                    }

	                                    $entryFieldArray['seo_lite__'.$seo_lite_field['field_id']]  = $temp;
	                                }
	                            }

                            }
                            else{
                            	if(isset($selectedData['configure']['seo_lite_fields'])){
	                                foreach($seo_lite_fields as $seo_lite_field){
	                                    if(isset($importDataRow[$selectedData['configure']['seo_lite_fields']['channel'][$channel_id]['fields'][$seo_lite_field['field_id']]]) && $importDataRow[$selectedData['configure']['seo_lite_fields']['channel'][$channel_id]['fields'][$seo_lite_field['field_id']]] !== '0'){
	                                        $entryFieldArray['seo_lite__'.$seo_lite_field['field_id']] = $importDataRow[$selectedData['configure']['seo_lite_fields']['channel'][$channel_id]['fields'][$seo_lite_field['field_id']]];
	                                    }
	                                }
                            	}
                                    
                            }

                        }
                        

                        if($structure){
                            foreach($structure_fields as $structure_field){
                                if(isset($importDataRow[$selectedData['configure']['structure_fields']['channel'][$channel_id]['fields'][$structure_field['field_id']]]) && $importDataRow[$selectedData['configure']['structure_fields']['channel'][$channel_id]['fields'][$structure_field['field_id']]] !== '0'){
                                    $_POST['structure__'.$structure_field['field_id']] = null;
                                    if($structure_field['field_id'] == 'parent_id' || $structure_field['field_id'] == 'template_id'){
                                        $entryFieldArray['structure__'.$structure_field['field_id']] = $_POST['structure__'.$structure_field['field_id']] = json_decode($importDataRow[$selectedData['configure']['structure_fields']['channel'][$channel_id]['fields'][$structure_field['field_id']]]);
                                        $entryFieldArray['structure_'.$structure_field['field_id']] = $_POST['structure__'.$structure_field['field_id']] = json_decode($importDataRow[$selectedData['configure']['structure_fields']['channel'][$channel_id]['fields'][$structure_field['field_id']]]);
                                    }else{
                                        $entryFieldArray['structure__'.$structure_field['field_id']] = $_POST['structure__'.$structure_field['field_id']] = $importDataRow[$selectedData['configure']['structure_fields']['channel'][$channel_id]['fields'][$structure_field['field_id']]];
                                    }
                                }
                            }
                        }

                        if($transcribe){
                            if(isset($transcribe_fields)){
                                foreach($transcribe_fields as $transcribe_field){
                                    if(isset($selectedData['configure']['transcribe_fields']) && isset($importDataRow[$selectedData['configure']['transcribe_fields']['channel'][$channel_id]['fields'][$transcribe_field['field_id']]]) && $importDataRow[$selectedData['configure']['transcribe_fields']['channel'][$channel_id]['fields'][$transcribe_field['field_id']]] !== '0'){
                                        $_POST['transcribe__'.$transcribe_field['field_id']] = null;
                                        $entryFieldArray['transcribe__'.$transcribe_field['field_id']] = $_POST['transcribe__'.$transcribe_field['field_id']] = $importDataRow[$selectedData['configure']['transcribe_fields']['channel'][$channel_id]['fields'][$transcribe_field['field_id']]];
                                    }
                                }   
                            }
                        }

                        if($update == 1){
                            $entry = ee('Model')->get('ChannelEntry', $entries->entry_id)
                                        ->with('Channel')
                                        ->first();

                            //get data from mapping author and only change if there is mapping and get data 
                            if(isset($selectedData['configure']['author']) && $selectedData['configure']['author'] !== '0' && $selectedData['configure']['author'] != ''){
                                $entry->author_id = $importDataRow[$selectedData['configure']['author']];
                            }else{
                                if(isset($selectedData['configure']['system_authors']) && $selectedData['configure']['system_authors'] !== '0' && $selectedData['configure']['system_authors'] != ''){
                                    $entry->author_id = $selectedData['configure']['system_authors'];
                                }else{
                                    if(ee()->session->userdata('member_id')){
                                        $entry->author_id = ee()->session->userdata('member_id');
                                    }else{
                                        //for cron when session is not set then pass 1 user ID
                                        $entry->author_id = 1;
                                    }
                                }
                            }

                            //get data from mapping status and only change if there is mapping and get data 
                            if(isset($selectedData['configure']['status']) && $selectedData['configure']['status'] !== '0' && $selectedData['configure']['status'] != ''){
                                if(is_numeric($importDataRow[$selectedData['configure']['status']]) && in_array($importDataRow[$selectedData['configure']['status']], $statuses_ids)){
                                    $entry->status = $importDataRow[$selectedData['configure']['status']];
                                }elseif(!is_numeric($importDataRow[$selectedData['configure']['status']]) && in_array($importDataRow[$selectedData['configure']['status']], $statuses)){
                                    $entry->status = $importDataRow[$selectedData['configure']['status']];
                                }else{
                                    if (isset($channel->deft_status))
                                    {
                                        $entry->status = $channel->deft_status;
                                    }
                                }
                            }

                                        
                        }else{
                            $entry = ee('Model')->make('ChannelEntry');
                            
                            //get data from mapping author and only change if there is mapping and get data 
                            if(isset($selectedData['configure']['author']) && $selectedData['configure']['author'] !== '0' && $selectedData['configure']['author'] != ''){
                                $entry->author_id = $importDataRow[$selectedData['configure']['author']];
                            }else{
                                if(isset($selectedData['configure']['system_authors']) && $selectedData['configure']['system_authors'] !== '0' && $selectedData['configure']['system_authors'] != ''){
                                    $entry->author_id = $selectedData['configure']['system_authors'];
                                }else{
                                    if(ee()->session->userdata('member_id')){
                                        $entry->author_id = ee()->session->userdata('member_id');
                                    }else{
                                        //for cron when session is not set then pass 1 user ID
                                        $entry->author_id = 1;
                                    }
                                }
                            }

                            // if (isset($channel->deft_status))
                            // {
                            //     $entry->status = $channel->deft_status;
                            // }

                            if(isset($selectedData['configure']['status']) && $selectedData['configure']['status'] !== '0' && $selectedData['configure']['status'] != ''){
                                if(is_numeric($importDataRow[$selectedData['configure']['status']]) && in_array($importDataRow[$selectedData['configure']['status']], $statuses_ids)){
                                    $entry->status = $importDataRow[$selectedData['configure']['status']];
                                }elseif(!is_numeric($importDataRow[$selectedData['configure']['status']]) && in_array($importDataRow[$selectedData['configure']['status']], $statuses)){
                                    $entry->status = $importDataRow[$selectedData['configure']['status']];
                                }else{
                                    if (isset($channel->deft_status))
                                    {
                                        $entry->status = $channel->deft_status;
                                    }
                                }
                            }else{
                                $entry->status = $channel->deft_status;
                            }
                        }
                        $entry->Channel = $channel;
                        // $entry->site_id = ee()->config->item('site_id');
                        $entry->site_id = $channel->site_id;
                        $entry->ip_address = ee()->session->userdata['ip_address'];
                        $entry->versioning_enabled = $channel->enable_versioning;
                        $entry->sticky = FALSE;
                        $entry->set($entryFieldArray);
                        $entry->allow_comments = (isset($channel->deft_comments)) ? $channel->deft_comments : TRUE;

                        if(!empty($channelCategoryData) && !empty($channelCategoryData[$channel_id])){
                            $catGrp = explode('|',$channelCategoryData[$channel_id]);
                            $CategoryGroup = ee('Model')->get('CategoryGroup')->filter('site_id', ee()->config->item('site_id'))->filter('group_id', 'IN', $catGrp)->all()->getDictionary('group_id','group_name');
                            $CategoryGroupRename = array();
                            foreach ($CategoryGroup as $key => $value) {
                                $CategoryGroupRename[$key] = "category_".strtolower($value);
                            }
                                  
                            $cats = array();
                            $import_cat_ids = array();
                            $import_cat_ids_merge = array();
                            $assigned_cat_groups = array();
                            foreach($catGrp as $cgroup){

                                //V3.1.4 : Handle category with create process and some more changes
                                if(!isset($importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]]) && !isset($importDataRow[$selectedData['configure']['category']['channel'][$channel_id][$cgroup]])){
                                    $assigned_cat_groups[] = $cgroup;
                                    $import_cat_ids = $this->handleImportCategorySimple(isset($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup] : '', 
                                    isset($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup] : '', 
                                        isset($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        $cgroup,
                                        isset($selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup] : ''
                                    );

                                    $import_cat_ids_merge = array_merge($import_cat_ids_merge, $import_cat_ids);
                                }
                                elseif(isset($importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]]) && $importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]] != '0'){
                                    $assigned_cat_groups[] = $cgroup;
                                    $import_cat_ids = $this->handleImportCategory(isset($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup] : '', 
                                        $importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]], 
                                        isset($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        $cgroup,
                                        isset($selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup] : ''
                                    );

                                    $import_cat_ids_merge = array_merge($import_cat_ids_merge, $import_cat_ids);
                                }
                                elseif(isset($importDataRow[$selectedData['configure']['category']['channel'][$channel_id][$cgroup]]) && $importDataRow[$selectedData['configure']['category']['channel'][$channel_id][$cgroup]] != '0'){
                                    $assigned_cat_groups[] = $cgroup;
                                    $import_cat_ids = $this->handleImportCategoryOnly(isset($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup] : '', 
                                        $importDataRow[$selectedData['configure']['category']['channel'][$channel_id][$cgroup]], 
                                        isset($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        $cgroup,
                                        isset($selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['parent_child_cat_group_delimiter']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['create_category_if_not']['channel'][$channel_id][$cgroup] : '',
                                        isset($selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup]) ? $selectedData['configure']['force_exact_categories']['channel'][$channel_id][$cgroup] : ''
                                    );

                                    $import_cat_ids_merge = array_merge($import_cat_ids_merge, $import_cat_ids);
                                }
                                

                                /*if(isset($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup]) && !empty($selectedData['configure']['cat_group_default_value']['channel'][$channel_id][$cgroup])){
                                    $cats[] = $selectedData['configure']['channel'][$channel_id]['cat_group_default_value'][$cgroup];
                                }else{

                                    if(isset($selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]) && !empty($selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup])){
                                        if(isset($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup]) && !empty($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup])){
                                            //V3.1.3 Category import work for json
                                            if($settings['setting']['import_file_type'] == "csv" || $settings['setting']['import_file_type'] == "json"){
                                                $cat = explode($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup], $importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]]);
                                                foreach($cat as $c){
                                                    if($c != "")
                                                    $cats[] = trim($c);
                                                }
                                            }elseif($settings['setting']['import_file_type'] == "xml"){
                                                //solve issue in the 3.0.2 =>  category update for the xml
                                                $cat_group_get = $importDataRow[$selectedData['configure']['cat_group']['channel'][$channel_id][$cgroup]];
                                                $cat_group_get = is_array($cat_group_get) ? '' : $cat_group_get;
                                                $cat = explode($selectedData['configure']['cat_group_delimiter']['channel'][$channel_id][$cgroup], $cat_group_get);
                                                if(is_array($cat)){
                                                    foreach($cat as $c){
                                                        if($c != "")
                                                        $cats[] = trim($c);
                                                    }
                                                }
                                            }
                                             
                                        }
                                    }
                                }*/
                            }

                            $unselect_cat_group_cat_ids = array();
                            //to get existing category for the unselected category groups
                            if( $update == 1 ) {
                                ee()->db->select( "exp_category_posts.cat_id, exp_categories.group_id" );
                                ee()->db->where( "entry_id", $entries->entry_id );
                                if( count( $assigned_cat_groups ) > 0 ) {
                                    ee()->db->where_not_in( "group_id", $assigned_cat_groups );
                                }
                                ee()->db->join("exp_categories", "exp_category_posts.cat_id = exp_categories.cat_id");
                                $query = ee()->db->get( "exp_category_posts" );
                                foreach( $query->result_array() as $row ) {
                                    $unselect_cat_group_cat_ids[] = $row["cat_id"];
                                }

                            }
                            $import_cat_ids_merge = array_merge($import_cat_ids_merge, $unselect_cat_group_cat_ids);
                            if(!empty($import_cat_ids_merge)){
                                $entry->Categories = ee('Model')->get('Category')->filter('cat_id', 'IN', $import_cat_ids_merge)->all();
                            }
                            
                        }
                        if(isset($selectedData['configure']['title']) && $selectedData['configure']['title'] !== '0'){
                            if(substr_count($selectedData['configure']['title'], ' -> ') > 0){
                                $title_arr = explode(" -> ", $selectedData['configure']['title']);
                                $title_array = array();
                                foreach($title_arr as $portion){
                                    $title_array = empty($title_array)?$importDataRow[$portion]:$title_array[$portion];
                                }
                                $entry->title = utf8_encode($title_array);
                            }else{
                                $entry->title = utf8_encode($importDataRow[$selectedData['configure']['title']]);
                            }
                        }else{
                            $entry->title = "";
                        }
                        if(isset($selectedData['configure']['url_title']) && $selectedData['configure']['url_title'] !== '0'){
                            if(substr_count($selectedData['configure']['url_title'], ' -> ') > 0){
                                $url_title_arr = explode(" -> ", $selectedData['configure']['url_title']);
                                $url_title_array = array();
                                foreach($url_title_arr as $portion){
                                    $url_title_array = empty($url_title_array)?$importDataRow[$portion]:$url_title_array[$portion];
                                }
                                $entry->url_title = ee('Format')->make('Text',$url_title_array)->urlSlug()->compile();
                            }else{
                                $entry->url_title = ee('Format')->make('Text',$importDataRow[$selectedData['configure']['url_title']])->urlSlug()->compile();
                            }
                        }else{
                            $entry->url_title = ee('Format')->make('Text',$entry->title)->urlSlug()->compile();
                        }
                        $entry->entry_date = (isset($selectedData['configure']['entry_date']) && $selectedData['configure']['entry_date'] !== '0') ? $importDataRow[$selectedData['configure']['entry_date']] : ee()->localize->now;
                        $entry->edit_date = (isset($selectedData['configure']['entry_date']) && $selectedData['configure']['entry_date'] !== '0') ? $importDataRow[$selectedData['configure']['entry_date']] : ee()->localize->now;
                        if(isset($selectedData['configure']['expiry_date']) && $selectedData['configure']['expiry_date'] !== '0'){
                            $entry->expiration_date = $importDataRow[$selectedData['configure']['expiry_date']];
                        }
                        $entry->save();
                        if(isset($entry->entry_id) && $entry->entry_id > 0){
                            $this->importData['success'][] = array('title' => $entry->title, 'entry_id' => $entry->entry_id);
                            if($update == 1 && $entry->entry_id > 0){
                                $this->setSession('updated_entries_'.$token, ($this->session('updated_entries_'.$token) + 1));
                            }
                            elseif($insert_new == 1 && $entry->entry_id > 0){
                                $this->setSession('imported_entries_'.$token, ($this->session('imported_entries_'.$token) + 1));
                            }elseif($entry->entry_id > 0){
                                $this->setSession('imported_entries_'.$token, ($this->session('imported_entries_'.$token) + 1));
                            }

                            //save update or inserted entries id for the deleted existing entries scenario
                            $this->setSession('processed_entries_'.$token, array_merge($this->session('processed_entries_'.$token), array($entry->entry_id)));
                        }else{
                            $this->importData['fails'][] = array('title' => $entry->title);
                        }
                    }
                }
            }
        }

        $memory_usage_for_this_batch = memory_get_usage() - $this->session('memory_usage_'.$token);
        $this->setSession('total_memory_usage_'.$token, $this->session('total_memory_usage_'.$token) + $memory_usage_for_this_batch);
        $this->setSession('memory_usage_'.$token, $memory_usage_for_this_batch);
        
        $time_taken_for_this_batch = time() - $this->session('time_taken_'.$token);
        $this->setSession('total_time_taken_'.$token, $this->session('total_time_taken_'.$token) + $time_taken_for_this_batch);
        $this->setSession('time_taken_'.$token, $time_taken_for_this_batch);

        if($this->importData['process']['total'] >= ($this->importData['process']['offset']+$this->importData['process']['batches']) ){
            return array('return' => true, 'offset' => ($this->importData['process']['offset']+$this->importData['process']['batches']), 'batches' => $this->importData['process']['batches'], 'status' => 'pending');
        }else{

            //after process all entries check for deleted existing entroes scenario
            if($this->importData['delete_existing_entries'] == 1){
                if(is_array($this->session('processed_entries_'.$token)) && count($this->session('processed_entries_'.$token)) > 0){

                    //delete existing entries
                    $delete_existing_entries = $this->delete_existing_entries($this->session('processed_entries_'.$token), $channel_id);
                    $this->setSession('deleted_existing_entries_'.$token, $delete_existing_entries);
                }
            }

            return array('return' => true, 'status' => 'completed');
        }
    }

    /*
    * delete existing entries
    */
    function delete_existing_entries($entries, $channel_id) {
        
        if( count( $entries ) == 0 ) {
            return false;
        }

        ee()->db->select("entry_id");
        ee()->db->where('channel_id = ', $channel_id); 
        ee()->db->where_not_in("entry_id", $entries); 
        $query = ee()->db->get("exp_channel_titles");
    
        $delete_entry_ids = array();
        foreach ($query->result() as $row)
        {
            $delete_entry_ids[] = $row->entry_id;
        }
    
        if(count($delete_entry_ids)) {
            ee()->legacy_api->instantiate('channel_entries');
            ee()->api_channel_entries->delete_entry($delete_entry_ids);
        }

        return count($delete_entry_ids);
        
    }

    function getColArray($string){

        $jsonDecode = @json_decode($string, true);
        if(json_last_error() == JSON_ERROR_NONE){
            if(is_array($jsonDecode)){
                return $jsonDecode;
            }
        }else{
            $unData = @unserialize($string);
            if ($unData !== 'b:0;' || $unData !== false) {
                if(is_array($unData)){
                    return $unData;
                }
            } else {
                $jsonDecodeBase64 = base64_decode($jsonDecode, true);
                if(is_array($jsonDecodeBase64)){
                    return $jsonDecodeBase64;
                }else{
                    $serializeDecodeBase64 = base64_decode($unData, true);
                    if(is_array($serializeDecodeBase64)){
                        return $serializeDecodeBase64;
                    }
                }
            }
        }
    }

    function array_depth($array) {
        $max_indentation = 1;

        $array_str = print_r($array, true);
        $lines = explode("\n", $array_str);

        foreach ($lines as $line) {
            $indentation = (strlen($line) - strlen(ltrim($line))) / 4;

            if ($indentation > $max_indentation) {
                $max_indentation = $indentation;
            }
        }

        return ceil(($max_indentation - 1) / 2) + 1;
    }
    
    function insertIntoGrid($field,$configurationSettings,$colDatas,$rowData){
        $gridData = $this->generateGridData($colDatas,'grid',$field,$configurationSettings,$rowData);
        return $gridData;
    }

    function generateGridData($colDatas, $come_from,$field,$configurationSettings,$rowData){
        if($come_from == 'fluid_grid'){
            foreach($field['field_data'] as $col_id=>$gridField){
                if($gridField['col_type'] == 'relationship'){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        if(isset($colDatas[$gridField['col_name']]) && is_array($colDatas[$gridField['col_name']]) && count($colDatas[$gridField['col_name']]) > 0){

                            $relsArray = array();
                            foreach($colDatas[$gridField['col_name']] as $row){
                                if(is_numeric($row)){
                                    $relsArray[] = $row;
                                }elseif(substr_count($row, '-') > 0){
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('url_title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }else{
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }
                            }
                            $gridFieldArray['col_id_'.$gridField['col_id']]['data'] = $relsArray;
                        }
                    }else{
                        $gridFieldArray['col_id_'.$gridField['col_id']] = "";
                    }
                }elseif($gridField['col_type'] == 'file'){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        if(isset($colDatas[$gridField['col_name']]) && $colDatas[$gridField['col_name']] !== '0'){
                            if($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id'].'-file_setting']['upload_action'] == 'y' ){
                                $fileData = $this->imgUpload($colDatas[$gridField['col_name']], $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id'].'-file_setting'], "file");
                                $gridFieldArray['col_id_'.$gridField['col_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                            }else{
                                $gridFieldArray['col_id_'.$gridField['col_id']] = "";
                            }
                        }
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = "";
                    }
                }elseif($gridField['col_type'] == 'assets'){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        if(is_array($colDatas[$gridField['col_name']]) && count($colDatas[$gridField['col_name']]) > 0){
                            $colData = array();
                            foreach($colDatas[$gridField['col_name']] as $no=>$assetImgUrl){
                                if($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id'].'-file_setting']['upload_action'] == 'y' ){
                                    $assetData = $this->imgUpload($assetImgUrl, $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id'].'-file_setting'], "assets");
                                    $colData[$no] = ($assetData != false) ? $assetData['file_id'] : "";
                                }else{
                                    $colData[$no] = "";
                                }

                            }
                            $gridFieldArray['col_id_'.$gridField['col_id']] = $colData;
                        }
                        
                    }else{
                        $gridFieldArray['col_id_'.$gridField['col_id']] = array();
                    }

                }elseif($gridField['col_type'] == 'smart_members_field'){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        if(isset($colDatas[$gridField['col_name']])){
                            $gridFieldArray['col_id_'.$gridField['col_id']]['data'] = explode("|", $colDatas[$gridField['col_name']]);
                        }else{
                            $gridFieldArray['col_id_'.$gridField['col_id']] = array();
                        }
                    }
                }elseif($colDetail['col_type'] == 'wygwam' && $colData != null){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        $gridFieldArray['col_id_'.$gridField['col_id']] = $colDatas[$gridField['col_name']];
                    }else{
                        $gridFieldArray['col_id_'.$gridField['col_id']] = "";
                    }
                }elseif($colDetail['col_type'] == 'editor' && $colData != null){
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        $gridFieldArray['col_id_'.$gridField['col_id']] = $colDatas[$gridField['col_name']];
                    }else{
                        $gridFieldArray['col_id_'.$gridField['col_id']] = "";
                    }
                }else{
                    if(isset($configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']]) && $configurationSettings['fields']['grid'][$field['field_id']]['cols'][$gridField['col_id']] !== '0'){
                        $gridFieldArray['col_id_'.$gridField['col_id']] = $colDatas[$gridField['col_name']];
                    }else{
                        $gridFieldArray['col_id_'.$gridField['col_id']] = "";
                    }
                }
            }
            return $gridFieldArray;
        }else{

            $gridFieldArray = array();
            foreach($field['field_data'] as $col_id=>$colDetail){

                $gridColumnName = explode(" -> ",$configurationSettings['cols'][$col_id]);
                if(count($gridColumnName) == 1){
                    if(isset($gridColumnName[0]) && isset($rowData[$gridColumnName[0]]) && $rowData[$gridColumnName[0]] != null){
                        if(is_array($rowData)){
                            $colData = $rowData[$gridColumnName[0]];
                        }else{
                            $colData = $rowData;
                        }
                        // $colData = $rowData[$gridColumnName[0]];
                    }else{
                        $colData = null;
                    }
                }elseif(count($gridColumnName) == 2){
                    if(isset($gridColumnName[1]) && isset($colDatas[$gridColumnName[1]]) && $colDatas[$gridColumnName[1]] != null){
                        if(is_array($colDatas)){
                            $colData = $colDatas[$gridColumnName[1]];
                        }else{
                            $colData = $colDatas;
                        }
                        // $colData = $colDatas[$gridColumnName[1]];
                    }else{
                        $colData = null;
                    }
                }elseif(count($gridColumnName) == 3){
                    if(isset($colDatas[$gridColumnName[1]][$gridColumnName[2]]) && isset($colDatas[$gridColumnName[1]][$gridColumnName[2]]) && $colDatas[$gridColumnName[1]][$gridColumnName[2]] != null){
                        if(is_array($colDatas)){
                            $colData = $colDatas[$gridColumnName[1]][$gridColumnName[2]];
                        }else{
                            $colData = $colDatas;
                        }
                        // $colData = $colDatas[$gridColumnName[1]][$gridColumnName[2]];
                    }elseif(isset($colDatas[$gridColumnName[2]]) && isset($colDatas[$gridColumnName[2]]) && $colDatas[$gridColumnName[2]] != null){
                        if(is_array($colDatas)){
                            $colData = $colDatas[$gridColumnName[2]];
                        }else{
                            $colData = $colDatas;
                        }
                        // $colData = $colDatas[$gridColumnName[2]];
                    }else{
                        $colData = null;
                    }
                }else{
                    $str = str_replace($configurationSettings['main'],"",$configurationSettings['cols'][$col_id]);
                    $result = $colDatas;
                    $temp =& $result;
                    foreach(explode(" -> ",ltrim($str, " -> ")) as $key) {
                        if(isset( $temp[$key])){
                            $temp = $temp[$key];
                        }
                    }
                    $colData = $result;
                }

                if($colDetail['col_type'] == 'relationship' && $colData != null){
                    if(isset($configurationSettings['cols'][$col_id]) && $configurationSettings['cols'][$col_id] !== '0'){

                        $relsArray = array();
                        if(is_array($colData)){
                            foreach($colData as $row){
                                if(is_numeric($row)){
                                    $relsArray[] = $row;
                                }elseif(substr_count($row, '-') > 0){
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('url_title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }else{
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }
                            }
                        }else{
                            $playaData = explode(",", $colData);
                            foreach($playaData as $row){
                                if(is_numeric($row)){
                                    $relsArray[] = $row;
                                }elseif(substr_count($row, '-') > 0){
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('url_title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }else{
                                    $ent = ee('Model')->get('ChannelEntry')
                                    ->filter('title', $row)
                                    ->filter('site_id', $this->site_id)->first();    
                                    if(isset($ent->entry_id)){
                                        $relsArray[] = $ent->entry_id;
                                    }
                                }
                            }
                        }
                        $gridFieldArray['col_id_'.$col_id]['data'] = $relsArray;
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = array();
                    }
                }elseif($colDetail['col_type'] == 'file' && $colData != null){

                    if(isset($configurationSettings['cols'][$col_id]) && $configurationSettings['cols'][$col_id] !== '0'){
                        if(isset($colData) && $colData !== '0'){
                            if($configurationSettings['cols'][$col_id.'-file_setting']['upload_action'] == 'y' ){
                                $fileData = $this->imgUpload($colData, $configurationSettings['cols'][$col_id.'-file_setting'], "file");
                                $gridFieldArray['col_id_'.$col_id] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                            }else{
                                $gridFieldArray['col_id_'.$col_id] = "";
                            }
                        }
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = "";
                    }
                }elseif($colDetail['col_type'] == 'assets' && $colData != null){
                    if(isset($configurationSettings['cols'][$col_id]) && $configurationSettings['cols'][$col_id] !== '0'){
                        if(count($colData) > 0){
                            foreach($colData as $no=>$assetImgUrl){
                                if($configurationSettings['cols'][$col_id.'-file_setting']['upload_action'] == 'y' ){
                                    $assetData = $this->imgUpload($assetImgUrl, $configurationSettings['cols'][$col_id.'-file_setting'], "assets");
                                    $colData[$no] = ($assetData != false) ? $assetData['file_id'] : "";
                                }else{
                                    $colData[$no] = "";
                                }

                            }
                        }
                        $gridFieldArray['col_id_'.$col_id] = $colData;
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = array();
                    }
                }elseif($colDetail['col_type'] == 'smart_members_field' && $colData != null){
                    if(isset($configurationSettings['cols'][$col_id]) && $configurationSettings['cols'][$col_id] !== '0'){
                        $gridFieldArray['col_id_'.$col_id]['data'] = explode("|", $colData);    
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = array();
                    }
                }elseif($colDetail['col_type'] == 'wygwam' && $colData != null){
                    $gridFieldArray['col_id_'.$col_id] = $colData;
                }elseif($colDetail['col_type'] == 'editor' && $colData != null){
                    $gridFieldArray['col_id_'.$col_id] = $colData;
                }else{
                    if(isset($configurationSettings['cols'][$col_id]) && $configurationSettings['cols'][$col_id] !== '0'){
                        if(isset($colData) && $colData != null){

                            $fieldData = $colData;
                            if($colDetail['col_type'] == 'multi_select'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'toggle'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'date'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'radio'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'select'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'checkboxes'){
                                $fieldData = $colData;
                            }
                            if($colDetail['col_type'] == 'duration'){
                                //solved issue for xml duration field when empty it gives the array value : V3.0.2
                                if(is_array($colData)){
                                    $fieldData = '';
                                }else{
                                    $fieldData = $colData;
                                }
                            }
                            $gridFieldArray['col_id_'.$col_id] = $fieldData;
                        }
                    }else{
                        $gridFieldArray['col_id_'.$col_id] = "";
                    }
                }
                
            }
            return $gridFieldArray;
        }
    }


    function insertIntoFluid($field_id,$FluidDataWithSetting, $fluidFieldData, $configuratinData, $row, $fileType){
        $fluidData = $this->generateFluidData($field_id, $FluidDataWithSetting, $fluidFieldData, $configuratinData, $row, $fileType);
        return $fluidData;
    }


    function arraySearch($array, $search, $fileType, $no)
    {
        if($fileType == "csv"){
            $results=array();
            foreach($array as $key=>$value){

                foreach($value as $k=>$v){
                    if($k == $search){
                        $results[$no] = $v;
                        $place_key = $key;
                        $no++;
                    }
                }
            }
            if(isset($results) && !empty($results)){
                return array('no'=>$no,'results'=>$results, 'place_key' => ++$place_key);
            }else{
                return false;
            }
        }elseif($fileType == "json"){
            $results=array();
            foreach($array as $key=>$value){

                foreach($value as $k=>$v){
                    if($k == $search){
                        $results[$no] = $v;
                        $place_key = $key;
                        $no++;
                    }
                }
            }
            if(isset($results) && !empty($results)){
                return array('no'=>$no,'results'=>$results, 'place_key' => ++$place_key);
            }else{
                return false;
            }
        }elseif($fileType == "xml"){
            $results=array();
            $place_key = '';
            foreach($array as $key=>$value){

                foreach($value as $k=>$v){
                    if($k == $search){
                        $results[$no] = $v;
                        $place_key = $key;
                        $no++;
                    }
                }

            }
            if(isset($results) && !empty($results)){
                return array('no'=>$no,'results'=>$results, 'place_key' => ++$place_key);
            }else{
                return false;
            }
        }elseif($fileType == "third_party_xml"){
            $results=array();
            $place_key = '';
            foreach($array as $key=>$value){

                foreach($value as $k=>$v){
                    if($k == $search){
                        $results[$no] = $v;
                        $place_key = $key;
                        $no++;
                    }
                }

            }
            if(isset($results) && !empty($results)){
                return array('no'=>$no,'results'=>$results, 'place_key' => ++$place_key);
            }else{
                return false;
            }
        }
    }

    function generateFluidData($fluid_id,$FluidDataWithSetting, $fluidFieldData, $configuratinData, $rowData, $fileType){
        if(isset($configuratinData['main']) && $configuratinData['main'] !== "0" /*&& !empty($rowData[$configuratinData['main']])*/){
            if($fileType == "csv" || $fileType == "json"){
                $fluidFieldDataArray = json_decode($fluidFieldData, true);
            }elseif($fileType == "xml"){
                $k = -1;
                $fluidFieldDataArray = array();
                foreach($fluidFieldData as $frowKey=>$frowValue){
                    if(!is_array($frowValue)){
                        $fluidFieldDataArray[$k+1][$frowKey] = $frowValue;
                        $k=$k+1;
                    }else{
                        foreach($frowValue as $kk=>$vv){
                            if(is_array($vv)){
                                //V3.1.1: To fetch xml data for the fluid field type
                                $fluidFieldDataArray[$k+1][$frowKey][$kk] = $frowValue[$kk];
                               
                            }
                        }

                        foreach($frowValue as $kk=>$vv){
                            if(!is_array($vv)){
                                //V3.1.1: To fetch xml data for the fluid field type
                                $fluidFieldDataArray[$k+1][$frowKey] = $frowValue[$kk];
                                $k=$k+1;
                                //break 1;
                            }
                        }
                        //V3.1.1: to fetch multiple xml data for the fluid field type
                        $k=$k+1;
                    }
                }
            }elseif($fileType == "third_party_xml"){
                $k = -1;
                $fluidFieldDataArray = array();
                foreach($fluidFieldData as $frowKey=>$frowValue){
                    if(!is_array($frowValue)){
                        $fluidFieldDataArray[$k+1][$frowKey] = $frowValue;
                        $k=$k+1;
                    }else{
                        foreach($frowValue as $kk=>$vv){
                            if(is_array($vv)){
                                //V3.1.1: To fetch xml data for the fluid field type
                                $fluidFieldDataArray[$k+1][$frowKey][$kk] = $frowValue[$kk];
                               
                            }
                        }

                        foreach($frowValue as $kk=>$vv){
                            if(!is_array($vv)){
                                //V3.1.1: To fetch xml data for the fluid field type
                                $fluidFieldDataArray[$k+1][$frowKey] = $frowValue[$kk];
                                $k=$k+1;
                                //break 1;
                            }
                        }
                        //V3.1.1: to fetch multiple xml data for the fluid field type
                        $k=$k+1;
                    }
                }
            }

            $fluidFieldArray = array();
            $i=1;

            $fluid_id = $fluid_id;
            if(is_array($FluidDataWithSetting['field_data'])){
                foreach($FluidDataWithSetting['field_data'] as $fieldID=>$fluidField){
                    // $i++;    
                    if($fluidField['field_type'] == 'relationship'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){

                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType,$i);

                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals = array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    if(isset($vals) && is_array($vals)){
                                        $i++;
                                        foreach($vals as $new_field_number=>$data){
                                            $relsArray = array();
                                            $data = explode(',', $data);
                                            foreach($data as $row){
                                                if(is_numeric($row)){
                                                    $relsArray[] = $row;
                                                }elseif(substr_count($row, '-') > 0){
                                                    $ent = ee('Model')->get('ChannelEntry')
                                                    ->filter('url_title', $row)
                                                    ->filter('site_id', $this->site_id)->first();    
                                                    if(isset($ent->entry_id)){
                                                        $relsArray[] = $ent->entry_id;
                                                    }
                                                }else{
                                                    $ent = ee('Model')->get('ChannelEntry')
                                                    ->filter('title', $row)
                                                    ->filter('site_id', $this->site_id)->first();    
                                                    if(isset($ent->entry_id)){
                                                        $relsArray[] = $ent->entry_id;
                                                    }
                                                }
                                            }
                                            $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['data'] = $relsArray;$fluidFieldArray['new_field_'.$new_field_number]['order']['data'] = $relsArray;
                                        }
                                    }
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                foreach($valsArr['results'] as $new_field_number=>$data){
                               
                                    if(is_array($valsArr['results']) && count($valsArr['results']) > 0){
                                        $relsArray = array();
                                        foreach($valsArr['results'] as $row){
                                            if(is_numeric($row)){
                                                $relsArray[] = $row;
                                            }elseif(@substr_count($row, '-') > 0){
                                                $ent = ee('Model')->get('ChannelEntry')
                                                ->filter('url_title', $row)
                                                ->filter('site_id', $this->site_id)->first();    
                                                if(isset($ent->entry_id)){
                                                    $relsArray[] = $ent->entry_id;
                                                }
                                            }else{
                                                $ent = ee('Model')->get('ChannelEntry')
                                                ->filter('title', $row)
                                                ->filter('site_id', $this->site_id)->first();    
                                                if(isset($ent->entry_id)){
                                                    $relsArray[] = $ent->entry_id;
                                                }
                                            }
                                        }
                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['data'] = $relsArray;
                                    }
                                }
                            }
                            // else{
                            //     $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['data'] = array();                                
                            // }
                        }
                    }
                    elseif($fluidField['field_type'] == 'file'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){

                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType, $i);

                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals = array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    if(is_array($vals) && count($vals)){
                                        $i++;
                                        foreach($vals as $new_field_number=>$data){

                                            if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== '0'){
                                                if(isset($data) && $data !== '0'){
                                                    if($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                        // $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $this->imgUpload($data, $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting'], "file");

                                                        $fileData = $this->imgUpload($data, $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting'], "file");
                                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";
                                                    }else{
                                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                                                    }
                                                }
                                            }else{
                                                $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                                            }
                                        }
                                    }else{
                                        //$fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                                    }
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                $place_key = $valsArr['place_key'];
                                foreach($valsArr['results'] as $new_field_number=>$data){
                                    $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $data;

                                    if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== '0'){
                                        if(isset($data) && $data !== '0'){
                                            if($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting']['upload_action'] == 'y' ){
                                                // $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $this->imgUpload($data, $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting'], "file");

                                                $fileData = $this->imgUpload($data, $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id'].'-file_setting'], "file");
                                                $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = ($fileData != false) ? "{filedir_".$fileData['dir_id']."}".$fileData['file_name'] : "";

                                            }else{
                                                $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                                            }
                                        }
                                    }else{
                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                                    }
                                }
                            }
                            // else{
                            //     $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = "";
                            // }
                        }
                    }
                    elseif($fluidField['field_type'] == 'assets'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){
                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType, $i);
                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals = array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    // $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']] = $vals[$i];
                                    if(isset($configurationSettings['fields']['assets'][$fieldID]) && $configurationSettings['fields']['assets'][$fieldID] !== '0'){
                                        $assetArr = explode(",",$vals[$i]);
                                        $i++;
                                        foreach($assetArr as $no=>$assetUrl){
                                            if(isset($colData) && $colData !== '0'){
                                                if($configurationSettings['fields']['assets'][$fieldID.'-file_setting']['upload_action'] == 'y' ){
                                                    // $assetArr[$no] = $this->imgUpload($assetUrl, $configurationSettings['fields']['assets'][$fieldID.'-file_setting'], "assets");


                                                    $fileData = $this->imgUpload($assetUrl, $configurationSettings['fields']['assets'][$fieldID.'-file_setting'], "assets");
                                                    $assetArr[$no] = ($fileData != false) ? $fileData['file_id'] : "";


                                                }else{
                                                    $assetArr[$no] = "";
                                                }
                                            }
                                        }
                                        $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']] = $assetArr;
                                    }else{
                                        $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']] = array();
                                    }
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                $place_key = $valsArr['place_key'];
                                foreach($valsArr['results'] as $new_field_number=>$data){
                                    // $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $data;
                                    if(isset($configurationSettings['fields']['assets'][$fieldID]) && $configurationSettings['fields']['assets'][$fieldID] !== '0'){
                                        $assetArr = explode(",",$data);
                                        foreach($assetArr as $no=>$assetUrl){
                                            if(isset($colData) && $colData !== '0'){
                                                if($configurationSettings['fields']['assets'][$fieldID.'-file_setting']['upload_action'] == 'y' ){

                                                    $assetArr[$no] = $this->imgUpload($assetUrl, $configurationSettings['fields']['assets'][$fieldID.'-file_setting'], "assets");
                                                }else{
                                                    $assetArr[$no] = "";
                                                }
                                            }
                                        }
                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $assetArr;
                                    }else{
                                        $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = array();
                                    }
                                }
                            }
                            // else{
                            //     $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = array();
                            // }
                        }
                    }
                    elseif($fluidField['field_type'] == 'smart_members_field'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){
                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType, $i);
                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals= array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    if(is_array($vals) && count($vals)){
                                        $i++;
                                        foreach($vals as $new_field_number=>$data){
                                            $smfArr = explode('|',$data);
                                            $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['data'] = $smfArr;
                                        }
                                    }
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                $place_key = $valsArr['place_key'];
                                foreach($valsArr['results'] as $new_field_number=>$data){
                                    $smfArr = explode('|',$data);
                                    $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['data'] = $smfArr;
                                }
                            }
                        }
                    }
                    elseif($fluidField['field_type'] == 'grid'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main'] !== "0"){
                            $gridNameArr = explode(" -> ", $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']);
                            $valsArr = $this->arraySearch($fluidFieldDataArray, end($gridNameArr), $fileType, $i);
                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main'] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']]))){
                                    $vals = array();
                                    $vals = $this->getColArray($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]['main']]);
                                    if(is_array($vals)){
                                        
                                        // foreach($vals as $new_field_number=>$gdata){
                                            foreach($vals as $row_number=>$row_data){
                                                $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']]['rows']['new_row_'.($row_number+1)] = $this->generateGridData($row_data, 'grid', $fluidField, $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']], $rowData);
                                            }
                                        // }
                                        $i++;    
                                    }
                                }
                            }
                            elseif(is_array($valsArr) && count($valsArr)){
                                $i = $valsArr['no'];
                                $place_key = $valsArr['place_key'];
                                    
                                foreach($valsArr['results'] as $new_field_number=>$vals){

                                    foreach($vals as $row_number=>$row_data){

                                        // foreach($gdata as $row_number=>$row_data){
                                            $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']]['rows']['new_row_'.($row_number+1)] = $this->generateGridData($row_data, 'fluid_grid', $fluidField, $configuratinData, $rowData);

                                        // }
                                    }
                                }
                            }else{
                                $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = array();
                            }
                        }
                    }
                    elseif($fluidField['field_type'] == 'wygwam'){
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){
                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType, $i);
                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals = array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']] = $vals[$i];
                                    $i++;
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                foreach($valsArr['results'] as $new_field_number=>$vals){
                                    $place_key = $valsArr['place_key'];
                                    $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $vals;
                                }
                            }
                        }
                    }
                    else{
                        if(isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0"){
                            $valsArr = $this->arraySearch($fluidFieldDataArray, $fluidField['field_name'], $fileType, $i);
                            if($valsArr == false){
                                if((isset($configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]) && $configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']] !== "0") && (isset($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]) && !empty($rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]]))){
                                    $vals = array();
                                    $vals[$i] = $rowData[$configuratinData['fields'][$fluidField['field_type']][$fluidField['field_id']]];
                                    $fluidFieldArray['new_field_'.$i]['field_id_'.$fluidField['field_id']] = $vals[$i];
                                    $i++;
                                }
                            }
                            if(is_array($valsArr['results']) && count($valsArr['results'])){
                                $i = $valsArr['no'];
                                foreach($valsArr['results'] as $new_field_number=>$vals){
                                    $place_key = $valsArr['place_key'];
                                    $fluidFieldArray['new_field_'.$new_field_number]['field_id_'.$fluidField['field_id']] = $vals;
                                }
                            }
                        }
                    }
                }  

                return $fluidFieldArray;
            }else{
                return array();
            }
        }
    }

    public function handleAddNewImportFinal($data){
        $data['cp_page_title'] = 'import_configure';
        return $data;
    }

    /* get column for matrix */
    public function get_columns_for_matrix($field_id){

        ee()->db->select('col_id, col_type, col_name, col_label, col_settings');
        ee()->db->from('matrix_cols');
        ee()->db->where('field_id', $field_id);

        $get = ee()->db->get();
        if($get->num_rows == 0) { return false; }

        $temp = $get->result_array();
        $select = "";
        for ($i = 0; $i < count($temp); $i++)
        {
            $select .= "col_id_" . $temp[$i]['col_id'] . " AS '" . $temp[$i]['col_name'] . "', ";
            $temp[$i]['col_settings'] = unserialize(base64_decode($temp[$i]['col_settings']));
            $ret["col_id_" . $temp[$i]['col_id']] = $temp[$i];
        }
        
        return $ret;

    }

    /* generate matrix data */
    function generateMatrixData($MatrixDataWithSetting, $configurationSettings, $colDatas, $rowData){
        if(isset($MatrixDataWithSetting['field_data'])){
            $playaUpdatedata = array();
            $i = -1;
            $matrixFieldData = array();
            foreach($MatrixDataWithSetting['field_data'] as $matrixField){

                if($matrixField['col_type'] == 'playa'){
                    $i++;    
                }

                $matrixColumnName = explode(" -> ",$configurationSettings['cols'][$matrixField['col_id']]);


                
                if(count($matrixColumnName) == 1){
                    if(isset($matrixColumnName[0]) && (isset($rowData[$matrixColumnName[0]]) && $rowData[$matrixColumnName[0]] != null)){
                        $colData = $rowData[$matrixColumnName[0]];
                    }else{
                        $colData = null;
                    }
                }elseif(count($matrixColumnName) == 2){
                    if(isset($matrixColumnName[1]) && isset($colDatas[$matrixColumnName[1]]) && $colDatas[$matrixColumnName[1]] != null){
                        $colData = $colDatas[$matrixColumnName[1]];
                    }else{
                        $colData = null;
                    }
                }

                if($matrixField['col_type'] == 'text' && $colData != null){
                    $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                }elseif($matrixField['col_type'] == 'date' && $colData != null) {
                    $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                }elseif($matrixField['col_type'] == 'file' && $colData != null) {
                    if(isset($configurationSettings['cols'][$matrixField['col_id']]) && $configurationSettings['cols'][$matrixField['col_id']] !== '0'){
                        if(isset($colData) && $colData !== '0'){
                            if($configurationSettings['cols'][$matrixField['col_id'].'-file_setting']['upload_action'] == 'y' ){
                                 $fileData = $this->imgUpload($colData, $configurationSettings['cols'][$matrixField['col_id'].'-file_setting'], "file");
                                 $matrixFieldData['col_id_'.$matrixField['col_id']] = ($fileData != false) ? array("filedir"=>$fileData['dir_id'], "filename"=>$fileData['file_name'], "existing"=>"|") : array();
                            }else{
                                $matrixFieldData['col_id_'.$matrixField['col_id']] = array();
                            }
                        }
                    }else{
                        $matrixFieldData['col_id_'.$matrixField['col_id']] = "";
                    }
                }elseif($matrixField['col_type'] == 'number' && $colData != null) {
                    $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                }elseif($matrixField['col_type'] == 'playa' && $colData != null) {
                    $playacolData = array();
                    if(is_array($colData)){
                        foreach($colData as $row){
                            if(is_numeric($row)){
                                $playacolData[] = $row;
                            }elseif(substr_count($row, '-') > 0){
                                $ent = ee('Model')->get('ChannelEntry')
                                ->filter('url_title', $row)
                                ->filter('site_id', $this->site_id)->first();    
                                if(isset($ent->entry_id)){
                                    $playacolData[] = $ent->entry_id;
                                }
                            }else{
                                $ent = ee('Model')->get('ChannelEntry')
                                ->filter('title', $row)
                                ->filter('site_id', $this->site_id)->first();    
                                if(isset($ent->entry_id)){
                                    $playacolData[] = $ent->entry_id;
                                }
                            }
                        }
                    }else{
                        $playaData = explode(",", $colData);
                        foreach($playaData as $row){
                            if(is_numeric($row)){
                                $playacolData[] = $row;
                            }elseif(substr_count($row, '-') > 0){
                                $ent = ee('Model')->get('ChannelEntry')
                                ->filter('url_title', $row)
                                ->filter('site_id', $this->site_id)->first();    
                                if(isset($ent->entry_id)){
                                    $playacolData[] = $ent->entry_id;
                                }
                            }else{
                                $ent = ee('Model')->get('ChannelEntry')
                                ->filter('title', $row)
                                ->filter('site_id', $this->site_id)->first();    
                                if(isset($ent->entry_id)){
                                    $playacolData[] = $ent->entry_id;
                                }
                            }
                        }
                    }
                    $matrixFieldData['col_id_'.$matrixField['col_id']]['selections'] = $playacolData;
                }elseif($matrixField['col_type'] == 'rte' && $colData != null) { 
                    $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                }elseif($matrixField['col_type'] == 'wygwam' && $colData != null) {
                    $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                }elseif($matrixField['col_type'] == 'assets' && $colData != null) {
                    if(isset($configurationSettings['cols'][$matrixField['col_id']]) && $configurationSettings['cols'][$matrixField['col_id']] !== '0'){
                        if(count($colData) > 0){
                            foreach($colData as $no=>$assetImgUrl){
                                if($configurationSettings['cols'][$matrixField['col_id'].'-file_setting']['upload_action'] == 'y' ){
                                    $fileData = $this->imgUpload($assetImgUrl, $configurationSettings['cols'][$matrixField['col_id'].'-file_setting'], "assets");
                                    $colData[$no] = ($fileData != false) ? $fileData['file_id'] : ""; 
                                }else{
                                    $colData[$no] = "";
                                }

                            }
                        }
                        $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                    }else{
                        $matrixFieldData['col_id_'.$matrixField['col_id']] = "";
                    }
                }else{
                    if($colData != null){
                        $matrixFieldData['col_id_'.$matrixField['col_id']] = $colData;
                    }
                }
            }

            return $matrixFieldData;
        }
    }

    /* generate file url in native EE */
    function generateFileUrl($fielFieldData){

        $file_dir_id = array();
        preg_match('/{filedir_([0-9]+)\}/', $fielFieldData, $file_dir_id);

        $filename = explode($file_dir_id[0], $fielFieldData);

        return array('filedir'=>$file_dir_id[1],'filename'=>$filename[1], 'existing'=>'|');
    }

    function getHttpResponseCode($url)
    {
        $headers = @get_headers($url);
        return substr($headers[0], 9, 3);
    }

    /* uplaod image otherwise return path */
    function doUpload( $fileurl, $UploadData, $from)
    {
        $UpDir = ee('Model')->get('UploadDestination')
            ->filter('id',$UploadData['upload'])
            ->filter('site_id', ee()->config->item('site_id'))
            ->first();

        //sovled the error when upload dir not set or not found
        if(!$UpDir){
            return false;
        }
        $UploadData['name'] = $UpDir->name;


        $agent= 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';

        

        /*Check wether image is exists or not*/
        if($this->getHttpResponseCode($fileurl) != "200"){
            //second try because some time it give 403 error for above function but still we tryu to get the data
            //then give the othe option to get this thing if server provide the more security
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, $agent);
            curl_setopt($ch, CURLOPT_URL,$fileurl);
            $secure_content=curl_exec($ch); 
            curl_close($ch);
            if(empty($secure_content)){
                return false;
            }
        }else{

        }

        $filename   = basename($fileurl);
        $filedir    = $UpDir->server_path;


        $url = parse_url( $fileurl );

        if(isset( $url["scheme"] ) )
        {
            if( file_exists( $filedir.$filename ) )
            {
                if($from == 'file'){
                    $file = ee('Model')->get('File')
                        ->filter('site_id', ee()->config->item('site_id'))
                        ->filter('upload_location_id', $UpDir->id)
                        ->filter('file_name', $filename)
                        ->first();
                    return array("dir_id"=>$UpDir->id, 'file_name'=>$filename, 'file_id'=>isset($file->file_id)?$file->file_id:"");
                }elseif($from == 'assets'){
                    ee()->db->select('file_id');
                    ee()->db->from('assets_files');
                    ee()->db->where('filedir_id', $UpDir->id);
                    ee()->db->where('file_name', $filename);

                    $get = ee()->db->get();
                    if($get->num_rows == 0) { return false; }
                    $fileDetail = $get->result_array();
                    return array("dir_id"=>$UpDir->id, 'file_name'=>$filename, 'file_id'=>$fileDetail[0]['file_id']);
                }
            }
            $fetch_url = true;
            if( $fetch_url === TRUE )
            {
                /*Get image data*/
                if(!isset($secure_content)){
                    $content = file_get_contents( $fileurl );
                }
                else{
                    $content = $secure_content;
                }

                if( $content === FALSE )
                {
                    return FALSE;
                }

                /*Create directore if not exists*/
                if( is_dir($filedir) === false )
                {

                    $new_dir = @mkdir($filedir,2);

                    if($new_dir == true)
                    {
                        chmod($filedir, 0777);
                    }

                }

                /*Save image on given directory*/
                if( file_put_contents($filedir.$filename, $content) === FALSE )
                {
                    return FALSE;
                }
            
            }
        }
        else
        {

            if(!is_dir($filedir))
            {

                $result = @mkdir($filedir);

                if($result == true)
                {
                    @chmod($filedir, 0777);
                }

            }

            $ch = curl_init($fileurl);
            $fp = fopen($filedir . $filename, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_exec($ch);
            curl_close($ch);

            fclose($fp);

            chmod($filedir . $filename, 0777);
        } 
        $imgData = array('name'=>$filename,'data'=>getimagesize($filedir . $filename),'size'=>filesize($filedir . $filename));
        $file = ee('Model')->make('File');
        $file->site_id = $this->site_id;
        $file->upload_location_id = $UploadData['upload'];
        $file->uploaded_by_member_id = ee()->session->userdata('member_id');
        $file->modified_by_member_id = ee()->session->userdata('member_id');
        $file->title = $filename;
        $file->file_name = $imgData['name'];
        $file->upload_date = ee()->localize->now;
        $file->modified_date = ee()->localize->now;
        $file->mime_type = $imgData['data']['mime'];
        $file->file_size = $imgData['size'];
        $file->save();

        // $fileMetaData = $this->doSyncFiles($UploadData['upload'],$imgData);

        if($from == 'assets'){
            $file_info = get_headers($fileurl);
            $ftype = (isset($file_info[0]) && isset($file_info[0]["Content-Type"])) ? $file_info[0]["Content-Type"] : $imgData['data']['mime'];
            $filetype = explode("/",$ftype);



            ee()->db->select('folder_id');
            ee()->db->from('assets_folders');
            ee()->db->where('folder_name', $UploadData['name']);

            $get = ee()->db->get();
            if($get->num_rows == 0) { return false; }

            $folderDetail = $get->result_array();

            $data = array();
            $data['folder_id'] = $folderDetail[0]['folder_id'];
            $data['source_type'] = "ee";
            $data['source_id'] = NULL;
            $data['filedir_id'] = $UploadData['upload'];
            $data['file_name'] = $imgData['name'];
            $data['title'] = NULL;
            $data['date'] = ee()->localize->now;
            $data['alt_text'] = NULL;
            $data['caption'] = NULL;
            $data['author'] = NULL;
            $data['desc'] = NULL;
            $data['location'] = NULL;
            $data['search_keywords'] = $imgData['name'];
            $data['date_modified'] = ee()->localize->now;
            $data['location'] = NULL;
            $data['keywords'] = NULL;
            $data['kind'] = $filetype[0]; 
            $data['width'] = NULL;
            $data['height'] = NULL;
            $data['size'] = NULL;

            ee()->db->insert('assets_files', $data);
            $assets_file_id = ee()->db->insert_id();



        }


        if($file->file_id > 0 ){
            if($from == 'assets'){
                return array("dir_id"=>$UploadData['upload'], 'file_name'=>$imgData['name'], 'file_id'=>$assets_file_id);
            }else{
                return array("dir_id"=>$UploadData['upload'], 'file_name'=>$imgData['name'], 'file_id'=>$file->file_id);
            }
        }else{
            return false;
        }


    }

    function get_remote_file_info($url) {
        if(!empty($url)){
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, TRUE);
            curl_setopt($ch, CURLOPT_NOBODY, TRUE);
            $data = curl_exec($ch);
            $fileSize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
            $httpResponseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [
                'fileExists' => (int) $httpResponseCode == 200,
                'fileSize' => (int) $fileSize
            ];
        }
    }
    
    /* upload image */
    function imgUpload($url, $UploadData = array(), $from = "file"){

        //support other file like mp3 (V2.0.1)
        $file_length = $this->get_remote_file_info($url, true);
        if(isset($url) && $file_length > 0 && $UploadData['upload_action'] == 'y'){
            return $this->doUpload($url, $UploadData, $from);
        }else{
            return false;
        }    


    }


    public function doSyncFiles($id,$file)
    {
        $type = 'insert';
        $errors = array();
        $file_data = array();
        $replace_sizes = array();
        $db_sync = 'y';
        // $id = ee()->input->post('upload_directory_id');
        $sizes = ee()->input->post('sizes') ?: array($id => '');


        // If file exists- make sure it exists in db - otherwise add it to db and generate all child sizes
        // If db record exists- make sure file exists -  otherwise delete from db - ?? check for child sizes??


        ee()->load->library('filemanager');
        ee()->load->model('file_model');

        $upload_dirs = ee()->filemanager->fetch_upload_dirs(array('ignore_site_id' => FALSE));
        $_upload_dirs = array();
        foreach ($upload_dirs as $row)
        {
            $_upload_dirs[$row['id']] = $row;
        }


        // Final run through, it syncs the db, removing stray records and thumbs
        if ($db_sync == 'y')
        {

        }
        $dir_data = $_upload_dirs[$id];

        ee()->filemanager->xss_clean_off();
        $dir_data['dimensions'] = (is_array($sizes[$id])) ? $sizes[$id] : array();
        ee()->filemanager->set_upload_dir_prefs($id, $dir_data);

        // Now for everything NOT forcably replaced

        $missing_only_sizes = (is_array($sizes[$id])) ? $sizes[$id] : array();

        // Check for resize_ids
        $resize_ids = ee()->input->post('resize_ids');

        if (is_array($resize_ids))
        {
            foreach ($resize_ids as $resize_id)
            {
                $replace_sizes[$resize_id] = $sizes[$id][$resize_id];
                unset($missing_only_sizes[$resize_id]);
            }
        }

        // @todo, bail if there are no files in the directory!  :D

        // $files = ee()->filemanager->fetch_files($id, $current_files, TRUE);

        // Setup data for batch insert
        // foreach ($files->files[$id] as $file)
        // {
            if ( ! $file['data']['mime'])
            {
                $errors[$file['name']] = lang('invalid_mime');
                // continue;
            }

            // Clean filename
            $clean_filename = basename(ee()->filemanager->clean_filename(
                $file['name'],
                $id,
                array('convert_spaces' => FALSE)
            ));

            if ($file['name'] != $clean_filename)
            {
                // It is just remotely possible the new clean filename already exists
                // So we check for that and increment if such is the case
                if (file_exists($_upload_dirs[$id]['server_path'].$clean_filename))
                {
                    $clean_filename = basename(ee()->filemanager->clean_filename(
                        $clean_filename,
                        $id,
                        array(
                            'convert_spaces' => FALSE,
                            'ignore_dupes' => FALSE
                        )
                    ));
                }

                // Rename the file
                if ( ! @copy($_upload_dirs[$id]['server_path'].$file['name'],
                            $_upload_dirs[$id]['server_path'].$clean_filename))
                {
                    $errors[$file['name']] = lang('invalid_filename');
                    // continue;
                }

                unlink($_upload_dirs[$id]['server_path'].$file['name']);
                $file['name'] = $clean_filename;
            }

            // Does it exist in DB?
            $query = ee()->file_model->get_files_by_name($file['name'], $id);

            if ($query->num_rows() > 0)
            {
                // It exists, but do we need to change sizes or add a missing thumb?

                if ( ! ee()->filemanager->is_editable_image($_upload_dirs[$id]['server_path'].$file['name'], $file['data']['mime']))
                {
                    // continue;
                }

                // Note 'Regular' batch needs to check if file exists- and then do something if so
                if ( ! empty($replace_sizes))
                {
                    $thumb_created = ee()->filemanager->create_thumb(
                        $_upload_dirs[$id]['server_path'].$file['name'],
                        array(
                            'server_path'   => $_upload_dirs[$id]['server_path'],
                            'file_name'     => $file['name'],
                            'dimensions'    => $replace_sizes,
                            'mime_type'     => $file['data']['mime']
                        ),
                        TRUE,   // Create thumb
                        FALSE   // Overwrite existing thumbs
                    );

                    if ( ! $thumb_created)
                    {
                        $errors[$file['name']] = lang('thumb_not_created');
                    }
                }

                // Now for anything that wasn't forcably replaced- we make sure an image exists
                $thumb_created = ee()->filemanager->create_thumb(
                    $_upload_dirs[$id]['server_path'].$file['name'],
                    array(
                        'server_path'   => $_upload_dirs[$id]['server_path'],
                        'file_name'     => $file['name'],
                        'dimensions'    => $missing_only_sizes,
                        'mime_type'     => $file['data']['mime']
                    ),
                    TRUE,   // Create thumb
                    TRUE    // Don't overwrite existing thumbs
                );

                $file_path_name = $_upload_dirs[$id]['server_path'].$file['name'];

                // Update dimensions
                $image_dimensions = ee()->filemanager->get_image_dimensions($file_path_name);

                $file_data = array(
                    'file_id'               => $query->row('file_id'),
                    'file_size'             => filesize($file_path_name),
                    'file_hw_original'      => $image_dimensions['height'] . ' ' . $image_dimensions['width']
                );
                ee()->file_model->save_file($file_data);

                // continue;
            }

            $file_location = reduce_double_slashes(
                $dir_data['url'].'/'.$file['name']
            );

            $file_path = reduce_double_slashes(
                $dir_data['server_path'].'/'.$file['name']
            );

            $file_dim = (isset($file['dimensions']) && $file['dimensions'] != '') ? str_replace(array('width="', 'height="', '"'), '', $file['dimensions']) : '';

            $image_dimensions = ee()->filemanager->get_image_dimensions($file_path);

            $file_data = array(
                'upload_location_id'    => $id,
                'site_id'               => ee()->config->item('site_id'),
                'mime_type'             => $file['data']['mime'],
                'file_name'             => $file['name'],
                'file_size'             => $file['size'],
                'uploaded_by_member_id' => ee()->session->userdata('member_id'),
                'modified_by_member_id' => ee()->session->userdata('member_id'),
                'file_hw_original'      => $image_dimensions['height'] . ' ' . $image_dimensions['width'],
                'upload_date'           => ee()->localize->now,
                'modified_date'         => ee()->localize->now
            );


            $saved = ee()->filemanager->save_file($_upload_dirs[$id]['server_path'].$file['name'], $id, $file_data, FALSE);
            return $saved;
            // if ( ! $saved['status'])
            // {
            //     $errors[$file['name']] = $saved['message'];
            // }
        // }

    }


    // public function sync($upload_id = NULL)
    // {

    //     ee()->load->model('file_upload_preferences_model');

    //     // Get upload destination with config.php overrides in place
    //     $upload_destination = ee()->file_upload_preferences_model->get_file_upload_preferences(
    //         ee()->session->userdata('group_id'),
    //         $upload_id
    //     );


    //     // Get a listing of raw files in the directory
    //     ee()->load->library('filemanager');
    //     $files = ee()->filemanager->directory_files_map(
    //         $upload_destination['server_path'],
    //         1,
    //         FALSE,
    //         $upload_destination['allowed_types']
    //     );
    //     $files_count = count($files);

    //     // Change the decription of this first field depending on the
    //     // type of files allowed
    //     $file_sync_desc = ($upload_destination['allowed_types'] == 'all')
    //         ? lang('file_sync_desc') : lang('file_sync_desc_images');


    //     $sizes = ee('Model')->get('FileDimension')
    //         ->filter('upload_location_id', $upload_id)->all();

    //     $size_choices = array();
    //     $js_size = array();
    //     foreach ($sizes as $size)
    //     {
    //         // For checkboxes
    //         $size_choices[$size->id] = [
    //             'label' => $size->short_name,
    //             'instructions' => lang($size->resize_type) . ', ' . $size->width . 'px ' . lang('by') . ' ' . $size->height . 'px'
    //         ];

    //         // For JS sync script
    //         $js_size[$size->upload_location_id][$size->id] = array(
    //             'short_name'   => $size->short_name,
    //             'resize_type'  => $size->resize_type,
    //             'width'        => $size->width,
    //             'height'       => $size->height,
    //             'quality'      => $size->quality,
    //             'watermark_id' => $size->watermark_id
    //         );
    //     }

    //     // Only show the manipulations section if there are manipulations
    //     if ( ! empty($size_choices))
    //     {
    //         $vars['sections'][0][] = array(
    //             'title' => 'apply_manipulations',
    //             'desc' => 'apply_manipulations_desc',
    //             'fields' => array(
    //                 'sizes' => array(
    //                     'type' => 'checkbox',
    //                     'choices' => $size_choices,
    //                     'no_results' => [
    //                         'text' => sprintf(lang('no_found'), lang('image_manipulations'))
    //                     ]
    //                 )
    //             )
    //         );
    //     }

    //     $base_url = ee('CP/URL')->make('files/uploads/sync/'.$upload_id);

    //     ee()->cp->add_js_script('file', 'cp/files/synchronize');

    //     // Globals needed for JS script
    //     ee()->javascript->set_global(array(
    //         'file_manager' => array(
    //             'sync_id'         => $upload_id,
    //             'sync_files'      => $files,
    //             'sync_file_count' => $files_count,
    //             'sync_sizes'      => $js_size,
    //             'sync_baseurl'    => $base_url->compile(),
    //             'sync_endpoint'   => ee('CP/URL')->make('files/uploads/do_sync_files')->compile(),
    //             'sync_dir_name'   => $upload_destination['name'],
    //         )
    //     ));

    //     ee()->view->base_url = $base_url;
    //     ee()->view->cp_page_title = lang('sync_title');
    //     ee()->view->cp_page_title_alt = sprintf(lang('sync_alt_title'), $upload_destination['name']);
    //     ee()->view->save_btn_text = 'btn_sync_directory';
    //     ee()->view->save_btn_text_working = 'btn_sync_directory_working';

    //     ee()->cp->set_breadcrumb(ee('CP/URL')->make('files'), lang('file_manager'));

    //     // Errors are given through a POST to this same page
    //     $errors = ee()->input->post('errors');
    //     if ( ! empty($errors))
    //     {
    //         ee()->view->set_message('warn', lang('directory_sync_warning'), json_decode($errors));
    //     }

    //     ee()->cp->render('settings/form', $vars);
    // }



    /* get assetsfolder location */
    function getLocationForAssets($dirArr){
        return $dirData = ee()->sieModel->getDirNameByIDForAssets($dirArr);
    }

    /* delete import*/
    function deleteImports(){
        $ret = ee()->sieModel->deleteImports();
        return $ret; 
    }

    /* Handle batch wise immport */
    function ajaxImportHandler()
    {
        $moduleThemeUrl     = URL_THIRD_THEMES . "smart_import_export/temp";
        $moduleThemePath    = PATH_THIRD_THEMES . "smart_import_export/temp";

        $ret = array(
            'status'    => 'pending',
            'offset'    => $this->importData['offset'] + $this->importData['limit'],
            'limit'     => $this->importData['limit'],
            'totalrows' => $this->importData['totalRows']
        );

        if(($this->importData['offset'] + $this->importData['limit']) >= $this->importData['totalRows']){
            $ret['status']  = "completed";
            // $ret['url']     = $moduleThemeUrl . "/" . $filename;
        }else{
            $query = $_GET;
            $query['offset'] = $ret['offset'];
            $query_result = http_build_query($query);
            $ret['next_batch'] = $_SERVER['PHP_SELF'] ."?". $query_result;
        }
        
        unset($this->importData);
        return base64_encode(json_encode($ret));
        exit();

    }

    //V3.1.0 : GRID OPTIONS FEATURE
    function setGridImportData($field_typee, $entries, $settings, $field, $channel_id, $update, $colArray, $selectedData, $importDataRow){
       

        //Get old records of the grid data
        $grid_count = 0;
        $grid_action_unique_select = isset($settings['configure'][$field_typee]['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]['action']) ? $settings['configure'][$field_typee]['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']]['action'] : 0;

        // 0 means delete the existing row and add new
        // -1 means keep exitig and appen new row
        // field id then pass the exiting record and compare with that field id and update it and append new row

        //only when entry is updated
        //grid_action_unique_select != 0
        $grid_compare_ar = array();
        if($update == 1 && ($grid_action_unique_select != 0) ){
            ee()->db->select( "*" );
            ee()->db->from( "exp_channel_grid_field_".$field['field_id'] );
            ee()->db->where( "entry_id", $entries->entry_id );
            ee()->db->order_by( "row_order ASC" );
            $query = ee()->db->get();
            
            $old_grid = array();
            $new_grid = array();
            if(is_array($colArray) && count($colArray) > 0){
                for($i=1;$i<=count($colArray);$i++){
                    $new_grid[] = $this->insertIntoGrid($field, $selectedData['configure'][$field_typee]['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']], $colArray[$i-1],$importDataRow); 
                }   
            }
            foreach( $query->result_array() as $row ) {
                $row_id = $row["row_id"]; 
                unset( $row["row_order"] );
                unset( $row["entry_id"] );
                unset( $row["row_id"] );
                unset( $row["fluid_field_data_id"] );

                //Compare with specific col and update other cols
                if($grid_action_unique_select != -1){
                    $compare_value = $row['col_id_'.$grid_action_unique_select];     
                    $find_same_grid_key = array_search($compare_value, array_column($new_grid, 'col_id_'.$grid_action_unique_select));

                    //set the value
                    if($find_same_grid_key != '' || $find_same_grid_key === 0){

                        $grid_compare_ar[] = $compare_value;
                        if(isset($new_grid[$find_same_grid_key]))
                        {
                            foreach ($new_grid[$find_same_grid_key] as $key => $value) {
                                $row[$key] = $value;
                            }
                            //unset from the new array
                            unset($new_grid[$find_same_grid_key]); 
                            $new_grid = array_values($new_grid);
                        }

                    }

                }

                $old_grid[ "new_row_" . ++$grid_count ] = $row;
            }


            //added the old grid data
            $entryFieldArray['field_id_'.$field['field_id']]['rows'] = $old_grid;
        }
        // exit;
        //add the new grid data
        if($this->is_multi($colArray)){
            if(is_array($colArray) && count($colArray) > 0){
                for($i=1;$i<=count($colArray);$i++){

                    $get_insert_grid = $this->insertIntoGrid($field, $selectedData['configure'][$field_typee]['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']], $colArray[$i-1],$importDataRow);
                    if(count($grid_compare_ar) > 0){
                        if(in_array($get_insert_grid['col_id_'.$grid_action_unique_select], $grid_compare_ar)){
                            continue;
                        }
                    }

                    $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_'.++$grid_count] = $get_insert_grid; 
                }   
            } 
        }else{
            $get_insert_grid = $this->insertIntoGrid($field, $selectedData['configure'][$field_typee]['channel'][$channel_id]['fields'][$field['field_type']][$field['field_id']], $colArray,$importDataRow);
            $entryFieldArray['field_id_'.$field['field_id']]['rows']['new_row_1'] = $get_insert_grid; 
        }
        return $entryFieldArray;    
    }

    //V3.1.4: handle the category import array
    function handleImportCategory($cat_default_value, $cat_data, $cat_delimiter, $cgroup, $pc_cat_delimeter, $create_category_if_not, $force_exact_categories){

        //make an array of categories ids and it is return of this function
        $ent_categories = array();
        
        if ( $cat_default_value != "" ) {
            if( is_numeric( $cat_default_value ) ) {
                // consider numeric categories are category ids
                $ent_categories[] = $cat_default_value;
            } else {
                $ent_categories[] = $this->category_create( $cat_default_value, $cgroup, $create_category_if_not, $force_exact_categories);
            }
        }
        
        //cat data should not be empty
        if ( $cat_data != '' ) {

            if ( $cat_delimiter != "" ) {
                $cats = explode( $cat_delimiter, $cat_data );
            } else {
                //make array of the cat data
                $cats = array( $cat_data );
            }
            
            // remove duplicated cateogry with trim data
            foreach( $cats as $id => $cat ) {
                $cats[ $id ] = trim( $cat );
            }
            $cats = array_unique( $cats );
            
            foreach( $cats as $cat ) {
                if( is_numeric( $cat ) ) {
                    // consider numeric categories are category ids
                    $ent_categories[] = $cat;
                } else {

                    //set parent child delimeter
                    if($pc_cat_delimeter != ''){
                        $level_cats = explode($pc_cat_delimeter, $cat);
                    }else{
                        // $level_cats = array($level_cats);
                    }
                    $parent_id = 0;
                    if ( isset($level_cats) && count( $level_cats ) == 1 ) {
                        $ent_categories[] = $this->category_create( $cat, $cgroup, $create_category_if_not, $force_exact_categories);
                    }else{
                        if(isset($level_cats)){
                            foreach( $level_cats as $lev_cat ) {
                                $cat_id = $this->category_create( $lev_cat, $cgroup, $create_category_if_not, $force_exact_categories, $parent_id );
                                $ent_categories[] = $cat_id;
                                $parent_id = $cat_id;
                            }
                        }elseif(isset($cats)){
                            foreach( $cats as $category ) {
                                $cat_id = $this->category_create( $category, $cgroup, $create_category_if_not, $force_exact_categories, $parent_id );
                                $ent_categories[] = $cat_id;
                                $parent_id = $cat_id;
                            }
                        }
                    }
                }
            }   
        }
        
        return $ent_categories;

    }

    //V3.1.11: handle the category import from sing column only 
    function handleImportCategoryOnly($cat_default_value, $cat_data, $cat_delimiter, $cgroup, $pc_cat_delimeter, $create_category_if_not, $force_exact_categories){

        //make an array of categories ids and it is return of this function
        $ent_categories = array();
        
        if ( $cat_default_value != "" ) {
            if( is_numeric( $cat_default_value ) ) {
                // consider numeric categories are category ids
                $ent_categories[] = $cat_default_value;
            } else {
                $ent_categories[] = $this->category_create( $cat_default_value, $cat_data, $cgroup, $create_category_if_not, $force_exact_categories);
            }
        }
        
        //cat data should not be empty
        if ( $cat_data != '' ) {

            if ( $cat_delimiter != "" ) {
                $cats = explode( $cat_delimiter, $cat_data );
            } else {
                //make array of the cat data
                $cats = array( $cat_data );
            }
            
            // remove duplicated cateogry with trim data
            foreach( $cats as $id => $cat ) {
                $cats[ $id ] = trim( $cat );
            }
            $cats = array_unique( $cats );
            
            foreach( $cats as $cat ) {
                if( is_numeric( $cat ) ) {
                    // consider numeric categories are category ids
                    $ent_categories[] = $cat;
                } else {

                    //set parent child delimeter
                    if($pc_cat_delimeter != ''){
                        $level_cats = explode($pc_cat_delimeter, $cat);
                    }else{
                        // $level_cats = array($level_cats);
                    }
                    $parent_id = 0;
                    if ( isset($level_cats) && count( $level_cats ) == 1 ) {
                        $ent_categories[] = $this->category_create( $cat, $cgroup, $create_category_if_not, $force_exact_categories);
                    }else{
                        if(isset($level_cats)){
                            foreach( $level_cats as $lev_cat ) {
                                $cat_id = $this->category_create( $lev_cat, $cgroup, $create_category_if_not, $force_exact_categories, $parent_id );
                                $ent_categories[] = $cat_id;
                                $parent_id = $cat_id;
                            }
                        }elseif(isset($cats)){
                            foreach( $cats as $category ) {
                                $cat_id = $this->category_create( $category, $cgroup, $create_category_if_not, $force_exact_categories, $parent_id );
                                $ent_categories[] = $cat_id;
                                $parent_id = $cat_id;
                            }
                        }
                    }
                }
            }   
        }
        
        return $ent_categories;

    }

    //V3.1.11: handle the category import from default value field 
    function handleImportCategorySimple($cat_default_value, $cat_data, $cat_delimiter, $cgroup, $pc_cat_delimeter, $create_category_if_not, $force_exact_categories){

        //make an array of categories ids and it is return of this function
        $ent_categories = array();
        
        if ( $cat_default_value != "" ) {
            if( is_numeric( $cat_default_value ) ) {
                // consider numeric categories are category ids
                $ent_categories[] = $cat_default_value;
            } else {
                $ent_categories[] = $this->category_create( $cat_default_value, $cat_data, $cgroup, $create_category_if_not, $force_exact_categories);
            }
        }
        
        //cat data should not be empty
        if ( $cat_data != '' ) {

            if ( $cat_delimiter != "" ) {
                $cats = explode( $cat_delimiter, $cat_data );
            } else {
                //make array of the cat data
                $cats = array( $cat_data );
            }
            
            // remove duplicated cateogry with trim data
            foreach( $cats as $id => $cat ) {
                $cats[ $id ] = trim( $cat );
            }
            $cats = array_unique( $cats );
            
            foreach( $cats as $cat ) {
                if( is_numeric( $cat ) ) {
                    // consider numeric categories are category ids
                    $ent_categories[] = $cat;
                } else {

                    //set parent child delimeter
                    if($pc_cat_delimeter != ''){
                        $level_cats = explode($pc_cat_delimeter, $cat);
                    }else{
                        // $level_cats = array($level_cats);
                    }
                    $parent_id = 0;
                    if ( count( $level_cats ) == 1 ) {
                        $ent_categories[] = $this->category_create( $cat, $cgroup, $create_category_if_not, $force_exact_categories);
                    }else{
                        foreach( $level_cats as $lev_cat ) {
                            $cat_id = $this->category_create( $lev_cat, $cgroup, $create_category_if_not, $force_exact_categories, $parent_id );
                            $ent_categories[] = $cat_id;
                            $parent_id = $cat_id;
                        }
                    }
                }
            }   
        }
        
        return $ent_categories;

    }



    //V3.1.4: this function create the category if does not exist
    function category_create($cat, $cgroup, $create_category_if_not, $force_exact_categories , $parent_id = 0){
        //trim the cat data
        $cat = trim($cat);
        
        if(empty($cat)){
            return '';
        } 
        
        ee()->db->select( "*" );
        ee()->db->where( "group_id",  $cgroup );
        ee()->db->where( "cat_name", $cat );
        if( $parent_id != 0 ) {
            ee()->db->where( "parent_id",  $parent_id );
        }else{
            if($force_exact_categories == 1){
              ee()->db->where( "parent_id",  0 ) ; 
            }
        }
        $query = ee()->db->get( "exp_categories" );

        //create the category if it doesn not exist
        if ( $query->num_rows == 0) {
            
            if($create_category_if_not == 1){
                $cat_url_title = ee('Format')->make('Text', $cat)->urlSlug(['separator' => '-', 'lowercase' => TRUE])->compile();           
                $create_array = array(
                    'cat_name' => $cat,
                    'group_id' => $cgroup,
                    'cat_url_title' => $cat_url_title,
                    'parent_id' => $parent_id,
                    'cat_order' => 1,
                    'cat_image' => '',
                    'site_id' => $this->site_id
                    );
                ee()->db->query(ee()->db->insert_string('exp_categories', $create_array));
                //get id of the added category
                $cate_id = ee()->db->insert_id();

                $create_array = array(
                    'cat_id'    => $cate_id,
                    'group_id'  => $cgroup,
                    'site_id'   => $this->site_id,
                );
                ee()->db->query(ee()->db->insert_string('exp_category_field_data', $create_array));

                return $cate_id;
            }else{
                return '';    
            }
            
        } else {
            //if cate is already created then it return the just cat id
            $data = $query->row();
            return $data->cat_id;
        }
    }

    function is_multi($a) {
        $rv = array_filter($a,'is_array');
        if(count($rv)>0) return true;
        return false;
    }


}


class XmlElement {
  var $name;
  var $attributes;
  var $content;
  var $children;
};