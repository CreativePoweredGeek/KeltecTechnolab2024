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
* @filesource   ./system/expressionengine/third_party/smart_import_export/mcp.smart_import_export.php
*
*/
require PATH_THIRD.'smart_import_export/config.php';
// use Zealousweb\SmartImportExport\ZealCore\EE_Mcp;
class smart_import_export_mcp
{

    /* Important globel variables */ 
    public $site_id;
    public $member_id;
    public $group_id;
    public $table_rows;
    public $module = "smart_import_export";
    public $module_name = "Smart Import Export";
    public $vars;

    public $addon_module_name = ZEAL_SIE_MOD_NAME;
    public $subscription_api_url = "https://www.zealousweb.com/store/webapi/v1/license/verify";
    public $sku = 'zosie';
    
    /* Constructor */
    public function __construct()
    {

        /* Assign values to golebel variables */
        $this->site_id      = ee()->config->item("site_id");
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata( SIE_APP_VER_L6 ? 'group_id' : 'role_id'); // ee6
        $this->table_rows   = 25;
        // $this->module_name   = ZEAL_SIE_MOD_NAME;

        /* Load helpful Libraries */
        ee()->load->library('sie_lib', null, 'sie');

        /* Add necessary data and files to head and footer */
        ee()->cp->add_to_head('<link rel="stylesheet" href="'.URL_THIRD_THEMES.'smart_import_export/css/screen.css?v=1" type="text/css" media="screen" />');
        ee()->cp->add_to_foot("<script src='".URL_THIRD_THEMES."smart_import_export/js/settings.js'></script>");
        ee()->cp->add_to_foot("
            <script type='text/javascript'>
                var se_url = '". ee()->sie->url('get_channel_fields_from_channel') ."';
                var loadingImage = '". URL_THIRD_THEMES."smart_import_export/images/indicator.gif" ."';
            </script>"
        );

        $this->startup_form();
    }

