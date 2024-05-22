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
class Smart_export
{

    /* Important globel variables */ 
	public $errors;

    /* Constructor */
	function __construct()
	{
		ee()->lang->loadfile('smart_export');
		
        /*Load helpful models and libraries*/
        ee()->load->library('se_lib', null, 'se');
    }

    /**
    * Export the data from outside of EE as action URL (Action method defined in UPD)
    * 
    * @return export page if ajax or exported file if normal export
    */
    function se_export($source = "export")
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
    			ee()->se->handlingMODErrors($source, $this->errors);
            }

        }

        /*Check the requested token is vaild or not*/
        $data = ee()->seModel->checkExportToken($token);
        if($data === false)
        {

            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('wrong_token'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
            	$this->errors['provider'] = lang('wrong_token');
    			ee()->se->handlingMODErrors($source, $this->errors);
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
                ee()->se->handlingMODErrors($source, $this->errors);
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
                ee()->se->handlingMODErrors($source, $this->errors);
            }

        } 

        /*Unserialize settings*/
        $data['settings'] = unserialize(base64_decode($data['settings']));
    	
        if($type == "ajax"){
            if(ee()->input->get('offset') == "" || ee()->input->get('offset') == 0){
                ee()->seModel->increaseCounter($token);
            }
        }else{
        	/*Increase counter after download the export*/
        	ee()->seModel->increaseCounter($token);
        }

        /*Generate export function*/
        $ret = ee()->se->generateExport($data, $type);
        
        if($ret === false)
        {
            if($type == "ajax" && ! (ee()->input->get('offset') == "" || ee()->input->get('offset') == 0)){
                $error = array('error' => lang('no_entries_found'));
                echo base64_encode(json_encode($error));
                exit();
            }else{
                $this->errors['provider'] = lang('no_entries_found');
                ee()->se->handlingMODErrors($source, $this->errors);
            }
        }

        if($type == "ajax")
        {
            if(ee()->input->get('offset') == "" || ee()->input->get('offset') == 0){
                $extra_data = "";
                $vars       = array();

                /*Append css and js files for beeter view of page*/
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_export/css/bootstrap.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_export/css/jquery.dataTables.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<link href='".URL_THIRD_THEMES."smart_export/css/screen.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_export/js/jquery.min.js'></script>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_export/js/bootstrap.min.js'></script>\n";
                $extra_data .= "<script src='".URL_THIRD_THEMES."smart_export/js/jquery.dataTables.min.js'></script>\n";

                $vars['extra_data']         = $extra_data;
                $vars['loading_image']      = URL_THIRD_THEMES."smart_export/images/indicator.gif";
                $vars['output']             = json_decode(base64_decode($ret), true);
                
                /*Return to the view*/
                return ee()->load->view('ajax_export', $vars);
            }else{
                echo $ret;
                exit();
            }
        }

	}

    function run()
    {
        $tagdata    = ee()->TMPL->tagdata;
        $tagparams  = ee()->TMPL->tagparams;
        if( ! ( isset($tagparams['token']) && $tagparams['token'] != "") )
        {
            if(isset($tagparams['id']) && $tagparams['id'] != "")
            {
                $tagparams['token'] = ee()->seModel->getToken('id', $tagparams['id']);
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
        return $this->se_export();
    }

}
