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
* @filesource   ./system/expressionengine/third_party/smart_import_export/mod.smart_import_export.php
*
*/
class Smart_import_export
{

    /* Important globel variables */ 
	public $errors;

    /* Constructor */
	function __construct()
	{
		ee()->lang->loadfile('smart_import_export');
		
        /*Load helpful models and libraries*/
        ee()->load->library('sie_lib', null, 'sie');

        // cron feature
        /* Neeful model classes */
        if(! class_exists('sie_model'))
        {
            ee()->load->model('sie_model','sieModel');
        }
    }

    /**
    * Export the data from outside of EE as action URL (Action method defined in UPD)
    * 
    * @return export page if ajax or exported file if normal export
    */
    function sie_export($source = "export")
    {
        $type = ee()->input->get_post('type', true);

        /*Fetch current member ID and token of export*/
		$member_id = ee()->session->userdata('member_id');
		$token = ee()->input->get_post('token', true);

		/*Throw error if token is not found*/
		if($token == "")
        {

            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('token_not_set'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
            	$this->errors['provider'] = lang('token_not_set');
    			ee()->sie->handlingMODErrors($source, $this->errors);
            }

        }

        /*Check the requested token is vaild or not*/
        $data = ee()->sieModel->checkExportToken($token);
        if($data === false)
        {

            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('wrong_token'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
            	$this->errors['provider'] = lang('wrong_token');
    			ee()->sie->handlingMODErrors($source, $this->errors);
            }

        }
        
        $data = $data[0];

        /*Check the conditional dependancies and throw error if not match criteria*/
        if($member_id == 0 && $data['download_without_login'] == "n")
        {

            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('export_download_login_error'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                $this->errors['provider'] = lang('export_download_login_error');
                ee()->sie->handlingMODErrors($source, $this->errors);
            }

        }

        /* Check if given export is private. If yes then is it call by authorized member? */
        if($data['type'] == 'private' && $member_id != $data['member_id'])
        {

            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('private_export'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                $this->errors['provider'] = lang('private_export');
                ee()->sie->handlingMODErrors($source, $this->errors);
            }

        } 

        /*Unserialize settings*/
        $data['settings'] = unserialize(base64_decode($data['settings']));
    	
        if($type == "ajax"){
            if(ee()->input->get('offset') == "" || ee()->input->get('offset') == 0){
                ee()->sieModel->increaseCounter($token);
            }
        }else{
        	/*Increase counter after download the export*/
        	ee()->sieModel->increaseCounter($token);
        }

        /*Generate export function*/
        $ret = ee()->sie->generateExport($data, $type);
        
        if($ret === false)
        {
            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('no_entries_found'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                $this->errors['provider'] = lang('no_entries_found');
                ee()->sie->handlingMODErrors($source, $this->errors);
            }
        }

        if($type == "ajax")
        {
            if(ee()->input->get('offset') == "" || ee()->input->get('offset') == 0){
                $extra_data = "";
                $vars       = array();

                /*Append css and js files for beeter view of page*/
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_import_export/css/bootstrap.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_import_export/css/jquery.dataTables.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_import_export/css/screen.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_import_export/js/jquery.min.js'></script>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_import_export/js/bootstrap.min.js'></script>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_import_export/js/jquery.dataTables.min.js'></script>\n";

                $vars['extra_data']         = $extra_data;
                $vars['loading_image']      = URL_THIRD_THEMES."smart_import_export/images/indicator.gif";
                $vars['output']             = json_decode(base64_decode($ret), true);
                
                /*Return to the view*/
                return ee()->load->view('ajax_export', $vars);
            }else{
                echo $ret;
                exit();
            }
        }

	}

    /**
    * Run export
    * 
    */
    function run()
    {
        $tagdata    = ee()->TMPL->tagdata;
        $tagparams  = ee()->TMPL->tagparams;
        if( ! ( isset($tagparams['token']) && $tagparams['token'] != "") )
        {
            if(isset($tagparams['id']) && $tagparams['id'] != "")
            {
                $tagparams['token'] = ee()->sieModel->getToken('id', $tagparams['id']);
            }
        }
        
        if(isset($tagparams) && is_array($tagparams))
        {
            foreach ($tagparams as $key => $value)
            {
                $_POST[$key] = $value;
            }
        }
        $_POST['xid'] = XID_SECURE_HASH;
        $_POST['csrf_token'] = XID_SECURE_HASH;
        return $this->sie_export();
    }

    /**
    * cron feature: import the data with template tag 
    */
    function run_import()
    {
        $tagparams  = ee()->TMPL->tagparams;
        $token = isset($tagparams['token']) ? trim($tagparams['token']) : '';
        return $this->import_action($token);
    }

    /**
    * cron feature: import the data with cron action url
    */
    function sie_import(){
        $token = ee()->input->get_post('token');
        echo $this->import_action($token);
    }

    /**
    * cron feature: cron common function
    */
    public function import_action($token){

        if(!empty($token)){

            //check valid import id or not
            $valid = ee()->sieModel->getImportDataByToken($token);
            if(count($valid) > 0){

                //set token and batches
                $importID = $valid[0]['id'];
                $batches = 0;

                //call import function 
                $ret = ee()->sie->handleMakeImport($importID, $token, $batches);

                $this->vars['import_id']    = $importID;
                $this->vars['token']    = $token;
                $this->vars['status']   = $ret['status'];
                $this->vars['batch']    = $ret['batches'];
                $this->vars['offset']    = $ret['offset'];
                $this->vars['use_in_cron']    = '';
                $this->vars = ee()->sie->handleImportSuccess($this->vars);

                extract($this->vars);

                //total memory usage
                $total_memory_usage = round(($total_memory_usage / (1024 * 1024)), 2);
             
                if($total_memory_usage <= 1024)
                {
                    $total_memory_usage = $total_memory_usage . " MB";
                }
                else
                {
                    $total_memory_usage = round(($total_memory_usage / 1024), 2) . " GB";
                }

                //total time taken
                if($total_time_taken > 60)
                {
                    $total_time_taken = round(($total_time_taken / 60), 2) . " " . lang('minutes') ;
                }
                else
                {
                    $total_time_taken = $total_time_taken . " " . lang('seconds');
                }

                //set print print data
                $print_data = '<p>===== '.lang('statastics').' =====</p>';
                $print_array = array(
                    'import_id'   =>  $import_id,
                    'total_row_to_perform_action'   =>  $total_entries,
                    'total_inserted_entries'   =>  $imported_entries,
                    'total_updated_entries'   =>  $updated_entries,
                    'total_re_created_entries'   =>  $recreated_entries,
                    'total_deleted_existing_entries'   =>  $deleted_existing_entries,
                    'total_skipped_entries'   =>  $skipped_entries,
                    /*'total_memory_usage'   =>  $total_memory_usage,
                    'total_time_taken_to_import'    => $total_time_taken,*/
                );

                foreach ($print_array as $key => $value) {
                    $print_data .= '<p>'.lang($key).': '.$value.' </p>';
                }

                return $print_data;


            }else{
                //set invalid error for the import Id
                $error = 'Please pass the valid token parameter for the import.';
                echo $error;
            }
        }else{
            //set empty error
            $error = 'Please pass the token parameter for the import.';
            echo $error;
        }

    }
}

/* End of file mod.smart_import_export.php */
/* Location: /system/expressionengine/third_party/smart_import_export/mod.smart_import_export.php */ 
?>