    /**
    * The module's Control Panel default controller
    * Export data setting page
    * 
    * @return void
    */
    function index()
    {


        /* Create table of saved exports */
        $this->vars = ee()->sie->createExportTable($this->vars, $this->table_rows);
        
        /* Popup Title */
        $this->vars['popup_data']['title']          = lang('export_popup_title');
        $this->vars['popup_data']['downloadTitle']  = lang('download_popup_title');
        
        // return ee()->load->view('index', $this->vars, TRUE);
        return array(
            'heading'    => lang('export_list'),
            'body'       => ee('View')->make('smart_import_export:index')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export')->compile() => lang('label_title_index')
                ),
            );

    }

    /**
    * Listing of created immport settinngs
    * 
    * @param 
    * @return void
    */
    function importIndex()
    {


        if(isset($_POST) && count($_POST) > 0){
            $ret = ee()->sie->deleteImports();

            if($ret === true)
            {
                if(count($_POST['selection']) > 1 ){
                    ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('imports_deleted'))->addToBody(lang('imports_deleted_success'))->defer();
                }else{
                    ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('import_deleted'))->addToBody(lang('import_deleted_success'))->defer();
                }
                ee()->functions->redirect(ee()->sie->url('import_index'));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }
          

        }

        /* Create table of saved exports */
        $this->vars = ee()->sie->createImportTable($this->vars, $this->table_rows);
        
        /* Popup Title */
        $this->vars['popup_data']['title']          = lang('import_popup_title'); //cron feature
        $this->vars['popup_data']['downloadTitle']  = lang('download_popup_title');
        
        // return ee()->load->view('index', $this->vars, TRUE);
        return array(
            'heading'    => lang('import_list'),
            'body'       => ee('View')->make('smart_import_export:import_index')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export')->compile() => lang('label_title_index')
                ),
            );

    }

    /**
    * General settinngs
    * 
    * @param 
    * @return void
    */
    function export_general_settings()
    {


        if(isset($_POST) && count($_POST) > 0)
        {

            $ret = ee()->sie->handleGeneralSettingsFormPost();

            if($ret === true)
            {
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('general_settings_udpated_success'))->defer();
                ee()->functions->redirect(ee()->sie->url('export_general_settings'));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }

        }

        $this->vars = ee()->sie->handleGeneralSettingsForm($this->vars);

        return array(
            'heading'    => lang('export_general_settings'),
            'body'       => ee('View')->make('smart_import_export:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export')->compile() => lang('label_title_index')
                ),
            );

    }


    function get_fluid_grid_data(){
        
    }

    /**
    * Export form to save and edit exports
    * 
    * @param $token (Optional) If token found, go to edit export 
    * @return void
    */
    public function export_form($token = "")
    {




        /* If form submitted, save the form or display the errors if found any. */
        if(isset($_POST) && count($_POST) > 0)
        {

            if(isset($_POST['name']) && is_null($_POST['name'])){

            }

            ee()->sie->handleExportFormPost();
            ee()->functions->redirect(ee()->sie->url());
        }

        /*Set title of the page*/
        ee()->view->cp_page_title = lang('label_title_index');


        $this->vars['token'] = $token;
        
        /* Set token if not found any via parameter */
        if($token == "")
        {
            $this->vars['token'] = ee()->input->get_post('token', true);
        }

        /* If not found any token, load add new export form or else load old export to update */
        if($this->vars['token'] == "")
        {
            $this->vars['edit']     = false;
            /* Get all statuses of given channel */
            $this->vars['status']   = array();
        }
        else
        {

            /* Update form settings preload */
            $this->vars['edit']             = true;
            $this->vars['data']             = ee()->sieModel->checkExportToken($this->vars['token']);
            $this->vars['data']             = $this->vars['data'][0];
            $this->vars['data']['settings'] = unserialize(base64_decode($this->vars['data']['settings']));
            
            /* Check given token is exists in our system or not. If not, give user an error. */
            if($this->vars['data'] === false)
            {
                show_error(lang('wrong_token'));
            }

            /* Get all statuses of given channel */
            $this->vars["status"] = ee()->sieModel->getStatusesFromChannelID($this->vars['data']['settings']['channel_id']);

            $temp = ee()->sie->getAllChannelFields($this->vars['data']['settings']['channel_id']);
            if($temp !== false){
                $this->vars['custom_fields'] = $temp;
                unset($temp);
            }

            /* Find if given channel is assigned any category or not. So we can have an option to export categories in export form */
            $this->vars['categories']       = ee()->sieModel->getCategoryGroups($this->vars['data']['settings']['channel_id'], true);

        }

        $this->vars['method']           = "smart_import_export";
        $this->vars['loading_image']    = URL_THIRD_THEMES."smart_import_export/images/indicator.gif";
        $this->vars['callback']         = ee()->sie->url('export_form');
        $this->vars['result']           = ee()->sieModel->getAllChannels($this->site_id);
        $this->vars['default_fields']   = ee()->sieModel->getDefaultFields();
        $this->vars['seo_lite']         = ee()->sieModel->checkModuleInstalled("Seo_lite");
        $this->vars['smart_seo']         = ee()->sieModel->checkModuleInstalled("Smart_seo");
        $this->vars['seeo']         = ee()->sieModel->checkModuleInstalled("Seeo");
        $this->vars['structure']         = ee()->sieModel->checkModuleInstalled("Structure");
        $this->vars['transcribe']         = ee()->sieModel->checkModuleInstalled("Transcribe");
        $this->vars['pages']            = ee()->sieModel->checkModuleInstalled("Pages");
        
        ee()->sie->loadDatePicker();
        return ee()->load->view('export_form', $this->vars, TRUE);

    }

    /**
    * Download export function to generate export file and download
    * 
    * @param $token (ID to find saved export)
    * @return void
    */
    function download_export($token = "")
    {
        
        $type = ee()->input->get_post('type', true);

        /* Set token if not found any via parameter */
        if($token == "")
        {
            $token = ee()->input->get_post('token', true);
        }
        
        /* If not found any token, give user error (You cannot download export which does not even exists.) */
        if($token == "")
        {

            if($type == "ajax"){
                $error = array('error' => lang('token_not_set'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                show_error(lang('token_not_set'));
            }

        }

        /* Check given token is exists in our system or not. If not, give user an error. */
        $data = ee()->sieModel->checkExportToken($token);
        if($data === false)
        {
            if($type == "ajax"){
                $error = array('error' => lang('wrong_token'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                show_error(lang('wrong_token'));
            }
        }

        $data = $data[0];
        $data['settings'] = unserialize(base64_decode($data['settings']));

        /* Increase download counter */
        if($type == "ajax"){
            if(ee()->input->get('offset') == "" || ee()->input->get('offset') == 0){
                ee()->sieModel->increaseCounter($token);
            }
        }else{
            ee()->sieModel->increaseCounter($token);
        }

        /* Main generate export function */
        $ret = ee()->sie->generateExport($data, $type);

        /* If there is no entries assigned to that channel yet, Give user error. (You cannot export entries if there is no any.) */
        if($ret === false)
        {
            if($type == "ajax"){
                $error = array('error' => lang('no_entries_found'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                show_error(lang('no_entries_found'));
            }
        }

        echo $ret;
        exit();
    }

    /**
    * Delete export settings via AJAX
    * 
    * @param $token (ID to find saved export)
    * @return void
    */
    function delete_export($token = "")
    {

        if(isset($_POST) && ! empty($_POST))
        {
            $action = ee()->input->post('bulk_action', true);
            if($action == "remove")
            {   

                $removeIds = ee()->input->post('selection', true);
                ee()->sieModel->deleteExport($removeIds);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_deleted_successfully'))->defer();
                ee()->functions->redirect(ee()->sie->url());

            }
        }
        else
        {
            show_error(lang('direct_access_not_allowed'));
        }
        
    }

    /**
    * Get channel fields with GRID and relationships extra selector field (AJAX CALL function)
    * 
    * @return Array of all general settings variable (To show those variables on export form once user hit select channel)
    */
    public function get_channel_fields_from_channel()
    {

        
        $retArray = array('error' => lang('channel_not_exists'));

        /*Fetch channel ID from POST*/
        $this->vars['channel_id'] = ee()->input->get_post('channel_id', true);

        if($this->vars['channel_id'] == ""){ echo json_encode($retArray); exit(); }

        $ret = ee()->sieModel->validateChannel($this->vars['channel_id']);
        if($ret === false) { echo json_encode($retArray); exit(); }

        $temp = ee()->sie->getAllChannelFields($this->vars['channel_id']);
        if($temp !== false){
            $this->vars['result'] = $temp;
            unset($temp);
        }
        
        /* Get all statuses of given channel */
        $this->vars["status"] = ee()->sieModel->getStatusesFromChannelID($this->vars['channel_id']);
        
        /* Find if given channel is assigned any category or not. So we can have an option to export categories in export form */
        $this->vars['categories']   = ee()->sieModel->getCategoryGroups($this->vars['channel_id'], true);

        echo json_encode($this->vars);
        exit;

    }

    /**
    * Startup form method to load basic dependencies
    * 
    * @return New array form of general form field basic variables (That will helpful in each and every form we will create at frontend.)
    */
    function startup_form()
    {   
        $this->vars['tabs'] = $this->tabs();
        if(version_compare(APP_VER, '2.8.0', '<'))
        {
            $this->vars['csrf_token'] = ee()->security->get_csrf_hash();
            $this->vars['xid'] = ee()->functions->add_form_security_hash('{XID_HASH}');
        }
        else
        {
            $this->vars['csrf_token'] = XID_SECURE_HASH;
            $this->vars['xid'] = XID_SECURE_HASH;
        }
        return TRUE;
    }

    /**
    * Add Tabbing in Module
    * 
    * @return Tab array (Side menu in module settings)
    */
    public function tabs()
    {
        // Create menu
        $sidebar = ee('CP/Sidebar')->make();

        // Header
        $sidebar->addHeader(lang('label_title_index'));
        
        if(SIE_APP_VER_L6){

            //ee5
            // Navbar main LI
            $this->navSettings = $sidebar->addHeader(lang('export_list'), ee()->sie->url());
            $settingsList = $this->navSettings->addBasicList();
            // Submenu
            $this->navLists = $settingsList->addItem(lang('create_new_export'), ee()->sie->url('export_form'));
            $this->navSettings = $sidebar->addHeader(lang('export_general_settings'), ee()->sie->url('export_general_settings'));
            // Navbar main LI
            $this->navSettings = $sidebar->addHeader(lang('import_list'), ee()->sie->url('import_index'));
            // Submenu
            $settingsList = $this->navSettings->addBasicList();
            $this->navLists = $settingsList->addItem(lang('create_new_import'), ee()->sie->url('import_form'));

        }else{

            //ee6
            $this->navSettings = $sidebar->addItem(lang('export_list'), ee()->sie->url());
            $this->navSettings = $sidebar->addItem(lang('create_new_export'), ee()->sie->url('export_form'));
            $sidebar->addDivider();
            $this->navSettings = $sidebar->addItem(lang('export_general_settings'), ee()->sie->url('export_general_settings'));
            $sidebar->addDivider();
            $this->navSettings = $sidebar->addItem(lang('import_list'), ee()->sie->url('import_index'));
            $this->navSettings = $sidebar->addItem(lang('create_new_import'), ee()->sie->url('import_form'));
            $sidebar->addDivider();

        }


        // $this->navSettings = $sidebar->addHeader(lang('import_general_settings'), ee()->sie->url('import_general_settings'));

        /* To get dynamic menu in the addon */    
        $this->customSidebar();

        if(SIE_APP_VER_L6){
            $this->navSettings = $sidebar->addHeader('Subscription', ee('CP/URL','addons/settings/smart_import_export/subscription'));
            $this->navSettings = $sidebar->addHeader(lang("documentation"), ZEAL_SIE_DOC_URL)->urlIsExternal(true);
        }else{
            $this->navSettings = $sidebar->addItem('Subscription', ee('CP/URL','addons/settings/smart_import_export/subscription'));
            $sidebar->addDivider();
            $this->navSettings = $sidebar->addItem(lang("documentation"), ZEAL_SIE_DOC_URL)->urlIsExternal(true);
        }
    }

    /* To get dynamic menu in the addon */    
    function customSidebar(){
        /*$sidebar = ee('CP/Sidebar')->make();
        $this->navSettings  = $sidebar->addHeader('Test Link', '');*/
    }

    /**
    * Run Import during the batches
    * 
    * @param $token (Optional), $status, $batch
    * @return void
    */
    function runImport($token = "", $status = "", $batch = "")
    {

        if($token == "") { $token  = ee()->input->get_post('token'); }
        if($token == "") { show_error(lang('token_not_set')); }
        
        if($batch == "") { $batch  = ee()->input->get_post('batch'); }
        if($batch == "") { $batch = 0; }

        if($status == "") { $status = ee()->input->get_post('status'); }
        if($status == "") { ee()->smie->unsetSession(); }

        $ret = ee()->smie->processRunImport($token, $batch);

        if($ret !== false)
        {

            if($ret['return'] === true && $ret['status'] == "completed")
            {
                ee()->functions->redirect(ee()->sm->url('run_import_success', array('token' => $token, 'status' => $ret['status'])));
            }
            elseif($ret['return'] === true)
            {
                ee()->functions->redirect(ee()->sm->url('run_import_success', array('token' => $token, 'status' => $ret['status'], 'batch' => $ret['batch'])));
            }

        }

    }

    /**
    * First entry point of Import process
    * 
    * @param $importID, $token, $status, $batches, $offset
    * @return void
    */
    public function makeImport($importID = 0, $token = "", $status = "", $batches = 2, $offset = 0){
        
        if($offset == "") { $offset  = ee()->input->get_post('offset'); }
        if($offset == "") { $offset = 0; }

        if($token == "") { $token  = ee()->input->get_post('token'); }
        if($token == "") { show_error(lang('token_not_set')); }
        // $token = "";
        
        if($batches == "") { $batches  = ee()->input->get_post('batches'); }
        if($batches == "") { $batches = 0; }

        if($status == "") { $status = ee()->input->get_post('status'); }
        if($status == "") { ee()->sie->unsetSession(); }

        $ret = ee()->sie->handleMakeImport($importID, $token, $batches, $status, $offset);

        if($ret !== false)
        {
            if($ret['return'] === true && $ret['status'] == "completed")
            {
                ee()->functions->redirect(ee()->sie->url('run_import_success', array('import_id' => $importID, 'token' => $token, 'status' => $ret['status'])));
            }
            elseif($ret['return'] === true)
            {
                ee()->functions->redirect(ee()->sie->url('run_import_success', array('import_id' => $importID, 'token' => $token, 'status' => 'pending', 'batches' => $ret['batches'], 'offset' => $ret['offset'])));
            }

        }

    }

    /*Run Import success method*/
    function run_import_success($import_id = 0, $token = "", $status = "", $batch = 2, $offset = 0)
    {

        $this->vars['import_id']    = $import_id;
        $this->vars['token']    = $token;
        $this->vars['status']   = $status;
        $this->vars['batch']    = $batch;
        $this->vars['offset']    = $offset;

        $this->vars = ee()->sie->handleImportSuccess($this->vars);

        
        /*Return to the view*/
        return array(
            'heading'    => lang('run_import_success'),
            'body'       => ee('View')->make('smart_import_export:run_import_success')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export')->compile() => lang('label_title_index')
                ),
            );

    }
    
    /**
    * Generate Import Form
    * 
    * @param $importID
    * @return void
    */
    public function importForm($importID = 0){

        if(isset($_POST) && is_array($_POST) && count($_POST))
        {
            /* Validate and submit data */
            $ret = ee()->sie->handleImportFormPost($importID);
            if($ret === true)
            {
                ee()->functions->redirect(ee()->sie->url('import_configure',array($_POST['import'])));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }
        }

        $this->vars['method']    = "smart_import_export_default_method";
        $this->vars['import_id']  = $importID;
        $this->vars = ee()->sie->handleAddNewImport($this->vars);
        return array(
            'heading'    => $this->vars['cp_page_title'],
            'body'       => ee('View')->make('smart_import_export:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export/')->compile() => lang('smart_import_export_module_name')
            ),
        );
    }

    /**
    * Configure the import settings
    * 
    * @param $importID
    * @return void
    */
    public function importconfigure($importID=0){
        @session_start();
        if(isset($_POST) && is_array($_POST) && count($_POST))
        {
            /* Validate and submit data */
            @session_start();
            $ret = ee()->sie->handleImportConfigureFormPostFinal($_POST);
            if($ret === true)
            {
                unset($_SESSION['Smart_import_export']);
                ee()->sie->_remove('setting');
                ee()->sie->_remove('configure');
                ee()->functions->redirect(ee()->sie->url('import_index'));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }
        }

        $this->vars['method']    = "smart_import_export_default_method";
        $this->vars['import_id']  = $importID;
        if($importID > 0){
            @session_start();
            unset($_SESSION['Smart_import_export']);
        }
        $this->vars = ee()->sie->handleImportConfigure($this->vars);
        return array(
            'heading'    => $this->vars['cp_page_title'],
            'body'       => ee('View')->make('smart_import_export:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export/')->compile() => lang('smart_import_export_module_name')
            ),
        );
    }

    /**
    * Import
    * 
    * @param $importID
    * @return void
    */
    public function import($importID=0){

        if(isset($_POST) && is_array($_POST) && count($_POST))
        {
            /* Validate and submit data */
            $ret = ee()->sie->handleImportFormPostFinal();
            if($ret === true)
            {
                ee()->functions->redirect(ee()->sie->url('import_configure'));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }
        }

        $this->vars['method']    = "smart_import_export_default_method";
        $this->vars['import_id']  = $importID;
        $this->vars = ee()->sie->handleAddNewImportFinal($this->vars);
        return array(
            'heading'    => $this->vars['cp_page_title'],
            'body'       => ee('View')->make('smart_import_export:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_import_export/')->compile() => lang('smart_import_export_module_name')
            ),
        );
    }

    public function subscription() {
        if(isset($_POST) && count($_POST) > 0){
            $rules = array(
                'subscription_key' => 'required',
            );
            
            $result = ee('Validation')->make($rules)->validate($_POST);
            $output = [];
            if ($result->isValid()) {
                $headers = array(
                    'X-Requested-With: XMLHttpRequest'
                );
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $this->subscription_api_url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                $data = array(
                    'api_key' =>ee()->input->post('subscription_key'),
                    'sku' => $this->sku,
                );
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                curl_setopt($curl,CURLOPT_HTTPHEADER, $headers); 
                $output = unserialize(curl_exec($curl));
                
                $api_response = '';
                if($output->error == 'valid') {
                    $api_response = '1';
                }
                if($output->error == 'expired') {
                    $api_response = '0';
                }
                curl_close($curl);
               
                if($output->error == 'invalid'){
                    ee('CP/Alert')->makeInline('shared-form')->asIssue()->withTitle(lang('invalid_subscription_key'))->addToBody(lang('invalid_subscription_key_desc'))->canClose()->now();
                } else {
                    if (ee()->db->simple_query("INSERT INTO `".ee()->db->dbprefix."zeal_subscription` (subscription_id, addon_name, subscription_key, api_response, last_api_call_date) VALUES('".$data['sku']."','".$this->addon_module_name."','".$data['api_key']."',$api_response,".strtotime(date('Y-m-d H:i:s')).") ON DUPLICATE KEY UPDATE    
                    subscription_key='".$data['api_key']."',api_response=$api_response"))
                        if($output->error == 'valid') {
                            ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('valid_subscription_key'))->addToBody(lang('subscription_key_added_successfully'))->canClose()->now();
                        } elseif($output->error == 'expired') {
                            ee('CP/Alert')->makeInline('shared-form')->asIssue()->withTitle(lang('valid_expired_subscription_key'))->addToBody(lang('subscription_key_added_successfully'))->canClose()->now();
                        }
                    else
                    {
                        ee('CP/Alert')->makeInline('shared-form')->asIssue()->withTitle(lang('subscription_key_fail'))->addToBody(lang('subscription_key_fail_desc'))->canClose()->now();
                    }
                }
            } else {
                ee('CP/Alert')->makeInline('shared-form')->asIssue()->withTitle(lang('subscription_key_required'))->addToBody(lang('subscription_key_required_desc'))->canClose()->now();
            }
        }
        $query = ee()->db->query("SELECT `subscription_key` from ".ee()->db->dbprefix."zeal_subscription where `addon_name` = '".$this->addon_module_name."'");
        $row = $query->row();
        
        // For displaying expired date html on page load as well
            $check_expired_date = $this->check_subscription();

            if(isset($check_expired_date) && $check_expired_date!='') {
                $expired_date = array(
                    'fields' => array(
                        'expired_date' => array(
                            'type'      => 'html',
                            'content'   => 'Your subscription will be expire on <i><b>' . date("F j, Y", strtotime($check_expired_date)) .'.</b></i>'
                        ),
                    ),
                );
            }
        // For displaying expired date html end

        $this->vars['sections'] = array(
            array(
                array(
                    'title' => 'Subscription',
                    'desc' => 'User needs to submit original subscription on live website.',
                    'fields' => array(
                        'subscription_key' => array(
                            'type'      => 'text',
                            'value'     => isset($row->subscription_key) && $row->subscription_key !='' ? $row->subscription_key : '',
                            'required'  => TRUE,
                        )
                    ),
                    'attrs' => array(
                        'class' => 'subscription',
                    ),
                ),
            )
        );
        
        // Merge subscription key field with expired date html
            if(isset($check_expired_date) && $check_expired_date!='') {
                $this->vars['sections'][0][] = $expired_date;
            }
        // Merge subscription key field with expired date html end
        
        $this->vars += array(
            'base_url'              => ee()->sie->url('subscription'),
            'cp_page_title'         => lang('Subscription'),
            'save_btn_text'         => 'Activate Subscription',
            'save_btn_text_working' => 'Activating...'
        );
        return array(
            'heading'    => 'Subscription',
            'body'       => ee('View')->make('ee:_shared/form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/'.$this->module.'/')->compile() => lang($this->module.'_module_name')
            ),
        ); 
    }

    public function check_subscription() {
        $query = ee()->db->query("SELECT `subscription_key`, `api_response` from ".ee()->db->dbprefix."zeal_subscription where `addon_name` = '".$this->addon_module_name."'");
        $row = $query->row();
        if($query->num_rows() == 1) { 
            $headers = array(
                'X-Requested-With: XMLHttpRequest'
            );
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->subscription_api_url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
            $data = array(
                'api_key' => $row->subscription_key,
                'sku' => $this->sku
            );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl,CURLOPT_HTTPHEADER, $headers); 
            $output = unserialize(curl_exec($curl));
            $api_response = '';
            if($output->error == 'valid') {
                $api_response = '1';
            }
            if($output->error == 'expired') {
                $api_response = '0';
            }
            curl_close($curl);

            return $output->exp_date;
        }
    }

    

    
}

/* End of file mcp.smart_import_export.php */
/* Location: /system/expressionengine/third_party/smart_import_export/mcp.smart_import_export.php */ 
?>