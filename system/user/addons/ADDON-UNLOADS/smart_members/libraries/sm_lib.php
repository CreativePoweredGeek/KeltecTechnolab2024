<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use EllisLab\ExpressionEngine\Library\CP\Table;
class Sm_lib
{

    /* Important globel variables */ 
    public $form_errors;
    public $group_id;
    public $form_settings;
    public $member_fields;
    public $fields;
    public $member_id;
    public $old_output;
    public $site_id;
    public $memberOBJ;
    public $staticFields;
    public $dynamicFields;
    public $memberData;

    public function __construct()
    {

        /*Setup instance to this class*/
        $this->site_id = ee()->config->item("site_id");

        /* Neeful Helper classes */
        ee()->load->helper(array('security', 'string', 'form', 'url', 'text'));

        /* Neeful Library classes */
        ee()->load->library(array('upload','email'));
        if(! class_exists('smart_members_model'))
        {
            ee()->load->model('smart_members_model', 'smModel');
        }

        if(! class_exists('members'))
        {
            ee()->load->library('members');
        }
        if(! class_exists('member_fields_lib'))
        {
            ee()->load->library('member_fields_lib');
        }

        /* Neeful model classes */
        ee()->load->model('member_model');

        /* Neeful Language files */
        ee()->lang->loadfile('smart_members');

        /*Set group id to 0 default*/
        $this->group_id = 0;

        /* Current form setting variable */
        $this->form_settings = ee()->smModel->getFormSettings();

        /*Dynamic member fields*/
        $this->member_fields = ee()->smModel->getMemberFields();

        /*Logged in member ID*/
        $this->member_id = ee()->session->userdata('member_id');

        /*All possible fields of member form*/
        $this->fields = $this->setArray($this->registrationFieldInitialize("edit_profile"));

        $this->staticFields   = ee()->smModel->allStaticFields();
        $this->dynamicFields  = ee()->smModel->dynamicMemberFieldsWithID(true);


    }

    /**
    * Validate the submitted form
    * @param $staticMemberFields (Array of all member field that can exists)
    **/
    public function runValidation()
    {

        $result = ee()->smValidation->validator->validate($_POST);
        if($result->isValid())
        {
            return true;
        }
        else
        {
            /*$result->renderErrors();
            $result->getAllErrors();*/
            foreach ($result->getAllErrors() as $key => $value)
            {
                $this->form_errors['error:' . $key] = array_values($value)[0];
            }
        }

        if(is_array($this->form_errors) && count($this->form_errors) > 0)
        {
            return $this->form_errors;
        }
        else
        {
            return true;
        }

    }

    /**
    * Registration form Fields intialize
    * Gather all possible fields for registration including custom and static fields
    *
    * @param $pref (Value to inform function is use for registration, update or view profile to remove some extra fields)
    **/
    public function registrationFieldInitialize($pref="", $source="")
    {

        /*Get static fields*/
        $staticFields = $this->staticMemberFields();

        /*Get dynamic fields*/
        $dynamic_fields = ee()->smModel->dynamicMemberFields();

        /*If found any dynamic fields*/
        if($dynamic_fields !== false)
        {
            $staticFields = array_merge($staticFields,$dynamic_fields);
        }

        /*Return the filtered array of all possible fields for registration*/
        return $staticFields;

    }

    /**
    * Login form Fields intialize
    * Gather all possible fields for Login form
    **/
    public function loginFieldInitialize()
    {
        return array('email', 'username', 'password', 'auto_login', 'group_id', 'captcha', 'recaptcha');
    }

    /**
    * Social Login form Fields intialize
    * Gather all possible fields for social Login form
    **/
    public function socialLoginFieldInitialize()
    {
        return array('providers', 'captcha', 'recaptcha');
    }

    /**
    * Forgot password form Fields intialize
    * Gather all possible fields for Forgot password form
    **/
    public function forgotPasswordFieldInitialize($tagparams)
    {

        if(isset($tagparams['identification_field']))
        {

            switch ($tagparams['identification_field'])
            {
                case 'member_id':
                    return array('member_id', 'captcha' , 'recaptcha');
                    break;

                case 'username':
                    return array('username', 'captcha' , 'recaptcha');
                    break;
                
                default:
                    return array('email', 'captcha' , 'recaptcha');
                    break;
            }

        }
        else
        {
            return array('email', 'captcha' , 'recaptcha');
        }

    }

    /**
    * Reset password form Fields intialize
    * Gather all possible fields for Reset password form
    **/
    public function resetPasswordFieldInitialize()
    {
        return array('password','password_confirm', 'captcha' , 'recaptcha');
    }

    /**
    * Delete profile form Fields intialize
    * Gather all possible fields for Delete profile form
    **/
    public function deleteProfileFieldInitialize()
    {
        return array('password', 'captcha' , 'recaptcha');
    }

    /**
    * Base function for generate final fields array with errors and post data
    *
    * @param $fetch (Boolean)
    * @param $wrapErrors (Array of errors found after submit any form)
    * @param $tagdata (String data between pair of tag)
    * @param $staticFields (Array of all possible fields of the form)
    **/
    public function listFieldsInDetail($fetch = false, $wrapErrors, $tagdata, $staticFields, $source = "")
    { 

        if($fetch === true)
        {
            $this->memberData = $this->profileExtract(true, array('member_id' => $this->member_id));
        }

        /*Array initialize for the errors not define {error:fields} in front end*/
        $outerError = array();

        /*Array to set {error:fields} data*/
        $tagFields = array();

        /*If we are in POST*/
        if($_POST)
        {
            /*Unset file fields from post data*/
            unset($_POST['avatar_filename']);
            unset($_POST['photo_filename']);
            unset($_POST['sig_img_filename']);
            
            /*Save the data of POST to fields as well replace the {error:fields} array from null to error value*/
            foreach ($staticFields as $key) 
            {

                /*If field is array (multi select, checkboxes)*/
                if($val = ee()->input->get_post($key))
                {

                    /*Convert string to array*/
                    if(is_array($val))
                    {
                        $val = implode("\n", $val);
                    }
                    
                    /*save post value if found any value in post*/
                    $tagFields[$key] = $val;

                }
                else
                {
                    /*Assign value if found in member data. If not found assing NULL*/
                    $tagFields[$key] = ($this->memberData === "") ? "" : (isset($this->memberData[0][$key]) ? $this->memberData[0][$key] : ""); // add bracket for the addon version 4.0.3
                }

                /*Assign error string if found*/
                $tagFields['error:' . $key] = isset($this->form_errors['error:' . $key]) ? $this->bindErrors($this->form_errors['error:' . $key], $wrapErrors) : "";
                
                /*If errors found but no variable at front end to catch errors*/
                if(strlen(strchr($tagdata,"{error:" . $key . "}")) === 0 && isset($this->form_errors['error:' . $key]))
                {
                    $outerError['error:' . $key] = $this->form_errors['error:' . $key];
                }

            }

            /*If error tags are missing and field is required, error will throw in default EE format*/
            if(count($outerError) > 0)
            {

                /**
                * Hook will call when error of field found and catch variable doesn't exists in frontend
                * @param $source [form status i.e, "registration", "edit_profile", "forgot_password", "reset_password", "login"]
                * @param $outerError
                * @return $outerError
                */
                if (ee()->extensions->active_hook('sm_outer_error') === TRUE)
                {

                    $tmp = ee()->extensions->call('sm_outer_error', $source, $outerError);
                    if(ee()->extensions->end_script === TRUE) return;

                    if($tmp != "")
                    {
                        $outerError = $tmp;
                        unset($tmp);
                    }

                }

                if(count($outerError) > 0)
                {
                    return ee()->output->show_user_error(false, $this->errorPrefix($outerError));
                }

            }
            
            /*Return final array of all possible fields with their value*/
            return $tagFields;

        }
        else
        {

            /*Fresh form ? assign value if found in $this->memberData variable or else set to NULL*/
            foreach ($staticFields as $key)
            {
                $tagFields[$key] = ($this->memberData === "") ? "" : (isset($this->memberData[0][$key]) ? $this->memberData[0][$key] : "");
                $tagFields['error:' . $key] = ""; // add bracket for the addon version 4.0.3
            }

            /*Return final array of all possible fields with their value*/
            return $tagFields;

        }
        
    }

    /**
    * Validation initialize form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    * @param $source (String of main source i.e., 'registration','forgot_password','reset_password','edit_profile')
    * @return Array comes from another method (Can be true or bunch of errors)
    **/
    public function initializeFormValidation($param_array = array(), $source = "")
    {
        /*Form rule array initialization*/
        $form_rules = array();

        /*Identify rules defined by user in front end tag parameter*/
        foreach($param_array as $key => $value)
        {

            $exp_key = explode(':', $key);

            if ($exp_key[0] == 'rule') 
            {
                $form_rules[$exp_key[1]] = $value;
            }

        }

        /*Assign rules given by user in paramter {rule:field}*/
        foreach ($this->fields as $key => $value)
        {
            $this->fields[$key]['rules'] = isset($form_rules[$key]) ? $form_rules[$key] : '';
        }

        /*Assign the default rules to static member fields*/
        if($source == "forgot_password")
        {
            $this->forgotPasswordRules($param_array);
        }
        elseif ($source == "reset_password")
        {
            $this->resetPasswordRules($param_array);
        }
        elseif ($source == "registration")
        {
            $this->registrationRules($param_array);
        }
        elseif ($source == "edit_profile")
        {
            $this->editProfileRules($param_array);
        }
        elseif ($source == "delete_profile")
        {
            $this->deleteProfileRules($param_array);
        }
        elseif ($source == "login")
        {
            $this->loginRules($param_array);
        }
        elseif ($source == "social_login")
        {
            $this->socialLoginRules($param_array);
        }

        /**
        * Hook will call after initialize validation array
        * @param $source [form status i.e, "registration", "edit_profile", "forgot_password", "reset_password", "login"]
        * @param $this->form_settings
        * @return $this->form_settings
        */
        if (ee()->extensions->active_hook('sm_init_validation') === TRUE)
        {

            $tmp = ee()->extensions->call('sm_init_validation', $source, $this->form_settings);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp != "")
            {
                $this->form_settings = $tmp;
                unset($tmp);
            }

        }

        if(ee()->session->userdata('member_id') == 0 || (ee()->config->item('captcha_require_members') == "" || ee()->config->item('captcha_require_members') == "y"))
        {

            /*If Recaptcha API entered in backend and enabled from front end form. Generate recaptcha*/
            if($this->form_settings['enable_recaptcha'] == "Y" && $this->form_settings['recaptcha_site_key'] != "" && $this->form_settings['recaptcha_secret'] != "" && isset($param_array['enable_recaptcha']) && $param_array['enable_recaptcha'] == 'yes')
            {

                /*Prevent the default captcha not throws error*/
                ee()->config->config['require_captcha'] = 'n';
                
                $_POST['recaptcha'] = ee()->input->post('g-recaptcha-response', true);
                unset($_POST['g-recaptcha-response']);

                $this->fields['recaptcha']['rules'] .= "|required|sm_recaptcha_validate";

            }
            elseif((isset($param_array['enable_recaptcha']) && $param_array['enable_recaptcha'] == 'yes') || ($source == "registration" && (ee()->config->item('use_membership_captcha') == 'y' || ee()->config->item('require_captcha') == 'y')))
            {   
                // Captcha identify and check the correct captcha is entered
                $this->fields['captcha']['rules'] .= "|required|sm_captcha_validate";
            }

        }
        /*if($this->form_settings['enable_recaptcha'] == "Y" && $this->form_settings['recaptcha_site_key'] != "" && $this->form_settings['recaptcha_secret'] != "" && isset($param_array['enable_recaptcha']) && $param_array['enable_recaptcha'] == 'yes')
        {

            $_POST['recaptcha'] = $_POST['g-recaptcha-response'];
            unset($_POST['g-recaptcha-response']);

            $this->fields['recaptcha']['rules'] .= "|required|sm_recaptcha_validate";

        }
        if(ee()->config->item('use_membership_captcha') == 'y' && $source == "registration")
        {

            // Captcha identify and check the correct captcha is entered
            $this->fields['captcha']['rules'] .= "|required|sm_captcha_validate";

        }*/

        /*Set validation rules*/
        $this->setValidationRules();

