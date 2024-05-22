<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use EllisLab\ExpressionEngine\Library\CP\Table;
class Sm_ie_lib
{

    /* Important globel variables */ 
    public $site_id;
    public $member_id;
    public $group_id;
    public $column_config   = array();
    public $heading         = array();
    public $rows            = array();
    public $exportData;
    public $importData;
    public $template        = NULL;
    public $footer          = array();
    public $auto_heading    = TRUE;
    public $caption         = NULL;
    public $newline         = "\n";
    public $empty_cells     = "";
    public $delim           = ",";
    public $enclosure       = '"';
    public $function        = FALSE;


    public function __construct()
    {

        /* Neeful Model classes */
        if(! class_exists('import_export_model'))
        {
           ee()->load->model('import_export_model', 'ieModel');
        }

        /* Neeful Library classes */
        if(! class_exists('social_login_lib'))
        {
           ee()->load->library('social_login_lib', null, 'sl');
        }

        /*Logged in member ID and group ID and site ID*/
        $this->site_id      = ee()->config->item("site_id");
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata('group_id');
        $this->exportData   = array();
        $this->importData   = array();

    }

    function createExportTable($vars, $perPage)
    {

        // ee()->load->library('pagination');

        /* remove confirm popup*/
        ee()->javascript->set_global('lang.remove_confirm', lang('export_list') . ': <b>### ' . lang('export_list') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        $vars['callButton']     = ee()->sm->url('export_form');
        $vars['popupURL']       = ee()->sm->url('export_members');

        /* Make table for displaying export listing */
        $table = ee('CP/Table', array(
            'sortable'  => false,
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
            $vars['callButton']
        );

        /*Default Settings*/
        $total          = ee()->ieModel->getExportList("", $this->group_id, $perPage);
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $perPage; // Offset is 0 indexed

        $vars['export_list']    = ee()->ieModel->getExportList($offset, $this->group_id, $perPage);

        $fieldData = array();
        if(isset($vars['export_list']) && is_array($vars['export_list']) && $vars['export_list'] > 0)
        {

            $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perPage)
            ->currentPage($currentpage)
            ->render(ee()->sm->url('export_members'));

            for ($i=0; $i < count($vars['export_list']); $i++)
            { 

                $vars['export_list'][$i]['settings'] = unserialize(base64_decode($vars['export_list'][$i]['settings']));
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
                            'href'      => ee()->sm->url('export_form', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('edit'))
                        ),
                        'download' => array(
                            'href'      => ee()->sm->url('download_export', array('token' => $vars['export_list'][$i]['token'])),
                            'title'     => strtolower(lang('download')),
                            'class'     => "download-export"
                        ),
                        'rte-link' => array(
                            'href'     => 'javascript:void(0);',
                            'title'     => strtolower(lang('url')),
                            'class'     => 'passkey',
                            'copy-link'      => ee()->functions->create_url("?ACT=".ee()->ieModel->getActionID("sm_export").AMP.'token='.$vars['export_list'][$i]['token']),
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

        $vars['table'] = $table->viewData(ee()->sm->url('export_members'));
        return $vars;

    }

    function createImportListTable($vars, $perPage)
    {

        ee()->load->library('pagination');

        /* remove confirm popup*/
        ee()->javascript->set_global('lang.remove_confirm', lang('import_list') . ': <b>### ' . lang('import_list') . '</b>');
        ee()->cp->add_js_script('file', 'cp/confirm_remove');

        $vars['callButton'] = ee()->sm->url('import_form');
        $vars['popupURL']   = ee()->sm->url('import_members');

        /* Make table for displaying export listing */
        $table = ee('CP/Table', array(
            'sortable'  => false,
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
            sprintf(lang('no_found'), lang('imports')),
            'create_new',
            $vars['callButton']
        );

        /*Default Settings*/
        $total          = ee()->ieModel->getImportList("", $this->group_id, $perPage);
        $currentpage    = ((int) ee()->input->get('page')) ?: 1;
        $offset         = ($currentpage - 1) * $perPage; // Offset is 0 indexed

        $vars['import_list']    = ee()->ieModel->getImportList($offset, $this->group_id, $perPage);
        $fieldData = array();
        if(isset($vars['import_list']) && is_array($vars['import_list']) && $vars['import_list'] > 0)
        {

            $vars['pagination'] = ee('CP/Pagination', $total)
            ->perPage($perPage)
            ->currentPage($currentpage)
            ->render(ee()->sm->url('export_members'));

            for ($i=0; $i < count($vars['import_list']); $i++)
            { 

                // $vars['import_list'][$i]['settings'] = unserialize(base64_decode($vars['import_list'][$i]['settings']));
                $columns = array(
                    'id'            => $vars['import_list'][$i]['id'],
                    'member_id'     => $vars['import_list'][$i]['member_id'],
                    'name'          => $vars['import_list'][$i]['name'],
                    'created_date'  => date('m/d/Y', $vars['import_list'][$i]['created_date']),
                    'last_modified' => date('m/d/Y', $vars['import_list'][$i]['last_modified']),
                    'type'          => $vars['import_list'][$i]['type'],
                    'format'        => $vars['import_list'][$i]['format'],
                    array('toolbar_items' => array(
                        'edit' => array(
                            'href'      => ee()->sm->url('choose_member_fields', array('token' => $vars['import_list'][$i]['token'])),
                            'title'     => strtolower(lang('edit'))
                        ),
                        'settings' => array(
                            'href'      => ee()->sm->url('import_form', array('token' => $vars['import_list'][$i]['token'])),
                            'title'     => strtolower(lang('settings'))
                        ),
                        'upload' => array(
                            'href'      => ee()->sm->url('run_import', array('token' => $vars['import_list'][$i]['token'])),
                            'title'     => strtolower(lang('run_import')),
                        ),
                        'rte-link' => array(
                            'href'      => 'javascript:void(0);',
                            'title'     => strtolower(lang('url')),
                            'class'     => 'passkey',
                            'copy-link' => ee()->functions->create_url("?ACT=".ee()->ieModel->getActionID("sm_import").AMP.'token='.$vars['import_list'][$i]['token']),
                        ),
                    )),
                    array(
                        'name'  => 'selection[]',
                        'value' => $vars['import_list'][$i]['id'],
                        'data'  => array(
                            'confirm' => lang('import') . ': <b>' . htmlentities($vars['import_list'][$i]['name'], ENT_QUOTES, 'UTF-8') . '</b>'
                        )
                    )
                );
                // unset($vars['import_list'][$i]['settings']);

                $attrs = array();
                if (ee()->session->flashdata('return_id') == $vars['import_list'][$i]['token'])
                {
                    $attrs = array('class' => 'selected');
                }

                $fieldData[] = array(
                    'attrs'     => $attrs,
                    'columns'   => $columns
                );
            }

        }
        unset($vars['import_list']);
        $table->setData($fieldData);

        $vars['table'] = $table->viewData(ee()->sm->url('import_members'));
        return $vars;

    }

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
        
        $data['settings'] = base64_encode(serialize($data['settings']));

        $token = ee()->input->get_post('token', true);
        if($token == "")
        {

            $data['created_date']   = ee()->localize->now;
            $data['last_modified']  = ee()->localize->now;
            $data['member_id']      = $this->member_id;
            $data['status']         = "active";
            $data['export_counts']  = 0;
            $data['token']          = strtolower(ee()->functions->random('md5',10));
            
            ee()->ieModel->saveExport($data);
            ee()->session->set_flashdata('return_id', $data['token']);
            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_saved_successfully'))->defer();
        }
        else
        {
            /* Update existing entry */
            $data['last_modified']  = ee()->localize->now;
            ee()->ieModel->updateExport($data, $token);
            ee()->session->set_flashdata('return_id', $token);
            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_updated_successfully'))->defer();
        }

    }

    /**
    * Generate export function to be called to create an export file
    * @param $export_array          (Array of basic settings need including file name, type, path etc.)
    * @param $member_dynamic_fields (Members dynamic data)
    * @param $from                  (source (Front end or backend ))
    **/
    function generateExport($data, $from = '')
    {
        $this->exportData           = $data;
		$this->exportData['query']  = "";

        $error = true;
        if(isset($this->exportData['settings']['member_static_fields']) && is_array($this->exportData['settings']['member_static_fields']) && count($this->exportData['settings']['member_static_fields']) > 0)
        {
            $error = false;
        }

        if(isset($this->exportData['settings']['member_dynamic_fields']) && is_array($this->exportData['settings']['member_dynamic_fields']) && count($this->exportData['settings']['member_dynamic_fields']) > 0)
        {
            $this->exportData['settings']['member_dynamic_fields'] = ee()->ieModel->getMemberDynamicFields($this->exportData['settings']['member_dynamic_fields']);
            if(is_array($this->exportData['settings']['member_dynamic_fields']) && count($this->exportData['settings']['member_dynamic_fields']) > 0)
            {
                $error = false;
            }
        }

        if($error)
        {
            if($from == 'outside')
            {
                return array('error' => lang('no_custom_or_dynamic_field_selected'));
            }
            else
            {
                show_error(lang('no_custom_or_dynamic_field_selected'));
            }
        }

        /*get final array to export*/
        $this->exportData['data'] = ee('Model')->get('Member');

        if(isset($this->exportData['settings']['member_groups']) && is_array($this->exportData['settings']['member_groups']) && count($this->exportData['settings']['member_groups']) > 0)
        {
            //ee5
            if(SM_APP_VER_L6){
                $this->exportData['data']->filter('group_id', "IN", $this->exportData['settings']['member_groups']);
            }else{
                //ee6
                $this->exportData['data']->filter('role_id', "IN", $this->exportData['settings']['member_groups']);
            }
        }

        if($this->exportData['data']->count() == 0)
        {
            if($from == 'outside')
            {
                return array('error' => lang('not_found_any_member'));
            }
            else
            {
                show_error(lang('not_found_any_member'));
            }
        }

        /*Export in XML or CSV based on users selection*/
        if(strtolower($this->exportData['format']) == "xml")
        {
            $this->generateXML();
        }
        else
        {
            $this->generateCSV();
		}

    }

    function checkURL($url)
    {

    	$ch = curl_init($url);    
    	curl_setopt($ch, CURLOPT_NOBODY, true);
    	curl_exec($ch);
    	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    	if($code == 200){
    		$status = true;
    	}else{
    		$status = false;
    	}
    	curl_close($ch);
    	return $status;

    }

    /**
    * convert the file data from csv to array
    * @param $data          (Array of basic settings need including file name, type, path etc.)
    * @param $only_header   (if header need as return value or not)
    **/
    function csvToArray($data = array(), $only_header = "no")
    {

        $filename  = isset($data['file_path']) ? $data['file_path'] : "";
        $delimiter  = isset($data['delimiter']) ? $data['delimiter'] : ",";

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
        if($ext != "csv")
        {
            return FALSE;
        }

        $header = NULL;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {

            while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE)
            {

                if(!$header)
                {

                    $header = array();
                    for ($i=0; $i < count($row); $i++)
                    {
                        trim($row[$i]);
                        $header[$i] = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $row[$i])));
                    }

                    if($only_header == "yes")
                    {
                        return $header;
                    }

                }
                else
                {
                    if(count($header) == count($row))
                    {
                        $data[] = array_combine($header, $row);
                    }
                }

            }

            fclose($handle);

        }

