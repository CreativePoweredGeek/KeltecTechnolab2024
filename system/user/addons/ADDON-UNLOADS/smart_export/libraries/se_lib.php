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
* ZealousWeb - Smart Export
*
* @package      SmartExport
* @author       Mufi
* @copyright    Copyright (c) 2016, ZealousWeb.
* @link         http://zealousweb.com/expressionengine/smart-export
* @filesource   ./system/expressionengine/third_party/smart_export/mod.smart_export.php
*
*/

use EllisLab\ExpressionEngine\Library\CP\Table;

class Se_lib
{

    /* Important globel variables */ 
    public $site_id;
    public $member_id;
    public $group_id;
    public $delim       = ",";
    public $newline     = "\n";
    public $enclosure   = '"';
    public $exportData;
    public $errors;
    public $type;
    public $search;
    public $replace;

    /* Constructor */
    public function __construct()
    {

        /*Logged in member ID, group ID and site ID*/
        $this->site_id      = ee()->config->item("site_id");
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata('group_id');
        $exportData         = array();

        /* Neeful model classes */
        if(! class_exists('se_model'))
        {
            ee()->load->model('se_model','seModel');
        }

        $this->search   = array('"', "{base_url}", "{base_path}");
        $this->replace  = array("\"", ee()->config->item('base_url'), ee()->config->item('base_path'));

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

        $url = 'addons/settings/smart_export/'.$method;
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
        $result = ee()->seModel->getChannelFields( array('group_id' => $group_id));
        
        if(isset($result) && is_array($result) && count($result) > 0)
        {

            for ($i=0; $i < count($result); $i++)
            {

                /*Check whether field is GRID type*/
                if($result[$i]['field_type'] == "grid")
                {

                    /*Relationships inside GRID*/
                    $q2 = ee()->seModel->relationshipsInsideGrid($result[$i]['field_id']);

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
                    $q2 = ee()->seModel->relationshipsInsideMatrix($result[$i]['field_id']);
                    
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
        $result = ee()->seModel->getAllChannelFields($channelID);
        
        if(isset($result) && is_array($result) && count($result) > 0)
        {

            for ($i=0; $i < count($result); $i++)
            {

                /*Check whether field is GRID type*/
                if($result[$i]['field_type'] == "grid")
                {

                    /*Relationships inside GRID*/
                    $q2 = ee()->seModel->relationshipsInsideGrid($result[$i]['field_id']);

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
                    $q2 = ee()->seModel->relationshipsInsideMatrix($result[$i]['field_id']);
                    
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
                        $fluideResults = ee()->seModel->getAllChannelFields($channelID, $result[$i]['field_settings']['field_channel_fields']);
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
                                    $q2 = ee()->seModel->relationshipsInsideGrid($fluideResults[$j]['field_id']);

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
                                    $q2 = ee()->seModel->relationshipsInsideMatrix($fluideResults[$j]['field_id']);
                                    
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
        
        $temp                                   = ee()->seModel->getGeneralSettings();
        $this->exportData['generalSettings']    = $temp['settings'];
        
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
            $this->exportData['customFields'] = ee()->seModel->getCustomFieldNames($this->exportData['exportSettings']['settings']['custom_fields']);
            if($this->exportData['customFields'] !== false)
            {
                $this->exportData['exportQuery'] .= $this->exportData['customFields']['select'];
                unset($this->exportData['customFields']['select']);
                $this->exportData['customFields'] = $this->exportData['customFields']['data'];
            }
        }
        
        /* Get all file directories to convert {filedir_x} to given file URL */
        $this->exportData['parseFiles'] = ee()->seModel->getFieldDirectories();

        /* Find total rows of particular channel only if triggered by AJAX */
        if($this->type == "ajax")
        {
            $this->exportData['totalRows']  = ee()->seModel->getChannelData($this->exportData, true);
        }

        $this->exportData['data']       = ee()->seModel->getChannelData($this->exportData);
        
        $pages                          = ee()->seModel->checkModuleInstalled('Pages');
        $siteID                         = ee()->seModel->getSiteID($this->exportData['exportSettings']['settings']['channel_id']);

        /* Get all pages (custom URIs defined in pages module) */
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['pages']) && $this->exportData['exportSettings']['settings']['general_settings']['pages'] == "yes" && $pages === true)
        {
            $this->exportData['pagesData'] = ee()->seModel->getSitePages($siteID);
            $this->exportData['pagesData'] = $this->exportData['pagesData'][1];
        }

        /* Get all category groups of given channel */
        if(isset($this->exportData['exportSettings']['settings']['general_settings']['categories']) && $this->exportData['exportSettings']['settings']['general_settings']['categories'] == "yes")
        {

            $this->exportData['categoryGroups']   = ee()->seModel->getCategoryGroups($this->exportData['exportSettings']['settings']['channel_id']);
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
            $includeSeoLite = ee()->seModel->checkModuleInstalled('Seo_lite');
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
                                $ret = ee()->seModel->getAssetsData($where, $this->exportData['parseFiles']);
                                $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                            }

                        }
                        elseif($fieldType == "channel_files")
                        {
                            $ret = ee()->seModel->getChannelImagesAndFilesData($entry_id, $this->exportData['customFields'][$key], $this->exportData['parseFiles'], "channel_files");
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                        }
                        elseif($fieldType == "channel_images")
                        {
                            $ret = ee()->seModel->getChannelImagesAndFilesData($entry_id, $this->exportData['customFields'][$key], $this->exportData['parseFiles']);
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;
                        }
                        elseif($fieldType == "channel_videos")
                        {

                        }
                        elseif($fieldType == "file_grid")
                        {
                            $this->exportData['data'][$i][$key] = ee()->seModel->getChannelGridData($this->exportData['customFields'][$key], $entry_id);
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
                                                    
                                                    $ret = ee()->seModel->getAssetsData($where, $this->exportData['parseFiles']);
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
                                                $ret = ee()->seModel->getRelationshipsData($where, $fieldRet);
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

                            $this->exportData['data'][$i][$key] = ee()->seModel->getChannelGridData($this->exportData['customFields'][$key], $entry_id);
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
                                                    
                                                    $ret = ee()->seModel->getAssetsData($where, $this->exportData['parseFiles']);
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
                                                $ret = ee()->seModel->getRelationshipsData($where, $fieldRet);
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

                            $this->exportData['data'][$i][$key] = ee()->seModel->getChannelMatrixData($this->exportData['customFields'][$key], $entry_id);

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
                                                    $ret = ee()->seModel->getAssetsData($where, $this->exportData['parseFiles']);
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
                                                
                                                $ret = ee()->seModel->getPlayaData($where, $fieldRet);
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
                            $fluidData  = ee()->seModel->getFluidData($field_id, $entry_id);
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

                                            $temp = ee()->seModel->getChannelGridData($fluidSubFieldData, $entry_id, $fluidData[$j]);

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
                                                                    
                                                                    $ret = ee()->seModel->getAssetsData($where, $this->exportData['parseFiles']);
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
                                                                $ret = ee()->seModel->getRelationshipsData($where, $fieldRet, $fluidData[$j]);
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
                                            $ret = ee()->seModel->getRelationshipsData($where, $fieldRet, $fluidData[$j]);
                                            $app[] = array($fluidData[$j]['field_name'] => ($ret === false) ? "" : $ret);

                                        }
                                        else
                                        {

                                            $temp = ee()->seModel->getNormalFluidFieldData($fluidData[$j], $fluidSubFieldData);
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
                            $ret = ee()->seModel->getRelationshipsData($where, $fieldRet);
                            $this->exportData['data'][$i][$key] = ($ret === false) ? "" : $ret;

                        }
                        elseif($fieldType == "playa")
                        {

                            $where = array(
                                'parent_entry_id' => $entry_id,
                                'parent_field_id' => $this->exportData['customFields'][$key]['field_id']
                            );
                            $fieldRet = $this->exportData['exportSettings']['settings']['playa_field'][$this->exportData['customFields'][$key]['field_id']];
                            
                            $ret = ee()->seModel->getPlayaData($where, $fieldRet);
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
                        
                        $temp = ee()->seModel->getAllCategories($entry_id, $this->exportData['categoryGroups'][$j]['group_id']);
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
                    $selLiteFields = ee()->seModel->getSeoLiteData($entry_id);
                    
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

        @ob_clean();
        @ob_start();

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
                                            $value = implode(", ", $tempArray);
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
                                                $value = implode(", ", $value);
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
            header('Content-Disposition: attachment;filename=smart_export_'.$this->exportData['exportSettings']['id'].'.csv');
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

        $vars['data'] = ee()->seModel->getGeneralSettings();
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
            'base_url' => $this->url('general_settings'),
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
        ee()->seModel->saveGeneralSettings($data);
        
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
        $moduleThemeUrl     = URL_THIRD_THEMES . "smart_export/temp";
        $moduleThemePath    = PATH_THIRD_THEMES . "smart_export/temp";

        if (!is_dir($moduleThemePath)) {
            mkdir($moduleThemePath, 0777, TRUE);
        }
        
        $filename = "smart_export_".$this->exportData['exportSettings']['id'] . "." . $fileType;
        if($this->exportData['offset'] == 0){
            @unlink($moduleThemePath . "/" . $filename);
        }

        $handle = fopen($moduleThemePath . "/" . $filename, 'a') or die('Cannot open file:  '.$filename);
        fwrite($handle, $out);
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
        
        @ob_clean();
        @ob_start();

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
                                    
                                    $xml .= $tab . $tab . $tab . "<{$key}_data>" . $newline;
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

                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<{$gridKey}_data>" . $newline;
                                                            foreach ($gridValue[$j] as $key1 => $value1)
                                                            {
                                                                $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                                            }
                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "</{$gridKey}_data>" . $newline;

                                                        }
                                                        else
                                                        {
                                                            $xml .= $tab . $tab . $tab . $tab . $tab . "<{$gridKey}_data><![CDATA[" . $gridValue[$j] . "]]></{$gridKey}_data>" . $newline;
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
                                    $xml .= $tab . $tab . $tab . "</{$key}_data>" . $newline;

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
                                                            
                                                            $xml .= $tab . $tab . $tab . $tab . "<{$key1}_data>" . $newline;
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

                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<{$gridKey}_data>" . $newline;
                                                                                    foreach ($gridValue[$j] as $key2 => $value2)
                                                                                    {
                                                                                        $xml .= $tab . $tab . $tab . $tab . $tab . $tab . $tab . "<{$key2}><![CDATA[" . $value2 . "]]></{$key2}>" . $newline;
                                                                                    }
                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "</{$gridKey}_data>" . $newline;

                                                                                }
                                                                                else
                                                                                {
                                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . $tab . "<{$gridKey}_data><![CDATA[" . $gridValue[$j] . "]]></{$gridKey}_data>" . $newline;
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
                                                            $xml .= $tab . $tab . $tab . $tab . "</{$key1}_data>" . $newline;

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

                                                                $xml .= $tab . $tab . $tab . $tab . "<{$key1}_data>" . $newline;
                                                                foreach ($value1[$j] as $key2 => $value2)
                                                                {
                                                                    $xml .= $tab . $tab . $tab . $tab . $tab . "<{$key2}><![CDATA[" . $value2 . "]]></{$key2}>" . $newline;
                                                                }
                                                                $xml .= $tab . $tab . $tab . $tab . "</{$key1}_data>" . $newline;

                                                            }
                                                            else
                                                            {
                                                                $xml .= $tab . $tab . $tab . $tab . "<{$key1}_data><![CDATA[" . $value1[$j] . "]]></{$key1}_data>" . $newline;
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

                                        $xml .= $tab . $tab . $tab . "<{$key}_data>" . $newline;
                                        foreach ($value[$j] as $key1 => $value1)
                                        {
                                            $xml .= $tab . $tab . $tab . $tab . "<{$key1}><![CDATA[" . $value1 . "]]></{$key1}>" . $newline;
                                        }
                                        $xml .= $tab . $tab . $tab . "</{$key}_data>" . $newline;

                                    }
                                    else
                                    {
                                        $xml .= $tab . $tab . $tab . "<{$key}_data><![CDATA[" . $value[$j] . "]]></{$key}_data>" . $newline;
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
            header('Content-Disposition: attachment;filename=smart_export_'.$this->exportData['exportSettings']['id'].'.xml');
            header('Content-Transfer-Encoding: binary');
            echo $xml;
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
            $data['settings']['filters']['start_date'] = ee()->localize->string_to_timestamp(str_replace("/", "-", $data['settings']['filters']['start_date']));
        }
        if(isset($data['settings']['filters']['end_date']))
        {
            $data['settings']['filters']['end_date'] = ee()->localize->string_to_timestamp(str_replace("/", "-", $data['settings']['filters']['end_date']));
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
            
            ee()->seModel->saveExport($data);
            ee()->session->set_flashdata('return_id', $data['token']);
            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_saved_successfully'))->defer();
        }
        else
        {
            /* Update existing entry */
            $data['last_modified']  = ee()->localize->now;
            ee()->seModel->updateExport($data, $token);
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

        /*$vars['export_form'] = ee('CP/URL')->make('addons/settings/smart_export/export_form');*/
        $vars['export_form'] = ee()->se->url('export_form');
        $vars['delete_export'] = ee()->se->url('delete_export');
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
        $total          = ee()->seModel->getExportList("", $this->group_id, $perPage);
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $perPage; // Offset is 0 indexed

        $vars['export_list']    = ee()->seModel->getExportList($offset, $this->group_id, $perPage);
        $vars['method']         = "smart_export";
        $vars['title']          = lang('export_list');

        $fieldData = array();
        if(isset($vars['export_list']) && is_array($vars['export_list']) && $vars['export_list'] > 0)
        {

            $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perPage)
            ->currentPage($currentpage)
            ->render(ee()->se->url());

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
                            'href'      => ee()->se->url('export_form', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('edit'))
                        ),
                        'download' => array(
                            'href'      => ee()->se->url('download_export', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('download')),
                            'class'     => "download-export $subClass"
                        ),
                        'rte-link' => array(
                            'href'     => 'javascript:void(0);',
                            'title'     => strtolower(lang('url')),
                            'class'     => 'passkey',
                            'copy-link'      => ee()->functions->create_url("?ACT=".ee()->seModel->getActionID("se_export").AMP.'token='.$vars['export_list'][$i]['token'] . (($procedure == "ajax") ? AMP . 'type=ajax': '')),
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

        $vars['table'] = $table->viewData(ee()->se->url());
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
                $message .= "<li><p><pre>".$value."</p></li>";
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

}