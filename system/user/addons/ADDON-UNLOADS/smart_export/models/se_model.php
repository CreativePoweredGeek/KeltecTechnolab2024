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
* ZealousWeb - Smart Export
*
* @package      SmartExport
* @author       Mufi
* @copyright    Copyright (c) 2016, ZealousWeb.
* @link         http://zealousweb.com/expressionengine/smart-export
* @filesource   ./system/expressionengine/third_party/smart_export/mod.smart_export.php
*
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Se_model extends CI_Model
{

	/* Important globel variables */ 
	public $site_id;

    /*Default constructor*/
	function __construct()
	{
		/*define site id*/
		$this->site_id = $this->config->item("site_id");

	}

    /**
    * Fetch all channels inside EE to show up in LIST
    * 
    * @access public
    * @param $site_id [string](Show fields of current site only)
    * @return $result [Array](Final Array of channel list)
    **/
	function getAllChannels($site_id)
	{

		$this->db->select('channel_id,channel_name,channel_title');
        $this->db->from('channels');
        $this->db->where('site_id',$site_id);
        $get = $this->db->get();

        if($get->num_rows == 0)
        {
            return FALSE;
        }
        
        return $get->result_array();

	}

    /**
    * Fetch all Common statuses
    * 
    * @access public
    * @param $site_id [string](fetch statuses only of current site only)
    * @return $result [Array](Final Array of status list)
    **/
	function getCommonStatuses($site_id, $group_id = "")
	{

        $this->db->order_by('group_id','asc');
		$this->db->select('s.group_id, s.status, sg.group_name');
        $this->db->from('statuses s');
        $this->db->join('status_groups sg', 's.group_id = sg.group_id');
        $this->db->where('s.site_id',$site_id);

        if($group_id != "")
        {
            $this->db->where('s.group_id',$group_id);
        }

        $get = $this->db->get();

        if($get->num_rows == 0)
        {
            return FALSE;
        }
        
        $temp = array();
        foreach ($get->result_array() as $key => $value)
        {
            $temp[$value['group_name']][] = $value;
        }

        return $temp;

	}

    function getStatusesFromChannelID($channelID)
    {

        $this->db->order_by('status_order', "ASC");
        $this->db->select('s.status_id, s.status');
        $this->db->from('statuses s');
        $this->db->join('channels_statuses cs', 's.status_id = cs.status_id');
        $this->db->where('cs.channel_id', $channelID);
        $get = $this->db->get();
        if($get->num_rows == 0){
            return false;
        }

        return $get->result_array();
        
    }

    /**
    * Fetch channel field group ID
    * 
    * @access public
    * @param $channel_id [string](Fetch channel field group of particular channel)
    * @return $result [String](string of field group)
    **/
	function getGroupIdFromChannelId($channel_id)
	{

		$this->db->select('field_group');
        $this->db->from('channels');
        $this->db->where('channel_id',$channel_id);
        $get = $this->db->get();

        if($get->num_rows == 0)
        {
            return FALSE;
        }
        if($get->row("field_group") != ""){
            return $get->row("field_group");
        }else{
            return FALSE;
        }

	}

    /**
    * Fetch all Chnanel field of particular Channel
    * 
    * @access public
    * @param $where [Array](Conditional array to filter Query)
    * @return $result [Array](Final Array of channel fields)
    **/
	function getChannelFields($where = array())
	{

		$this->db->select('field_id, field_name, field_label, field_type');
        $this->db->from('channel_fields');
        $this->db->where('site_id',$this->site_id);
        	
        if(count($where) != 0)
        {
        	$this->db->where($where);
        }

        $get = $this->db->get();
        
        if($get->num_rows == 0)
        {
            return false;
        }

        return $get->result_array();

	}

    function getAllChannelFields($channelID, $fieldIDs = array())
    {

        $this->db->select('cf.field_id, cf.field_name, cf.field_label, cf.field_type, cf.field_settings');
        $this->db->from('channel_fields cf');
        $this->db->join('channels_channel_fields ccf', 'ccf.field_id = cf.field_id');
        if(count($fieldIDs) != 0){
            $this->db->where_in('ccf.field_id', $fieldIDs);
        }else{
            $this->db->where('ccf.channel_id', $channelID);
        }

        $get = $this->db->get();
        $individualFields = array();
        if($get->num_rows != 0){
            $individualFields = $get->result_array();
        }

        $this->db->group_by('field_id');
        $this->db->select('cf.field_id, cf.field_name, cf.field_label, cf.field_type, cf.field_settings');
        $this->db->from('channel_field_groups_fields cfgf');
        $this->db->join('channel_fields cf', 'cf.field_id = cfgf.field_id');
        $this->db->join('channels_channel_field_groups ccfg', 'ccfg.group_id = cfgf.group_id');
        
        if(count($fieldIDs) != 0){
            $this->db->where_in('cfgf.field_id', $fieldIDs);
        }else{
            $this->db->where('ccfg.channel_id', $channelID);
        }

        $get = $this->db->get();

        $groupFields = array();
        if($get->num_rows != 0){
            $groupFields = $get->result_array();
        }

        foreach ($groupFields as $key => $value)
        {
            
            foreach ($individualFields as $key1 => $value1)
            {
                if($value['field_id'] == $value1['field_id'])
                {
                    unset($individualFields[$key1]);
                    break;
                }
            }

        }

        return array_merge($groupFields, $individualFields);

    }

    /**
    * Fetch relationships inside GRID
    * 
    * @access public
    * @param $field_id [string](Id of particular Field)
    * @return $result [Array](Final Array of GRID column data)
    **/
	function relationshipsInsideGrid($field_id)
	{

		$this->db->select('col_id, col_label, col_name, col_type');
        $this->db->from('grid_columns');
        $this->db->where('field_id', $field_id);
        $this->db->where('col_type', 'relationship');

        $get = $this->db->get();
        
        if($get->num_rows == 0)
        {
            return false;
        }

        return $get->result_array();

	}

    /**
    * Fetch relationships inside Matrix
    * 
    * @access public
    * @param $field_id [string](Id of particular Field)
    * @return $result [Array](Final Array of Matrix column data)
    **/
    function relationshipsInsideMatrix($field_id)
    {

        $this->db->select('col_id, col_label, col_name, col_type');
        $this->db->from('matrix_cols');
        $this->db->where('field_id', $field_id);
        $this->db->where('col_type', 'playa');

        $get = $this->db->get();
        
        if($get->num_rows == 0)
        {
            return false;
        }

        return $get->result_array();

    }
    /**
    * File uploads directories
    * 
    * @access public
    * @return $result [Array](Final Array of File upload directories)
    **/
	function getFieldDirectories()
	{

		$this->db->select('id, url');
        $this->db->from('upload_prefs');
        $this->db->where('site_id',$this->site_id);

        $get = $this->db->get();
        
        if($get->num_rows == 0)
        {
            return false;
        }

        $temp = $get->result_array();
        $ret = array();
        
        for ($i = 0; $i < count($temp); $i++)
        {
            $ret[$temp[$i]['id']] = $temp[$i]['url'];
        }

        return $ret;

	}

    /**
    * Fetch Categoris of particular Entry
    * 
    * @access public
    * @param $entry_id [string](Entry ID)
    * @return $result [Array](Final Array of cateogries)
    **/
	function getAllCategories($entry_id, $group_id)
	{

		$this->db->select('c.cat_id, c.cat_name, c.parent_id');
        $this->db->from('categories c');
        $this->db->join('category_posts cp', 'c.cat_id = cp.cat_id');

        $this->db->where('cp.entry_id', $entry_id);
        $this->db->where('c.group_id', $group_id);

        $get = $this->db->get();
        if($get->num_rows == 0)
        {
            return false;
        }

        $result = $get->result_array();
        $ret = array();
        for ($i = 0; $i < count($result); $i++)
        {
            $ret[$result[$i]['cat_id']] = $result[$i];
        }
        return $ret;
	}

    /**
    * Fetch Status GROUP from Channel ID
    * 
    * @access public
    * @param $channel_id [Channel ID)
    * @return $result [Array](Final Array of data)
    **/
    function getStatusesGroupFromChannelID($channel_id)
    {

        $this->db->select('status_group');
        $this->db->from('channels');
        $this->db->where('channel_id',$channel_id);
        $get = $this->db->get();

        if($get->num_rows == 0)
        {
            return FALSE;
        }
        
        return $get->row("status_group");

    }

    /**
    * Fetch all table fields of channel_titles table
    * 
    * @access public
    * @return $result [Array](Final Array of data)
    **/
    function getDefaultFields()
    {
        return $this->db->list_fields('channel_titles');
    }

    /**
    * Get list of generated exports
    * @param $offset     (Number of pagination row, Offset of table data)
    * @param $group_id   (Group ID of member)
    * @param $perPage (limit of table data per page)
    **/
    function getExportList($offset, $group_id, $perPage)
    {

        $this->db->select('*');
        // $this->db->from('smart_exports');
        $this->db->where('status', 'active');

        if($group_id != 1)
        {
            $this->db->where('type', 'public');
            $this->db->or_where('member_id', ee()->session->userdata('member_id'));
        }

        
        if($offset === "")
        {
            return $this->db->get('smart_exports')->num_rows;
        }
        else
        {
            
            $data = $this->db->get('smart_exports', $perPage, $offset);
            
            if($data->num_rows > 0)
            {
                return $data->result_array();
            }
            else
            {
                return false;
            }
            
        }

    }

    /**
    * Check the requested token is exists in database or not
    * @param $token     (Token of export row)
    **/
    function checkExportToken($token)
    {

        $this->db->select('*');
        $this->db->from('smart_exports');
        $this->db->where('token', $token);

        $data = $this->db->get();
        if($data->num_rows > 0)
        {
            return $data->result_array();
        }
        else
        {
            return false;
        }

    }

    /**
    * Increase counter of export on every download
    * @param $token (Token of export row)
    **/
    function increaseCounter($token)
    {
        $this->db->where('token', $token);
        $this->db->set('export_counts', '`export_counts` + 1', FALSE);
        $this->db->update('smart_exports');
    }

    /**
    * Delete the export by token
    * @param $token (Token of export row)
    **/
    function deleteExport($removeIds)
    {
        $this->db->where_in('id', $removeIds);
        $this->db->delete('smart_exports');
    }

    /**
    * Get action ID from method
    * @param $method (To find the action ID of perticular method)
    * @return Action ID
    **/
    function getActionID($method)
    {

        $this->db->limit(1);
        $this->db->select('action_id');
        $this->db->from('actions');
        $this->db->where('method', $method);
        
        return $this->db->get()->row("action_id");
        
    }

    /**
    * Check that given module installed in EE or not.
    * @param $module_name (module to check)
    * @return true or false
    **/
    function checkModuleInstalled($module_name)
    {

        $this->db->select('module_id');
        $this->db->from('modules');
        $this->db->where('module_name', $module_name);

        if($this->db->get()->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }

    }

    /**
    * Check that given Channel is exists or not
    * @param $channel_id (Channel to check)
    * @return true or false
    **/
    function validateChannel($channel_id)
    {

        $this->db->select('channel_id');
        $this->db->from('channels');
        $this->db->where('channel_id', $channel_id);

        if($this->db->get()->num_rows > 0)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    /**
    * Save export form settings
    * @param $data (Array of export settings)
    **/
    function saveExport($data)
    {
        $this->db->insert('smart_exports', $data);
    }

    /**
    * Update export form settings
    * @param $data (Array of export settings)
    **/
    function updateExport($data, $token)
    {
        $this->db->where('token', $token);
        $this->db->update('smart_exports', $data);
    }

    /**
    * Generate custom field query names to export them by name instead of IDs)
    * @param $customFieldArray (Array of custom fields)
    * @return Array (Select query and all data in proper array format)
    **/
    function getCustomFieldNames($customFieldArray)
    {

        $this->db->select('field_id, field_name, field_type, field_label, field_settings, legacy_field_data');
        $this->db->from('channel_fields');
        $this->db->where_in('field_id', $customFieldArray);
        $get = $this->db->get();
        if($get->num_rows == 0) { return false;}

        $temp = $get->result_array();
        $finalResults = array();
        $select = "";
        for ($i = 0; $i < count($temp); $i++)
        {
            if($temp[$i]['legacy_field_data'] == "y")
            {
                $select .= "cd" . ".field_id_" . $temp[$i]['field_id'] . " AS '" . $temp[$i]['field_name'] . "', ";
            }
            else
            {
                $select .= "cd" . $temp[$i]['field_id'] . ".field_id_" . $temp[$i]['field_id'] . " AS '" . $temp[$i]['field_name'] . "', ";
            }
            $temp[$i]['field_settings'] = unserialize(base64_decode($temp[$i]['field_settings']));
            
            if($temp[$i]['field_type'] == "grid") {
                $temp[$i]['gridFields'] = $this->gridFieldColumns($temp[$i]['field_id']);
            }elseif($temp[$i]['field_type'] == "file_grid") {
                $temp[$i]['gridFields'] = $this->gridFieldColumns($temp[$i]['field_id']);
            }elseif($temp[$i]['field_type'] == "matrix"){
                $temp[$i]['matrixFields'] = $this->matrixFieldColumns($temp[$i]['field_id']);
            }elseif($temp[$i]['field_type'] == "fluid_field"){
                if(isset($temp[$i]['field_settings']['field_channel_fields']) && is_array($temp[$i]['field_settings']['field_channel_fields']) && count($temp[$i]['field_settings']['field_channel_fields']) > 0)
                {
                    $temp[$i]['fluidFields'] = $this->getCustomFieldNames($temp[$i]['field_settings']['field_channel_fields']);
                }
                else
                {
                    $temp[$i]['fluidFields'] = "";
                }
            }

            $finalResults[$temp[$i]['field_name']] = $temp[$i];

        }
        $select = rtrim($select, ", ");
        unset($temp);

        return array('select' => $select, 'data' => $finalResults);

    }

    /**
    * Get channel data by given query
    * @param $exportData (Array of All settings)
    * @param $countOnly (if true, we will return total rows exists in channel instead of all channel data)
    * @return Channel data array OR Count of total entries in given channel
    **/
    function getChannelData($exportData, $countOnly = false)
    {

        /*if(isset($exportData['exportSettings']['settings']['general_settings']))
        {

            $generalFields = $exportData['exportSettings']['settings']['general_settings'];
            
            $seoLite = $this->checkModuleInstalled('Seo_lite');
            if(isset($generalFields['seo_lite']) && $generalFields['seo_lite'] == "yes" && $seoLite === true)
            {
                $exportData['exportQuery'] .= ", slc.title AS 'seo_lite_title', slc.keywords AS 'seo_lite_keywords', slc.description AS 'seo_lite_description'";
            }

        }*/

        if($countOnly === false && $exportData['exportSettings']['settings']['procedure'] == "ajax" && isset($exportData['limit']))
        {
            $this->db->limit($exportData['limit'], $exportData['offset']);
        }

        $this->db->select('ct.entry_id AS ne_entry_id, '. $exportData['exportQuery']);
        $this->db->from('channel_titles ct');
        
        /*if(isset($exportData['exportSettings']['settings']['general_settings']))
        {
            
            $generalFields = $exportData['exportSettings']['settings']['general_settings'];
            
            if(isset($generalFields['seo_lite']) && $generalFields['seo_lite'] == "yes" && $seoLite === true)
            {
                $this->db->join('seolite_content slc', 'entry_id = slc.entry_id');
            }

        }*/
        
        /*$temp = array();
        if(isset($exportData['exportSettings']['settings']['custom_fields']))
        {
            $temp = $exportData['exportSettings']['settings']['custom_fields'];
        }
        
        if(is_array($temp) && count($temp) > 0)
        {

            $this->db->join("channel_data cd", "cd.entry_id = ct.entry_id", "left");
            for ($i = 0; $i < count($temp); $i++)
            {
                $this->db->join("channel_data_field_{$temp[$i]} cd{$temp[$i]}", "cd$temp[$i].entry_id = ct.entry_id", "left");
            }
            
        }*/

        if(isset($exportData['customFields']) && is_array($exportData['customFields']) && count($exportData['customFields']) > 0)
        {
            
            $this->db->join("channel_data cd", "cd.entry_id = ct.entry_id", "left");
            foreach ($exportData['customFields'] as $key => $value)
            {
                if($value['legacy_field_data'] == "n")
                {
                    $this->db->join("channel_data_field_{$value['field_id']} cd{$value['field_id']}", "cd" . $value['field_id'] . ".entry_id = ct.entry_id", "left");
                }
            }
        }

        $this->db->where('ct.channel_id', $exportData['exportSettings']['settings']['channel_id']);
        
        if(isset($_POST['status']))
        {
            if(strtolower($_POST['status']) != "all")
            {
                $this->db->where('ct.status', $_POST['status']);
            }
        }
        elseif(isset($exportData['exportSettings']['settings']['status']) && strtolower($exportData['exportSettings']['settings']['status']) != "all")
        {
            $this->db->where('ct.status', $exportData['exportSettings']['settings']['status']);
        }

        if(isset($exportData['exportSettings']['settings']['filters']['date']) && $exportData['exportSettings']['settings']['filters']['date'] == "y")
        {
            if(isset($_POST['start_date']) && $_POST['start_date'] != "" && $this->isValidTimeStamp($_POST['start_date']))
            {
                $this->db->where('ct.entry_date >=', $_POST['start_date']);
            }
            elseif(isset($exportData['exportSettings']['settings']['filters']['start_date']) && $exportData['exportSettings']['settings']['filters']['start_date'] != "" && $this->isValidTimeStamp($exportData['exportSettings']['settings']['filters']['start_date']) )
            {
                $this->db->where('ct.entry_date >=', $exportData['exportSettings']['settings']['filters']['start_date']);
            }

            if(isset($_POST['end_date']) && $_POST['end_date'] != "" && $this->isValidTimeStamp($_POST['end_date']))
            {
                $this->db->where('ct.entry_date <=', $_POST['end_date']);
            }
            elseif(isset($exportData['exportSettings']['settings']['filters']['end_date']) && $exportData['exportSettings']['settings']['filters']['end_date'] != "" && $this->isValidTimeStamp($exportData['exportSettings']['settings']['filters']['end_date']) )
            {
                $this->db->where('ct.entry_date <=', $exportData['exportSettings']['settings']['filters']['end_date']);
            }
        }

        if(isset($_POST['entry_id']) && $_POST['entry_id'] != "")
        {
            if (preg_match('/not (.*)/', $_POST['entry_id'], $match))
            {
                $this->db->where_not_in('ct.entry_id', explode('|', $match[1]));
            }
            else
            {
                $this->db->where_in('ct.entry_id', explode('|', $_POST['entry_id']));
            }
        }

        $get = $this->db->get();
        if($countOnly !== false)
        {
            return $get->num_rows;
        }

        if($get->num_rows == 0) { return false;}
        return $get->result_array();

    }

    function isValidTimeStamp($strTimestamp)
    {
        return ((string) (int) $strTimestamp === (string) $strTimestamp)
        && ($strTimestamp = ~PHP_INT_MAX);
    }
    /**
    * Get channel data by given query of Fluide field
    * @return Channel data array OR Count of total entries in given channel
    **/
    function getNormalFluidFieldData($fluidData, $fluidSubFieldData)
    {

        ee()->db->select("field_id_{$fluidData['field_id']} as '{$fluidData['field_name']}'");
        ee()->db->from("channel_data_field_{$fluidData['field_id']}");
        ee()->db->where('id', $fluidData['field_data_id']);
        $get = ee()->db->get();
        if($get->num_rows == 0)
        {
            return false;
        }
        elseif($get->num_rows == 1)
        {
            $ret = $get->result_array();
            return $ret[0];
        }
        else
        {
            return $get->result_array();
        }

    }

    /**
    * Get SEO LITE module data
    * @param $entry_id (ID of Entry to fetch the SEO content of)
    * @return Array of SEO data or false if not found any data for given entry.
    **/
    function getSeoLiteData($entry_id)
    {

        $this->db->select("title AS 'seo_lite_title', keywords AS 'seo_lite_keywords', description AS 'seo_lite_description'");
        $this->db->from('seolite_content');
        $this->db->where('entry_id', $entry_id);
        $get = $this->db->get();
        if($get->num_rows == 0){
            return false;
        }else{
            $result = $get->result_array();
            return $result[0];
        }

    }
    
    /**
    * Get Images data of assets field type for given field ID
    * @param $where (Field ID condition with matrix or grid columns)
    * @param $parseFiles (parsing array to Parse EE {filedir_X} tag)
    * @return Array of parsed files
    **/
    function getAssetsData($where, $parseFiles)
    {

        $this->db->select('source.settings, afiles.source_id, afiles.filedir_id, afolders.full_path, afiles.file_name');
        $this->db->from('assets_files afiles');
        $this->db->join('assets_selections aselections', 'aselections.file_id = afiles.file_id');
        $this->db->join('assets_folders afolders', 'afolders.folder_id = afiles.folder_id');
        $this->db->join('assets_sources source', 'source.source_id = afiles.source_id', "left");
        $this->db->where($where);
        $get = $this->db->get();
        if($get->num_rows == 0){return false;}

        $temp = $get->result_array();
        $ret = array();
        for ($i = 0; $i < count($temp); $i++)
        {

            if(isset($parseFiles[$temp[$i]['filedir_id']]))
            {
                $ret[] = $parseFiles[$temp[$i]['filedir_id']] . $temp[$i]['full_path'] . $temp[$i]['file_name'];
            }
            elseif(isset($temp[$i]['settings']) && $temp[$i]['settings'] != "")
            {
                $temp[$i]['settings'] = json_decode($temp[$i]['settings'], true);
                if(isset($temp[$i]['settings']['url_prefix']))
                {
                    $ret[] = $temp[$i]['settings']['url_prefix'] . $temp[$i]['full_path'] . $temp[$i]['file_name'];
                }
            }
            
        }
        
        unset($temp);
        if(count($ret) > 0){
            return $ret;
        } else {
            return false;
        }
        
    }

    /**
    * Get All files saved in channel images and channel files fieldTypes
    * @param $entry_id (Entry ID of given channel to get data of that entry)
    * @param $fieldData (Array of general settings)
    * @param $parseFiles (parsing array to Parse EE {filedir_X} tag)
    * @param $type (FieldType name [Either channel images or channel files])
    * @return array of files
    **/
    function getChannelImagesAndFilesData($entry_id, $fieldData, $parseFiles, $type="channel_images")
    {
        
        $select = "";
        if(isset($fieldData['field_settings'][$type]['columns']) && is_array($fieldData['field_settings'][$type]['columns']))
        foreach ($fieldData['field_settings'][$type]['columns'] as $key => $value) {
            if($value != "" && $key != "row_num" && $key != "id" && $key != "image"){
                if($key == "desc") { $key = "description"; }
                $select .= $key . ", ";
            }
        }
        $select = rtrim($select, ", ");
        $this->db->select('filename, ' . $select);
        $this->db->from($type);
        $this->db->where(array(
            'entry_id' => $entry_id,
            'field_id' => $fieldData['field_id']
        ));

        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        $ret = $get->result_array();
        if(isset($fieldData['field_settings'][$type]['locations']['local']['location']) && $fieldData['field_settings'][$type]['locations']['local']['location'] != "")
        {
            $directory = $fieldData['field_settings'][$type]['locations']['local']['location'];
            for ($i = 0; $i < count($ret); $i++)
            {
                $ret[$i]['filename'] = $parseFiles[$directory] . $entry_id . "/" .$ret[$i]['filename'];
            }
        }

        return $ret;

    }

    /**
    * Get field columns of GRID field type
    * @param $field_id (Field ID of given channel to get data of that field)
    * @return Array (Select query and all data in proper array format)
    **/
    function gridFieldColumns($field_id)
    {

        $this->db->select('col_id, col_type, col_name, col_label, col_settings');
        $this->db->from('grid_columns');
        $this->db->where('field_id', $field_id);

        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        $temp = $get->result_array();
        $select = "";
        for ($i = 0; $i < count($temp); $i++)
        {
            $select .= "col_id_" . $temp[$i]['col_id'] . " AS '" . $temp[$i]['col_name'] . "', ";
            $temp[$i]['col_settings'] = json_decode($temp[$i]['col_settings'], true);
            $ret[$temp[$i]['col_name']] = $temp[$i];
        }
        
        return array('select' => $select, 'data' => $ret);

    }

    /**
    * Get field columns of Matrix field type
    * @param $field_id (Field ID of given channel to get data of that field)
    * @return Array (Select query and all data in proper array format)
    **/
    function matrixFieldColumns($field_id)
    {

        $this->db->select('col_id, col_type, col_name, col_label, col_settings');
        $this->db->from('matrix_cols');
        $this->db->where('field_id', $field_id);

        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        $temp = $get->result_array();
        $select = "";
        for ($i = 0; $i < count($temp); $i++)
        {
            $select .= "col_id_" . $temp[$i]['col_id'] . " AS '" . $temp[$i]['col_name'] . "', ";
            $temp[$i]['col_settings'] = unserialize(base64_decode($temp[$i]['col_settings']));
            $ret[$temp[$i]['col_name']] = $temp[$i];
        }
        
        return array('select' => $select, 'data' => $ret);

    }

    /**
    * Get all data in GRID field of particular entry
    * @param $gridCustomFields (Select query and all data in proper array format)
    * @param $entry_id (Entry ID of given channel)
    * @return Array (Query result array)
    **/
    function getChannelGridData($gridCustomFields, $entry_id, $fluidData = array())
    {
        $this->db->order_by('row_order', 'asc');
        $this->db->select("row_id, " . $gridCustomFields['gridFields']['select']);
        $this->db->from('channel_grid_field_' . $gridCustomFields['field_id']);
        $this->db->where('entry_id', $entry_id);

        if(isset($fluidData) && is_array($fluidData) && count($fluidData) > 0)
        {
            $this->db->where('fluid_field_data_id', $fluidData['id']);
        }
        else
        {
            $this->db->where('fluid_field_data_id', "0");
        }
        
        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        return $get->result_array();

    }

    /**
    * Get all data in Matrix field of particular entry
    * @param $gridCustomFields (Select query and all data in proper array format)
    * @param $entry_id (Entry ID of given channel)
    * @return Array (Query result array)
    **/
    function getChannelMatrixData($matrixCustomFields, $entry_id)
    {

        $this->db->order_by('row_order', 'asc');
        $this->db->select("row_id, " . $matrixCustomFields['matrixFields']['select']);
        $this->db->from('matrix_data');
        $this->db->where('entry_id', $entry_id);
        
        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        return $get->result_array();

    }

    /**
    * Get Data to fill in relationships field
    * @param $where (Condition to match in terms of entry and field)
    * @param $fieldRet (what client wants to return?) [either entry_id, url_title or title]
    * @return Array (array of relationships field data)
    **/
    function getRelationshipsData($where, $fieldRet, $fluidData = array())
    {

        if($fieldRet == "entry_id"){
            $select = 're.child_id as "ret_data"';
        }elseif($fieldRet == "url_title"){
            $select = 'ct.url_title as "ret_data"';
        }elseif($fieldRet == "title"){
            $select = 'ct.title as "ret_data"';
        }

        $this->db->order_by('order', 'asc');
        $this->db->select($select);
        $this->db->from('relationships re');
        if($fieldRet != "entry_id"){
            $this->db->join('channel_titles ct', 'ct.entry_id = re.child_id');
        }
        $this->db->where($where);
        if(isset($fluidData) && is_array($fluidData) && count($fluidData) > 0)
        {
            $this->db->where('fluid_field_data_id', $fluidData['id']);
        }
        else
        {
            $this->db->where('fluid_field_data_id', "0");
        }
        
        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        $temp = $get->result_array();
        $ret = array();
        for ($i = 0; $i < count($temp); $i++)
        {
            $ret[] = $temp[$i]['ret_data'];
        }
        return $ret;

    }

    /**
    * Get Data to fill in playa field
    * @param $where (Condition to match in terms of entry and field)
    * @param $fieldRet (what client wants to return?) [either entry_id, url_title or title]
    * @return Array (array of playa field data)
    **/
    function getPlayaData($where, $fieldRet)
    {

        if($fieldRet == "entry_id"){
            $select = 'pr.child_entry_id as "ret_data"';
        }elseif($fieldRet == "url_title"){
            $select = 'ct.url_title as "ret_data"';
        }elseif($fieldRet == "title"){
            $select = 'ct.title as "ret_data"';
        }

        $this->db->order_by('rel_order', 'asc');
        $this->db->select($select);
        $this->db->from('playa_relationships pr');
        if($fieldRet != "entry_id"){
            $this->db->join('channel_titles ct', 'ct.entry_id = pr.child_entry_id');
        }
        $this->db->where($where);
        
        $get = $this->db->get();
        if($get->num_rows == 0) { return false; }

        $temp = $get->result_array();
        $ret = array();
        for ($i = 0; $i < count($temp); $i++)
        {
            $ret[] = $temp[$i]['ret_data'];
        }
        return $ret;

    }

    /**
    * Get all category groups of given channel
    * @param $channel_id (ID of channel to fetch category group by condition)
    * @param $countOnly (Check weather we want total number of category groups or actual category data]
    * @return Array (array result query)
    **/
    function getCategoryGroups($channel_id, $countOnly = false)
    {
        
        $this->db->select("cat_group");
        $this->db->from('channels');
        $this->db->where("channel_id", $channel_id);
        $get = $this->db->get();

        if($get->num_rows == 0){ return false; }

        $groups = $get->row("cat_group");
        if($groups == "" || $groups == NULL){ return false; }

        if($countOnly === true){ return true; }

        $groups = explode('|', $groups);

        if(is_array($groups) && count($groups) > 0)
        {

            $this->db->select('group_id, group_name');
            $this->db->from('category_groups');
            $this->db->where_in('group_id', $groups);
            $get = $this->db->get();

            if($get->num_rows == 0){ return false; }

            return $get->result_array();

        }
        else
        {
            return false;
        }

    }

    /**
    * Get Site ID
    * @param $channel_id (ID of channel to fetch category group by condition)
    * @return String of site ID
    **/
    function getSiteID($channelID)
    {
        $this->db->select('site_id');
        $this->db->from('channels');
        $this->db->where_in('channel_id', $channelID);
        $get = $this->db->get();
        if($get->num_rows == 0){ return false; }
        return $get->row("site_id");
    }

    /**
    * Get Site Pages (URIs saved via pages module)
    * @param $siteID (Site ID to get only those pages which we wanted)
    * @return Array of site pages URIs
    **/
    function getSitePages($siteID)
    {

        $this->db->select('site_pages');
        $this->db->from('sites');
        $this->db->where_in('site_id', $siteID);
        $get = $this->db->get();
        if($get->num_rows == 0){ return false; }
        $sitePages =  $get->row("site_pages");
        
        return unserialize(base64_decode($sitePages));

    }

    function getFluidData($field_id, $entry_id)
    {
        
        $this->db->order_by('order', "ASC");
        $this->db->select('fluid_field_data.*, channel_fields.field_name');
        $this->db->from('fluid_field_data');
        $this->db->join('channel_fields', 'fluid_field_data.field_id = channel_fields.field_id');
        $this->db->where('fluid_field_id', $field_id);
        $this->db->where('entry_id', $entry_id);
        $get = $this->db->get();
        
        if($get->num_rows == 0){ return false; }

        return $get->result_array();

    }

    function getGeneralSettings()
    {
        $data = $this->db->get('smart_exports_settings')->result_array();
        $data = $data[0];

        $data['settings'] = unserialize(base64_decode($data['settings']));
        return $data;
    }

    function saveGeneralSettings($data)
    {
        ee()->db->where('id', $data['id']);
        ee()->db->update('smart_exports_settings', $data);
    }

    function getToken($identifier, $value)
    {
        $get = ee()->db->select('token')->where($identifier, $value)->get('smart_exports');
        if($get->num_rows > 0)
        {
            return $get->row('token');
        }
        
        return "";
    }
}