        unset($header);
        unset($handle);
        return $data;

    }

    /**
    * convert the file data from xml to array
    * @param $data          (Array of basic settings need including file name, type, path etc.)
    * @param $only_header   (if header need as return value or not)
    **/
    function xmlToArray($data = array(), $only_header = "no")
    {

        $filename = $data['file_path'];

        /*Get extension of file*/
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext != "xml")
        {
            return FALSE;
        }
        
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
        
        /*Get data in string from file*/
        // $xml = simplexml_load_file($filename, "SimpleXMLElement", LIBXML_NOCDATA);
        $file = @file_get_contents($filename);
        if($file == "" || $file == NULL || $file == false)
        {
            return false;
        }
        
        /*Convert string data to xml*/
        $xml = simplexml_load_string($file, "SimpleXMLElement", LIBXML_NOCDATA);
        unset($file);
        
        $xml = json_encode($xml);
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

                    $ret = array();
                    foreach ($value as $key => $value)
                    {
                        $ret[] = $key;
                    }
                    
                    return $ret;

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

    /**
    * Process the import data functionality
    * @param $token  (Token to identify settings of import data from database)
    * @param $batch  (current batch to be use as offset of array to prevent dyplication of data)
    * @param $source (source of import [backend or front end])
    **/
    function processRunImport($token, $batch, $source = "inside")
    {
        $this->importData['token']  = $token;
        $this->importData['batch']  = $batch;
        $this->importData['source'] = $source;

        /*Check import token is valid or not*/
        $this->importData['data'] = ee()->ieModel->checkImportToken($this->importData['token']);

        /*show error if token is not valid*/
        if($this->importData['data'] === false)
        {
            show_error(lang('wrong_token'));
        }
        $this->importData['data'] = $this->importData['data'][0];

        /*Get all static and dynamic fields*/
        $this->importData['staticMemberFields']   = ee()->ieModel->listFields('members');
        $this->importData['dynamicMemberFields']  = ee()->ieModel->getMemberDynamicFields();
        
        /*If import is just started and batch is fresh, set session variables to store the data in session to be use to show result*/
        if($batch == 0)
        {
            $this->setSession('total_members_'.$this->importData['token'], 0);
            $this->setSession('imported_members_'.$this->importData['token'], 0);
            $this->setSession('updated_members_'.$this->importData['token'], 0);
            $this->setSession('recreated_members_'.$this->importData['token'], 0);
            $this->setSession('skipped_members_'.$this->importData['token'], 0);
            $this->setSession('total_memory_usage_'.$this->importData['token'], 0);
            $this->setSession('total_time_taken_'.$this->importData['token'], 0);
        }
        
        $this->setSession('memory_usage_'.$this->importData['token'], memory_get_usage());
        $this->setSession('time_taken_'.$this->importData['token'], time());

        /*Add field in members table to identify data to get member id of newly generated entry*/
        ee()->load->dbforge();
        if(! ee()->db->field_exists('token_id', 'members'))
        {
            /*Add fields to main setting table*/
            $fields = array(
                'token_id' => array(
                    'type'          => 'varchar',
                    'constraint'    => '100',
                    'null'          => TRUE
                    )
                );
            ee()->dbforge->add_column('members', $fields);
        }

        /*Get all possible member groups*/
        $temp = ee()->ieModel->getMemberGroups();
        for ($i=0; $i < count($temp); $i++)
        {
            $this->importData['data']['member_groups'][$temp[$i]['group_id']] = $temp[$i]['group_title'];
        }
        unset($temp);

        /*Basic settings of import*/
        $this->importData['data']['settings'] = unserialize(base64_decode($this->importData['data']['settings']));
        
        /*Covert import file data into array as per users inputed csv or xml*/
        if($this->importData['data']['format'] == "csv")
        {
            $this->importData['data']['importData'] = $this->csvToArray(array('file_path' => $this->importData['data']['settings']['filename']));
        }
        else
        {
            $this->importData['data']['importData'] = $this->xmlToArray(array('file_path' => $this->importData['data']['settings']['filename']));
        }
        
        $this->setSession('total_members_'.$this->importData['token'], count($this->importData['data']['importData']));

        $this->importData['insertArray']   = array();
        $ret            = array();

        if($batch == 0)
        {
            $ret_array  = array();
        }
        else
        {

            $ret_array  = $this->session('ret_'.$this->importData['token']);
            
            if($ret_array === false)
            {
                $ret_array  = array();
            }
            else
            {
                $ret_array  = unserialize($ret_array);
            }

        }

        /*Set batched to offset the array*/
        if($this->importData['data']['settings']['meta_settings']['batches'] == 0 || strtolower($this->importData['data']['settings']['meta_settings']['batches']) == "all")
        {
            $batch_start    = 0;
            $batch_end      = count($this->importData['data']['importData']);
            $ret['status']  = "completed";
        }
        else
        {

            $batch_start = $batch;
            $tmp = $batch_start + $this->importData['data']['settings']['meta_settings']['batches'];

            if($tmp >= count($this->importData['data']['importData']))
            {
                $batch_end      = count($this->importData['data']['importData']);
                $ret['status']  = "completed";
            }
            else
            {
                $batch_end      = $tmp;
                $ret['status']  = "pending";
            }
            
        }

        $ret['batch'] = $batch_end;
        
        for ($i = $batch_start; $i < $batch_end; $i++)
        {

            /*Check the data and settings are match*/
            if(isset($this->importData['data']['settings']['member_static_fields']) && is_array($this->importData['data']['settings']['member_static_fields']) && count($this->importData['data']['settings']['member_static_fields']) > 0)
            {
                foreach ($this->importData['data']['settings']['member_static_fields'] as $key => $value)
                {
                    if($value != "")
                    {

                        if(isset($this->importData['data']['importData'][$i][$value]))
                        {
                            
                            if($this->importData['data']['importData'][$i][$value] == "" || empty($this->importData['data']['importData'][$i][$value]))
                            {
                                $m_val = "";
                            }
                            else
                            {
                                $m_val = $this->importData['data']['importData'][$i][$value];
                            }

                        }
                        else
                        {
                            $m_val = "";
                        }

                        if(isset($this->importData['staticMemberFields'][$key]))
                        {
                            $this->importData['insertArray'][$i]['data']['members'][$key] = $m_val;
                        }

                    }
                }
            }
            if(isset($this->importData['data']['settings']['member_dynamic_fields']) && is_array($this->importData['data']['settings']['member_dynamic_fields']) && count($this->importData['data']['settings']['member_dynamic_fields']) > 0)
            {
                /*Check the data and settings are match*/
                foreach ($this->importData['data']['settings']['member_dynamic_fields'] as $key => $value)
                {

                    if($value != "")
                    {

                        if(isset($this->importData['data']['importData'][$i][$value]))
                        {
                            
                            if($this->importData['data']['importData'][$i][$value] == "" || empty($this->importData['data']['importData'][$i][$value]))
                            {
                                $m_val = "";
                            }
                            else
                            {
                                $m_val = $this->importData['data']['importData'][$i][$value];
                            }

                        }
                        else
                        {
                            $m_val = "";
                        }

                        if(isset($this->importData['dynamicMemberFields'][$key]))
                        {
                            $this->importData['insertArray'][$i]['data']['member_data'][$key] = $m_val;
                        }

                    }

                }
            }

            $this->importData['insertArray'][$i]['meta']['type'] = 'insert';
            
            if($this->importData['data']['settings']['meta_settings']['email_empty'] == "0" && ((isset($this->importData['insertArray'][$i]['data']['members']['email']) && $this->importData['insertArray'][$i]['data']['members']['email'] == "") || (! isset($this->importData['insertArray'][$i]['data']['members']['email']))))
            {
                unset($this->importData['insertArray'][$i]);
                $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
                continue;
            }

            /*Each insert array operations*/
            $flag = true;
            switch ($this->importData['data']['settings']['meta_settings']['username_empty']) 
            {

                case '0':
            	default:
                if((! isset($this->importData['insertArray'][$i]['data']['members']['username'])) || (isset($this->importData['insertArray'][$i]['data']['members']['username']) && $this->importData['insertArray'][$i]['data']['members']['username'] == ""))
                {
                    $this->importData['insertArray'][$i]['data']['members']['username'] = isset($this->importData['insertArray'][$i]['data']['members']['email']) ? $this->importData['insertArray'][$i]['data']['members']['email'] : "";
                }
        		break;

            	case '1':
                    if((! isset($this->importData['insertArray'][$i]['data']['members']['username'])) || (isset($this->importData['insertArray'][$i]['data']['members']['username']) && $this->importData['insertArray'][$i]['data']['members']['username'] == ""))
                    {
                        unset($this->importData['insertArray'][$i]);
	                    $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
	                    $flag = false;
	                }
        		break;

            }
            if($flag == false) { continue; }


            switch ($this->importData['data']['settings']['meta_settings']['screen_name_empty']) 
            {

                case '0':
                default:
                if((! isset($this->importData['insertArray'][$i]['data']['members']['screen_name'])) || (isset($this->importData['insertArray'][$i]['data']['members']['screen_name']) && $this->importData['insertArray'][$i]['data']['members']['screen_name'] == ""))
                {
                    $this->importData['insertArray'][$i]['data']['members']['screen_name'] = isset($this->importData['insertArray'][$i]['data']['members']['username']) ? $this->importData['insertArray'][$i]['data']['members']['username'] : "";
                }
                break;

                case '1':
                if((! isset($this->importData['insertArray'][$i]['data']['members']['screen_name'])) || (isset($this->importData['insertArray'][$i]['data']['members']['screen_name']) && $this->importData['insertArray'][$i]['data']['members']['screen_name'] == ""))
                {
                    $this->importData['insertArray'][$i]['data']['members']['screen_name'] = isset($this->importData['insertArray'][$i]['data']['members']['email']) ? $this->importData['insertArray'][$i]['data']['members']['email'] : "";
                }
                break;

                case '2':
                    if((! isset($this->importData['insertArray'][$i]['data']['members']['screen_name'])) || (isset($this->importData['insertArray'][$i]['data']['members']['screen_name']) && $this->importData['insertArray'][$i]['data']['members']['screen_name'] == ""))
                    {
                        unset($this->importData['insertArray'][$i]);
                        $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
                        $flag = false;
                    }
                break;

            }
            if($flag == false) { continue; }

            /*Case of same Username found*/
            if(isset($this->importData['insertArray'][$i]['data']['members']['username']) && $this->importData['insertArray'][$i]['data']['members']['username'] != "")
            {

                if(ee()->ieModel->matchKey("username", $this->importData['insertArray'][$i]['data']['members']['username']) === true)
                {

                    if($this->importData['data']['settings']['meta_settings']['same_username'] == "0")
                    {
                        unset($this->importData['insertArray'][$i]);
                        $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
                        continue;
                    }

                    switch ($this->importData['data']['settings']['meta_settings']['same_username'])
                    {

                        case '1':
                        default :
                            break;
                        
                        case '2':
                            $this->importData['insertArray'][$i]['meta']['type']   = 'update';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'username';
                            break;

                        case '3':
                            $this->importData['insertArray'][$i]['meta']['type']   = 'delete';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'username';
                            break;

                    }
                    
                }

            }

            /*Case of same email address is found*/
            if(isset($this->importData['insertArray'][$i]['data']['members']['email']) && $this->importData['insertArray'][$i]['data']['members']['email'] != "")
            {

                if(ee()->ieModel->matchKey("email", $this->importData['insertArray'][$i]['data']['members']['email']) === true)
                {

                    if($this->importData['data']['settings']['meta_settings']['same_email'] == "0")
                    {

                        unset($this->importData['insertArray'][$i]);
                        $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
                        continue;

                    }

                    switch ($this->importData['data']['settings']['meta_settings']['same_email'])
                    {

                        case '1':
                        default :
                            $this->importData['insertArray'][$i]['meta']['type']   = 'update';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'email';
                            break;

                        case '2':
                            $this->importData['insertArray'][$i]['meta']['type']   = 'delete';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'email';
                            break;
                        case '3':
                            break;

                    }
                    
                }

            }
            elseif($this->importData['data']['settings']['meta_settings']['pending_if_no_email'] == "yes")
            {
                $this->importData['insertArray'][$i]['data']['members'][(SM_APP_VER_L6) ? 'group_id' : 'role_id'] = 4;
            }

            /*Case of same member ID found */
            if(isset($this->importData['insertArray'][$i]['data']['members']['member_id']) && $this->importData['insertArray'][$i]['data']['members']['member_id'] != "" && ctype_digit($this->importData['insertArray'][$i]['data']['members']['member_id']))
            {

                if(ee()->ieModel->matchKey("member_id", $this->importData['insertArray'][$i]['data']['members']['member_id']) === true)
                {

                    if($this->importData['data']['settings']['meta_settings']['same_member_id'] == "0")
                    {
                        unset($this->importData['insertArray'][$i]);
                        $this->setSession('skipped_members_'.$token, $this->session('skipped_members_'.$token) + 1);
                        continue;
                    }

                    switch ($this->importData['data']['settings']['meta_settings']['same_member_id'])
                    {

                        case '1':
                        default :
                            unset($this->importData['insertArray'][$i]['data']['members']['member_id']);
                            unset($this->importData['insertArray'][$i]['data']['member_data']['member_id']);
                            break;
                        
                        case '2':
                            $this->importData['insertArray'][$i]['meta']['type']   = 'update';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'member_id';
                            break;

                        case '3':
                            $this->importData['insertArray'][$i]['meta']['type']   = 'delete';
                            $this->importData['insertArray'][$i]['meta']['key']    = 'member_id';
                            break;

                    }
                    
                }

            }
            else
            {
                unset($this->importData['insertArray'][$i]['data']['members']['member_id']);
            }

            if($this->importData['insertArray'][$i]['meta']['type'] == "insert")
            {
                /*Insert the new data*/
                $finalMemberID = $this->insertMember($this->importData['insertArray'][$i]);
                $this->setSession('imported_members_'.$token, $this->session('imported_members_'.$token) + 1);
            }
            elseif($this->importData['insertArray'][$i]['meta']['type'] == "update")
            {
                /*Update the existing data with current data*/
                $finalMemberID = $this->updateMember($this->importData['insertArray'][$i]);
                $this->setSession('updated_members_'.$token, $this->session('updated_members_'.$token) + 1);
            }
            elseif($this->importData['insertArray'][$i]['meta']['type'] == "delete")
            {
                /*Delete old entry and create new one*/
                $finalMemberID = $this->recreateMember($this->importData['insertArray'][$i]);
                $this->setSession('recreated_members_'.$token, $this->session('recreated_members_'.$token) + 1);
            }

            /*Set outgoing array to show user progress of import*/
            $arr = array(
                'member_id'     => $finalMemberID,
                'group_id'      => isset($this->importData['insertArray'][$i]['data']['members'][ (SM_APP_VER_L6) ? 'group_id' : 'role_id' ])      ? $this->importData['insertArray'][$i]['data']['members'][ (SM_APP_VER_L6) ? 'group_id' : 'role_id' ] : "",
                'screen_name'   => isset($this->importData['insertArray'][$i]['data']['members']['screen_name'])   ? $this->importData['insertArray'][$i]['data']['members']['screen_name'] : "",
                'username'      => isset($this->importData['insertArray'][$i]['data']['members']['username'])      ? $this->importData['insertArray'][$i]['data']['members']['username'] : "",
                'email'         => isset($this->importData['insertArray'][$i]['data']['members']['email'])         ? $this->importData['insertArray'][$i]['data']['members']['email'] : "",
                );

            /*Set profile link if import called from backend*/
            if($source == "inside")
            {

                //for ee5
                if(SM_APP_VER_L6){
                    $arr['view_profile'] = '
                    <div class="toolbar-wrap">
                        <ul class="toolbar">
                            <li class="view">
                                <a href="' . ee('CP/URL')->make('members/profile/settings&id='.$finalMemberID) . '" title="view"  target="_blank">
                                </a>
                            </li>
                        </ul>
                    </div>';
                }else{
                    //for ee6
                    $arr['view_profile'] = '<div class="toolbar-wrap">
                        <div class="button-toolbar toolbar">
                            <div class="button-group button-group-xsmall">
                                <a class="view button button--default" href="' . ee('CP/URL')->make('members/profile/settings&id='.$finalMemberID) . '"  target="_blank" title="view"><span class="hidden">view</span></a>
                            </div>
                        </div>
                    </div>';
                }

            }

            $ret_array[$this->importData['insertArray'][$i]['meta']['type']][] = $arr;

            unset($this->importData['insertArray'][$i]);

        }

        /*update session variables*/
        $memory_usage_for_this_batch = memory_get_usage() - $this->session('memory_usage_'.$token);
        $this->setSession('total_memory_usage_'.$token, $this->session('total_memory_usage_'.$token) + $memory_usage_for_this_batch);
        $this->setSession('memory_usage_'.$token, $memory_usage_for_this_batch);
        
        $time_taken_for_this_batch = time() - $this->session('time_taken_'.$token);
        $this->setSession('total_time_taken_'.$token, $this->session('total_time_taken_'.$token) + $time_taken_for_this_batch);
        $this->setSession('time_taken_'.$token, $time_taken_for_this_batch);
        
        $this->setSession('ret_'.$token, serialize($ret_array));
        
        $ret['return'] = true;
        
        return $ret;
        /*if(ee()->db->field_exists('token_id', 'members'))
        {
            ee()->dbforge->drop_column('members', 'token_id');
        }*/
    }

    /**
    * Insert new members as per array of importer
    * @param $insertRow     (data to be insert)
    * @param $meta_settings (meta settings of importer)
    **/
    function insertMember($insertRow)
    {

        /*Generate a Unique ID to identify latest entered row*/
        $insertRow['data']['members']['token_id'] = ee()->functions->random('numeric', 8);
        
        if(! (isset($insertRow['data']['members']['unique_id']) && $insertRow['data']['members']['unique_id'] != ""))
        {
            $insertRow['data']['members']['unique_id']   = ee()->functions->random('encrypt');
        }

        if(! (isset($insertRow['data']['members']['ip_address']) && $insertRow['data']['members']['ip_address'] != ""))
        {
            $insertRow['data']['members']['ip_address']  = ee()->input->ip_address();
        }
        
        if(! (isset($insertRow['data']['members']['join_date']) && $insertRow['data']['members']['join_date'] != ""))
        {
            $insertRow['data']['members']['join_date']   = ee()->localize->now;
        }
        
        if(! (isset($insertRow['data']['members']['language']) && $insertRow['data']['members']['language'] != ""))
        {
            $insertRow['data']['members']['language']    = (ee()->config->item('deft_lang')) ? ee()->config->item('deft_lang') : 'english';
        }

        if(! (isset($insertRow['data']['members']['time_format']) && $insertRow['data']['members']['time_format'] != ""))
        {
            $insertRow['data']['members']['time_format'] = (ee()->config->item('time_format')) ? ee()->config->item('time_format') : 'us';
        }
            
        if(! (isset($insertRow['data']['members']['timezone']) && $insertRow['data']['members']['timezone'] != ""))
        {
            $insertRow['data']['members']['timezone']    = (ee()->config->item('default_site_timezone') && ee()->config->item('default_site_timezone') != '') ? ee()->config->item('default_site_timezone') : ee()->config->item('server_timezone');
        }
        
        /*Case of group ID or email not defined*/
        if( ! (isset($insertRow['data']['members'][(SM_APP_VER_L6) ? 'group_id' : 'role_id']) && $insertRow['data']['members'][(SM_APP_VER_L6) ? 'group_id' : 'role_id'] != "" && isset($this->importData['data']['member_groups'][$insertRow['data']['members'][(SM_APP_VER_L6) ? 'group_id' : 'role_id']])) )
        {
            $insertRow['data']['members'][(SM_APP_VER_L6) ? 'group_id' : 'role_id'] = $this->importData['data']['settings']['meta_settings']['default_member_group'];
        }

        if(isset($insertRow['data']['members']['screen_name']) && $insertRow['data']['members']['screen_name'] != "")
        {

            switch ($this->importData['data']['settings']['meta_settings']['screen_name'])
            {

                default:
                case '0':
                break;
                
                case '1':
                $insertRow['data']['members']['screen_name'] = ee()->sl->sanitize($insertRow['data']['members']['screen_name']);
                break;

            }

            $insertRow['data']['members']['screen_name'] = ee()->sl->generateUnique('screen_name', $insertRow['data']['members']['screen_name']);

        }

        if($this->importData['data']['settings']['meta_settings']['same_username'] != "4")
        {

            if(isset($insertRow['data']['members']['username']) && $insertRow['data']['members']['username'] != "")
            {

                switch ($this->importData['data']['settings']['meta_settings']['username'])
                {

                    default:
                    case '0':
                        break;
                    
                    case '1':
                        $insertRow['data']['members']['username'] = ee()->sl->sanitize($insertRow['data']['members']['username']);
                        break;

                }

                $insertRow['data']['members']['username'] = ee()->sl->generateUnique('username', $insertRow['data']['members']['username']);

            }

        }

        if(isset($insertRow['data']['members']['password']) && $insertRow['data']['members']['password'] != "")
        {

            switch ($this->importData['data']['settings']['meta_settings']['password'])
            {

                default:
                case '0':
                    break;
                
                case '1':
                case '2':
                    ee()->load->library('auth');
                    $hasPassword = ee()->auth->hash_password($insertRow['data']['members']['password']);
                    $insertRow['data']['members']['password'] = $hasPassword['password'];
                    $insertRow['data']['members']['salt'] = $hasPassword['salt'];
                    break;

            }

        }

        /**
        * Hook will call when Importer will import a member (Either insert, update or recreate member)
        * @param $array to be insert in
        * @param $outerError
        * @return $outerError
        */
        if (ee()->extensions->active_hook('sm_element_before_import') === TRUE)
        {

            $tmp = ee()->extensions->call('sm_element_before_import', $insertRow);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp != "")
            {
                $insertRow = $tmp;
                unset($tmp);
            }

        }
        
        $temp = $insertRow['data']['members'];

        unset($temp['avatar_filename']);
        unset($temp['avatar_width']);
        unset($temp['avatar_height']);
        unset($temp['photo_filename']);
        unset($temp['photo_width']);
        unset($temp['photo_height']);
        unset($temp['sig_img_filename']);
        unset($temp['sig_img_width']);
        unset($temp['sig_img_height']);

        $temp['token_id'] = $insertRow['data']['members']['token_id'];
        if(isset($temp['member_id']) && $temp['member_id'] != "")
        {
            ee()->db->insert('members', $temp);
            ee()->db->insert('member_data', array('member_id' => $temp['member_id']));
            $memberOBJ = ee('Model')->get('Member', $temp['member_id'])->first();
        } 
        else
        {
            $memberOBJ = ee('Model')->make('Member', $temp);
        }

        foreach ($insertRow['data']['member_data'] as $k1 => $v1)
        {
            $temp['m_field_id_'.$k1] = $v1;
        }
        
        $memberOBJ->set($temp);
        $memberOBJ->save();

        /*Upload static images*/
        $this->uploadImages($insertRow['data']['members'], $memberOBJ);

        /**
        * Hook will call when Importer will import a member (Either insert, update or recreate member)
        * @param $array to be insert in
        * @param $outerError
        * @return $outerError
        */
        if (ee()->extensions->active_hook('sm_element_after_import') === TRUE)
        {
            ee()->extensions->call('sm_element_after_import', $insertRow, $memberOBJ->member_id);
            if(ee()->extensions->end_script === TRUE) return;
        }
        
        return $memberOBJ->member_id;

    }
    
    /**
    * Update old members as per array of importer
    * @param $data          (data to be insert)
    * @param $meta_settings (meta settings of importer)
    **/
    function updateMember($updateRow)
    {

        /*Get member ID of existing user*/
        $member_id = ee()->ieModel->getMemberID($updateRow['meta']['key'], $updateRow['data']['members'][$updateRow['meta']['key']]);
        
        /*If no record found, Go to insert new member*/
        if($member_id === false)
        {
            $this->insertMember($data);
        }

        if(isset($updateRow['data']['members']['screen_name']) && $updateRow['data']['members']['screen_name'] != "")
        {

            switch ($this->importData['data']['settings']['meta_settings']['screen_name'])
            {

                default:
                case '0':
                break;
                
                case '1':
                $updateRow['data']['members']['screen_name'] = ee()->sl->sanitize($updateRow['data']['members']['screen_name']);
                break;

            }

        }

        if($this->importData['data']['settings']['meta_settings']['same_username'] != "4")
        {

            if(isset($updateRow['data']['members']['username']) && $updateRow['data']['members']['username'] != "")
            {

                switch ($this->importData['data']['settings']['meta_settings']['username'])
                {

                    default:
                    case '0':
                        break;
                    
                    case '1':
                        $updateRow['data']['members']['username'] = ee()->sl->sanitize($updateRow['data']['members']['username']);
                        break;

                }

                $updateRow['data']['members']['username'] = ee()->sl->generateUnique('username', $updateRow['data']['members']['username'], $member_id);

            }

        }

        if(isset($updateRow['data']['members']['password']) && $updateRow['data']['members']['password'] != "")
        {

            switch ($this->importData['data']['settings']['meta_settings']['password'])
            {

                default:
                case '0':
                    break;
                
                case '1':
                    $updateRow['data']['members']['password'] = md5($updateRow['data']['members']['password']);
                    break;

                case '2':
                    $updateRow['data']['members']['password'] = sha1(($updateRow['data']['members']['password']));
                    break;

            }

        }

        /**
        * Hook will call when Importer will import a member (Either insert, update or recreate member)
        * @param $array to be insert in
        * @param $outerError
        * @return $outerError
        */
        if (ee()->extensions->active_hook('sm_element_before_import') === TRUE)
        {

            $tmp = ee()->extensions->call('sm_element_before_import', $updateRow);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp != "")
            {
                $updateRow = $tmp;
                unset($tmp);
            }

        }
        
        $temp = $updateRow['data']['members'];

        /*Unset unnecesary data*/
        unset($temp['avatar_filename']);
        unset($temp['avatar_width']);
        unset($temp['avatar_height']);
        unset($temp['photo_filename']);
        unset($temp['photo_width']);
        unset($temp['photo_height']);
        unset($temp['sig_img_filename']);
        unset($temp['sig_img_width']);
        unset($temp['sig_img_height']);

        if(isset($this->importData['data']['settings']['meta_settings']['ignore_static_fields']) && is_array($this->importData['data']['settings']['meta_settings']['ignore_static_fields']) && count($this->importData['data']['settings']['meta_settings']['ignore_static_fields']) > 0 && $this->importData['data']['settings']['meta_settings']['ignore_static_fields'][0] != "")
        {

            for ($i=0; $i < count($this->importData['data']['settings']['meta_settings']['ignore_static_fields']); $i++)
            { 
                unset($temp[$this->importData['data']['settings']['meta_settings']['ignore_static_fields'][$i]]);
            }

        }

        if(isset($this->importData['data']['settings']['meta_settings']['ignore_dynamic_fields']) && count($this->importData['data']['settings']['meta_settings']['ignore_dynamic_fields']) > 0 && $this->importData['data']['settings']['meta_settings']['ignore_dynamic_fields'][0] != "")
        {

            for ($i=0; $i < count($this->importData['data']['settings']['meta_settings']['ignore_dynamic_fields']); $i++)
            { 
                unset($updateRow['data']['member_data']['m_field_id_'.$this->importData['data']['settings']['meta_settings']['ignore_dynamic_fields'][$i]]);
            }

        }

        /*Update members*/
        $memberOBJ = ee('Model')->get('Member', $member_id)->first();
        $memberOBJ->set($temp);
        
        ee()->db->select('m_field_id, m_field_label, m_field_type, m_field_name');
        $query = ee()->db->get('member_fields');
        if ($query->num_rows() > 0)
        {
            foreach ($query->result_array() as $row)
            {

                if(isset($updateRow['data']['member_data'][$row['m_field_id']]))
                {
                    $fname = 'm_field_id_'.$row['m_field_id'];
                    $post = $updateRow['data']['member_data'][$row['m_field_id']];
                    $memberOBJ->$fname = ee('Security/XSS')->clean($post);
                }

            }
        }

        /*Upload static images*/
        $this->uploadImages($updateRow['data']['members'], $memberOBJ);
        
        $memberOBJ->save();

        /**
        * Hook will call when Importer will import a member (Either insert, update or recreate member)
        * @param $array to be insert in
        * @param $outerError
        * @return $outerError
        */
        if (ee()->extensions->active_hook('sm_element_after_import') === TRUE)
        {
            ee()->extensions->call('sm_element_after_import', $updateRow, $memberOBJ->member_id);
            if(ee()->extensions->end_script === TRUE) return;
        }

        return $memberOBJ->member_id;

    }
    
    /**
    * Delete old members and create new as per array of importer
    * @param $data          (data to be insert)
    * @param $meta_settings (meta settings of importer)
    **/
    function recreateMember($data)
    {

        /*Get member ID of existing user*/
        $member_id = ee()->ieModel->getMemberID($data['meta']['key'], $data['data']['members'][$data['meta']['key']]);
        $memberOBJ = ee('Model')->get('Member', $member_id)->delete();
        
        /*Call insert member function*/
        return $this->insertMember($data);

    }

    /*Upload all static images handling funtion*/
    function uploadImages($members, $memberOBJ)
    {

        /*Avatar file field upload*/
        if(isset($members['avatar_filename']) && $members['avatar_filename'] != "")
        {

            if($this->importData['data']['settings']['meta_settings']['avatar_filename'] == 0)
            {
                return $this->doUpload($members['avatar_filename'], 'avatar', $memberOBJ);
            }
            else
            {
                return $this->updateMemberImages($memberOBJ, 'avatar');
            }

        }

        /*Photo file field upload*/
        if(isset($members['photo_filename']) && $members['photo_filename'] != "")
        {

            if($this->importData['data']['settings']['meta_settings']['photo_filename'] == 0)
            {
                return $this->doUpload($members['photo_filename'], 'photo', $memberOBJ);
            }
            else
            {
                return $this->updateMemberImages($memberOBJ, 'photo');
            }

        }

        /*Signature file field upload*/
        if(isset($members['sig_img_filename']) && $members['sig_img_filename'] != "")
        {

            if($this->importData['data']['settings']['meta_settings']['sig_img_filename'] == 0)
            {
                return $this->doUpload($members['sig_img_filename'], 'sig_img', $memberOBJ);
            }
            else
            {
                return $this->updateMemberImages($memberOBJ, 'sig_img');
            }

        }

    }

    /*Upload all static images */
    function doUpload( $fileurl, $type, $memberOBJ)
    {

        $member_id = $memberOBJ->member_id;
        /*Check wether image is exists or not*/
        if($this->getHttpResponseCode($fileurl) != "200")
        {
            return false;
        }

        $filename   = $type."_". $member_id.".jpg";
        $filedir    = ee()->config->item($type.'_path');

        /*Extra variable for avatar*/
        /*if($type == "avatar")
        {
            $filedir .= 'uploads/';
        }*/

        /*Rename if file exists*/
        $cnt = 1;
        while (file_exists($filedir . $filename))
        {
            $filename = $type.'_'.$member_id.'_'.$cnt++.'.jpg';
        }

        $url = parse_url( $fileurl );

        /*Using get and put content*/
        if(isset( $url["scheme"] ) )
        {

            if( file_exists( $filename ) )
            {
                return $filedir . $filename;
            }
            
            $fetch_url = true;
            if( $fetch_url === TRUE )
            {

                /*Get image data*/
                $content = file_get_contents( $fileurl );
                if( $content === FALSE )
                {
                    return FALSE;
                }

                /*Create directore if not exists*/
                if( is_dir($filedir) === false )
                {

                    $new_dir = mkdir($filedir,2);

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
        /*Usign Curl method*/
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

        /*Get image size*/
        $size = getimagesize($filedir . $filename);

        switch ($size['mime'])
        {
            case 'image/png':
                $filename = str_replace('.jpg', '.png', $filename);
                break;
            case 'image/gif':
                $filename = str_replace('.jpg', '.gif', $filename);
                break;
            default:
                break;
        }

        /*New file name with path*/
        $new_filepath = $filedir.$filename;

        $max_w  = (ee()->config->item($type.'_max_width') == '' OR ee()->config->item($type.'_max_width') == 0) ? 100 : ee()->config->item($type.'_max_width');
        $max_h  = (ee()->config->item($type.'_max_height') == '' OR ee()->config->item($type.'_max_height') == 0) ? 100 : ee()->config->item($type.'_max_height');
        
        /*resize image function called and resize image as per maximum sizes are given in setting by user in backend member fields settings*/
        if ($size[0] > $max_w && $size[1] > $max_h)
        {

            $config['source_image'] = $filedir . $filename;
            $config['new_image'] = $new_filepath;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $max_w;
            $config['height'] = $max_h;

            if(! class_exists('image_lib'))
            {
                ee()->load->library('image_lib');
            }

            ee()->image_lib->initialize($config);
            ee()->image_lib->resize();

            @chmod($new_filepath, 0777);
            // @unlink($filedir . $filename);

        }
        elseif ($new_filepath != $filedir . $filename)
        {
            copy($filedir . $filename, $new_filepath);

            @chmod($new_filepath, 0777);
            // @unlink($filedir . $bqasename);
        }

        /*Enter new file name in database with width and heigth*/
        if (file_exists($new_filepath))
        {

            $size = getimagesize($new_filepath);

            /*if($type == "avatar")
            {
                $filename = "uploads/".$filename;
            }*/

            if ($size === false)
            {
                $member_data = array($type.'_filename'=>$filename);
            }
            else
            {
                $member_data = array($type.'_filename'=>$filename, $type.'_width'=>$size[0], $type.'_height'=>$size[1]);
            }

            $memberOBJ->set($member_data);
            $memberOBJ->save();

            return true;

        }

    }

    /*Update member images in database without uploading */
    function updateMemberImages($memberOBJ, $type)
    {

        $member_id = $memberOBJ->member_id;
        $filename   = $type."_". $member_id.".jpg";

        $filedir    = ee()->config->item($type.'_path');

        /*if($type == "avatar")
        {
            $filedir .= 'uploads/';
        }*/

        /*Save the image name in datase if file exists*/
        if(file_exists($filedir . $filename))
        {
            
            $size = getimagesize($filedir . $filename);

            /*if($type == "avatar")
            {
                $filename = "uploads/".$filename;
            }*/

            if ($size === false)
            {
                $member_data = array($type.'_filename'=>$filename);
            }
            else
            {
                $member_data = array($type.'_filename'=>$filename, $type.'_width'=>$size[0], $type.'_height'=>$size[1]);
            }

            $memberOBJ->set($member_data);
            $memberOBJ->save();

            return true;
        }

        return false;

    }

    function handleImportSuccess($vars)
    {

        /*Define hepful variables*/
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

        /*Load helpful classes*/
        ee()->load->library('table');

        /*Append css and js files for beeter view of page*/
        ee()->cp->add_to_head('<link rel="stylesheet" href="'.URL_THIRD_THEMES.'smart_members/css/jquery.dataTables.min.css" type="text/css" media="screen" />');
        ee()->cp->add_to_foot('<script src="'.URL_THIRD_THEMES.'smart_members/js/jquery.dataTables.min.js"></script>');

        /*reload the page with another batch if all data isnt exported*/
        if($vars['status'] == "pending" && $vars['batch'] != "")
        {
            $vars['redirect_import']  = ee()->sm->url('run_import', array('token' => $vars['token'], 'status' => $vars['status'], 'batch' => $vars['batch']));
        }
        else
        {
            $vars['redirect_import'] = false;
        }

        /*Setup fields to be show in table*/
        $vars['loading_image']        = URL_THIRD_THEMES."smart_members/images/indicator.gif";
        $vars['total_members']        = $this->session('total_members_' . $vars['token']);
        $vars['imported_members']     = $this->session('imported_members_' . $vars['token']);
        $vars['updated_members']      = $this->session('updated_members_' . $vars['token']);
        $vars['recreated_members']    = $this->session('recreated_members_' . $vars['token']);
        $vars['skipped_members']      = $this->session('skipped_members_' . $vars['token']);
        $vars['memory_usage']         = $this->session('memory_usage_' . $vars['token']);
        $vars['total_memory_usage']   = $this->session('total_memory_usage_' . $vars['token']);
        $vars['time_taken']           = $this->session('time_taken_' . $vars['token']);
        $vars['total_time_taken']     = $this->session('total_time_taken_' . $vars['token']);
        $vars['data']                 = unserialize($this->session('ret_' . $vars['token']));

        /*echo '<pre>';
        print_r($vars['data']);
        exit;*/

        /*Set column header of table*/
        $columns = array(
            'member_id'     => array('header' => lang('member_id')),
            'group_id'      => array('header' => lang((SM_APP_VER_L6) ? 'group_id' : 'role_id')),
            // 'screen_name'   => array('header' => lang('screen_name')),
            'username'      => array('header' => lang('username')),
            'email'         => array('header' => lang('email')),
            'view_profile'  => array('header' => lang('view_profile'))
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

        ee()->javascript->output($js);

        return $vars;
    }

    /*Check the header response code to idebntify given URL is exists or not*/
    function getHttpResponseCode($url)
    {
        $headers = get_headers($url);
        return substr($headers[0], 9, 3);
    }

    /**
    * Generate CSV file
    * @param $delim Default delim
    * @param $newline Default newline
    * @param $enclosure Default enclosure
    * @return download export file
    **/
    function generateCSV($delim = ",", $newline = "\n", $enclosure = '"')
    {
        
        @ob_clean();
        @ob_start();

        $this->delim        = $delim;
        $this->newline      = $newline;
        $this->enclosure    = $enclosure;
        $out                = '';

        foreach ($this->exportData['data']->all() as $value)
        {
            if(isset($this->exportData['settings']['member_static_fields']) && is_array($this->exportData['settings']['member_static_fields']) && count($this->exportData['settings']['member_static_fields']) > 0)
            {
                for ($i = 0; $i < count($this->exportData['settings']['member_static_fields']); $i++)
                {
                    $out .= $this->enclose($this->exportData['settings']['member_static_fields'][$i]);
                }
            }

            if(isset($this->exportData['settings']['member_dynamic_fields']) && is_array($this->exportData['settings']['member_dynamic_fields']) && count($this->exportData['settings']['member_dynamic_fields']) > 0)
            {
                foreach ($this->exportData['settings']['member_dynamic_fields'] as $k => $v)
                {
                    $out .= $this->enclose($v['m_field_name']);
                }
            }
            break;
        }
        $out .= $newline;

        /*Replace some of the data to be sure CSV not broken up*/
        $search     = array("<", ">", '"');
        $replace    = array("&lt;", "&gt;", "\"");

        /*Get directories of files fileds*/
        $fieldDirectories = ee()->mf->parseDirectory();

        if($fieldDirectories !== false)
        {
            foreach ($fieldDirectories as $key => $value)
            {
                $search[]   = "{".$key."}";
                $replace[]  = $value;
            }
        }

        foreach ($this->exportData['data']->all() as $value)
        {

            //For EE6: Add additional roles
            if(!SM_APP_VER_L6){
                $addition_roles = is_array($value->getRoles()->toArray()) ? $value->getRoles()->toArray() : array();
                $addition_role_ids = '';
                foreach ($addition_roles as $key_r => $value_r) {
                    $addition_role_ids .= $value_r['role_id'].'|';
                }
                $addition_role_ids = rtrim($addition_role_ids, '|');
            }

            if(isset($this->exportData['settings']['member_static_fields']) && is_array($this->exportData['settings']['member_static_fields']) && count($this->exportData['settings']['member_static_fields']) > 0)
            {
                for ($i = 0; $i < count($this->exportData['settings']['member_static_fields']); $i++)
                {
                    
                    $temp = $this->exportData['settings']['member_static_fields'][$i];
                    $memberValue = $value->$temp;
                    if($temp == "avatar_filename" && $memberValue != "")
                    {
                        $memberValue = str_replace($search, $replace, ee()->config->slash_item('avatar_url') . $memberValue);
                    }
                    elseif($temp == "photo_filename" && $memberValue != "")
                    {
                        $memberValue = str_replace($search, $replace, ee()->config->slash_item('photo_url') . $memberValue);
                    }
                    elseif($temp == "sig_img_filename" && $memberValue != "")
                    {
                        $memberValue = str_replace($search, $replace, ee()->config->slash_item('sig_img_url') . $memberValue);
                    }
                    elseif($temp == "cp_homepage_channel" && $memberValue != "")
                    {
                        $memberValue = implode("|", $memberValue);
                    }
                    //For EE6: Add additional roles
                    elseif($temp == "multiple_role_id" && !SM_APP_VER_L6)
                    {
                        $memberValue = $addition_role_ids;
                    }
                    else
                    {
                        $memberValue = str_replace($search, $replace, $memberValue);
                    }

                    $out .= $this->enclose($memberValue);

                }
            }

            if(isset($this->exportData['settings']['member_dynamic_fields']) && is_array($this->exportData['settings']['member_dynamic_fields']) && count($this->exportData['settings']['member_dynamic_fields']) > 0)
            {
                foreach ($this->exportData['settings']['member_dynamic_fields'] as $k => $v)
                {   
                    $temp = 'm_field_id_' . $k;

                    if($v['m_field_type'] == 'date')
                    {
                        $out .= (!empty($value->$temp)) ? date("Y-m-d H:i:s", $value->$temp) : '';
                    }
                    else
                    {                        
                        $out .= $this->enclose($value->$temp);
                    }
                    
                }
            }
            $out = rtrim($out);
            $out .= $newline;

        }

        $now = gmdate("D, d M Y H:i:s");
        header('Expires: Tue, 03 Jul 2001 06:00:00 GMT');
        header('Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate');
        header("Last-Modified: {$now} GMT");

        // force download
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');

        // disposition / encoding on response body
        header('Content-Disposition: attachment;filename=smart_members_export_'.$this->exportData['id'].'.csv');
        header('Content-Transfer-Encoding: binary');
        echo $out;

        exit(ob_get_clean());

    }

    /**
    * Generate XML file
    * @return download export file
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

        /*Replace some of the data to be sure CSV not broken up*/
        $search     = array();
        $replace    = array();

        /*Get directories of files fileds*/
        $fieldDirectories = ee()->mf->parseDirectory();

        if($fieldDirectories !== false)
        {
            foreach ($fieldDirectories as $key => $value)
            {
                $search[]   = "{".$key."}";
                $replace[]  = $value;
            }
        }

        // Create variables for convenience
        extract($params);
        $xml = "";
        $xml .= '<?xml version="1.0"?>'.$newline;
        $xml .= "<{$root}>" . $newline;

        foreach ($this->exportData['data']->all() as $value)
        {   

            //For EE6: Add additional roles
            if(!SM_APP_VER_L6){
                $addition_roles = is_array($value->getRoles()->toArray()) ? $value->getRoles()->toArray() : array();
                $addition_role_ids = '';
                foreach ($addition_roles as $key_r => $value_r) {
                    $addition_role_ids .= $value_r['role_id'].'|';
                }
                $addition_role_ids = rtrim($addition_role_ids, '|');
            }

            $xml .= $tab."<{$element}>" . $newline;
            for ($i = 0; $i < count($this->exportData['settings']['member_static_fields']); $i++)
            {
                
                $temp = $this->exportData['settings']['member_static_fields'][$i];
                $memberValue = $value->$temp;
                if($temp == "avatar_filename" && $memberValue != "")
                {
                    $memberValue = str_replace($search, $replace, ee()->config->slash_item('avatar_url') . $memberValue);
                }
                elseif($temp == "photo_filename" && $memberValue != "")
                {
                    $memberValue = str_replace($search, $replace, ee()->config->slash_item('photo_url') . $memberValue);
                }
                elseif($temp == "sig_img_filename" && $memberValue != "")
                {
                    $memberValue = str_replace($search, $replace, ee()->config->slash_item('sig_img_url') . $memberValue);
                }
                //For EE6: Add additional roles
                elseif($temp == "multiple_role_id" && !SM_APP_VER_L6)
                {
                    $memberValue = $addition_role_ids;
                }
                else
                {
                    $memberValue = str_replace($search, $replace, $memberValue);
                }

                if(is_array($memberValue))
                {
                    if(count($memberValue) > 0){
                        $memberValue = json_encode($memberValue);
                    } else {
                        $memberValue = "";
                    }
                }
                $xml .= $tab . $tab . "<{$temp}><![CDATA[" . $memberValue . "]]></{$temp}>" . $newline;

            }

            if(isset($this->exportData['settings']['member_dynamic_fields'])){
                foreach ($this->exportData['settings']['member_dynamic_fields'] as $k => $v)
                {
                    $temp = 'm_field_id_' . $k;
                    $xml .= $tab . $tab . "<{$v['m_field_name']}><![CDATA[" . $value->$temp . "]]></{$v['m_field_name']}>" . $newline;
                }
            }

            $xml .= $tab."</{$element}>" . $newline;

        }

        $xml .= "</{$root}>";
    
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
        header('Content-Disposition: attachment;filename=smart_members_export_'.$this->exportData['id'].'.xml');
        header('Content-Transfer-Encoding: binary');
        echo $xml;
        exit(ob_get_clean());

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

    /*Create session from session variable name and value to store in session */
    function setSession($name, $data)
    {

        if(!isset($_SESSION))
        {
             session_start();
        }

        $_SESSION['sm'][$name] = $data;

    }

    /*Get the data of session by session name*/
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

    /*Unset the session from session name*/
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

    /*Set columns of table*/
    function setColumns($cols = array())
    {

        // @todo hook to register column?
        $headers = array();
        $defaults = array(
            'sort' => TRUE,
            'html' => TRUE
        );

        foreach ($cols as $key => &$col)
        {

            // asking for trouble
            if ( ! is_array($col))
            {
                $col = array();
            }

            // if no header, pass key to lang()
            if (isset($col['header']))
            {
                $headers[] = $col['header'];
                unset($col['header']);
            }
            else
            {
                $headers[] = lang($key);
            }

            // set defaults
            $col = array_merge($defaults, $col);

        }

        $this->setHeading($headers);
        $this->column_config = $cols;

    }

    /*Set data inside table*/
    function setData($table_data = NULL)
    {

        $ordered_columns = array_keys($this->column_config);

        foreach ($table_data as &$row)
        {
            $new_row = array();

            foreach ($ordered_columns as $key)
            {
                $new_row[] = (isset($row[$key])) ? $row[$key] : '';
            }

            $row = $this->_prepArgs($new_row);
        }

        $this->rows = $table_data;

    }

    /*Set heading of table*/
    function setHeading()
    {
        $args = func_get_args();
        $this->heading = $this->_prepArgs($args);
    }

    /*Generate new table*/
    function generate($table_data = NULL)
    {

        // The table data can optionally be passed to this function
        // either as a database result object or an array
        if ( ! is_null($table_data))
        {

            if (is_object($table_data))
            {
                $this->_setFromObject($table_data);
            }
            elseif (is_array($table_data))
            {
                $setHeading = (count($this->heading) == 0 AND $this->auto_heading == FALSE) ? FALSE : TRUE;
                $this->_setFromArray($table_data, $setHeading);
            }

        }

        // Is there anything to display?  No?  Smite them!
        if (count($this->heading) == 0 AND count($this->rows) == 0)
        {
            return 'Undefined table data';
        }

        // Compile and validate the template date
        $this->_compileTemplate();

        // set a custom cell manipulation function to a locally scoped variable so its callable
        $function = $this->function;

        // Build the table!
        $out = $this->template['table_open'];
        $out .= $this->newline;

        // Add any caption here
        if ($this->caption)
        {

            $out .= $this->newline;
            $out .= '<caption>' . $this->caption . '</caption>';
            $out .= $this->newline;

        }

        // Is there a table heading to display?
        if (count($this->heading) > 0)
        {

            $out .= $this->template['thead_open'];
            $out .= $this->newline;
            $out .= $this->template['heading_row_start'];
            $out .= $this->newline;

            foreach($this->heading as $heading)
            {

                $temp = $this->template['heading_cell_start'];

                foreach ($heading as $key => $val)
                {
                    if ($key != 'data')
                    {
                        $temp = str_replace('<th', "<th $key='$val'", $temp);
                    }
                }

                $out .= $temp;
                $out .= isset($heading['data']) ? $heading['data'] : '';
                $out .= $this->template['heading_cell_end'];

            }

            $out .= $this->template['heading_row_end'];
            $out .= $this->newline;
            $out .= $this->template['thead_close'];
            $out .= $this->newline;

        }

        // Build the table rows
        if (count($this->rows) > 0)
        {
            $out .= $this->template['tbody_open'];
            $out .= $this->newline;

            $i = 1;
            foreach($this->rows as $row)
            {
                if ( ! is_array($row))
                {
                    break;
                }

                // We use modulus to alternate the row colors
                $name = (fmod($i++, 2)) ? '' : 'alt_';

                $out .= $this->template['row_'.$name.'start'];
                $out .= $this->newline;

                foreach($row as $cell)
                {
                    $temp = $this->template['cell_'.$name.'start'];

                    foreach ($cell as $key => $val)
                    {
                        if ($key != 'data')
                        {
                            $temp = str_replace('<td', "<td $key='$val'", $temp);
                        }
                    }

                    $cell = isset($cell['data']) ? $cell['data'] : '';
                    $out .= $temp;

                    if ($cell === "" OR $cell === NULL)
                    {
                        $out .= $this->empty_cells;
                    }
                    else
                    {
                        if ($function !== FALSE && is_callable($function))
                        {
                            $out .= $function($cell);
                        }
                        else
                        {
                            $out .= $cell;
                        }
                    }

                    $out .= $this->template['cell_'.$name.'end'];
                }

                $out .= $this->template['row_'.$name.'end'];
                $out .= $this->newline;
            }

            $out .= $this->template['tbody_close'];
            $out .= $this->newline;
        }

        // Is there a table heading to display?
        if (count($this->footer) > 0)
        {
            $out .= $this->template['tfoot_open'];
            $out .= $this->newline;
            $out .= $this->template['heading_row_start'];
            $out .= $this->newline;

            foreach($this->footer as $footer)
            {
                $temp = $this->template['heading_cell_start'];

                foreach ($footer as $key => $val)
                {
                    if ($key != 'data')
                    {
                        $temp = str_replace('<th', "<th $key='$val'", $temp);
                    }
                }

                $out .= $temp;
                $out .= isset($footer['data']) ? $footer['data'] : '';
                $out .= $this->template['heading_cell_end'];
            }

            $out .= $this->template['heading_row_end'];
            $out .= $this->newline;
            $out .= $this->template['tfoot_close'];
            $out .= $this->newline;
        }

        $out .= $this->template['table_close'];

        // Clear table class properties before generating the table
        $this->clear();

        return $out;
    }

    function _prepArgs($args)
    {

        // If there is no $args[0], skip this and treat as an associative array
        // This can happen if there is only a single key, for example this is passed to table->generate
        // array(array('foo'=>'bar'))
        if (isset($args[0]) AND (count($args) == 1 && is_array($args[0])))
        {

            // args sent as indexed array
            if ( ! isset($args[0]['data']))
            {

                foreach ($args[0] as $key => $val)
                {

                    if (is_array($val) && isset($val['data']))
                    {
                        $args[$key] = $val;
                    }
                    else
                    {
                        $args[$key] = array('data' => $val);
                    }

                }

            }

        }
        else
        {

            foreach ($args as $key => $val)
            {

                if ( ! is_array($val))
                {
                    $args[$key] = array('data' => $val);
                }

            }

        }

        return $args;

    }

    function _compileTemplate()
    {

        if ($this->template == NULL)
        {
            $this->template = $this->_defaultTemplate();
            return;
        }

        $this->temp = $this->_defaultTemplate();
        $segments = array(
            'table_open',
            'thead_open', 'thead_close',
            'heading_row_start', 'heading_row_end',
            'heading_cell_start', 'heading_cell_end',
            'tbody_open', 'tbody_close',
            'row_start', 'row_end',
            'cell_start', 'cell_end',
            'row_alt_start', 'row_alt_end',
            'cell_alt_start', 'cell_alt_end',
            'tfoot_open', 'tfoot_close',
            'table_close'
        );

        foreach ($segments as $val)
        {

            if ( ! isset($this->template[$val]))
            {
                $this->template[$val] = $this->temp[$val];
            }

        }

    }

    function _defaultTemplate()
    {

        return  array (
            'table_open'         => '<table border="0" cellpadding="4" cellspacing="0">',

            'thead_open'         => '<thead>',
            'thead_close'        => '</thead>',

            'heading_row_start'  => '<tr>',
            'heading_row_end'    => '</tr>',
            'heading_cell_start' => '<th>',
            'heading_cell_end'   => '</th>',

            'tbody_open'         => '<tbody>',
            'tbody_close'        => '</tbody>',

            'row_start'          => '<tr>',
            'row_end'            => '</tr>',
            'cell_start'         => '<td>',
            'cell_end'           => '</td>',

            'row_alt_start'      => '<tr>',
            'row_alt_end'        => '</tr>',
            'cell_alt_start'     => '<td>',
            'cell_alt_end'       => '</td>',

            'tfoot_open'         => '<tfoot>',
            'tfoot_close'        => '</tfoot>',

            'table_close'        => '</table>'
        );

    }

    function _setFromObject($query)
    {

        if ( ! is_object($query))
        {
            return FALSE;
        }

        // First generate the headings from the table column names
        if (count($this->heading) == 0)
        {

            if ( ! method_exists($query, 'list_fields'))
            {
                return FALSE;
            }

            $this->heading = $this->_prepArgs($query->list_fields());

        }

        // Next blast through the result array and build out the rows

        if ($query->num_rows() > 0)
        {

            foreach ($query->result_array() as $row)
            {
                $this->rows[] = $this->_prepArgs($row);
            }

        }

    }

    function _setFromArray($data, $setHeading = TRUE)
    {

        if ( ! is_array($data) OR count($data) == 0)
        {
            return FALSE;
        }

        $i = 0;
        foreach ($data as $row)
        {

            // If a heading hasn't already been set we'll use the first row of the array as the heading
            if ($i == 0 AND count($data) > 1 AND count($this->heading) == 0 AND $setHeading == TRUE)
            {
                $this->heading = $this->_prepArgs($row);
            }
            else
            {
                $this->rows[] = $this->_prepArgs($row);
            }

            $i++;

        }

    }

    function clear()
    {
        $this->rows             = array();
        $this->heading          = array();
        $this->auto_heading     = TRUE;
    }

    function handleImportForm($vars)
    {

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));
        
        $vars['sections'] = array(
            array(
                array(
                    'fields' => array(
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => isset($vars['data']['id']) ? $vars['data']['id'] : "",
                        ),
                        'token' => array(
                            'type'      => 'hidden',
                            'value'     => isset($vars['data']['token']) ? $vars['data']['token'] : "",
                        )
                    ),
                    'attrs' => array(
                        'class' => 'last hidden',
                    ),
                ),
            ),
            array(
                'format' => array(
                    'title'     => 'import_type_label',
                    'desc'      => 'import_type_desc',
                    'fields' => array(
                        'format' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'csv' => lang('csv'),
                                'xml' => lang('xml')
                            ),
                            'value' => (isset($vars['data']['format'])) ? $vars['data']['format'] : "",
                        )
                    )
                ),
                array(
                    'title'     => 'import_filename_label',
                    'desc'      => 'import_filename_desc',
                    'fields' => array(
                        'filename' => array(
                            'type' => 'text',
                            'value' => (isset($vars['data']['settings']['filename'])) ? $vars['data']['settings']['filename'] : "",
                        )
                    )
                ),
            )
        );

        if(isset($vars['data']['id']) && $vars['data']['id'] != "")
        {
            $vars['sections'][1]['format']['fields']['format'] = array(
                'type' => 'text',
                'value' => (isset($vars['data']['format'])) ? $vars['data']['format'] : "",
                'disabled'  => true
            );
            $vars['sections'][0][0]['fields']['format'] = array(
                'type'      => 'hidden',
                'value' => (isset($vars['data']['format'])) ? $vars['data']['format'] : "",
            );
        }
        
        $vars += array(
            'base_url'              => ee()->sm->url('import_form', (isset($vars['data']['token']) ? array('token' => $vars['data']['token']) : "")),
            'cp_page_title'         => lang('import_members'),
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return $vars;

    }

    function handleImportFormPost()
    {

        $rules = array(
            'format'    => 'required|allowedFile[csv,xml]',
            'filename'  => 'required|checkFileExistance',
        );

        ee()->smValidation->validator->setRules($rules);
        $result = ee()->smValidation->validator->validate($_POST);

        if ($result->isValid())
        {

            if($_POST['id'] == "")
            {
                $tempToken = strtolower(ee()->functions->random('md5',10));
                $this->setSession($tempToken, $_POST);
                ee()->functions->redirect(ee()->sm->url('choose_member_fields', array('token' => $tempToken)));
            }
            else
            {
                ee()->ieModel->updateBasicImportSettings();
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('basic_import_updated_success'))->defer();
                ee()->functions->redirect(ee()->sm->url('import_members'));
            }

        }
        else
        {
            return $result;
        }
    }

    function handleChooseMemberFieldsForm($vars)
    {
        $vars['mode']       = "add";
        $vars['tokenData']  = $this->session($vars['token']);
        if($vars['tokenData'] === false)
        {
            $vars['data'] = ee()->ieModel->checkImportToken($vars['token']);
            if($vars['data'] === false)
            {
                show_error(lang('wrong_token'));
            }
            else
            {
                $vars['mode'] = "edit";
                $vars['data'] = $vars['data'][0];
                $vars['data']['settings'] = unserialize(base64_decode($vars['data']['settings']));
            }
        }

        if($vars['mode'] == "add")
        {
            if(strtolower($vars['tokenData']['format']) == "csv")
            {
                $vars['import_dropdown'] = $this->csvToArray(array('file_path' => $vars['tokenData']['filename']), "yes");
            }
            elseif(strtolower($vars['tokenData']['format']) == "xml")
            {
                $vars['import_dropdown'] = $this->xmlToArray(array('file_path' => $vars['tokenData']['filename']), "yes");
            }
            else
            {
                show_error(lang('invalid_import_type'));
            }
        }
        else
        {
            if(strtolower($vars['data']['format']) == "csv")
            {
                $vars['import_dropdown'] = $this->csvToArray(array('file_path' => $vars['data']['settings']['filename']), "yes");
            }
            elseif(strtolower($vars['data']['format']) == "xml")
            {
                $vars['import_dropdown'] = $this->xmlToArray(array('file_path' => $vars['data']['settings']['filename']), "yes");
            }
            else
            {
                show_error(lang('invalid_import_type'));
            }
        }
        
        if($vars['import_dropdown'] === false)
        {
            show_error(lang('file_not_found_or_unreadable_path'));
        }

        $temp = array('' => '---');
        for ($i = 0; $i < count($vars['import_dropdown']); $i++)
        {
            $temp[$vars['import_dropdown'][$i]] = $vars['import_dropdown'][$i];
        }
        $vars['import_dropdown'] = $temp;
        unset($temp);

        
        $vars['member_static_fields']     = ee()->ieModel->getMemberStaticFields();
        $vars['member_dynamic_fields']    = ee()->ieModel->get_member_dynamic_fields();
        
        /*Get all possible member groups*/
        $vars['member_groups'] = array();
        $temp                  = ee()->ieModel->getMemberGroups();
        for ($i=0; $i < count($temp); $i++)
        {
            $vars['member_groups'][$temp[$i]['group_id']] = $temp[$i]['group_title'];
        }
        unset($temp);

        $staticFieldDropdown = array("" => "---");
        for ($i = 0; $i < count($vars['member_static_fields']); $i++)
        {
            $staticFieldDropdown[$vars['member_static_fields'][$i]['name']] = $vars['member_static_fields'][$i]['name'];
        }

        $dynamicFieldDropdown = array("" => "---");
        if(isset($vars['member_dynamic_fields']) && is_array($vars['member_dynamic_fields']) && count($vars['member_dynamic_fields']) > 0)
        {
            for ($i = 0; $i < count($vars['member_dynamic_fields']); $i++)
            {
                $dynamicFieldDropdown[$vars['member_dynamic_fields'][$i]['m_field_id']] = $vars['member_dynamic_fields'][$i]['m_field_label'];
            }
        }

        ee()->cp->add_js_script(array(
            'file' => array('cp/form_group'),
        ));
        
        $vars['sections'] = array(
            array(
                array(
                    'fields' => array(
                        'token' => array(
                            'type'      => 'hidden',
                            'value'     => isset($_POST['token']) ? $_POST['token'] : (isset($vars['token']) ? $vars['token'] : ""),
                        ),
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => isset($_POST['id']) ? $_POST['id'] : (isset($vars['tokenData']['id']) ? $vars['tokenData']['id'] : (isset($vars['data']['id']) ? $vars['data']['id'] : "")),
                        ),
                        'format' => array(
                            'type'      => 'hidden',
                            'value'     => isset($_POST['format']) ? $_POST['format'] : (isset($vars['tokenData']['format']) ? $vars['tokenData']['format'] : (isset($vars['data']['format']) ? $vars['data']['format'] : "")),
                        ),
                        'settings[filename]' => array(
                            'type'      => 'hidden',
                            'value'     => isset($_POST['settings']['filename']) ? $_POST['settings']['filename'] : (isset($vars['tokenData']['filename']) ? $vars['tokenData']['filename'] : (isset($vars['data']['settings']['filename']) ? $vars['data']['settings']['filename'] : "")),
                        ),
                    ),
                    'attrs' => array(
                        'class' => 'last hidden',
                    ),
                ),
            ),

        );

        for ($i = 0; $i < count($vars['member_static_fields']); $i++)
        {
            
            $vars['sections']['member_static_fields'][$i] = array(
                'title' => lang($vars['member_static_fields'][$i]['name']),
                'desc'  => '',
                'fields' => array(
                    'settings[member_static_fields]['.$vars['member_static_fields'][$i]['name'].']' => array(
                        'type'      => 'select',
                        'choices'   => $vars['import_dropdown'],
                        'value'     => isset($_POST['settings']['member_static_fields'][$vars['member_static_fields'][$i]['name']]) ? $_POST['settings']['member_static_fields'][$vars['member_static_fields'][$i]['name']] : (isset($vars['data']['settings']['member_static_fields'][$vars['member_static_fields'][$i]['name']]) ? $vars['data']['settings']['member_static_fields'][$vars['member_static_fields'][$i]['name']] : ""),
                    )
                ),
                'attrs' => array(
                    'class' => 'add-border',
                ),
            );

            if($vars['member_static_fields'][$i]['name'] == "username" || $vars['member_static_fields'][$i]['name'] == "screen_name")
            {
                $vars['sections']['member_static_fields'][$i]['fields']['settings[meta_settings]['.$vars['member_static_fields'][$i]['name'].']'] = 
                array(
                    'type'      => 'select',
                    'choices'   => array(
                        lang('plain_text'), 
                        lang('sanitize')
                    ),
                    'value'     => isset($_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : (isset($vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : ""),
                    'margin_top' => (SM_APP_VER_L6) ? TRUE : FALSE, // ee6
                );
            }
            elseif($vars['member_static_fields'][$i]['name'] == "password")
            {
                $vars['sections']['member_static_fields'][$i]['fields']['settings[meta_settings]['.$vars['member_static_fields'][$i]['name'].']'] = 
                array(
                    'type'      => 'select',
                    'choices'   => array(
                        lang('plain_text'), 
                        "MD5", 
                        "SHA1"
                    ),
                    'value'     => isset($_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : (isset($vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : ""),
                    'margin_top' => (SM_APP_VER_L6) ? TRUE : FALSE, // ee6
                );
            }
            elseif($vars['member_static_fields'][$i]['name'] == "avatar_filename" || $vars['member_static_fields'][$i]['name'] == "photo_filename" || $vars['member_static_fields'][$i]['name'] == "sig_img_filename")
            {
                $vars['sections']['member_static_fields'][$i]['fields']['settings[meta_settings]['.$vars['member_static_fields'][$i]['name'].']'] = 
                array(
                    'type'      => 'select',
                    'choices'   => array(
                        lang('upload_files'), 
                        lang('dont_upload_files')
                    ),
                    'value'     => isset($_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $_POST['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : (isset($vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']]) ? $vars['data']['settings']['meta_settings'][$vars['member_static_fields'][$i]['name']] : ""),
                    'margin_top' => (SM_APP_VER_L6) ? TRUE : FALSE, // ee6
                );
            }
        }

        for ($i = 0; $i < count($vars['member_dynamic_fields']); $i++)
        {
            
            $vars['sections']['member_dynamic_fields'][$i] = array(
                'title' => $vars['member_dynamic_fields'][$i]['m_field_label'],
                'desc'  => '',
                'fields' => array(
                    'settings[member_dynamic_fields]['.$vars['member_dynamic_fields'][$i]['m_field_id'].']' => array(
                        'type'      => 'select',
                        'choices'   => $vars['import_dropdown'],
                        'value'     => isset($_POST['settings']['member_dynamic_fields'][$vars['member_dynamic_fields'][$i]['m_field_id']]) ? $_POST['settings']['member_dynamic_fields'][$vars['member_dynamic_fields'][$i]['m_field_id']] : (isset($vars['data']['settings']['member_dynamic_fields'][$vars['member_dynamic_fields'][$i]['m_field_id']]) ? $vars['data']['settings']['member_dynamic_fields'][$vars['member_dynamic_fields'][$i]['m_field_id']] : ""),
                    )
                ),
                'attrs' => array(
                    'class' => 'add-border',
                ),
            );

        }


        $vars['sections']['meta_action_settings'] = array(
            array(
                'title'     => 'same_member_id_label',
                'desc'      => 'same_member_id_desc',
                'fields' => array(
                    'settings[meta_settings][same_member_id]' => array(
                        'type'      => 'select',
                        'choices'   => array(
                            lang('same_member_id_skip'), 
                            lang('same_member_id_change'), 
                            lang('same_member_id_modify'), 
                            lang('same_member_id_delete')
                        ),
                        'value'     => isset($_POST['settings']['meta_settings']['same_member_id']) ? $_POST['settings']['meta_settings']['same_member_id'] : (isset($vars['data']['settings']['meta_settings']['same_member_id']) ? $vars['data']['settings']['meta_settings']['same_member_id'] : ""),
                    )
                )
            ),
            array(
                'title'     => 'same_username_label',
                'desc'      => 'same_username_desc',
                'fields' => array(
                    'settings[meta_settings][same_username]' => array(
                        'type'      => 'select',
                        'choices'   => array(
                            lang('same_username_skip'), 
                            lang('same_username_change'), 
                            lang('same_username_modify'), 
                            lang('same_username_delete'), 
                            lang('same_username_create')
                        ),
                        'value'     => isset($_POST['settings']['meta_settings']['same_username']) ? $_POST['settings']['meta_settings']['same_username'] : (isset($vars['data']['settings']['meta_settings']['same_username']) ? $vars['data']['settings']['meta_settings']['same_username'] : ""),
                    )
                )
            ),
            array(
                'title'     => 'same_email_label',
                'desc'      => 'same_email_desc',
                'fields' => array(
                    'settings[meta_settings][same_email]' => array(
                        'type'      => 'select',
                        'choices'   => array(
                            lang('same_email_skip'), 
                            lang('same_email_mofify'), 
                            lang('same_email_delete'), 
                            lang('same_email_create')
                        ),
                        'value'     => isset($_POST['settings']['meta_settings']['same_email']) ? $_POST['settings']['meta_settings']['same_email'] : (isset($vars['data']['settings']['meta_settings']['same_email']) ? $vars['data']['settings']['meta_settings']['same_email'] : ""),
                    )
                )
            ),
            array(
                'title'     => (SM_APP_VER_L6) ? 'pending_if_no_email_label' : 'pending_role_if_no_email_label',
                'desc'      => (SM_APP_VER_L6) ? 'pending_if_no_email_desc' : 'pending_role_if_no_email_desc',
                'fields' => array(
                    'settings[meta_settings][pending_if_no_email]' => array(
                        'type'      => 'inline_radio',
                        'choices'   => array(
                            'no' => lang('no'), 
                            'yes' => lang('yes')
                        ),
                        'value'     => isset($_POST['settings']['meta_settings']['pending_if_no_email']) ? $_POST['settings']['meta_settings']['pending_if_no_email'] : (isset($vars['data']['settings']['meta_settings']['pending_if_no_email']) ? $vars['data']['settings']['meta_settings']['pending_if_no_email'] : ""),
                    )
                )
            ),
            array(
                'title'     => (SM_APP_VER_L6) ? 'default_member_group_label' : 'default_member_role_label' ,
                'desc'      => (SM_APP_VER_L6) ? 'default_member_group_desc' : 'default_member_role_desc',
                'fields' => array(
                    'settings[meta_settings][default_member_group]' => array(
                        'type'      => 'radio',
                        'choices'   => $vars['member_groups'],
                        'value'     => isset($_POST['settings']['meta_settings']['default_member_group']) ? $_POST['settings']['meta_settings']['default_member_group'] : (isset($vars['data']['settings']['meta_settings']['default_member_group']) ? $vars['data']['settings']['meta_settings']['default_member_group'] : "5"),
                    )
                )
            ),
            array(
                'title'     => 'batches_label',
                'desc'      => 'batches_desc',
                'fields' => array(
                    'settings[meta_settings][batches]' => array(
                        'type'      => 'select',
                        'choices'   => array(
                            '5'     => '5',
                            '10'    => '10',
                            '25'    => '25',
                            '50'    => '50',
                            '100'   => '100',
                            '200'   => '200',
                            '300'   => '300',
                            '500'   => '500',
                            'ALL'   => 'ALL',
                        ),
                        'value'     => isset($_POST['settings']['meta_settings']['batches']) ? $_POST['settings']['meta_settings']['batches'] : (isset($vars['data']['settings']['meta_settings']['batches']) ? $vars['data']['settings']['meta_settings']['batches'] : "50"),
                    )
                )
            ),
        );
        
        $vars['sections']['ignore_specific_fields_on_modify'] = array(
            array(
                'title'     => 'ignore_static_fields_label',
                'desc'      => 'ignore_static_fields_desc',
                'fields' => array(
                    'settings[meta_settings][ignore_static_fields][]' => array(
                        'type'      => 'select',
                        'choices'   => $staticFieldDropdown,
                        'value'     => isset($_POST['settings']['meta_settings']['ignore_static_fields']) ? $_POST['settings']['meta_settings']['ignore_static_fields'] : (isset($vars['data']['settings']['meta_settings']['ignore_static_fields']) ? $vars['data']['settings']['meta_settings']['ignore_static_fields'] : ""),
                        'attrs'     => 'multiple="multiple" size="15" style="text-align:left" '
                    )
                )
            ),
            array(
                'title'     => 'ignore_dynamic_fields_label',
                'desc'      => 'ignore_dynamic_fields_desc',
                'fields' => array(
                    'settings[meta_settings][ignore_dynamic_fields][]' => array(
                        'type'      => 'select',
                        'choices'   => $dynamicFieldDropdown,
                        'value'     => isset($_POST['settings']['meta_settings']['ignore_dynamic_fields']) ? $_POST['settings']['meta_settings']['ignore_dynamic_fields'] : (isset($vars['data']['settings']['meta_settings']['ignore_dynamic_fields']) ? $vars['data']['settings']['meta_settings']['ignore_dynamic_fields'] : ""),
                        'attrs'     => 'multiple="multiple"' . ( (count($dynamicFieldDropdown) > 15 ? 'size="15"' : (count($dynamicFieldDropdown) > 10 ? 'size="10"' : 'size="5"')) ) .' style="text-align:left"',
                    )
                )
            ),
        );

        $vars['sections']['field_action_settings'] = array(
            array(
                'title'     => 'email_empty_label',
                'desc'      => 'email_empty_desc',
                'fields' => array(
                    'settings[meta_settings][email_empty]' => array(
                        'type'      => 'select',
                        'choices'   => array('' => '---', lang('email_empty_skip')),
                        'value'     => isset($_POST['settings']['meta_settings']['email_empty']) ? $_POST['settings']['meta_settings']['email_empty'] : (isset($vars['data']['settings']['meta_settings']['email_empty']) ? $vars['data']['settings']['meta_settings']['email_empty'] : ""),
                    )
                )
            ),
            array(
                'title'     => 'username_empty_label',
                'desc'      => 'username_empty_desc',
                'fields' => array(
                    'settings[meta_settings][username_empty]' => array(
                        'type'      => 'select',
                        'choices'   => array(lang('username_empty_use_email'), lang('username_empty_skip')),
                        'value'     => isset($_POST['settings']['meta_settings']['username_empty']) ? $_POST['settings']['meta_settings']['username_empty'] : (isset($vars['data']['settings']['meta_settings']['username_empty']) ? $vars['data']['settings']['meta_settings']['username_empty'] : ""),
                    )
                )
            ),
            array(
                'title'     => 'screen_name_empty_label',
                'desc'      => 'screen_name_empty_desc',
                'fields' => array(
                    'settings[meta_settings][screen_name_empty]' => array(
                        'type'      => 'select',
                        'choices'   => array(lang('screen_name_empty_use_username'), lang('screen_name_empty_use_email'), lang('screen_name_empty_skip')),
                        'value'     => isset($_POST['settings']['meta_settings']['screen_name_empty']) ? $_POST['settings']['meta_settings']['screen_name_empty'] : (isset($vars['data']['settings']['meta_settings']['screen_name_empty']) ? $vars['data']['settings']['meta_settings']['screen_name_empty'] : ""),
                    )
                )
            ),
        );

        $vars['sections']['general_settings'] = array(
            array(
                'title'     => 'name_label',
                'desc'      => 'name_desc',
                'fields' => array(
                    'name' => array(
                        'type'      => 'text',
                        'value'     => (isset($vars['data']['name'])) ? $vars['data']['name'] : "",
                        'required'  => TRUE
                    )
                )
            ),
            array(
                'title'     => 'import_without_login_label',
                'desc'      => 'import_without_login_desc',
                'fields' => array(
                    'import_without_login' => array(
                        'type'      => 'inline_radio',
                        'choices'   => array('n' => lang('no'), 'y' => lang('yes')),
                        'value'     => (isset($vars['data']['import_without_login'])) ? $vars['data']['import_without_login'] : "",
                    )
                )
            ),
            array(
                'title'     => 'import_type_label',
                'desc'      => 'import_type_desc',
                'fields' => array(
                    'type' => array(
                        'type'      => 'inline_radio',
                        'choices'   => array('private' => lang('private'), 'public' => lang('public')),
                        'value'     => (isset($vars['data']['type'])) ? $vars['data']['type'] : "",
                    )
                )
            ),
        );

        $vars['sections']['general_settings'][] = array(
            'fields' => array(
                'last' => array(
                    'type'      => 'html',   
                    'content'   => '',
                )
            ),
            'attrs' => array(
                'class' => 'last_fieldset hidden last'
            )
        );

        $vars += array(
            'base_url'              => ee('CP/URL', 'addons/settings/smart_members/choose_member_fields/' . (isset($vars['token']) ? $vars['token'] : "")),
            'cp_page_title'         => lang('choose_import_fields'),
            'save_btn_text'         => 'btn_save_import',
            'save_btn_text_working' => 'btn_saving'
        );

        return $vars;

    }

    function handleChooseMemberFieldsFormPost()
    {

        $rules = array(
            'name' => 'required'
        );

        ee()->smValidation->validator->setRules($rules);
        $result = ee()->smValidation->validator->validate($_POST);

        if ($result->isValid())
        {
            if($_POST['id'] == "")
            {

                $data = $_POST;
                $data['settings'] = base64_encode(serialize($data['settings']));
                unset($data['id']);

                $data['member_id']      = $this->member_id;
                $data['created_date']   = ee()->localize->now;
                $data['last_modified']  = ee()->localize->now;
                $data['import_counts']  = 0;
                $data['status']         = 'active';

                ee()->ieModel->saveImport($data);
                $this->unsetSession($_POST['token']);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('import_saved_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('import_members'));

            }
            else
            {
                $data = $_POST;
                $data['settings']       = base64_encode(serialize($data['settings']));
                $data['last_modified']  = ee()->localize->now;
                ee()->ieModel->updateImport($data, $data['token']);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('import_updated_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('import_members'));
            }
        }
        else
        {
            return $result;
        }

    }
}