        /*Run Validation*/
        return $this->runValidation();

    }

    /**
    * Method to assign default rules to fields in registration form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function registrationRules($param_array)
    {

        /*Assign the default rules to static member fields*/
        if($this->form_settings['email_as_username'] == 'Y')
        {
            $this->fields['username']['rules'] .= '|required|email|valid_username|is_unique[members.email]|is_unique[members.username]';
        }
        else
        {
            $this->fields['username']['rules'] .= '|required|valid_username|is_unique[members.username]';
            $this->fields['email']['rules'] .= '|required|email|is_unique[members.email]';
        }
        
        if(isset($_POST['screen_name']))
        {
            $this->fields['screen_name']['rules'] .= '|required|valid_screen_name';
        }

        $this->fields['password']['rules'] .= '|required|valid_password';

        $this->fields['password_confirm']['rules'] .= '|required|matches[password]';

        /*Check accept term parameter if terms are exists*/
        if (ee()->config->item('require_terms_of_service') == 'y')
        {
            $this->fields['accept_terms']['field'] = "accept_terms";
            $this->fields['accept_terms']['label'] = lang('accept_terms');
            $this->fields['accept_terms']['rules'] = "|required";
        }

        if(!empty($_FILES['avatar_filename']['name']))
        {
            $_POST['avatar_filename'] = "ZEAL";
            $this->fields['avatar_filename']['rules'] .= '|enable[avatars]|allow_avatar_uploads|chk_img_type[avatar_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[avatar_filename.avatar]|chk_img_size[avatar_filename.avatar]';            
        }

        if(!empty($_FILES['photo_filename']['name']))
        {
            $_POST['photo_filename'] = "ZEAL";
            $this->fields['photo_filename']['rules'] .= '|enable[photos]|chk_img_type[photo_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[photo_filename.photo]|chk_img_size[photo_filename.photo]';
        }

        if(!empty($_FILES['sig_img_filename']['name']))
        {
            $_POST['sig_img_filename'] = "ZEAL";
            $this->fields['sig_img_filename']['rules'] .= '|sig_allow_img_upload|chk_img_type[sig_img_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[sig_img_filename.photo]|chk_img_size[sig_img_filename.photo]';
        }

    }
    
    /**
    * Method to assign default rules to fields in Edit profile form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function editProfileRules($param_array)
    {
        /*If username or email fields are posted to update*/
        if(isset($_POST['username']) || isset($_POST['email']))
        {

            /*If email as username is enabled from backend*/
            if($this->form_settings['email_as_username'] == 'Y')
            {
                if(isset($_POST['email']) && isset($_POST['username']))
                {
                    $this->fields['email']['rules'] .= '|required|email|is_unique_profile[members.email]';
                    $this->fields['username']['rules'] .= '|required|valid_username|is_unique_profile[members.username]';
                }
                elseif(isset($_POST['email']))
                {
                    $this->fields['email']['rules'] .= '|required|email|is_unique_profile[members.email]';
                }
                elseif(isset($_POST['username']))
                {
                    $this->fields['username']['rules'] .= '|required|email|valid_username|is_unique_profile[members.username]|is_unique_profile[members.email]';
                }
            }
            else
            {
                if(isset($_POST['username']))
                {
                    $this->fields['username']['rules'] .= '|required|valid_username|is_unique_profile[members.username]';
                }
                if(isset($_POST['email']))
                {
                    $this->fields['email']['rules'] .= '|required|email|is_unique_profile[members.email]';
                }
            }

        }

        if(isset($_POST['screen_name']))
        {
            $this->fields['screen_name']['rules'] .= '|required|valid_screen_name';
        }

        /*If Password field is posted to update*/
        if (isset($_POST['password']) && $_POST['password'] != "")
        {

            if(isset($param_array['password_required']) && $param_array['password_required'] == "no")
            {
                $this->fields['current_password']['rules'] = '';
            }
            else
            {
                $this->fields['current_password']['rules'] .= '|required|auth_password';
            }

            $this->fields['password']['rules'] .= '|required|valid_password|matches[password_confirm]';
            $this->fields['password_confirm']['rules'] .= '|required';

        }
        elseif(isset($param_array['password_required']) && $param_array['password_required'] == "yes")
        {
            $this->fields['current_password']['rules'] .= '|required|auth_password';
        }

        if(!empty($_FILES['avatar_filename']['name']))
        {
            $_POST['avatar_filename'] = "ZEAL";
            $this->fields['avatar_filename']['rules'] .= '|enable[avatars]|allow_avatar_uploads|chk_img_type[avatar_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[avatar_filename.avatar]|chk_img_size[avatar_filename.avatar]';            
        }

        if(!empty($_FILES['photo_filename']['name']))
        {
            $_POST['photo_filename'] = "ZEAL";
            $this->fields['photo_filename']['rules'] .= '|enable[photos]|chk_img_type[photo_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[photo_filename.photo]|chk_img_size[photo_filename.photo]';
        }

        if(!empty($_FILES['sig_img_filename']['name']))
        {
            $_POST['sig_img_filename'] = "ZEAL";
            $this->fields['sig_img_filename']['rules'] .= '|sig_allow_img_upload|chk_img_type[sig_img_filename.image/jpeg, image/jpg, image/png]|chk_img_height_width[sig_img_filename.photo]|chk_img_size[sig_img_filename.photo]';
        }

    }

    /**
    * Method to assign default rules to fields in Delete Profile form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function deleteProfileRules($param_array)
    {
        $this->fields['password']['rules'] .= "|required|auth_password";
    }

    /**
    * Method to assign default rules to fields in Forgot password form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function forgotPasswordRules($param_array)
    {

        if(isset($param_array['identification_field']))
        {

            switch ($param_array['identification_field'])
            {

                case 'member_id':
                    $this->fields['member_id']['rules'] .= "|required|check_field[member_id]";
                    break;

                case 'username':
                    $this->fields['username']['rules'] .= "|required|check_field[username]";
                    break;
                
                default:
                    $this->fields['email']['rules'] .= "|required|email|check_field[email]";
                    break;

            }

        }
        else
        {
            $this->fields['email']['rules'] .= "|required|email|check_field[email]";
        }

    }

    /**
    * Method to assign default rules to fields in Forgot password form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function loginRules($param_array)
    {

        if(isset($_POST['email']))
        {
            $this->fields['email']['rules'] .= "|required|email|check_field[email]";
        }
        else
        {
            $this->fields['username']['rules'] .= "|required|check_field[username]";
        }

        $this->fields['password']['rules'] .= '|required|auth_login';

        if(isset($_POST['group_id']))
        {
            $this->fields['group_id']['rules'] .= '|required|match_group';
        }

        //for ee6
        if(isset($_POST['role_id']))
        {
            $this->fields['role_id']['rules'] .= '|required|match_group';
        }

    }

    /**
    * Method to assign default rules to fields in Social login form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function socialLoginRules($param_array)
    {
        $this->fields['providers']['rules'] .= "|chk_provider";
    }

    /**
    * Method to assign default rules to fields in Reset password form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function resetPasswordRules($param_array)
    {

        $this->fields['password']['rules'] .= '|required|valid_password|matches[password_confirm]';
        $this->fields['password_confirm']['rules'] .= '|required';

    }

    /**
    * Setup array with name, label and rule
    * @param $fields (Array of all possible fields that can use in form)
    * @return defined and set array of member fields
    **/
    function setArray($fields)
    {

        foreach ($fields as $key => $value)
        {
            $tmp[$value] = $value;
        }

        $fields = $tmp;
        unset($tmp);

        /*Custom member field assignment*/
        if($this->member_fields !== false)
        {

            foreach ($this->member_fields as $m)
            {
                $tmp[$m['m_field_name']]['field'] = $m['m_field_name'];
                $tmp[$m['m_field_name']]['label'] = $m['m_field_label'];
                $tmp[$m['m_field_name']]['rules'] = "";

                unset($fields[$m['m_field_name']]);

            }

        }

        /*Static fields assignment*/
        foreach ($fields as $m)
        {
            $tmp[$m]['field'] = $m;
            $tmp[$m]['label'] = lang($m);
            $tmp[$m]['rules'] = "";
        }
        unset($fields);

        /**
        * Hook will call Every time constructor called and set fields for forms
        * @param $tmp
        * @return $tmp
        */
        if (ee()->extensions->active_hook('sm_total_fields') === TRUE)
        {

            $tmp1 = ee()->extensions->call('sm_total_fields', $tmp);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp1 != "")
            {
                $tmp = $tmp1;
                unset($tmp1);
            }

        }

        return $tmp;

    }

    /**
    * Process the submission after successful validation of forgot password form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    public function processForgotPassword($param_array)
    {

        /*Fectch member id from identification_field we got im POST*/
        if(isset($param_array['identification_field']))
        {

            switch ($param_array['identification_field'])
            {

                case 'member_id':
                    $member_id = ee()->input->get_post('member_id');
                    break;

                case 'username':
                    $member_id = ee()->smModel->getMemberFieldFromEmail('member_id',array('username'=>ee()->input->get_post('username')));
                    break;
                
                default:
                    $member_id = ee()->smModel->getMemberFieldFromEmail('member_id',array('email'=>ee()->input->get_post('email')));
                    break;
            }

        }
        else
        {
            $member_id = ee()->smModel->getMemberFieldFromEmail('member_id',array('email'=>ee()->input->get_post('email')));
        }

        /*Clean or erase all reset codes of this member*/
        ee()->smModel->cleanOldResetCodes($member_id);

        /*Generate new reset code and enter the data in database*/
        $resetcode = ee()->smModel->generateResetCode($member_id);

        /*Setup config variable for email*/
        $config = $this->configForgotPasswordEmail($member_id);

        /*Do we have reset password template in backend*/
        if(isset($param_array['reset_password_template']))
        {
            $config['reset_url'] = $param_array['reset_password_template'];
        }

        //sovled in the 3.0.3
        //set template issue of email:subject not getting from the template
        if(isset($param_array['email:subject']))
        {
            $config['subject'] = $param_array['email:subject'];
        }

        //sovled in the 3.0.3
        //set template issue of email:word_wrap not getting from the template
        /*if(isset($param_array['email:word_wrap']))
        {
            $config['word_wrap'] = $param_array['email:word_wrap'];
        }*/

        //sovled in the 3.0.3
        //set template issue of email:mailtype not getting from the template
        /*if(isset($param_array['email:mailtype']))
        {
            $config['mailtype'] = $param_array['email:mailtype'];
        }*/

        /*Bind reset code in URL of email*/
        $config['resetcode'] = $resetcode;
        
        if($config['reset_url'] != "")
        {
            $config['reset_url'] .= '/' . $resetcode;
        }

        /*Send email*/
        $ret = $this->sendEmail($config, "forgot_password");

    }

    /**
    * Process the submission after successful validation of reset password form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    public function processResetPassword($param_array)
    {

        /*Fectch member id from email address we got im POST*/
        $member_id = ee()->smModel->resetPasswordFields('member_id', array('resetcode' => $param_array['reset_code']));
        
        if($member_id === false)
        {
            return ee()->output->show_user_error(false, $this->errorPrefix(array('token' => lang('reset_token_invalid'))));
        }

        /*Fetch the new password*/
        $password  = ee()->input->get_post('password', true);
        
        if($password != "")
        {

            ee()->load->library('auth');
            $memberOBJ      = ee('Model')->get('Member', $member_id)->first();
            
            if($memberOBJ == "")
            {
                return ee()->output->show_user_error(false, $this->errorPrefix(array('member' => lang('no_member_available'))));
            }

            $hasPassword    = ee()->auth->hash_password($password);
            $postData       = array("password" => $hasPassword['password'], "salt" => $hasPassword['salt']);

            $memberOBJ->set($postData);
            $memberOBJ->save();
            
            unset($memberOBJ);
            
            /*Clean or erase all reset codes from database*/
            ee()->smModel->cleanOldResetCodes($member_id);

        }

    }

    /**
    * Process the submission after successful validation of Delete profile form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    public function processDeleteProfile($param_array)
    {

        /*Member ID of current member*/
        $member_id = ee()->session->userdata('member_id');

        if ($member_id != 0)
        {

            /*Fetch member data from memebr ids and group ids*/
            $member_data = ee()->smModel->memberStaticFields($where = array('member_id' => $member_id), "group_id");
            
            if($member_data === false)
            {
                return lang('not_authorized');
            }

            if($member_data[0]['group_id'] == 1)
            {
                return lang('cant_delete_super_admin');
            }

            /*Delete member*/
            ee()->member_model->delete_member($member_id);

            return true;

        }

    }

    /*Process the Logout URL click action*/
    public function processLogout()
    {
        /* Stop Default message to enter the profile data */
        if(SM_APP_VER_L6){
            $this->fake_output();
            $this->loadDefaultMemberClasses('member_auth')->member_logout();
            $this->release_fake_output();
        }else{
            //ee6
            $this->member_logout();
        }

        return true;
    }
    
    /**
    * Process the submission after successful validation of Login form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    public function processLogin($param_array, $member_data)
    {

        /*Load needful classes to authenticate member and login*/
        if(! class_exists('auth'))
        {
            ee()->load->library('auth');
        }

        if ( ! ee()->auth->check_require_ip())
        {
            return false;
        }

        $field = ""; $val = "";

        if(isset($_POST['email']))
        {
            $field  = $_POST['email'];
            $val    = "email";
        }
        elseif (isset($_POST['username']))
        {
            $field = $_POST['username'];
            $val    = "username";
        }

        /*Initialize session*/
        $session = ee()->auth->authenticate_id($member_data['member_id'], $_POST['password']);
        if (! $session)
        {

            if($field != "")
            {
                ee()->session->save_password_lockout($field);
                return false;
            }

        }

        /*Check user if banned*/
        if ($session->is_banned())
        {
            return false;
        }

        /*If already loggedin and multi login is set to no*/
        if (ee()->config->item('allow_multi_logins') == 'n' AND $session->has_other_session())
        {
            return false;
        }

        /*Remember user to login next time directly*/
        if (isset($_POST['auto_login']) && $_POST['auto_login'] != "")
        {
            $session->remember_me(3600 * 24 * 365);
        }

        /*Start session*/
        $session->start_session();

        /*Update online status of user*/
        ee()->smModel->updateOnlineUserStats();

        return true;

    }

    /**
    * Process the submission after successful validation of Registration form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    public function processRegistration($param_array)
    {

        /*If email as username is enabled from backend*/
        if($this->form_settings['email_as_username'] == 'Y')
        {
            $_POST['email'] = $_POST['username'];
        }

        /* Override screen name if not posted by user and enabled */
        if( ! isset($_POST['screen_name']) || (isset($_POST['screen_name']) && $_POST['screen_name'] == ""))
        {
            if($this->form_settings['screen_name_override'] == "Y" && $this->form_settings['screen_name_field'] != "" && isset($_POST[$this->form_settings['screen_name_field']]))
            {
                $_POST['screen_name'] = $_POST[$this->form_settings['screen_name_field']];
            }
        }
        
        $post_data = $_POST;

        if(isset($_FILES))
        {

            /*Update member custom fields created by smart member (File)*/
            $ret = ee()->mf->update_custom_file_fields($post_data);
            
            /*Return if error occured in smart member custom fields*/
            if(isset($ret['error']) && count($ret['error']) > 0)
            {
                $this->form_errors = $ret['error'];
                return $ret['error'];
            }
            else
            {
                $post_data = $ret['post_data'];
            }

        }

        /* Stop Default message to enter the profile data */

        $this->fake_output();
        if(SM_APP_VER_L6){
            $this->loadDefaultMemberClasses('member_register')->register_member();
        }else{
            $this->register_member_v6();
        }
        $this->release_fake_output();

        /*Get member id of new user*/
        // $member_id = ee()->smModel->getMemberFieldFromEmail('member_id', array('email' => $_POST['email']));
        $this->memberOBJ = ee('Model')->get('Member')->filter('email', $_POST['email'])->first();

        /*Unset unwanted data*/
        unset($post_data['ACT']);
        unset($post_data['params']);
        unset($post_data['site_id']);
        unset($post_data['username']);
        unset($post_data['password']);
        unset($post_data['password_confirm']);
        unset($post_data['email']);
        unset($post_data['screen_name']);
        unset($post_data['group_id']);
        unset($post_data['role_id']); // for ee6
        unset($post_data['url']);
        unset($post_data['location']);
        unset($post_data['captcha']);
        unset($post_data['recaptcha']);
        unset($post_data['accept_terms']);
        unset($post_data['avatar_filename']);
        unset($post_data['photo_filename']);
        unset($post_data['sig_img_filename']);
        unset($post_data['current_password']);
        unset($post_data['auto_login']);
        
        /*Setting up group id of user*/
        if(isset($param_array['group_id']))
        {
            $post_data['group_id'] = (int)$param_array['group_id'];
        }
        //for ee6
        if(isset($param_array['role_id']))
        {
            $post_data['group_id'] = (int)$param_array['role_id'];
        }
        elseif(isset($param_array['allowed_groups']))
        {

            $group_ids = explode('|', $param_array['allowed_groups']);
            
            if(count($group_ids) > 0)
            {

                if(count($group_ids) == 1)
                {
                    $post_data['group_id'] = (int)$group_ids[0];
                }
                else
                {

                    if(isset($_POST['group_id']) && in_array($_POST['group_id'], $group_ids))
                    {
                        $post_data['group_id'] = $_POST['group_id'];
                    }
                    else if(isset($_POST['role_id']) && in_array($_POST['role_id'], $group_ids))
                    {   
                        //ee6
                        $post_data['group_id'] = $_POST['role_id'];
                    }
                    else
                    {
                        $post_data['group_id'] = (int)$group_ids[0];
                    }

                }

            }

        }

        /*If member need any activation after registration*/
        if(ee()->config->item('req_mbr_activation') != "none")
        {

            /*Set group id to use after pending registration process*/
            if(isset($post_data['group_id']))
            {
                $this->group_id = $post_data['group_id'];
                $this->role_id = $post_data['group_id']; // for ee6
                unset($post_data['group_id']);
            }
            else if(isset($post_data['role_id']))
            {   
                //for ee6
                $this->group_id = $post_data['role_id'];
                $this->role_id = $post_data['role_id'];
                unset($post_data['role_id']);
            }
            else
            {
                $this->group_id = 0;
                $this->role_id = 0; // for ee6
            }

        }

        /*Update static fields of member*/
        $this->updateMember($post_data);

        /*Upload and save images if enter by user*/
        $this->uploadStaticImages($this->memberOBJ->member_id);

        return true;

    }

    /**
    * Process the submission after successful validation of Edit profile form
    * @param $param_array (Array of forms parameter assign in parameter tags)
    **/
    function processUpdateProfile($param_array)
    {

        /*If email as username is enabled from backend*/
        if($this->form_settings['email_as_username'] == 'Y' && isset($_POST['username']) && $_POST['username'] != "")
        {
            if(! isset($_POST['email']))
            {
                $_POST['email'] = $_POST['username'];
            }
        }
        
        /*Fetch member id from session*/
        // $member_id = ee()->session->userdata('member_id');
        $member_id = ee()->sm->member_id;
        $this->memberOBJ = ee('Model')->get('Member', $member_id)->with( (SM_APP_VER_L6) ? 'MemberGroup' : 'PrimaryRole')->first(); // ee6

        /*Get all post data*/
        $post_data = $_POST;

        if (isset($post_data['remove_avatar'])  && $post_data['remove_avatar'] != "")    $post_data['remove_avatar']     = "remove_avatar";
        if (isset($post_data['remove_photo'])   && $post_data['remove_photo'] != "")      $post_data['remove_photo']      = "remove_photo";
        if (isset($post_data['remove_sig_img']) && $post_data['remove_sig_img'] != "")  $post_data['remove_sig_img']    = "remove_sig_img";

        /*Unset the data we dont need any more*/
        unset($post_data['ACT']);
        unset($post_data['params']);
        unset($post_data['csrf_token']);
        unset($post_data['XID']);
        unset($post_data['site_id']);
        unset($post_data['password_confirm']);
        unset($post_data['captcha']);
        unset($post_data['recaptcha']);
        unset($post_data['accept_terms']);

        if(isset($post_data['remove_avatar']) && $post_data['remove_avatar'] == "remove_avatar")
        {
            $post_data['avatar_filename'] = "";
        }
        else
        {
            unset($post_data['avatar_filename']);
        }
        
        if(isset($post_data['remove_photo']) && $post_data['remove_photo'] == "remove_photo")
        {
            $post_data['photo_filename'] = "";
        }
        else
        {
            unset($post_data['photo_filename']);
        }

        if(isset($post_data['remove_sig_img']) && $post_data['remove_sig_img'] == "remove_sig_img")
        {
            $post_data['sig_img_filename'] = "";
        }
        else
        {
            unset($post_data['sig_img_filename']);
        }

        if($member_id === 0)
        {
            $this->form_errors = "Logged in member not found";
            return $ret['error'];
        }

        if(isset($_FILES))
        {

            /*Update member custom fields created by smart member (File)*/
            $ret = ee()->mf->update_custom_file_fields($post_data);
            
            /*Return if error occured in smart member custom fields*/
            if(isset($ret['error']) && count($ret['error']) > 0)
            {
                $this->form_errors = $ret['error'];
                return $ret['error'];
            }
            else
            {
                $post_data = $ret['post_data'];
            }

        }

        if(isset($post_data['password']) && $post_data['password'] != "")
        {
            ee()->load->library('auth');
            $hasPassword = ee()->auth->hash_password($post_data['password']);
            $post_data['password'] = $hasPassword['password'];
            $post_data['salt'] = $hasPassword['salt'];
        }
        else
        {
            unset($post_data['password']);
            unset($post_data['salt']);
        }

        /*Update static fields of member*/
        $this->updateMember($post_data);

        /*Upload and save images if enter by user*/
        $this->uploadStaticImages($this->memberOBJ->member_id);

       return true;

    }

    function updateMember($post_data)
    {

        $dynamicFields = ee()->smModel->dynamicMemberFieldsWithID(true);
        if($dynamicFields !== false)
        {
            foreach ($post_data as $key => $value)
            {
                if(isset($dynamicFields[$key]))
                {

                    if(is_array($value))
                    {
                        $value = implode("\n", $value);
                    }
                    $post_data['m_field_id_' . $dynamicFields[$key]['id']] = $value;
                    unset($post_data[$key]);

                }
            }
        }

        if(is_array($post_data) && count($post_data) > 0)
        {
            //solve the issue of black group id for ee5
            if(isset($post_data['group_id']))
                if(empty($post_data['group_id']))
                    unset($post_data['group_id']);

            //solve the issue of black group id for ee6
            if(isset($post_data['role_id']))
                if(empty($post_data['role_id']))
                    unset($post_data['role_id']);    
            $this->memberOBJ->set($post_data);
            $this->memberOBJ->save();
        }

        return true;
    }

    /**
    * Upload image method for all static images (Photo, Signature, Avatar)
    * @param $member_id (Member ID of user)
    **/
    function uploadStaticImages($member_id)
    {

        /*If Avatar file id uploaded*/
        if(isset($_FILES['avatar_filename']))
        {

            if($_FILES['avatar_filename']['name'] != "")
            {

                /*Setting up file extension*/
                $_FILES['userfile'] = $_FILES['avatar_filename'];
                $x = explode('.', $_FILES['avatar_filename']['name']);
                $extension = '.' . end($x);

                /*Configuration of upload data*/
                $config['file_name']    = 'avatar_' . $member_id . $extension;
                $config['upload_path']  = ee()->config->slash_item('avatar_path');/*.'uploads/'*/
                $config['is_image']     = TRUE;
                $config['max_size']     = (ee()->config->item('avatar_max_kb') == '' OR ee()->config->item('avatar_max_kb') == 0) ? 50 : ee()->config->item('avatar_max_kb');
                $config['max_width']    = (ee()->config->item('avatar_max_width') == '' OR ee()->config->item('avatar_max_width') == 0) ? 100 : ee()->config->item('avatar_max_width');
                $config['max_height']   = (ee()->config->item('avatar_max_height') == '' OR ee()->config->item('avatar_max_height') == 0) ? 100 : ee()->config->item('avatar_max_height');
                $config['overwrite']    = TRUE;

                /*Initialize our config var*/
                ee()->upload->initialize($config);
                
                ee()->upload->set_allowed_types('*');

                /*Upload image*/
                $upload = ee()->members->upload_member_images('avatar', $member_id);

            }

        }

        /*If Photo file id uploaded*/
        if(isset($_FILES['photo_filename']))
        {

            if($_FILES['photo_filename']['name'] != "")
            {

                /*Setting up file extension*/
                $_FILES['userfile'] = $_FILES['photo_filename'];
                $x = explode('.', $_FILES['photo_filename']['name']);
                $extension = '.' . end($x);

                /*Configuration of upload data*/
                $config['file_name'] = 'photo_' . $member_id.$extension;
                $config['upload_path'] = ee()->config->slash_item('photo_path');
                $config['is_image'] = TRUE;
                $config['max_size'] = (ee()->config->item('photo_max_kb') == '' OR ee()->config->item('photo_max_kb') == 0) ? 50 : ee()->config->item('photo_max_kb');
                $config['max_width']  = (ee()->config->item('photo_max_width') == '' OR ee()->config->item('photo_max_width') == 0) ? 100 : ee()->config->item('photo_max_width');
                $config['max_height']  = (ee()->config->item('photo_max_height') == '' OR ee()->config->item('photo_max_height') == 0) ? 100 : ee()->config->item('photo_max_height');
                $config['overwrite'] = TRUE;

                
                /*Initialize our config var*/
                ee()->upload->initialize($config);

                ee()->upload->set_allowed_types("*");
                /*Upload image*/
                $upload = ee()->members->upload_member_images('photo', $member_id);

            }

        }

        /*If Signature file id uploaded*/
        if(isset($_FILES['sig_img_filename']))
        {

            if($_FILES['sig_img_filename']['name'] != "")
            {

                /*Setting up file extension*/
                $_FILES['userfile'] = $_FILES['sig_img_filename'];
                $x = explode('.', $_FILES['sig_img_filename']['name']);
                $extension = '.' . end($x);

                /*Configuration of upload data*/
                $config['file_name'] = 'sig_img_' . $member_id . $extension;
                $config['upload_path'] = ee()->config->slash_item('sig_img_path');
                $config['is_image'] = TRUE;
                $config['max_size'] = (ee()->config->item('sig_img_max_kb') == '' OR ee()->config->item('sig_img_max_kb') == 0) ? 50 : ee()->config->item('sig_img_max_kb');
                $config['max_width']  = (ee()->config->item('sig_img_max_width') == '' OR ee()->config->item('sig_img_max_width') == 0) ? 100 : ee()->config->item('sig_img_max_width');
                $config['max_height']  = (ee()->config->item('sig_img_max_height') == '' OR ee()->config->item('sig_img_max_height') == 0) ? 100 : ee()->config->item('sig_img_max_height');
                $config['overwrite'] = TRUE;

                /*Initialize our config var*/
                ee()->upload->initialize($config);

                ee()->upload->set_allowed_types('*');

                /*Upload image*/
                $upload = ee()->members->upload_member_images('sig_img', $member_id);

            }

        }

    }

    /**
    * Set values from parsed var fields
    * @param $tagdata (String value of data pass between pair tag)
    * @param $tagparams (Array of parameter passed in tag)
    * @return $tagdata
    **/
    public function setupVarFields($tagdata, $tagparams, $total_fields = array())
    {

        foreach (ee()->TMPL->var_pair as $key => $value)
        {

            $selectWrapper  = array();
            $selectWrapper  = explode(' ', $key);
            $fetch_key      = $selectWrapper[0];
            unset($selectWrapper);

            $data = ee()->TMPL->fetch_data_between_var_pairs($tagdata, $fetch_key);

            /*If key if Member group (need handle in different ways)*/
            if($fetch_key == "data_group_id")
            {
                $replace_data = $this->varPairMemberGroup($fetch_key, $data, $tagparams);
            }
            elseif($fetch_key == "providers")
            {
                $replace_data = $this->varPairProviders($fetch_key, $data, $tagparams);
            }
            /*Skip if pagination variables or profile variable triggered*/
            elseif($fetch_key == "sm_list_all_fields" || $fetch_key == "sm_paginate" || $fetch_key == "sm_pagination_links" || $fetch_key == "first_page" || $fetch_key == "previous_page" || $fetch_key == "page" || $fetch_key == "next_page" || $fetch_key == "last_page")
            {
                continue;
            }
            else
            {
                $replace_data = $this->varPairResult($key, $data, $tagparams);
            }

            if($replace_data != "")
            {
                /*Replace new tag pair variable with tagdata*/
                $tagdata = preg_replace("/" . LD . $key .RD."(.*?)".LD."\/".$fetch_key.RD."/s", $replace_data, $tagdata);
            }
            else
            {
                return preg_replace("/" . LD . $key .RD."(.*?)".LD."\/".$fetch_key.RD."/s", "", $tagdata);
            }
                
        }

        /*Return tagdata*/
        return $tagdata;

    }

    /**
    * member group var pair replacement method
    * @param $key (String value of member group)
    * @param $data (Inside tag data of var pair)
    * @param $tagparams (parameter of tagdata)
    * @return Array of String value of pair data
    **/
    public function varPairMemberGroup($key, $data, $tagparams, $profile = false)
    {

        /*Check for allowed group passed in parameter*/
        if(isset($tagparams['allowed_groups']))
        {
            $group_id = $tagparams['allowed_groups'];
        }
        else
        {
            $group_id = ee()->config->item( SM_APP_VER_L6 ? 'default_member_group' : 'default_primary_role'); //ee6
        }

        /*Declaration of needful variables*/
        $return_data = ""; $count = 0; $value=""; $label="";

        /*Group data of defined groups*/
        $field_data = ee()->smModel->getMemberGroups(explode('|', $group_id));
        
        /*replacement of EE data to value*/
        foreach ($field_data as $key => $value)
        {
            $temp = $data;
            $temp = str_replace(LD."group_id_value".RD, $value['group_id'], $temp);
            $temp = str_replace(LD."group_id_label".RD, $value['group_title'], $temp);
            $temp = str_replace(LD."data_group_id:count".RD, ++$count, $temp);
            $temp = str_replace(LD."data_group_id:total_results".RD, count($field_data), $temp);

            /*Give EE prep to use those fields with if condition etc.*/
            $temp = ee()->functions->prep_conditionals($temp, array(
                'group_id_value' => $value['group_id'], 
                'group_id_label' => $value['group_title'], 
                'data_group_id:count' => $count,
                'data_group_id:total_results' => count($field_data)
                ));

            $return_data .= trim($temp)."\n";

        }

        return $return_data;

    }

    /**
    * var pair replacement method for providers variables
    * Get field data from backend and place in the field var replacement
    * @param $key (String value of member group)
    * @param $data (Inside tag data of var pair)
    * @param $tagparams (parameter of tagdata)
    * @return Array of String value of pair data
    **/
    public function varPairProviders($key, $data, $tagparams, $profile = false)
    {

        /*Load needful classes if not exists*/
        if(! class_exists('social_login_model'))
        {
            ee()->load->model('social_login_model', 'slModel');
        }

        $providers = array();

        if(isset($tagparams['providers']) && $tagparams['providers'] != "")
        {
            $providers = explode('|', $tagparams['providers']);
        }

        /*fetch member field data of custom field*/
        $field_data = ee()->slModel->getProvidersList($providers);
        if($field_data === false){return "";}

        /*Declaration of needful variables*/
        $return_data = ""; $count = 0; $value=""; $label="";

        foreach ($field_data as $key => $value)
        {

            $temp = $data;

            $temp = str_replace(LD."provider_name".RD, $value['short_name'], $temp);
            $temp = str_replace(LD."provider_label".RD, $value['settings']['label'], $temp);
            $temp = str_replace(LD."providers:count".RD, ++$count, $temp);
            $temp = str_replace(LD."providers:total_results".RD, count($field_data), $temp);

            /*Give EE prep to use those fields with if condition etc.*/
            $temp = ee()->functions->prep_conditionals($temp, array(
                'provider_name' => $value['short_name'], 
                'provider_label' => $value['settings']['label'], 
                'providers:count' => $count,
                'providers:total_results' => count($field_data)
                ));

            $return_data .= trim($temp)."\n";

        }

        return $return_data;

    }
    
    /**
    * var pair replacement method for select box, checkbox, radio etc.
    * Get field data from backend and place in the field var replacement
    * @param $key (String value of member group)
    * @param $data (Inside tag data of var pair)
    * @param $tagparams (parameter of tagdata)
    * @return Array of String value of pair data
    **/
    public function varPairResult($key, $data, $tagparams)
    {

        /*Find the actual key before prefix*/
        $key = str_replace('data_', '', $key);

        /*fetch member field data of custom field*/
        $field_data = ee()->smModel->getMemberFields(array('m_field_name' => $key));
        
        if($field_data === false){return "";}

        $val_arr = array();
        if($field_data[0]['m_field_list_items'] != "")
        {
            $temp = preg_split( "/\r|\n/", $field_data[0]['m_field_list_items']);
            for ($i = 0; $i < count($temp); $i++)
            {
                $val_arr[$temp[$i]] = $temp[$i];
            }
        }
        elseif($field_data[0]['m_field_settings'] != "")
        {
            $temp = json_decode($field_data[0]['m_field_settings'], true);
            if(isset($temp['value_label_pairs']) && count($temp['value_label_pairs']) > 0)
            {
                $val_arr = $temp['value_label_pairs'];
            }
        }

        if(empty($val_arr))
        {
            return "";
        }

        /*Declaration of needful variables*/
        $return_data = ""; $count = 0; $value=""; $label="";
        
        /*List out the options*/
        $link_array = array();

        /*Addtitional code for checkboxes*/
        if(isset($_POST[$key]))
        {

            if(! is_array($_POST[$key]))
            {
                $link_array = preg_split( "/\r|\n/", $_POST[$key]);
            }
            else
            {
                $link_array = $_POST[$key];
            }

        }
        elseif(isset($this->memberData) && is_array($this->memberData) && count($this->memberData) > 0 && isset($this->memberData[0][$key]) && $this->memberData[0][$key] != "")
        {
            $link_array = preg_split( "/\r|\n/", $this->memberData[0][$key]);
        }
        /*if($profile === true)
        {
            $total_results = count($link_array);
        }
        else
        {
        }*/

        $total_results = count($val_arr);
        /*replacement of EE data to value*/
        $replacement = array();
        $i = 0;
        foreach ($val_arr as $value => $label)
        {

            $replacement[$i][$key."_value"] = $value;
            $replacement[$i][$key."_label"] = $label;
            $replacement[$i]["data_".$key.":count"] = $i + 1;
            $replacement[$i]["data_".$key.":total_results"] = $total_results;
            $replacement[$i][$key."_value:exists"] = (in_array($value, $link_array) ? true : false);
            
            $i++;

        }
        
        $return_data = ee()->TMPL->parse_variables($data, $replacement);
        $extra_field = "<input type='checkbox' value='' name='".$key."' checked style='display:none;'>";
        return $extra_field . $return_data;

    }

    /**
    * Extract member profile data
    * @param $params (parameters of tagdata)
    * @param $tagdata (Inside tag data)
    * @return String of value replaced from tagdata
    **/
    //sovled the issue of guest not coming in the edit profile form but is not needed in view profile
    public function profileExtract($form = false, $params = array(), $tagdata = "", $all_group_ids=true)
    {

        //ee6: we set group_id if we got role_id in parameter
        if(isset($params['role_id'])){
            $params['group_id'] = $params['role_id'];
        }
        /*Load template parsing library*/
        ee()->load->library('template_parse', null, 'tmpl_parse');
        
        /*$source is to identify the method is called normally or for email purpose*/
        $source = "direct";

        $this->memberOBJ = ee('Model')->get('Member');

        /*Group ID filter operation*/
        $group_id = array();

        if(isset($params['group_id']) && $params['group_id'] != "")
        {
            $group_id = explode('|', $params['group_id']);
        }
        else
        {
            /*Get all possible group ids*/
            $group_id = ee()->smModel->allGroupIds($all_group_ids);
            if($group_id === false)
            {
                $group_id = array();
            }
        }

        /*Remvoe the member group what user not want to show*/
        if(isset($params['not_group_id']) && $params['not_group_id'] != "")
        {

            $n_group_ids = explode('|', $params['not_group_id']);
            
            for ($i=0; $i < count($n_group_ids); $i++)
            { 
                if(($key = array_search($n_group_ids[$i], $group_id)) !== false)
                {
                    unset($group_id[$key]);
                }
            }

        }

        if(isset($group_id) && is_array($group_id) && count($group_id) > 0)
        {
            $this->memberOBJ = $this->memberOBJ->filter( (SM_APP_VER_L6) ? 'group_id' : 'role_id', 'IN', $group_id);
        }

        /*True if called directly*/
        if(ee()->tmpl_parse->member_id == 0)
        {
            if(isset($params['member_id']) && $params['member_id'] != "")
            {
                $member_id = $params['member_id'];
            } 
            elseif(ee()->session->userdata('member_id') == 0)
            {
                return ee()->TMPL->no_results();
            }
            else
            {
                $member_id = ee()->session->userdata('member_id');
            }

            if($member_id == "CURRENT_MEMBER")
            {
                $member_id = ee()->session->userdata('member_id');
            }

        }
        else
        {
            /*If called from email*/
            $member_id = ee()->tmpl_parse->member_id;
            $source = "email";
        }

        if($member_id != "ALL_MEMBERS")
        {
            /*Member ID filter operation*/
            $member_id = explode('|', $member_id);

            if(is_array($member_id) && count($member_id) > 0)
            {
                $this->memberOBJ = $this->memberOBJ->filter('member_id', 'IN', $member_id);
            }
        }

        /*Remvoe the member ID what user not want to show*/
        //solved the issue of member id not getting due to array not pass in condtion now added the it with explode
        if(isset($params['not_member_id']) && is_array(explode('|', $params['not_member_id'])) && count(explode('|', $params['not_member_id'])) > 0)
        {
            $this->memberOBJ = $this->memberOBJ->filter('member_id', 'NOT IN', explode('|', $params['not_member_id']));
        }

        $order_by = isset($params['order_by']) ? $params['order_by'] : "member_id";
        if(isset($this->dynamicFields[$order_by]))
        {
            $order_by = "m_field_id_" . $this->dynamicFields[$order_by]['id'];
        }
        elseif( ! in_array($order_by, $this->staticFields) )
        {
            $order_by = "member_id";
        }

        $this->memberOBJ = $this->memberOBJ->order($order_by, isset($params['sort']) ? $params['sort'] :  "asc");
        $total = $this->memberOBJ->count();
        $limit = isset($params['limit']) ? $params['limit'] : 100;
        $offset = 0;
        
        if(isset(ee()->TMPL->var_pair["sm_paginate"]))
        {

            /*Setup pagination*/
            $data = $this->paginate($tagdata, $limit, $total);

            /*Basic pagination data with base url and current page*/
            $pagination_data = $this->pagination_data();
            if(isset($pagination_data['cur_page']))
            {
                $offset = $pagination_data['cur_page'];
            }

            $tagdata                = $data['tagdata'];
            $pagination_final_data  = $data['pagination_final_data'];

            $paginate = "bottom";
            if(isset($params['paginate']) && ($params['paginate'] == "top" || $params['paginate'] == "both"))
            {
                $paginate = $params['paginate'];
            }
        }

        /**
        * Hook will call Before replace tagdata with actual data.
        * @param $member_data
        * @return $member_data
        */
        if (ee()->extensions->active_hook('sm_view_profile_end') === TRUE)
        {
            $tmp = ee()->extensions->call('sm_view_profile_end', $this->memberOBJ);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp != "")
            {
                $this->memberOBJ = $tmp;
                unset($tmp);
            }
        }

        $this->memberOBJ = $this->memberOBJ->limit($limit)->offset($offset)->all();

        if($this->memberOBJ->count() == 0)
        {
            return ee()->TMPL->no_results();
        }

        $temp = ee()->smModel->getMemberGroups();

        $memberGroups = array();
        if(is_array($temp) && count($temp) > 0)
        {
            for ($i = 0; $i < count($temp); $i++) {
                $memberGroups[$temp[$i]['group_id']] = $temp[$i]['group_title'];
            }
        }

        $replace = array();
        $cnt = 0;

        foreach ($this->memberOBJ as $member)
        {
            for ($i = 0; $i < count($this->staticFields); $i++)
            {
                $key = $this->staticFields[$i];
                if(! is_array($member->$key))
                {
                    $replace[$cnt][$this->staticFields[$i]] = $member->$key;
                }
            }

            $replace[$cnt]['group_title_label'] = $memberGroups[$replace[$cnt][ SM_APP_VER_L6 ? 'group_id' : 'role_id']]; //ee6

            if(is_array($this->dynamicFields) && count($this->dynamicFields) > 0)
            {

                foreach ($this->dynamicFields as $k => $v)
                {
                    $key = "m_field_id_" . $v['id'];
                    $replace[$cnt][$k] = $member->$key;
                    if(($this->dynamicFields[$k]['type'] == "checkboxes" || $this->dynamicFields[$k]['type'] == "multi_select") && $form === false)
                    {
                        $field_data = ee()->smModel->getMemberFields(array('m_field_name' => $this->dynamicFields[$k]['name']));
                        $data = $this->arrangeVarTag($k, $member->$key, $field_data);
                        $replace[$cnt][$k] = $data['data'];
                        $replace[$cnt][$k . ":total_rows"] = $data['total_rows'];
                    }
                }

            }

            if($form === false)
            {
                $replace[$cnt]['sm_list_all_fields'] = $this->createData($replace[$cnt], $tagdata, $this->staticFields, $this->dynamicFields);
            }
            $cnt++;

        }

        if($form === true)
        {
            return $replace;
        }

        $tagdata = $this->addLabels($tagdata);
        /*Return the string of final value replaced from tagdata*/
        $tagdata = ee()->TMPL->parse_variables($tagdata, $replace);
        
        $tagdata = $this->addGeneralContent($tagdata);

        if(isset($paginate))
        {
            switch ($paginate)
            {

                case 'bottom':
                default:
                $tagdata .= $pagination_final_data;
                break;
                
                case 'top':
                $tagdata = $pagination_final_data . $tagdata;
                break;

                case 'both':
                $tagdata = $pagination_final_data . $tagdata . $pagination_final_data;
                break;

            }
        }

        return $tagdata;

    }

    function addLabels($tagdata)
    {

        $replaceRow = array();
        for ($i = 0; $i < count($this->staticFields); $i++)
        {
            $replaceRow[$this->staticFields[$i] . "_label"] = lang($this->staticFields[$i]);
        }

        if(is_array($this->dynamicFields) && count($this->dynamicFields) > 0)
        {
            foreach ($this->dynamicFields as $key => $value)
            {
                $replaceRow[$value['name'] . "_label"] = lang($value['label']);
            }
        }
        
        return ee()->TMPL->parse_variables_row($tagdata, $replaceRow);

    }

    function addGeneralContent($tagdata)
    {
        $replaceRow = ee()->mf->parseDirectory();
        $replaceRow['avatar_url']   = ee()->config->slash_item('avatar_url');
        $replaceRow['photo_url']    = ee()->config->slash_item('photo_url');
        $replaceRow['sig_img_url']  = ee()->config->slash_item('sig_img_url');
        $replaceRow['base_url']     = ee()->config->slash_item('base_url');
        $replaceRow['base_path']    = ee()->config->slash_item('base_path');
        
        return ee()->TMPL->parse_variables_row($tagdata, $replaceRow);
    }

    function arrangeVarTag($k, $data, $field_data)
    {

        $ret = array('total_rows' => 0, 'data' => array());

        if($data != "")
        {
            $settings = json_decode($field_data[0]['m_field_settings'], true);

            $data = preg_split( "/\r|\n/", $data);
            for ($i = 0; $i < count($data); $i++)
            {
                $ret['data'][$i][$k . ':value'] = $data[$i];
                $ret['data'][$i][$k . ':label'] = $settings['value_label_pairs'][$data[$i]];
                $ret['data'][$i][$k . ':count'] = $i + 1;
            }
            $ret['total_rows'] = count($data);
        }

        return $ret;
    }

    /**
    * Create pagination with use of this function
    * @param $tagdata   (Inside tag data)
    * @param $per_page  (entries each page will show [limit parameter])
    * @param $total     (Total entries found in database)
    * @param $paginate  (main pagination variable)
    * @param $pagination_links (sub pagination variable [can be direct value or var pair tag])
    * @return Array of pagination_final_data and tagdata
    **/
    function paginate($tagdata, $per_page, $total, $paginate = "sm_paginate", $pagination_links = "sm_pagination_links")
    {

        /*Load needful classes if not exists*/
        if(! class_exists('pagination'))
        {
            ee()->load->library('pagination');
        }

        /*Basic pagination data [base url and current page]*/
        $pagination_data    = $this->pagination_data();
        $data               = array();

        /*Set offset if not first page*/
        if(isset($pagination_data['cur_page']))
        {
            $profile_data['offset'] = $pagination_data['cur_page'];
        }

        /*Pagination cofiguration*/
        $p_config = $this->paginationConfig($total, $per_page, $pagination_data);

        /*Initialize pagination*/
        ee()->pagination->initialize($p_config);

        /*Fetch data between {sm_paginate}{/sm_paginate}*/
        $pagination_final_data = ee()->TMPL->fetch_data_between_var_pairs($tagdata,$paginate);

        /*Inside possible keys*/
        $inside_keys = array(
            'first_page'    => "",
            'next_page'     => "",
            'last_page'     => "",
            'previous_page' => "",
            'page'          => ""
            );

        /*Call if {sm_pagination_links} is var tag field*/
        if(isset(ee()->TMPL->var_pair[$pagination_links]))
        {

            /*Create array of alll possible pagination links*/
            $replace_data = ee()->pagination->create_link_array();
            /*save data temporary between {sm_pagination_links} {/sm_pagination_links}*/
            $temp_tagdata = ee()->TMPL->fetch_data_between_var_pairs($pagination_final_data, $pagination_links);

            /*Loop of all possible inside keys*/
            foreach ($inside_keys as $k => $v)
            {

                $temp_tag_pair = ee()->TMPL->fetch_data_between_var_pairs($temp_tagdata, $k);
                $ret_tag_pair = "";

                if(isset($replace_data) && is_array($replace_data) && count($replace_data) > 0)
                {
                    for ($i=0; $i < count($replace_data[$k]); $i++)
                    {

                        $temp       = $temp_tag_pair;
                        $replace    = array();

                        /*Setup values to replace with EE tags*/
                        if(isset($replace_data[$k][$i]['pagination_url']) && $replace_data[$k][$i]['pagination_url'] != "")
                        {

                            $replace['pagination_url']         = $replace_data[$k][$i]['pagination_url'];
                            $replace['text']                   = (isset($replace_data[$k][$i]['text']))                    ? $replace_data[$k][$i]['text']                     : "";
                            $replace['current_page']           = (isset($replace_data[$k][$i]['current_page']))            ? $replace_data[$k][$i]['current_page']             : "";
                            $replace['pagination_page_number'] = (isset($replace_data[$k][$i]['pagination_page_number']))  ? $replace_data[$k][$i]['pagination_page_number']   : "";

                            /*Append the string to return the data*/
                            $ret_tag_pair .= ee()->TMPL->parse_variables_row($temp, $replace);

                        }

                    }
                }

                /*Replace EE data with replaced values between {sm_pagination_links}{/sm_pagination_links} tag*/
                $temp_tagdata  = preg_replace("/" . LD . $k .RD."(.*?)".LD."\/". $k .RD."/s", $ret_tag_pair, $temp_tagdata);

                /*Setup upper inside keys keys*/
                if($k != "page" && $k != "first_page")
                {
                    $inside_keys[$k] = (isset($replace['pagination_url'])) ? $replace['pagination_url'] : "";
                }

            }

            unset($inside_keys['page']);
            $inside_keys['first_page'] = $pagination_data['base_url'];
            
            /*Parse array with tagdata*/
            $tagdata = ee()->TMPL->parse_variables_row($tagdata, $inside_keys);
            $tagdata = ee()->functions->prep_conditionals($tagdata, $inside_keys); 
            
            /*Replace EE pagination to NULL to set it at top bottom or both behaviour*/
            $pagination_final_data = $temp_tagdata;
            $tagdata = preg_replace("/" . LD . $pagination_links .RD."(.*?)".LD."\/".$pagination_links.RD."/s", $pagination_final_data, $tagdata);

        }
        /*Call if {sm_pagination_links} is normal tag field*/
        else
        {

            /*Setup inside keys value to use as pagination*/
            unset($inside_keys['page']);
            $inside_keys['first_page'] = $pagination_data['base_url'];

            if(isset($pagination_data['cur_page']))
            {
                $cur_page = $pagination_data['cur_page'];
            }
            else
            {
                $cur_page = 0;
            }

            if(($cur_page - $per_page) > 0)
            {
                $inside_keys['previous_page'] = $pagination_data['base_url'] . '/P' . ($cur_page - $per_page);
            }
            elseif(($cur_page - $per_page) == 0)
            {
                $inside_keys['previous_page'] = $inside_keys['first_page'];
            }
            else
            {
                $inside_keys['previous_page'] = "";
            }

            $max = ($total) - ($total % $per_page);
            
            if(($cur_page + $per_page) > $max)
            {
                $inside_keys['next_page'] = "";
            }
            else
            {
                $inside_keys['next_page'] = $pagination_data['base_url'] . '/P' . ($cur_page + $per_page);
            }

            $inside_keys['last_page'] = $pagination_data['base_url'] . '/P' . $max;
            
            /*Prepare array to use as condition*/
            $tagdata = ee()->functions->prep_conditionals($tagdata, $inside_keys);
            $inside_keys[$pagination_links] = ee()->pagination->create_links();

            /*Replace tagdata values with array*/
            $tagdata = ee()->TMPL->parse_variables_row($tagdata, $inside_keys);
            
        }

        /*Fetch the data between {sm_paginate} {/sm_paginate} tag and save it in diff variable and make old tagdata pagination to null*/
        $data['pagination_final_data']  = ee()->TMPL->fetch_data_between_var_pairs($tagdata, $paginate);
        $data['tagdata']                = preg_replace("/" . LD . $paginate .RD."(.*?)".LD."\/".$paginate.RD."/s", "", $tagdata);

        return $data;

    }

    /*Configuration of basic pagination data i.e., base url and current page*/
    function pagination_data()
    {

        $data               = array();
        $search_segment     = ee()->uri->query_string;
        $data['base_url']   = ee()->functions->fetch_current_uri();
        // $uri_string     = ee()->uri->uri_string;

        if(preg_match("#^P(\d+)|/P(\d+)#", $search_segment, $match))
        {
            $data['cur_page'] = (isset($match[2])) ? $match[2] : $match[1];
            // $uri_string = reduce_double_slashes(str_replace($match[0], '', $uri_string));
            $data['base_url']   = trim_slashes(str_replace($match[0], "", $data['base_url']));
        }

        return $data;

    }

    /*Config function to set initialize CI pagination library*/
    function paginationConfig($total_rows, $table_rows, $data)
    {

        if(isset($data['cur_page']))
        {
            $config['cur_page'] = $data['cur_page'];
        }

        $config['base_url'] = $data['base_url'];
        
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $table_rows;

        $config['prefix']   = "P";
        $config['page_query_string'] = FALSE;
        
        $config['full_tag_open'] = '<div id="paginationLinks">';
        $config['full_tag_close'] = '</div>';
        $config['prev_link'] = 'Previous';
        $config['next_link'] = 'Next';
        $config['first_link'] = 'First';
        $config['last_link'] = 'Last';

        return $config;

    }
    
    /**
    * Create a filter array of member(s)
    * @param $member_data (Array of member(s))
    * @param $tagdata (String value of data pass between pair tag)
    * @param $fields (array of all possible fields that can use in profile)
    * @return Array of filterd data of member(s)
    **/
    function createData($member_data, $tagdata, $staticFields, $dynamicFields)
    {

        $ret = array();

        $myStaticFields = array('member_id','group_title_label', 'username', 'screen_name', 'email', 'avatar_filename', 'photo_filename', 'sig_img_filename', 'join_date'); //ee6

        //ee6
        if(SM_APP_VER_L6){
            $myStaticFields = array_merge($myStaticFields, ['group_id']);
        }
        else{
            $myStaticFields = array_merge($myStaticFields, ['role_id']);   
        }
        for ($j = 0; $j < count($myStaticFields); $j++)
        {
            if($member_data[$myStaticFields[$j]] != "")
            {
                $isNotAppend = false;
                if($myStaticFields[$j] == "avatar_filename")
                {
                    $append = "{avatar_url}";
                }
                elseif($myStaticFields[$j] == "photo_filename")
                {
                    $append = "{photo_url}";
                }
                elseif($myStaticFields[$j] == "sig_img_filename")
                {
                    $append = "{sig_img_url}";
                }
                elseif($myStaticFields[$j] == "join_date")
                {
                    $isNotAppend = true;
                    $append = date("Y-m-d H:i:s", $member_data[$myStaticFields[$j]]);
                }
                else
                {
                    $append = "";
                }
                $ret[] = array(
                    'field_label' => lang($myStaticFields[$j]),
                    'field_value' => (!$isNotAppend) ? $append . $member_data[$myStaticFields[$j]] : $append,
                    'field_sort_name' => $myStaticFields[$j],
                    'field_db_name' => $myStaticFields[$j],
                );
            }
        }

        if(!empty($dynamicFields))
        {
            foreach ($dynamicFields as $key => $value)
            {

                if(is_array($member_data[$key]))
                {
                    if(count($member_data[$key]) > 0)
                    {
                        $temp = "";
                        for ($j = 0; $j < count($member_data[$key]); $j++)
                        {
                            $temp .= $member_data[$key][$j][$key . ":value"] . "\n";
                        }
                        $member_data[$key] = rtrim($temp, "\n");
                    }
                    else
                    {
                        $member_data[$key] = "";
                    }
                }
                if($member_data[$key] != "")
                {
                    $ret[] = array(
                        'field_label' => $value['label'],
                        'field_value' => $member_data[$key],
                        'field_sort_name' => $value['name'],
                        'field_db_name' =>  "m_field_id_" . $value['id'],
                    );
                }
            }
        }

        for ($j = 0; $j < count($ret); $j++)
        {
            $ret[$j]['sm_list_all_fields:count'] = $j + 1;
            $ret[$j]['sm_list_all_fields:total_results'] = count($ret);
        }

        return $ret;

    }

    /**
    * Send email function
    * @param $msg_data (Array of configuration for email)
    **/
    function sendEmail($msg_data="", $source="")
    {

        if($msg_data == "") {return FALSE;}

        /*Load template parsing library*/
        ee()->load->library('template_parse', null, 'tmpl_parse');
        $data = ee()->tmpl_parse->template_parser($msg_data);

        /*Emaial initialize*/

        $data['params']['from_email'] = ee()->config->item('webmaster_email');
        $data['params']['from_name'] = ee()->config->item('webmaster_name');
        $data['params']['to'] = ee()->smModel->getMemberFieldFromEmail('email', array('member_id'=>$msg_data['member_id']));
        
        if(! isset($data['params']['attachment']))
        {
            $data['params']['attachment'] = "";
        }

        /**
        * Hook will call Before send email
        * @param $source [form status i.e, "registration", "forgot_password"]
        * @param $data
        * @return $data
        */
        if (ee()->extensions->active_hook('sm_before_send_email') === TRUE)
        {

            $tmp = ee()->extensions->call('sm_before_send_email', $source, $data);
            if(ee()->extensions->end_script === TRUE) return;

            if($tmp != "")
            {
                $data = $tmp;
                unset($tmp);
            }

        }

        ee()->email->initialize();
        ee()->email->mailtype = $data['params']['email:mailtype'];
        ee()->email->subject($data['params']['email:subject']);

        ee()->email->from($data['params']['from_email'], $data['params']['from_name']);
        ee()->email->to($data['params']['to']);
        
        ee()->email->message($data['msg_body']);
        
        if($data['params']['attachment'] !== "")
        {

            if(is_array($data['params']['attachment']))
            {

                for ($i=0; $i < count($data['params']['attachment']); $i++)
                { 
                    ee()->email->attach($data['params']['attachment'][$i]);
                }

            }
            else
            {
                ee()->email->attach($data['params']['attachment']);
            }

        }

        if(ee()->email->Send())
        {
            return true;
        }
        else
        {
            return false;
        }

    }

    /**
    * Basic configuration to set registration email
    * @param $member_id (ID of current member)
    * @return Array of config variable
    **/
    public function configRegistrationEmail($member_id)
    {

        if($this->form_settings['registration_template'] == 0 || $this->form_settings['registration_template'] == NULL)
        {
            $msg_data['registration_template'] = 0;
            $msg_data['template_id'] = 0;
        }
        else
        {
            $msg_data['registration_template'] = 1;
            $msg_data['template_id'] = $this->form_settings['registration_email_template'];
        }
        
        $msg_data['subject'] = $this->form_settings['registration_email_subject'];
        $msg_data['message_body'] = $this->form_settings['registration_email_body'];
        $msg_data['word_wrap'] = $this->form_settings['registration_email_word_wrap'];
        $msg_data['mailtype'] = $this->form_settings['registration_email_mail_type'];

        $msg_data['member_id'] = $member_id;
        $msg_data['from_email'] = ee()->config->item('webmaster_email');
        $msg_data['from_name'] = ee()->config->item('webmaster_name');

        return $msg_data;

    }

    /**
    * Basic configuration to set Forgot password email
    * @param $member_id (ID of current member)
    * @return Array of config variable
    **/
    public function configForgotPasswordEmail($member_id)
    {

        if($this->form_settings['registration_template'] == 0 || $this->form_settings['registration_template'] == NULL)
        {
            $msg_data['registration_template'] = 0;
            $msg_data['template_id'] = 0;
        }
        else
        {
            $msg_data['registration_template'] = 1;
            $msg_data['template_id'] = $this->form_settings['forgot_pass_email_template'];
        }
        
        $msg_data['reset_url'] = ee()->smModel->getTemplatePath($this->form_settings['reset_password_template']);
        $msg_data['subject'] = $this->form_settings['forgot_pass_email_subject'];
        $msg_data['message_body'] = $this->form_settings['forgot_pass_email_body'];
        $msg_data['word_wrap'] = $this->form_settings['forgot_pass_word_wrap'];
        $msg_data['mailtype'] = $this->form_settings['forgot_pass_mail_type'];

        $msg_data['member_id'] = $member_id;
        $msg_data['from_email'] = ee()->config->item('webmaster_email');
        $msg_data['from_name'] = ee()->config->item('webmaster_name');

        return $msg_data;

    }

    /**
    * Basic static fields of ExpressionEngine
    * @param $pref (String to identify status)
    * @param $source (String to identify form or profile)
    * @return Array of fields
    **/
    public function staticMemberFields($pref = "", $source = "")
    {
        return ee()->smModel->allStaticFields();
    }

    /**
    * Wrap errors method
    * @param $error (Array of errors found in POST data)
    * @param $wrap_errors (String of wrap defined by user in paramter)
    * @return String of error in wrap mode.
    **/
    public function bindErrors($error, $wrap_errors="")
    {

        /*Assign default error wrapper if not given by user*/
        if($wrap_errors == "") {$wrap_errors = "<span class='error-inline'>|</span>";}

        $wrap = explode('|', $wrap_errors);
        
        $error = str_ireplace('<p>','',$error);
        $error = str_ireplace('</p>','',$error); 
        
        return $wrap[0].$error.$wrap[1];

    }

    /**
    * EE3 method to add prefix before outline errors to identify field of error
    * @param $error (Array of errors)
    * @return Array of modify array.
    **/
    public function errorPrefix($errors)
    {

        foreach ($errors as $key => $value)
        {

            $key = str_replace('error:', '', $key);
            
            if(isset($this->fields[$key]['label']))
            {
                $errors['error:' . $key] = "<b>".$this->fields[$key]['label'].": </b>".$value;
            }

        }

        return $errors;

    }

    /*Check the new member registrations are allowed or not*/
    public function allowRegister()
    {

        if (IS_CORE)
        {
            return lang('ee_core_error');
        }

        if (ee()->config->item('allow_member_registration') == 'n') 
        {
            return lang('not_allowed_new_registrations');
        }

        if (ee()->session->userdata('member_id') != 0) {
            return lang('already_logged_in');
        }

    }

    /*Set session function*/
    function setSession($name, $data)
    {

        if(!isset($_SESSION))
        {
            session_start();
        }

        $_SESSION[$name] = $data;

    }

    /*Get session function*/
    function session($name)
    {

        if(!isset($_SESSION))
        {
            session_start();
        }

        if(isset($_SESSION[$name]))
        {
            return $_SESSION[$name];
        }

        return false;

    }

    /*Unset session function*/
    function unsetSession($name)
    {

        if(!isset($_SESSION))
        {
            session_start();
        }

        unset($_SESSION[$name]);

    }

    /*Check the user is able to submit a form or not*/
    public function checkAccessLevel()
    {

        /*Check for banned USERs*/
        if (ee()->session->userdata('is_banned') === true)
        {
            return false;
        }

        /*blacklisted member ?*/
        if (ee()->blacklist->blacklisted == 'y' && ee()->blacklist->whitelisted == 'n')
        {
            return false;
        }

        return true;

    }

    /*Set validation rules*/
    public function setValidationRules()
    {

        $rules = array();
        foreach ($this->fields as $key => $value)
        {
            if($value['rules'] != "") $rules[$key] = $value['rules'];
        }

        ee()->smValidation->validator->setRules($rules);

    }

    /*Load default ExpressionEngine classes to perform actions*/
    public function loadDefaultMemberClasses($class)
    {

        if ( ! class_exists('Member')) 
        {
            require PATH_MOD . 'member/mod.member.php';
        }

        $class = ucfirst($class);
        if ( ! class_exists($class))
        {
            require PATH_MOD . 'member/mod.' . strtolower($class) . '.php';
        }

        return new $class();

    }

    /**
    * Find history of site
    * @param %val (Number of page to go back)
    * @retun Result of URL
    **/
    public function history($val)
    {

        $track = ee()->session->tracker;

        if (isset($track[$val])) 
        {

            if ($track[$val] === 'index') 
            {
                return '/';
            }

            return $track[$val];

        }

    }

    /*Set fake output to prevent the default EE screen call*/
    public function fake_output()
    {
        ee()->load->library('sm_fake_output');

        ee()->set('old_output', ee()->output);
        ee()->remove('output');
        
        ee()->set('output', ee()->sm_fake_output);

    }

    /*Reset the original output*/
    public function release_fake_output()
    {

        ee()->remove('output');
        ee()->set('output', ee()->old_output);
        
        ee()->remove('old_output');

    }
    
    function handleBasicSettingsForm($vars)
    {

        /*Default Settings*/
        $vars['method']           = "smart_members";
        $vars['forms_setting']    = ee()->smModel->getFormSettings();
        $vars['hidden']           = array('id'=>$vars['forms_setting']['id']);
        $vars['id']               = $vars['forms_setting']['id'];

        $vars['member_fields'] = array("" => lang('select_member_field'));
        $temp    = ee()->smModel->getMemberFields();
        if($temp != false){
            foreach ($temp as $m) {
                $vars['member_fields'][$m['m_field_name']] = $m['m_field_label'];
            }
        }
        unset($temp);
        
        $temp = ee()->smModel->templates();
        $vars['templates'] = array('' => lang('select'));
        if($temp != false && is_array($temp) && count($temp) > 0){
            for ($i = 0; $i < count($temp); $i++) {
                $vars['templates'][$temp[$i]['template_id']] = $temp[$i]['group_name'] . "/" . $temp[$i]['template_name'];
            }
        }
        unset($temp);
        
        $vars['forms_setting']['self_activation'] = @unserialize(@base64_decode($vars['forms_setting']['self_activation']));

        $vars['sections'] = array(
            array(
                array(
                    'fields' => array(
                        'id' => array(
                            'type'      => 'hidden',
                            'value'     => $vars['id'],
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
                    'title'     => 'email_as_username_label',
                    'desc'      => 'email_as_username_desc',
                    'fields' => array(
                        'email_as_username' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => lang('yes'),
                                'N' => lang('no')
                            ),
                            'value' => (isset($vars['forms_setting']['email_as_username'])) ? $vars['forms_setting']['email_as_username'] : "N",
                        )
                    )
                ),

                array(
                    'title'     => 'registration_template_label',
                    'desc'      => 'registration_template_desc',
                    'fields' => array(
                        'registration_template' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                lang('default'), 
                                lang('front_end_template')
                            ),
                            'group_toggle' => array(
                                0 => 'email_template_setting',
                                1 => 'email_templates',
                            ),
                            'value' => (isset($vars['forms_setting']['registration_template'])) ? $vars['forms_setting']['registration_template'] : "0",
                        )
                    )
                ),

                array(
                    'title'     => 'reset_key_expiration_hours_label',
                    'desc'      => 'reset_key_expiration_hours_desc',
                    'fields' => array(
                        'reset_key_expiration_hours' => array(
                            'type' => 'text',
                            'value' => (isset($vars['forms_setting']['reset_key_expiration_hours'])) ? $vars['forms_setting']['reset_key_expiration_hours'] : "",
                        )
                    )
                ),

                array(
                    'title'     => 'screen_name_override_label',
                    'desc'      => 'screen_name_override_desc',
                    'fields' => array(
                        'screen_name_override' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => lang('yes'),
                                'N' => lang('no')
                            ),
                            'group_toggle' => array(
                                'Y' => 'screen_name_field',
                            ),  
                            'value' => (isset($vars['forms_setting']['screen_name_override'])) ? $vars['forms_setting']['screen_name_override'] : "N",
                        )
                    )
                ),

                array(
                    'group' => 'screen_name_field',
                    'title'     => 'screen_name_field_label',
                    'desc'      => 'screen_name_field_desc',
                    'fields' => array(
                        'screen_name_field' => array(
                            'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                            'choices' => $vars['member_fields'],
                            'value' => (isset($vars['forms_setting']['screen_name_field'])) ? $vars['forms_setting']['screen_name_field'] : "",
                        )
                    )
                ),

                array(
                    'title'     => 'enable_recaptcha_label',
                    'desc'      => 'enable_recaptcha_desc',
                    'fields' => array(
                        'enable_recaptcha' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => lang('yes'),
                                'N' => lang('no')
                            ),
                            'group_toggle' => array(
                                'Y' => 'recaptcha_site_key',
                            ),
                            'value' => (isset($vars['forms_setting']['enable_recaptcha'])) ? $vars['forms_setting']['enable_recaptcha'] : "",
                        )
                    )
                ),

                array(
                    'group' => 'recaptcha_site_key',
                    'title'     => 'recaptcha_site_key_label',
                    'desc'      => 'recaptcha_site_key_desc',
                    'fields' => array(
                        'recaptcha_site_key' => array(
                            'type' => 'text',
                            'value' => (isset($vars['forms_setting']['recaptcha_site_key'])) ? $vars['forms_setting']['recaptcha_site_key'] : "",
                        )
                    )
                ),

                array(
                    'group' => 'recaptcha_site_key',
                    'title'     => 'recaptcha_secret_label',
                    'desc'      => 'recaptcha_secret_desc',
                    'fields' => array(
                        'recaptcha_secret' => array(
                            'type' => 'text',
                            'value' => (isset($vars['forms_setting']['recaptcha_secret'])) ? $vars['forms_setting']['recaptcha_secret'] : "",
                        )
                    )
                ),
            ),
            
            'email_template_setting' => array(
                'group' => 'email_template_setting',
                'settings' => array(
                    array(
                        'title'     => 'registration_email_subject_label',
                        'desc'      => 'registration_email_subject_desc',
                        'fields' => array(
                            'registration_email_subject' => array(
                                'type' => 'text',
                                'value' => (isset($vars['forms_setting']['registration_email_subject'])) ? $vars['forms_setting']['registration_email_subject'] : "",
                            )
                        )
                    ),

                    array(
                        'title'     => 'registration_email_word_wrap_label',
                        'desc'      => 'registration_email_word_wrap_desc',
                        'fields' => array(
                            'registration_email_word_wrap' => array(
                                'type' => 'inline_radio',
                                'choices' => array(
                                    'yes' => lang('yes'),
                                    'no' => lang('no')
                                ),
                                'value' => (isset($vars['forms_setting']['registration_email_word_wrap'])) ? $vars['forms_setting']['registration_email_word_wrap'] : "no",
                            )
                        )
                    ),

                    array(
                        'title'     => 'registration_email_mail_type_label',
                        'desc'      => 'registration_email_mail_type_desc',
                        'fields' => array(
                            'registration_email_mail_type' => array(
                                'type' => 'inline_radio',
                                'choices' => array(
                                    'html' => lang('html'),
                                    'text' => lang('text')
                                ),
                                'value' => (isset($vars['forms_setting']['registration_email_mail_type'])) ? $vars['forms_setting']['registration_email_mail_type'] : "text",
                            )
                        )
                    ),

                    array(
                        'title'     => 'registration_email_body_label',
                        'desc'      => 'registration_email_body_desc',
                        'fields' => array(
                            'registration_email_body' => array(
                                'type' => 'textarea',
                                'value' => (isset($vars['forms_setting']['registration_email_body'])) ? $vars['forms_setting']['registration_email_body'] : "",
                            )
                        )
                    ),
                    
                    array(
                        'title'     => 'forgot_pass_email_subject_label',
                        'desc'      => 'forgot_pass_email_subject_desc',
                        'fields' => array(
                            'forgot_pass_email_subject' => array(
                                'type' => 'text',
                                'value' => (isset($vars['forms_setting']['forgot_pass_email_subject'])) ? $vars['forms_setting']['forgot_pass_email_subject'] : "",
                            )
                        )
                    ),

                    array(
                        'title'     => 'forgot_pass_word_wrap_label',
                        'desc'      => 'forgot_pass_word_wrap_desc',
                        'fields' => array(
                            'forgot_pass_word_wrap' => array(
                                'type' => 'inline_radio',
                                'choices' => array(
                                    'yes' => lang('yes'),
                                    'no' => lang('no')
                                ),
                                'value' => (isset($vars['forms_setting']['forgot_pass_word_wrap'])) ? $vars['forms_setting']['forgot_pass_word_wrap'] : "no",
                            )
                        )
                    ),

                    array(
                        'title'     => 'forgot_pass_mail_type_label',
                        'desc'      => 'forgot_pass_mail_type_desc',
                        'fields' => array(
                            'forgot_pass_mail_type' => array(
                                'type' => 'inline_radio',
                                'choices' => array(
                                    'html' => lang('html'),
                                    'text' => lang('text')
                                ),
                                'value' => (isset($vars['forms_setting']['forgot_pass_mail_type'])) ? $vars['forms_setting']['forgot_pass_mail_type'] : "text",
                            )
                        )
                    ),

                    array(
                        'title'     => 'reset_password_template_label',
                        'desc'      => 'reset_password_template_desc',
                        'fields' => array(
                            'reset_password_template' => array(
                                'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                                'choices' => $vars['templates'],
                                'value' => (isset($vars['forms_setting']['reset_password_template'])) ? $vars['forms_setting']['reset_password_template'] : "",
                            )
                        )
                    ),

                    array(
                        'title'     => 'forgot_pass_email_body_label',
                        'desc'      => 'forgot_pass_email_body_desc',
                        'fields' => array(
                            'forgot_pass_email_body' => array(
                                'type' => 'textarea',
                                'value' => (isset($vars['forms_setting']['forgot_pass_email_body'])) ? $vars['forms_setting']['forgot_pass_email_body'] : "",
                            )
                        )
                    ),
                ),
            ),

            'email_templates' => array(
                'group' => 'email_templates',
                'settings' => array(
                    array(
                        'title'     => 'registration_email_template_label',
                        'desc'      => 'registration_email_template_desc',
                        'fields' => array(
                            'registration_email_template' => array(
                                'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                                'choices' => $vars['templates'],
                                'value' => (isset($vars['forms_setting']['registration_email_template'])) ? $vars['forms_setting']['registration_email_template'] : "",
                            )
                        )
                    ),
                    array(
                        'title'     => 'forgot_pass_email_template_label',
                        'desc'      => 'forgot_pass_email_template_desc',
                        'fields' => array(
                            'forgot_pass_email_template' => array(
                                'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                                'choices' => $vars['templates'],
                                'value' => (isset($vars['forms_setting']['forgot_pass_email_template'])) ? $vars['forms_setting']['forgot_pass_email_template'] : "",
                            )
                        )
                    ),
                )
            ),

            'override_self_activation' => array(
                array(
                    'title'     => 'self_activation_label',
                    'desc'      => 'self_activation_desc',
                    'fields' => array(
                        'self_activation[override]' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => lang('yes'),
                                'N' => lang('no')
                            ),
                            'group_toggle' => array(
                                'Y' => 'self_activation_redirect',
                            ),  
                            'value' => (isset($vars['forms_setting']['self_activation']['override'])) ? $vars['forms_setting']['self_activation']['override'] : "N",
                        )
                    )
                ),

                array(
                    'group'     => 'self_activation_redirect',
                    'title'     => 'self_activation_redirect_label',
                    'desc'      => 'self_activation_redirect_desc',
                    'fields' => array(
                        'self_activation[redirect]' => array(
                            'type'      => "text",
                            'value'     => (isset($vars['forms_setting']['self_activation']['redirect'])) ? $vars['forms_setting']['self_activation']['redirect'] : "",
                        )
                    )
                ),
                array(
                    'group'     => 'self_activation_redirect',
                    'title'     => 'self_activation_auto_login_label',
                    'desc'      => 'self_activation_auto_login_desc',
                    'fields' => array(
                        'self_activation[auto_login]' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => lang('yes'),
                                'N' => lang('no')
                            ),
                            'value'     => (isset($vars['forms_setting']['self_activation']['auto_login']) && $vars['forms_setting']['self_activation']['auto_login'] == "Y") ? "Y" : "N",
                        )
                    )
                ),
            ),
        );

        $vars += array(
            'base_url' => ee('CP/URL', 'addons/settings/smart_members/index'),
            'cp_page_title' => lang('lable_title_index'),
            'save_btn_text' => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );

        return $vars;
    }

    function handleBasicSettingsFormPost($post)
    {
        unset($post['submit']);
        unset($post['XID']);
        unset($post['csrf_token']);
        
        $post['self_activation'] = base64_encode(serialize($post['self_activation']));
        ee()->smModel->updateBasicForm($post);

        return true;
    }

    function handlememberPreferencesForm($vars)
    {

        //for ee6
        $data = ee()->smModel->getMemberPreferences($this->site_id);

        if($data === false)
        {
            return false;
        }
        
        $preference = array(
            'enable_avatars'        => isset($data['enable_avatars']) ? $data['enable_avatars'] : '' ,
            'allow_avatar_uploads'  => isset($data['allow_avatar_uploads']) ? $data['allow_avatar_uploads'] : '' ,
            'avatar_url'            => $data['avatar_url'],
            'avatar_path'           => $data['avatar_path'],
            'avatar_max_width'      => $data['avatar_max_width'],
            'avatar_max_height'     => $data['avatar_max_height'],
            'avatar_max_kb'         => $data['avatar_max_kb'],

            'enable_photos'         => $data['enable_photos'],
            'photo_url'             => $data['photo_url'],
            'photo_path'            => $data['photo_path'],
            'photo_max_width'       => $data['photo_max_width'],
            'photo_max_height'      => $data['photo_max_height'],
            'photo_max_kb'          => $data['photo_max_kb'],

            'sig_allow_img_upload'  => $data['sig_allow_img_upload'],
            'sig_img_url'           => $data['sig_img_url'],
            'sig_img_path'          => $data['sig_img_path'],
            'sig_img_max_width'     => $data['sig_img_max_width'],
            'sig_img_max_height'    => $data['sig_img_max_height'],
            'sig_img_max_kb'        => $data['sig_img_max_kb'],
        );

        $vars['sections'] = array(
            'avatar_settings' => array(
                array(
                    'title'     => 'enable_avatars_label',
                    'desc'      => 'enable_avatars_desc',
                    'fields' => array(
                        'enable_avatars' => array(
                            'type'  => 'yes_no',
                            'value' => $preference['enable_avatars'],
                        )
                    )
                ),
                array(
                    'title'     => 'allow_avatar_uploads_label',
                    'desc'      => 'allow_avatar_uploads_desc',
                    'fields' => array(
                        'allow_avatar_uploads' => array(
                            'type'  => 'yes_no',
                            'value' => $preference['allow_avatar_uploads'],
                        )
                    )
                ),
            ),
            'url_and_path_settings1' => array(
                array(
                    'title'     => 'avatar_url_label',
                    'desc'      => 'avatar_url_desc',
                    'fields' => array(
                        'avatar_url' => array(
                            'type'  => 'text',
                            'value' => $preference['avatar_url'],
                        )
                    )
                ),
                array(
                    'title'     => 'avatar_path_label',
                    'desc'      => 'avatar_path_desc',
                    'fields' => array(
                        'avatar_path' => array(
                            'type'  => 'text',
                            'value' => $preference['avatar_path'],
                        )
                    )
                ),
            ),
            'avatar_file_restrictions' => array(
                array(
                    'title'     => 'avatar_max_width_label',
                    'desc'      => 'avatar_max_width_desc',
                    'fields' => array(
                        'avatar_max_width' => array(
                            'type'  => 'text',
                            'value' => $preference['avatar_max_width'],
                        )
                    )
                ),
                array(
                    'title'     => 'avatar_max_height_label',
                    'desc'      => 'avatar_max_height_desc',
                    'fields' => array(
                        'avatar_max_height' => array(
                            'type'  => 'text',
                            'value' => $preference['avatar_max_height'],
                        )
                    )
                ),
                array(
                    'title'     => 'avatar_max_kb_label',
                    'desc'      => 'avatar_max_kb_desc',
                    'fields' => array(
                        'avatar_max_kb' => array(
                            'type'  => 'text',
                            'value' => $preference['avatar_max_kb'],
                        )
                    )
                ),
            ),
            'member_profile_photo_settings' => array(
                array(
                    'title'     => 'enable_photos_label',
                    'desc'      => 'enable_photos_desc',
                    'fields' => array(
                        'enable_photos' => array(
                            'type'  => 'yes_no',
                            'value' => $preference['enable_photos'],
                        )
                    )
                ),
            ),
            'url_and_path_settings2' => array(
                array(
                    'title'     => 'photo_url_label',
                    'desc'      => 'photo_url_desc',
                    'fields' => array(
                        'photo_url' => array(
                            'type'  => 'text',
                            'value' => $preference['photo_url'],
                        )
                    )
                ),
                array(
                    'title'     => 'photo_path_label',
                    'desc'      => 'photo_path_desc',
                    'fields' => array(
                        'photo_path' => array(
                            'type'  => 'text',
                            'value' => $preference['photo_path'],
                        )
                    )
                ),
            ),
            'photo_file_restrictions' => array(
                array(
                    'title'     => 'photo_max_width_label',
                    'desc'      => 'photo_max_width_desc',
                    'fields' => array(
                        'photo_max_width' => array(
                            'type'  => 'text',
                            'value' => $preference['photo_max_width'],
                        )
                    )
                ),
                array(
                    'title'     => 'photo_max_height_label',
                    'desc'      => 'photo_max_height_desc',
                    'fields' => array(
                        'photo_max_height' => array(
                            'type'  => 'text',
                            'value' => $preference['photo_max_height'],
                        )
                    )
                ),
                array(
                    'title'     => 'photo_max_kb_label',
                    'desc'      => 'photo_max_kb_desc',
                    'fields' => array(
                        'photo_max_kb' => array(
                            'type'  => 'text',
                            'value' => $preference['photo_max_kb'],
                        )
                    )
                ),
            ),
            'member_signature_photo_settings' => array(
                array(
                    'title'     => 'sig_allow_img_upload_label',
                    'desc'      => 'sig_allow_img_upload_desc',
                    'fields' => array(
                        'sig_allow_img_upload' => array(
                            'type'  => 'yes_no',
                            'value' => $preference['sig_allow_img_upload'],
                        )
                    )
                ),
            ),
            'url_and_path_settings3' => array(
                array(
                    'title'     => 'sig_img_url_label',
                    'desc'      => 'sig_img_url_desc',
                    'fields' => array(
                        'sig_img_url' => array(
                            'type'  => 'text',
                            'value' => $preference['sig_img_url'],
                        )
                    )
                ),
                array(
                    'title'     => 'sig_img_path_label',
                    'desc'      => 'sig_img_path_desc',
                    'fields' => array(
                        'sig_img_path' => array(
                            'type'  => 'text',
                            'value' => $preference['sig_img_path'],
                        )
                    )
                ),
            ),
            'signature_file_restrictions' => array(
                array(
                    'title'     => 'sig_img_max_width_label',
                    'desc'      => 'sig_img_max_width_desc',
                    'fields' => array(
                        'sig_img_max_width' => array(
                            'type'  => 'text',
                            'value' => $preference['sig_img_max_width'],
                        )
                    )
                ),
                array(
                    'title'     => 'sig_img_max_height_label',
                    'desc'      => 'sig_img_max_height_desc',
                    'fields' => array(
                        'sig_img_max_height' => array(
                            'type'  => 'text',
                            'value' => $preference['sig_img_max_height'],
                        )
                    )
                ),
                array(
                    'title'     => 'sig_img_max_kb_label',
                    'desc'      => 'sig_img_max_kb_desc',
                    'fields' => array(
                        'sig_img_max_kb' => array(
                            'type'  => 'text',
                            'value' => $preference['sig_img_max_kb'],
                        )
                    )
                ),
            ),
        );

        // for ee6
        if (ee()->db->table_exists('config'))
        {
            //remove the avatat setting.
            unset($vars['sections']['avatar_settings']);    
        }

        $vars += array(
            'base_url'              => ee('CP/URL', 'addons/settings/smart_members/member_preferences'),
            'cp_page_title'         => lang('member_preferences_title'),
            'save_btn_text'         => 'btn_save_settings',
            'save_btn_text_working' => 'btn_saving'
        );
        
        return $vars;

    }

    function handlememberPreferencesFormPost($post)
    {

        //for ee6
        $data = ee()->smModel->getMemberPreferences($this->site_id);

        if(isset($data['enable_avatars'])){
            $data['enable_avatars']         = isset($post['enable_avatars'])       ? $post['enable_avatars']          : $data['enable_avatars'];
        }
        if(isset($data['allow_avatar_uploads'])){
            $data['allow_avatar_uploads']   = isset($post['allow_avatar_uploads']) ? $post['allow_avatar_uploads']    : $data['allow_avatar_uploads'];
        }
        $data['avatar_url']             = isset($post['avatar_url'])           ? $post['avatar_url']              : $data['avatar_url'];
        $data['avatar_path']            = isset($post['avatar_path'])          ? $post['avatar_path']             : $data['avatar_path'];
        $data['avatar_max_width']       = isset($post['avatar_max_width'])     ? $post['avatar_max_width']        : $data['avatar_max_width'];
        $data['avatar_max_height']      = isset($post['avatar_max_height'])    ? $post['avatar_max_height']       : $data['avatar_max_height'];
        $data['avatar_max_kb']          = isset($post['avatar_max_kb'])        ? $post['avatar_max_kb']           : $data['avatar_max_kb'];
        
        $data['enable_photos']          = isset($post['enable_photos'])        ? $post['enable_photos']           : $data['enable_photos'];
        $data['photo_url']              = isset($post['photo_url'])            ? $post['photo_url']               : $data['photo_url'];
        $data['photo_path']             = isset($post['photo_path'])           ? $post['photo_path']              : $data['photo_path'];
        $data['photo_max_width']        = isset($post['photo_max_width'])      ? $post['photo_max_width']         : $data['photo_max_width'];
        $data['photo_max_height']       = isset($post['photo_max_height'])     ? $post['photo_max_height']        : $data['photo_max_height'];
        $data['photo_max_kb']           = isset($post['photo_max_kb'])         ? $post['photo_max_kb']            : $data['photo_max_kb'];
        
        $data['sig_allow_img_upload']   = isset($post['sig_allow_img_upload']) ? $post['sig_allow_img_upload']    : $data['sig_allow_img_upload'];
        $data['sig_img_url']            = isset($post['sig_img_url'])          ? $post['sig_img_url']             : $data['sig_img_url'];
        $data['sig_img_path']           = isset($post['sig_img_path'])         ? $post['sig_img_path']            : $data['sig_img_path'];
        $data['sig_img_max_width']      = isset($post['sig_img_max_width'])    ? $post['sig_img_max_width']       : $data['sig_img_max_width'];
        $data['sig_img_max_height']     = isset($post['sig_img_max_height'])   ? $post['sig_img_max_height']      : $data['sig_img_max_height'];
        $data['sig_img_max_kb']         = isset($post['sig_img_max_kb'])       ? $post['sig_img_max_kb']          : $data['sig_img_max_kb'];

        $data = base64_encode(serialize($data));

        ee()->smModel->updateMemberPreferences($data, $this->site_id);
        return true;
    }

    function handleSocialSettingsList($vars)
    {

        /*Load helpful classes*/
        if(! class_exists('social_login_lib'))
        {
            ee()->load->library('social_login_lib', null, 'sl');
        }

        /*Default Settings*/
        $vars['all_settings'] = $this->url('social_settings', array('form_id' => "all"));

        /* Make table for displaying listing */
        $table = ee('CP/Table', array(
            'sortable'  => false,
            'reorder'   => false
        ));

        /* Make table columns headings for displaying listing */
        $table->setColumns(
            array(
                'provider'      => array('encode' => FALSE),
                'short_name'    => array('encode' => FALSE),
                'callback_url'  => array('encode' => FALSE),
                'status'        => array('encode' => FALSE),
                'manage'        => array('type'  => Table::COL_TOOLBAR)
            )
        );

        /* Set no result text if no data found */
        $table->setNoResultsText(lang('no_social_settings_found'));

        $forms_setting = ee()->slModel->getSocialFormSettings();

        $fieldData = array();
        for ($i=0; $i < count($forms_setting); $i++)
        { 

            $columns = array();
            $forms_setting[$i]['settings'] = unserialize($forms_setting[$i]['settings']);

            $columns['provider']   = $forms_setting[$i]['settings']['label'];
            $columns['short_name'] = $forms_setting[$i]['short_name'];

            if($forms_setting[$i]['settings']['call_back_url'] == 1)
            {
                $columns['callback_url'] = lang('default_callback_url');
            }
            elseif($forms_setting[$i]['settings']['call_back_url'] == 2)
            {
                $columns['callback_url'] = lang('custom_callback_url');
            }
            else
            {
                $columns['callback_url'] = lang('not_specified');
            }

            if($forms_setting[$i]['key'] == "" || $forms_setting[$i]['secret'] == "" || $forms_setting[$i]['key'] == NULL || $forms_setting[$i]['secret'] == NULL)
            {
                $columns['status'] = '<span class="inactive">' . lang('inactive') . '</div>';
            }
            else
            {
                $columns['status'] = '<span class="active">' . lang('active') . '</div>';
            }
            
            $columns['manage'] = array('toolbar_items' => 
                array(
                    'edit' => array(
                        'href'      => $this->url('social_settings', array('form_id' => $forms_setting[$i]['id'])),
                        'title'     => strtolower(lang('edit'))
                    ),
                ),
            );

            $attrs = array();
            if (ee()->session->flashdata('return_id') == $forms_setting[$i]['id'])
            {
                $attrs = array('class' => 'selected');
            }

            $fieldData[] = array(
                'attrs' => $attrs,
                'columns' => $columns
            );

        }

        $table->setData($fieldData);
        unset($forms_setting);
        $vars['table'] = $table->viewData($this->url('social_settings_list'));

        $action_id = ee()->smModel->getActionID("sm_social_api");
        $url = "?ACT=".$action_id;
        $vars['default_callback_url'] = ee()->functions->create_url($url);
        $vars['sl_callback_url'] = ee()->slModel->slCallbackURL();

        $vars['sections'] = array(
            'basic_settings' => array(
                array(
                    'title'     => 'default_callback_url_label',
                    'desc'      => 'default_callback_url_desc',
                    'fields' => array(
                        'default_callback_url' => array(
                            'type'      => 'text',
                            'value'     => $vars['default_callback_url'],
                            'disabled'  => true
                        ),
                        'callback_url' => array(
                            'type'          => 'html',
                            'content'       => '<div class="test-btn"><a href="' . $vars['default_callback_url'] . '" class="btn action" target="_blank">Test</a></div>',
                            'margin_top'    => 0, 
                        )
                    ),
                    'attrs' => array(
                        'class' => 'merge-btn',
                    ),
                ),
                array(
                    'title'     => 'sl_callback_url_label',
                    'desc'      => 'sl_callback_url_desc',
                    'fields' => array(
                        'sl_callback_url' => array(
                            'type'  => 'text',
                            'value' => $vars['sl_callback_url'],
                        ),
                        'callback_url' => array(
                            'type'      => 'html',
                            'content'   =>  
                            '<div class="test-btn"><a href="' . $vars['sl_callback_url'] . '" class="btn action" target="_blank">Test</a></div>',
                            'margin_top'  => 0, 
                        )
                    ),
                    'attrs' => array(
                        'class' => 'merge-btn',
                    ),
                ),
            ),
        );

        $vars += array(
            'base_url'              => $this->url('social_settings_list'),
            'cp_page_title'         => lang('social_settings'),
            'save_btn_text'         => 'btn_change_callback_url',
            'save_btn_text_working' => 'btn_saving'
        );
        return $vars;

    }

    function handleSocialSettingsForm($vars)
    {

        if(! class_exists('social_login_lib'))
        {
            ee()->load->library('social_login_lib', null, 'sl');
        }

        $vars['forms_setting']    = ee()->slModel->getSocialFormSettings($vars['form_id']);
        if($vars['forms_setting'] === false)
        {
            show_error(lang('entered_wrong_form_id'));
        }

        $action_id  = ee()->smModel->getActionID("sm_social_api");
        $url        = "?ACT=".$action_id;

        $vars['default_callback_url'] = ee()->functions->create_url($url);
        $vars['sl_callback_url']      = ee()->slModel->slCallbackURL();

        $temp = ee()->smModel->getMemberFields();
        $vars['member_fields'] = array();
        if(is_array($temp) && count($temp) > 0)
        {
            for ($i = 0; $i < count($temp); $i++)
            {
                $vars['member_fields'][$temp[$i]['m_field_id']] = $temp[$i]['m_field_label'];
            }
        }

        $temp = ee()->ieModel->getMemberGroups("social");
        $vars['member_groups'] = array();
        if(is_array($temp) && count($temp) > 0)
        {
            for ($i = 0; $i < count($temp); $i++) {
                $vars['member_groups'][$temp[$i]['group_id']] = $temp[$i]['group_title'];
            }
        }
        
        /* Shared form array */
        $vars['sections'] = array(
            array(
                array(
                    'fields'   => array(
                        'form_id' => array(
                            'type'  => 'hidden',
                            'value' => $vars['form_id']
                        ),
                    ),
                    'attrs' => array(
                        'class' => 'last hidden'
                    ),
                ),
            ),
        );

        $elem = "";
        for ($i=0; $i < count($vars['forms_setting']); $i++)
        { 

            $vars['forms_setting'][$i]['settings'] = unserialize($vars['forms_setting'][$i]['settings']);
            $elem = $vars['forms_setting'][$i]['short_name'] . '_settings';
            $vars['sections'][$elem] = array(
                array(
                    'title'  => 'important_urls',
                    'fields' => array(
                        'important_urls' => array(
                            'type' => 'html',
                            'content' => '<div class="set_p">' . lang('generate_credentials_from') . ' <a href="' . $vars['forms_setting'][$i]['settings']['dev_url'] . '" target="_blank">' . lang('here') . ' </a> <br />' .
                            lang('more_information_about_this') . ' <a href="' . $vars['forms_setting'][$i]['settings']['more_info'] . '" target="_blank">' . lang('here') . ' </a> </div>',
                        ),
                    ),
                ),
                array(
                    'title' => 'call_back_url',
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[settings][call_back_url]' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                '1' => lang('default_callback_url'),
                                '2' => lang('custom_callback_url')
                            ),
                            'value' => (isset($vars['forms_setting'][$i]['settings']['call_back_url'])) ? $vars['forms_setting'][$i]['settings']['call_back_url'] : "1",
                        )
                    )
                ),

                array(
                    'title' => $vars['forms_setting'][$i]['settings']['Key_label'],
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[key]' => array(
                            'type' => 'text',
                            'value' => (isset($vars['forms_setting'][$i]['key'])) ? $vars['forms_setting'][$i]['key'] : "",
                            'attrs' => 'class="chk_for_filter"',
                        )
                    )
                ),

                array(
                    'title' => $vars['forms_setting'][$i]['settings']['secret_label'],
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[secret]' => array(
                            'type' => 'text',
                            'value' => (isset($vars['forms_setting'][$i]['secret'])) ? $vars['forms_setting'][$i]['secret'] : "",
                            'attrs' => 'class="chk_for_filter"',
                        )
                    )
                ),
                
                array(
                    'title' => (SM_APP_VER_L6) ? 'member_group_to_assign' : 'member_role_to_assign',
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[settings][member_group]' => array(
                            'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                            'choices' => $vars['member_groups'],
                            'value' => (isset($vars['forms_setting'][$i]['settings']['member_group'])) ? $vars['forms_setting'][$i]['settings']['member_group'] : "",
                        )
                    )
                ),

                array(
                    'title' => (SM_APP_VER_L6) ? 'member_pending_if_no_email' : 'member_pending_role_if_no_email',
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[settings][pending_if_no_email]' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => 'Yes',
                                'N' => 'No',
                            ),
                            'value' => (isset($vars['forms_setting'][$i]['settings']['pending_if_no_email'])) ? $vars['forms_setting'][$i]['settings']['pending_if_no_email'] : "N",
                        )
                    )
                ),

                array(
                    'title' => 'use_email_as_username',
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[settings][email_as_username]' => array(
                            'type' => 'inline_radio',
                            'choices' => array(
                                'Y' => 'Yes',
                                'N' => 'No',
                            ),
                            'value' => (isset($vars['forms_setting'][$i]['settings']['email_as_username'])) ? $vars['forms_setting'][$i]['settings']['email_as_username'] : "N",
                        )
                    )
                ),

                array(
                    'title' => $vars['forms_setting'][$i]['settings']['custom_field_label'],
                    'fields' => array(
                        $vars['forms_setting'][$i]['id'] . '[settings][custom_field_uname]' => array(
                            'type' => (version_compare(APP_VER, '4.0.0', '<')) ? 'select' : 'dropdown',
                            'choices' => $vars['member_fields'],
                            'value' => (isset($vars['forms_setting'][$i]['settings']['custom_field_uname'])) ? $vars['forms_setting'][$i]['settings']['custom_field_uname'] : "",
                        )
                    ),
                ),

            );
        }

        $vars['sections'][$elem][] = array(
            'fields' => array(
                'last' => array(
                    'type' => 'html',   
                    'content' => '',
                )
            ),
            'attrs' => array(
                'class' => 'last_fieldset hidden last'
            )
        );

        if(count($vars['forms_setting']) == 1)
        {
            $vars['heading'] = $vars['forms_setting'][0]['short_name'] . '_settings';
        }
        else
        {
            $vars['heading'] = "all_social_settings";
        }

        $vars += array(
            'base_url'              => $this->url('social_settings', array('form_id' => $vars['form_id'])),
            'cp_page_title'         => lang('social_settings'),
            'save_btn_text'         => 'save',
            'save_btn_text_working' => 'btn_saving',
        );

        return $vars;

    }

    function handleSocialSettingsFormPost()
    {

        if(! class_exists('social_login_lib'))
        {
            ee()->load->library('social_login_lib', null, 'sl');
        }

        $ret = ee()->sl->saveSocialSettings();
        return $ret;
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

        $url = 'addons/settings/smart_members/' . $method;
        if(is_array($parameters) && count($parameters) > 0)
        {
            foreach ($parameters as $key => $value)
            {
                $url .= "/" . $value;
            }
        }
        
        return ee('CP/URL')->make($url);

    }

    function insertUpdateMemberData($member_id, $data)
    {

        $firstKey = array_keys(array_slice($data, 0, 1, TRUE));
        $firstKey = $firstKey[0];

        if(ee()->db->table_exists('member_data_field_' . $firstKey))
        {

            foreach ($data as $key => $value)
            {
                $found = ee()->db->where('member_id', $member_id)->get('member_data_field_' . $key)->num_rows;
                if($found > 0){
                    $this->db->where('member_id', $member_id);
                    $this->db->update('member_data_field_' . $key, array('member_id' => $member_id, 'm_field_id_' . $key => $value));
                } else {
                    $this->db->insert('member_data_field_' . $key, array('member_id' => $member_id, 'm_field_id_' . $key => $value));
                }
            }

        }
        else
        {

            $found = ee()->db->where('member_id', $member_id)->get('member_data')->num_rows;
            $data['member_id'] = $member_id;
            if($found > 0){
                $this->db->where('member_id', $member_id);
                $this->db->update('member_data', $data);
            } else {
                $this->db->insert('member_data', $data);
            }

        }

        $found = ee()->db->where('member_id', $member_id)->get('member_data')->num_rows;
        if($found == 0){
            $this->db->insert('member_data', array('member_id' => $member_id));
        }

    }

    function handleValidateMemberHook($returnMember = false)
    {

        /*Get list of members who are remaing to send registration confirmation email*/
        $remain_to_send_mail = ee()->smModel->getMembersFromMailingList();
        
        if($remain_to_send_mail !== FALSE)
        {

            $change_ids = array();

            foreach ($remain_to_send_mail as $key => $value) 
            {
            
                if(! ($value['group_id'] == 0 || $value['group_id'] == '0' || $value['group_id'] == "" || $value['group_id'] == 4 || $value['group_id'] == NULL))
                {
                    $updated_members = ee()->smModel->updateMemberGroup($value['member_id'], $value['group_id']);
                }
                
                /*Send email*/
                $msg_data = ee()->sm->configRegistrationEmail($value['member_id']);

                if(ee()->sm->sendEmail($msg_data, "registration"))
                {
                    $change_ids[] = $value['member_id'];
                }
            
            }

            /*remove the user from "remain to send email" list*/
            if(count($change_ids) > 0){
                $updated_rows = ee()->smModel->updateMailingList($change_ids);
            }

        }
        
        if($returnMember === true && isset($change_ids[0]) && $change_ids[0] != "")
        {
            return $change_ids[0];
        }

        return true;

    }

    //ee6: This function only used for the ee6 to register member, We added function here because this type of core function return the value in ee6 so we can not rely on it' 
    public function register_member_v6()
    {
        // Do we allow new member registrations?
        if (ee()->config->item('allow_member_registration') == 'n')
        {
            return FALSE;
        }

        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_link = ee()->functions->determine_return();
        $return_error_link = ee()->functions->determine_error_return();

        // Is user banned?
        if (ee()->session->userdata('is_banned') === TRUE)
        {
            return ee()->output->show_user_error(
                'general',
                array(lang('not_authorized'))
            );
        }

        // Blocked/Allowed List Check
        if (ee()->blockedlist->blocked == 'y' &&
            ee()->blockedlist->allowed == 'n')
        {
            return ee()->output->show_user_error(
                'general',
                array(lang('not_authorized'))
            );
        }

        ee()->load->helper('url');

        // -------------------------------------------
        // 'member_member_register_start' hook.
        //  - Take control of member registration routine
        //  - Added EE 1.4.2
        //
            ee()->extensions->call('member_member_register_start');
            if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------

        // Set the default globals
        $default = array(
            'username', 'password', 'password_confirm', 'email',
            'screen_name'
        );

        foreach ($default as $val)
        {
            if ( ! isset($_POST[$val])) $_POST[$val] = '';
        }

        if ($_POST['screen_name'] == '')
        {
            $_POST['screen_name'] = $_POST['username'];
        }


        // Do we have any custom fields?
        $query = ee()->db->select('m_field_id, m_field_name, m_field_label, m_field_type, m_field_list_items, m_field_required')
                              ->where('m_field_reg', 'y')
                              ->get('member_fields');

        $cust_errors = array();
        $custom_data = array();

        if ($query->num_rows() > 0)
        {
            $member_field_ids = array();
            foreach ($query->result_array() as $row)
            {
                $member_field_ids[] = $row['m_field_id'];
            }

            $member_fields = ee('Model')->get('MemberField', $member_field_ids)
                ->all()
                ->indexBy('m_field_id');

            foreach ($query->result_array() as $row)
            {
                $field_name = 'm_field_id_'.$row['m_field_id'];

                // Assume we're going to save this data, unless it's empty to begin with
                $valid = isset($_POST[$field_name]) && $_POST[$field_name] != '';

                // Basic validations
                if ($row['m_field_type'] == 'select' && $valid)
                {
                    $field_model = $member_fields[$row['m_field_id']];
                    $field_settings = $field_model->getField()->getItem('field_settings');

                    // Get field options
                    if (isset($field_settings['value_label_pairs']) && ! empty($field_settings['value_label_pairs']))
                    {
                        $options = array_keys($field_settings['value_label_pairs']);
                    }
                    else
                    {
                        $options = explode("\n", $row['m_field_list_items']);
                    }

                    // Ensure their selection is actually a valid choice
                    if (! in_array(htmlentities($_POST[$field_name]), $options))
                    {
                        $valid = FALSE;
                        $cust_errors[] = lang('mbr_field_invalid').'&nbsp;'.$row['m_field_label'];
                    }
                }

                if ($valid)
                {
                    $custom_data[$field_name] = ee('Security/XSS')->clean($_POST[$field_name]);
                }
            }
        }

        if (isset($_POST['email_confirm']) && $_POST['email'] != $_POST['email_confirm'])
        {
            $cust_errors[] = lang('mbr_emails_not_match');
        }

        if (ee('Captcha')->shouldRequireCaptcha())
        {
            if ( ! isset($_POST['captcha']) OR $_POST['captcha'] == '')
            {
                $cust_errors[] = lang('captcha_required');
            }
        }

        if (ee()->config->item('require_terms_of_service') == 'y')
        {
            if ( ! isset($_POST['accept_terms']))
            {
                $cust_errors[] = lang('mbr_terms_of_service_required');
            }
        }


        // -------------------------------------------
        // 'member_member_register_errors' hook.
        //  - Additional error checking prior to submission
        //  - Added EE 2.5.0
        //
            ee()->extensions->call('member_member_register_errors', $this);
            if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------


        ee()->load->helper('security');

        // Assign the data
        $data = array(
            'username'      => trim_nbs(ee()->input->post('username')),
            'password'      => ee()->input->post('password'),
            'ip_address'    => ee()->input->ip_address(),
            'join_date'     => ee()->localize->now,
            'email'         => trim_nbs(ee()->input->post('email')),
            'screen_name'   => trim_nbs(ee()->input->post('screen_name')),

            // overridden below if used as optional fields
            'language'      => (ee()->config->item('deft_lang')) ?: 'english',
        );

        $data = array_merge($data, $custom_data);

        // Set member group

        if (ee()->config->item('req_mbr_activation') == 'manual' OR
            ee()->config->item('req_mbr_activation') == 'email')
        {
            $data['role_id'] = 4;  // Pending
        }
        else
        {
            if (ee()->config->item('default_primary_role') == '')
            {
                $data['role_id'] = 4;  // Pending
            }
            else
            {
                $data['role_id'] = ee()->config->item('default_primary_role');
            }
        }

        // Optional Fields

        $optional = array(
            'bio'               => 'bio',
            'language'          => 'language',
            'timezone'          => 'server_timezone',
            'date_format'       => 'date_format',
            'time_format'       => 'time_format',
            'include_seconds'   => 'include_seconds'
        );

        foreach($optional as $key => $value)
        {
            if (isset($_POST[$value]))
            {
                $data[$key] = ee()->input->post($value, TRUE); //XSS clean this
            }
        }

        // We generate an authorization code if the member needs to self-activate
        if (ee()->config->item('req_mbr_activation') == 'email')
        {
            $data['authcode'] = ee()->functions->random('alnum', 10);
        }

        $member = ee('Model')->make('Member', $data);
        $result = $member->validate();

        // Validate password
        if (($pw_validate = $member->validatePassword('password', $_POST['password'])) !== TRUE)
        {
            $cust_errors[] = lang($pw_validate);
        }

        if ($_POST['password'] != $_POST['password_confirm'])
        {
            $cust_errors[] = lang('missmatched_passwords');
        }

        $field_labels = array();

        foreach ($member->getDisplay()->getFields() as $field)
        {
            $field_labels[$field->getName()] = $field->getLabel();
        }

        $field_errors = array();

        if ($result->failed())
        {
            $e = $result->getAllErrors();
            $errors = array_map('current', $e);

            foreach ($errors as $field => $error)
            {
                $label = lang($field);

                if (isset($field_labels[$field]))
                {
                    $label = $field_labels[$field];
                }

                $field_errors[] = "<b>{$label}: </b>{$error}";
            }
        }

        $errors = array_merge($field_errors, $cust_errors, $this->errors);

        // Display error if there are any
        if (count($errors) > 0)
        {
            ee()->session->set_flashdata('errors', $errors);

            // Save the POSTed variables to refill the form, except for sensitive or dynamically created ones.
            $field_values = array_filter($_POST, function ($key) {
                return ! (in_array($key, array('ACT', 'RET', 'FROM', 'P', 'site_id', 'password', 'password_confirm')));
            }, ARRAY_FILTER_USE_KEY);

            ee()->session->set_flashdata('field_values', $field_values);

            return ee()->output->show_user_error('submission', $errors, '', $return_error_link);
        }

        $member->hashAndUpdatePassword($member->password);

        // Do we require captcha?
        if (ee('Captcha')->shouldRequireCaptcha())
        {
            $query = ee()->db->query("SELECT COUNT(*) AS count FROM exp_captcha WHERE word='".ee()->db->escape_str($_POST['captcha'])."' AND ip_address = '".ee()->input->ip_address()."' AND date > UNIX_TIMESTAMP()-7200");

            if ($query->row('count')  == 0)
            {
                return ee()->output->show_user_error('submission', array(lang('captcha_incorrect')), '', $return_error_link);
            }

            ee()->db->query("DELETE FROM exp_captcha WHERE (word='".ee()->db->escape_str($_POST['captcha'])."' AND ip_address = '".ee()->input->ip_address()."') OR date < UNIX_TIMESTAMP()-7200");
        }

        $member->save();

        // Update stats
        if (ee()->config->item('req_mbr_activation') == 'none')
        {
            ee()->stats->update_member_stats();
        }

        $member_id = $member->member_id;


        // Send admin notifications
        if (ee()->config->item('new_member_notification') == 'y' &&
            ee()->config->item('mbr_notification_emails') != '')
        {
            $name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

            $swap = array(
                            'name'                  => $name,
                            'site_name'             => stripslashes(ee()->config->item('site_name')),
                            'control_panel_url'     => ee()->config->item('cp_url'),
                            'username'              => $data['username'],
                            'email'                 => $data['email']
                         );

            $template = ee()->functions->fetch_email_template('admin_notify_reg');
            $email_tit = $this->_var_swap($template['title'], $swap);
            $email_msg = $this->_var_swap($template['data'], $swap);

            // Remove multiple commas
            $notify_address = reduce_multiples(ee()->config->item('mbr_notification_emails'), ',', TRUE);

            // Send email
            ee()->load->helper('text');

            ee()->load->library('email');
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
            ee()->email->to($notify_address);
            ee()->email->subject($email_tit);
            ee()->email->message(entities_to_ascii($email_msg));
            ee()->email->Send();
        }

        // -------------------------------------------
        // 'member_member_register' hook.
        //  - Additional processing when a member is created through the User Side
        //  - $member_id added in 2.0.1
        //
            ee()->extensions->call('member_member_register', $data, $member_id);
            if (ee()->extensions->end_script === TRUE) return;
        //
        // -------------------------------------------

        // Send user notifications
        if (ee()->config->item('req_mbr_activation') == 'email')
        {
            $action_id  = ee()->functions->fetch_action_id('Member', 'activate_member');

            $name = ($data['screen_name'] != '') ? $data['screen_name'] : $data['username'];

            $board_id = (ee()->input->get_post('board_id') !== FALSE && is_numeric(ee()->input->get_post('board_id'))) ? ee()->input->get_post('board_id') : 1;

            $forum_id = (ee()->input->get_post('FROM') == 'forum') ? '&r=f&board_id='.$board_id : '';

            $swap = array(
                'name'              => $name,
                'activation_url'    => ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$action_id.'&id='.$data['authcode'].$forum_id,
                'site_name'         => stripslashes(ee()->config->item('site_name')),
                'site_url'          => ee()->config->item('site_url'),
                'username'          => $data['username'],
                'email'             => $data['email']
             );

            $template = ee()->functions->fetch_email_template('mbr_activation_instructions');
            $email_tit = $this->_var_swap($template['title'], $swap);
            $email_msg = $this->_var_swap($template['data'], $swap);

            // Send email
            ee()->load->helper('text');

            ee()->load->library('email');
            ee()->email->wordwrap = true;
            ee()->email->from(ee()->config->item('webmaster_email'), ee()->config->item('webmaster_name'));
            ee()->email->to($data['email']);
            ee()->email->subject($email_tit);
            ee()->email->message(entities_to_ascii($email_msg));
            ee()->email->Send();

            $message = lang('mbr_membership_instructions_email');
        }
        elseif (ee()->config->item('req_mbr_activation') == 'manual')
        {
            $message = lang('mbr_admin_will_activate');
        }
        else
        {
            // Log user in (the extra query is a little annoying)
            ee()->load->library('auth');
            $member_data_q = ee()->db->get_where('members', array('member_id' => $member_id));

            $incoming = new Auth_result($member_data_q->row());
            $incoming->remember_me();
            $incoming->start_session();

            $message = lang('mbr_your_are_logged_in');
        }

        // Build the message
        if (ee()->input->get_post('FROM') == 'forum')
        {
            $query = $this->_do_form_query();

            $site_name  = $query->row('board_label') ;
            $return     = parse_config_variables($query->row('board_forum_url'));
        }
        else
        {
            $site_name = (ee()->config->item('site_name') == '') ? lang('back') : stripslashes(ee()->config->item('site_name'));
            $return = ee()->config->item('site_url');
        }

        $data = array(
            'title'     => lang('mbr_registration_complete'),
            'heading'   => lang('thank_you'),
            'content'   => lang('mbr_registration_completed')."\n\n".$message,
            'redirect'  => '',
            'link'      => array($return, $site_name)
        );

        //return ee()->functions->redirect($return_link);
    }

    /**
     * EE6 : used only for ee6 to Replace variables
     */
    public function _var_swap($str, $data)
    {
        if ( ! is_array($data))
        {
            return FALSE;
        }

        foreach ($data as $key => $val)
        {
            $str = str_replace('{'.$key.'}', $val, $str);
        }

        return $str;
    }


    //ee6: This function only used for the ee6 to logout member, We added function here because this type of core function return the value in ee6 so we can not rely on it
    public function member_logout()
    {
        // Handle our protected data if any. This contains our extra params.
        $protected = ee()->functions->handle_protected();

        // Determine where we need to return to in case of success or error.
        $return_link = ee()->functions->determine_return(true);
        $return_error_link = ee()->functions->determine_error_return();

        // If they are already logged out then send them away.
        if (ee()->session->userdata('member_id') === 0)
        {
            //return ee()->functions->redirect($return_link); //custom change for ee6s
        }

        // If this is a GET request, they're using the `path="logout"` tag so check the CSRF Token.
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $token = FALSE;
            if ( ! $token) $token = ee()->input->get('csrf_token');
            if ( ! $token) $token = ee()->input->get('XID');

            if ( ! bool_config_item('disable_csrf_protection') && $token != CSRF_TOKEN)
            {
                return ee()->output->show_user_error('general', array(lang('not_authorized')));
            }
        }
        // Kill the session and cookies
        ee()->db->where('site_id', ee()->config->item('site_id'));
        ee()->db->where('ip_address', ee()->input->ip_address());
        ee()->db->where('member_id', ee()->session->userdata('member_id'));
        ee()->db->delete('online_users');

        ee()->session->destroy();

        ee()->input->delete_cookie('read_topics');

        $csrf_token = ee()->csrf->refresh_token();

        /* -------------------------------------------
        /* 'member_member_logout' hook.
        /*  - Perform additional actions after logout
        /*  - Added EE 1.6.1
        */
            ee()->extensions->call('member_member_logout');
            if (ee()->extensions->end_script === TRUE) return;
        /*
        /* -------------------------------------------*/

        if (ee()->input->get_post('FROM') == 'forum' && bool_config_item('forum_is_installed'))
        {
            if (ee()->input->get_post('board_id') !== FALSE &&
                is_numeric(ee()->input->get_post('board_id')))
            {
                $query = ee()->db->select("board_forum_url, board_label")
                    ->where('board_id', (int) ee()->input->get_post('board_id'))
                    ->get('forum_boards');
            }
            else
            {
                $query = ee()->db->select('board_forum_url, board_label')
                    ->where('board_id', (int) 1)
                    ->get('forum_boards');
            }

            $forum_return_link = $query->row('board_forum_url') ;

            if (! empty($forum_return_link))
            {
                $return_link = parse_config_variables($forum_return_link);
            }
        }

        //return ee()->functions->redirect($return_link); //custom change for ee6
    }
}