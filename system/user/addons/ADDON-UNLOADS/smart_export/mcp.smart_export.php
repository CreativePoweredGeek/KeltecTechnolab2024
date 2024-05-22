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

require PATH_THIRD.'smart_export/config.php';
class Smart_export_mcp
{

    /* Important globel variables */ 
    public $site_id;
    public $member_id;
    public $group_id;
    public $table_rows;

    /* Constructor */
    public function __construct()
    {

        /* Assign values to golebel variables */
        $this->site_id      = ee()->config->item("site_id");
        $this->member_id    = ee()->session->userdata('member_id');
        $this->group_id     = ee()->session->userdata('group_id');
        $this->table_rows   = 25;

        /* Load helpful Libraries */
        ee()->load->library('se_lib', null, 'se');

        /* Add necessary data and files to head and footer */
        ee()->cp->add_to_head('<link rel="stylesheet" href="'.URL_THIRD_THEMES.'smart_export/css/screen.css" type="text/css" media="screen" />');
        ee()->cp->add_to_foot("<script src='".URL_THIRD_THEMES."smart_export/js/settings.js'></script>");
        ee()->cp->add_to_foot("
            <script type='text/javascript'>
                var se_url = '". ee()->se->url('get_channel_fields_from_channel') ."';
                var loadingImage = '". URL_THIRD_THEMES."smart_export/images/indicator.gif" ."';
            </script>"
        );

    }

    /**
    * The module's Control Panel default controller
    * Export data setting page
    * 
    * @return void
    */
    function index()
    {

        /* Basic dependancy of Backend forms */
        $this->startup_form();

        /* Create table of saved exports */
        $this->vars = ee()->se->createExportTable($this->vars, $this->table_rows);
        
        /* Popup Title */
        $this->vars['popup_data']['title']          = lang('export_popup_title');
        $this->vars['popup_data']['downloadTitle']  = lang('download_popup_title');
        
        // return ee()->load->view('index', $this->vars, TRUE);
        return array(
            'heading'    => lang('export_list'),
            'body'       => ee('View')->make('smart_export:index')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_export')->compile() => lang('lable_title_index')
                ),
            );

    }

    function general_settings()
    {

        /* Basic dependancy of Backend forms */
        $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {

            $ret = ee()->se->handleGeneralSettingsFormPost();

            if($ret === true)
            {
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('general_settings_udpated_success'))->defer();
                ee()->functions->redirect(ee()->se->url('general_settings'));
            }
            else
            {
                $this->vars['errors'] = $ret;
            }

        }

        $this->vars = ee()->se->handleGeneralSettingsForm($this->vars);

        return array(
            'heading'    => lang('general_settings'),
            'body'       => ee('View')->make('smart_export:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_export')->compile() => lang('lable_title_index')
                ),
            );

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
            ee()->se->handleExportFormPost();
            ee()->functions->redirect(ee()->se->url());
        }

        /*Set title of the page*/
        ee()->view->cp_page_title = lang('lable_title_index');

        /*Basic dependancy of Backend forms*/
        $this->startup_form();

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
            $this->vars['data']             = ee()->seModel->checkExportToken($this->vars['token']);
            $this->vars['data']             = $this->vars['data'][0];
            $this->vars['data']['settings'] = unserialize(base64_decode($this->vars['data']['settings']));
            
            /* Check given token is exists in our system or not. If not, give user an error. */
            if($this->vars['data'] === false)
            {
                show_error(lang('wrong_token'));
            }

            /* Get all statuses of given channel */
            $this->vars["status"] = ee()->seModel->getStatusesFromChannelID($this->vars['data']['settings']['channel_id']);

            $temp = ee()->se->getAllChannelFields($this->vars['data']['settings']['channel_id']);
            if($temp !== false){
                $this->vars['custom_fields'] = $temp;
                unset($temp);
            }

            /* Find if given channel is assigned any category or not. So we can have an option to export categories in export form */
            $this->vars['categories']       = ee()->seModel->getCategoryGroups($this->vars['data']['settings']['channel_id'], true);

        }

        $this->vars['method']           = "smart_export";
        $this->vars['loading_image']    = URL_THIRD_THEMES."smart_export/images/indicator.gif";
        $this->vars['callback']         = ee()->se->url('export_form');
        $this->vars['result']           = ee()->seModel->getAllChannels($this->site_id);
        $this->vars['default_fields']   = ee()->seModel->getDefaultFields();
        $this->vars['seo_lite']         = ee()->seModel->checkModuleInstalled("Seo_lite");
        $this->vars['pages']            = ee()->seModel->checkModuleInstalled("Pages");
        
        ee()->se->loadDatePicker();
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
        $data = ee()->seModel->checkExportToken($token);
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
                ee()->seModel->increaseCounter($token);
            }
        }else{
            ee()->seModel->increaseCounter($token);
        }

        /* Main generate export function */
        $ret = ee()->se->generateExport($data, $type);

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
                ee()->seModel->deleteExport($removeIds);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_deleted_successfully'))->defer();
                ee()->functions->redirect(ee()->se->url());

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

        /*Basic dependency of Backend forms*/
        $this->startup_form();
        
        $retArray = array('error' => lang('channel_not_exists'));

        /*Fetch channel ID from POST*/
        $this->vars['channel_id'] = ee()->input->get_post('channel_id', true);
        if($this->vars['channel_id'] == ""){ echo json_encode($retArray); exit(); }

        $ret = ee()->seModel->validateChannel($this->vars['channel_id']);
        if($ret === false) { echo json_encode($retArray); exit(); }

        $temp = ee()->se->getAllChannelFields($this->vars['channel_id']);
        if($temp !== false){
            $this->vars['result'] = $temp;
            unset($temp);
        }
        
        /* Get all statuses of given channel */
        $this->vars["status"] = ee()->seModel->getStatusesFromChannelID($this->vars['channel_id']);
        
        /* Find if given channel is assigned any category or not. So we can have an option to export categories in export form */
        $this->vars['categories']   = ee()->seModel->getCategoryGroups($this->vars['channel_id'], true);

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

        $this->vars = array();

        $this->vars['tabs'] = $this->tabs();

        /*CSRF and XID is same after EE V 2.8.0. For previous versions (Backward compatibility)*/
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

    }

    /**
    * Add Tabbing in Module
    * 
    * @return Tab array (Side menu in module settings)
    */
    public function tabs()
    {

        /*$tabs   = array(
            'smart_export' => array(
                'link'      => ee()->se->url('index'),
                'title'     => lang('smart_export'),
                'new_tab'   => FALSE
                ),
            'docs'          => array(
                'link'      => ZEAL_SE_DOC_URL,
                'title'     => lang('documentation'),
                'new_tab'   => TRUE
                ),
            );

        return $tabs;*/
        // Create menu
        $sidebar = ee('CP/Sidebar')->make();

        // Header
        $sidebar->addHeader(lang('lable_title_index'));
        
        // Navbar main LI
        $this->navSettings = $sidebar->addHeader(lang('export_list'), ee()->se->url());
        // Submenu
        $settingsList = $this->navSettings->addBasicList();
        $this->navLists = $settingsList->addItem(lang('create_new_export'), ee()->se->url('export_form'));

        $this->navSettings = $sidebar->addHeader(lang('general_settings'), ee()->se->url('general_settings'));

        $this->navSettings = $sidebar->addHeader("documentation", ZEAL_SE_DOC_URL);

    }

}
?>