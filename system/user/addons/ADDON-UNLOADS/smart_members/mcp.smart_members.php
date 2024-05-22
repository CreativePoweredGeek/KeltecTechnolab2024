<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Zealousweb\SmartMembersPRO\ZealCore\EE_Mcp;
class Smart_members_mcp extends EE_Mcp 
{

    /* Basic settings form */
    public function index()
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {

            $ret = ee()->sm->handleBasicSettingsFormPost($_POST);
            if($ret === true)
            {
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('settings_updated_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url());
            }
        
        }

        $this->vars = ee()->sm->handleBasicSettingsForm($this->vars);
        
        return array(
            'heading'    => lang('nav_basic_settings'),
            'body'       => ee('View')->make('smart_members:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members/')->compile() => lang('lable_title_index')
            ),
        );
        
    }
    function customSidebar(){
        /*$sidebar = ee('CP/Sidebar')->make();
        $this->navSettings  = $sidebar->addHeader('Test Link', '');*/
    }
    public function memberPreferences()
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {
            $ret = ee()->sm->handlememberPreferencesFormPost($_POST);
            if($ret === true)
            {
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('preferences_saved_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('member_preferences'));
            }
        }

        $this->vars = ee()->sm->handlememberPreferencesForm($this->vars);
        
        return array(
            'heading'    => lang('member_preferences_title'),
            'body'       => ee('View')->make('smart_members:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
            ),
        );

    }

    /*Social settings form*/
    function socialSettings($form_id = "")
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();
        if(isset($_POST) && count($_POST) > 0)
        {
            $ret = ee()->sm->handleSocialSettingsFormPost();
            if($ret === true)
            {
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('social_settings_updated_success'))->defer();
                ee()->functions->redirect(ee()->sm->url('social_settings_list'));
            }
        }

        if($form_id == "" && ee()->input->get('form_id') != "")
        {
            $form_id = ee()->input->get('form_id');
        }

        if($form_id == "")
        {
            show_error(lang('form_id_missing'));
        }

        $this->vars['form_id']  = $form_id;
        $this->vars             = ee()->sm->handleSocialSettingsForm($this->vars);
        
        return array(
            'heading'    => lang($this->vars['heading']),
            'body'       => '<div class="'.(($form_id == "all") ? 'sl_setting_form' : 'single_setting_form').'">'. ee('View')->make('smart_members:_shared_form')->render($this->vars) .'</div>',
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index'),
                ee('CP/URL', 'addons/settings/smart_members/social_settings_list')->compile() => lang('social_settings'),
            ),
        );

    }

    /*List all social settings in table*/
    public function socialSettingsList()
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {

            if(isset($_POST['sl_callback_url']))
            {
                /*callback method for change callback url*/
                $data = array('id' => 1, 'sl_callback_url' => $_POST['sl_callback_url']);
                ee()->smModel->updateBasicForm($data);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('callback_url_updated_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('social_settings_list'));
            }

        }

        $this->vars = ee()->sm->handleSocialSettingsList($this->vars);
        
        return array(
            'heading'    => lang('social_settings'),
            'body'       => ee('View')->make('smart_members:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
            ),
        );

    }

    /*Export member list settings function*/
    function exportMembers()
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && ! empty($_POST))
        {
            $action = ee()->input->post('bulk_action', true);
            if($action == "remove")
            {   
                $removeIds = ee()->input->post('selection', true);
                ee()->ieModel->deleteExport($removeIds);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('export_deleted_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('export_members'));
            }
        }

        /* Create table of saved exports */
        $this->vars = ee()->smie->createExportTable($this->vars, $this->table_rows);


        /* Popup Title */
        $this->vars['popup_data']['title']          = lang('export_popup_title');
        $this->vars['popup_data']['downloadTitle']  = lang('download_popup_title');
        $this->vars['heading']                      = lang('export_list');

        // return ee()->load->view('index', $this->vars, TRUE);
        return array(
            'heading'    => lang('export_list'),
            'body'       => ee('View')->make('smart_members:_table')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
            ),
        );

    }

    /*Export first form*/
    function exportForm($token = "")
    {

        // $this->startup_form();
        
        /* If form submitted, save the form or display the errors if found any. */
        if(isset($_POST) && count($_POST) > 0)
        {
            ee()->smie->handleExportFormPost();
            ee()->functions->redirect(ee()->sm->url('export_members'));
        }

        if($token == "")
        {
            $token = ee()->input->get_post('token');
        }

        if($token != "")
        {
            $this->vars['data'] = ee()->ieModel->checkExportToken($token);
            if($this->vars['data'] === false)
            {
                show_error(lang('wrong_token'));
            }

            $this->vars['data']     = $this->vars['data'][0];
            $this->vars['data']['settings'] = unserialize(base64_decode($this->vars['data']['settings']));

        }

        $this->vars['token']    = $token;
        
        $this->vars['callback']                 = ee()->sm->url('export_form');
        $this->vars['member_groups']            = ee()->ieModel->getMemberGroups();
        $this->vars['member_static_fields']     = ee()->ieModel->getMemberStaticFields();
        $this->vars['member_dynamic_fields']    = $this->member_dynamic_fields;
        
        return array(
            'heading'    => lang('export_form_title'),
            'body'       => ee('View')->make('smart_members:export_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
                ),
            );

    }

    /*Download export function to generate export file and download*/
    function downloadExport($token = "")
    {

        if($token == "")
        {
            $token = ee()->input->get_post('token', true);
        }

        if($token == "")
        {
            show_error(lang('token_not_set'));
        }

        $data = ee()->ieModel->checkExportToken($token);
        if($data === false)
        {
            show_error(lang('wrong_token'));
        }

        $data = $data[0];
        $data['settings'] = unserialize(base64_decode($data['settings']));
        
        ee()->ieModel->increaseCounter($token);
        ee()->smie->generateExport($data);

    }

    function importMembers()
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && ! empty($_POST))
        {
            $action = ee()->input->post('bulk_action', true);
            if($action == "remove")
            {   
                $removeIds = ee()->input->post('selection', true);
                ee()->ieModel->deleteImport($removeIds);
                ee('CP/Alert')->makeInline('shared-form')->asSuccess()->withTitle(lang('settings_updated'))->addToBody(lang('import_deleted_successfully'))->defer();
                ee()->functions->redirect(ee()->sm->url('import_members'));
            }
        }

        /* Create table of saved exports */
        $this->vars = ee()->smie->createImportListTable($this->vars, $this->table_rows);

        /* Popup Title */
        $this->vars['popup_data']['title']          = lang('import_popup_title');
        $this->vars['popup_data']['downloadTitle']  = lang('import_popup_title');
        $this->vars['heading']                      = lang('import_list');
        
        if( ! ini_get('allow_url_fopen') )
        {
            ee('CP/Alert')->makeInline('server_error')->asIssue()->withTitle(lang('server_error_title'))->addToBody(lang('allow_url_fopen_error'))->now();
        }

        return array(
            'heading'    => lang('import_list'),
            'body'       => ee('View')->make('smart_members:_table')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
            ),
        );

    }

    function importForm($token = "")
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {

            $ret = ee()->smie->handleImportFormPost($_POST);
            if($ret !== true)
            {
                $this->vars['errors'] = $ret;
            }
        
        }

        if($token == "")
        {
            $token = ee()->input->get_post('token');
        }

        if($token != "")
        {
            $this->vars['data'] = ee()->ieModel->checkImportToken($token);
            if($this->vars['data'] === false)
            {
                show_error(lang('wrong_token'));
            }

            $this->vars['data']             = $this->vars['data'][0];
            $this->vars['data']['settings'] = unserialize(base64_decode($this->vars['data']['settings']));
            
        }

        $this->vars['token']    = $token;

        $this->vars = ee()->smie->handleImportForm($this->vars);
        
        return array(
            'heading'    => lang('import_members'),
            'body'       => ee('View')->make('smart_members:_shared_form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members/')->compile() => lang('lable_title_index')
            ),
        );

    }

    function chooseMemberFields($token = "")
    {

        /*Basic dependancy of Backend forms*/
        // $this->startup_form();

        if(isset($_POST) && count($_POST) > 0)
        {
            $ret = ee()->smie->handleChooseMemberFieldsFormPost();
            if($ret !== true)
            {
                $this->vars['errors'] = $ret;
            }
        }

        if($token == "")
        {
            $token = ee()->input->get_post('token');
        }

        if($token == "")
        {
            show_error(lang('token_not_set'));
        }

        $this->vars['token'] = $token;

        $this->vars = ee()->smie->handleChooseMemberFieldsForm($this->vars);

        return array(
            'heading'    => lang('choose_import_fields'),
            'body'       => '<div class="sl_setting_form all-open">' . ee('View')->make('smart_members:_shared_form')->render($this->vars) . '</div>',
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members/')->compile() => lang('lable_title_index')
            ),
        );

    }

    /*Run import method to import the data from CSV or XML*/
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

    /*Run Import success method*/
    function run_import_success($token = "", $status = "", $batch = "")
    {

        // $this->startup_form();

        $this->vars['token']    = $token;
        $this->vars['status']   = $status;
        $this->vars['batch']    = $batch;

        $this->vars = ee()->smie->handleImportSuccess($this->vars);

        
        /*Return to the view*/
        return array(
            'heading'    => lang('run_import_success'),
            'body'       => ee('View')->make('smart_members:run_import_success')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
                ),
            );

    }

    /*Member Fields list page method*/
    function memberFields()
    {

        if(SM_APP_VER_L6){
            if ( ! ee()->cp->allowed_group('can_admin_mbr_groups'))
            {
                show_error(lang('unauthorized_access'), 403);
            }
        }
        else{
            //for ee6
            if ( ! ee('Permission')->hasAll('can_admin_mbr_groups') )
            {
                show_error(lang('unauthorized_access'), 403);
            }
        }

        if(isset($_POST) && is_array($_POST) && count($_POST) > 0)
        {
            $action = ee()->input->post('bulk_action', true);
            if($action == "remove")
            {
                ee()->mf->handleMemberFieldsListPost();
                ee()->functions->redirect(ee()->sm->url('memberFields'));
            }
        }
        /*Set title of the page*/
        ee()->view->cp_page_title = lang('sm_member_fields');
        ee()->lang->loadfile('members');
        ee()->lang->loadfile('channel');

        /*Default Settings*/
        // $this->startup_form();

        $this->vars             = ee()->mf->handleMemberFieldsList($this->vars, $this->table_rows);
        $this->vars['heading']  = lang('sm_member_fields');
        $this->vars['bg']       = "normal";

        return array(
            'heading'    => lang('sm_member_fields'),
            'body'       => ee('View')->make('smart_members:_table')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members')->compile() => lang('lable_title_index')
                ),
            );

    }

    /*Create new member field method*/
    function createMemberField($fieldID = NULL)
    {

        /*Set title of the page*/
        ee()->view->cp_page_title = lang('sm_member_fields');
        ee()->lang->loadfile('members');
        ee()->lang->loadfile('channel');

        /*Default Settings*/
        // $this->startup_form();
        $this->vars['field_id'] = $fieldID;
        $this->vars = ee()->mf->handleCreateMemberFieldForm($this->vars);

        return array(
            'heading'    => $this->vars['cp_page_title'],
            'body'       => ee('View')->make('ee:_shared/form')->render($this->vars),
            'breadcrumb' => array(
                ee('CP/URL', 'addons/settings/smart_members/')->compile() => lang('lable_title_index')
            ),
        );

    }


    // /*Startup form method to load basic dependancies*/
    // function startup_form()
    // {
    //     $this->vars = array();
    //     $this->vars['tabs'] = $this->tabs();
    //     $this->vars['csrf_token'] = XID_SECURE_HASH;
    //     $this->vars['xid'] = XID_SECURE_HASH;
    // }

}
?>