<?php if ( ! defined('BASEPATH') ) exit('No direct script access allowed');

require PATH_THIRD.'smart_members/config.php';

Class Smart_members_ext
{
	public $name            = SM_NAME;
	public $version         = SM_VER;
	public $description     = '';
	public $settings_exist  = 'n';
	public $docs_url        = SM_DOC_URL;
	public $user_base       = '';
	public $settings        = array();
	public $required_by     = array('module');

	function __construct($settings = '')
    {
        /*Define settings*/
        $this->settings = $settings;

        /*Load helpful libraries*/
		ee()->load->library('sm_lib', null ,'sm');

	}

	function activate_extension(){}
    
    function disable_extension(){}

    function update_extension(){}

    /*Send user email after successfully validate account by admin*/
    public function cp_members_validate_members()
    {
        return ee()->sm->handleValidateMemberHook();
    }

    /*Send user email after successfully validate account by themselves*/
    public function member_register_validate_members()
    {
        $memberID  = ee()->sm->handleValidateMemberHook(true);

        if($memberID !== "")
        {

            $formSettings = ee()->smModel->getFormSettings();
            if(! isset($formSettings['self_activation']))
            {
                return true;
            }

            $formSettings['self_activation'] = @unserialize(@base64_decode($formSettings['self_activation']));

            if( ! ( isset($formSettings['self_activation']['override']) && $formSettings['self_activation']['override'] == "Y" ) )
            {
                return true;
            }

            if(isset($formSettings['self_activation']['auto_login']) && $formSettings['self_activation']['auto_login'] == "Y")
            {
                $selectWrapper = array();
                $selectWrapper = ee()->smModel->memberStaticFields(array('member_id' => $memberID), "*");

                if($selectWrapper === false)
                {
                    ee()->output->show_user_error(false, ee()->sm->errorPrefix(array('member' => lang('no_member_available'))));
                }
                
                $row = (object) $selectWrapper[0];
                unset($selectWrapper);

                // ee6 => no need to change we get role_id in group_id
                if(! ($row->group_id == 0 || $row->group_id == 2 || $row->group_id == 4))
                {
                    ee()->load->library('auth');
                    $sess = new Auth_result($row);
                    
                    /*Create session*/
                    $sess->start_session();
                }
                else
                {
                    return true;
                }
            }

            /*Generating return URL*/
            $return     = isset($formSettings['self_activation']['redirect']) ? $formSettings['self_activation']['redirect'] : "";
            if($return == "")
            {
                $return_url = ee()->config->site_url();
            }
            else
            {
                $return_url = ee()->functions->create_url($return);
            }
            
            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $return_url = str_replace('http://', $protocol, $return_url);

            ee()->functions->redirect($return_url);

        }
        else
        {
            return true;
        }

    }

}
?>