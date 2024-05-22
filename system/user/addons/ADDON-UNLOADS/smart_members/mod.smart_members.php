<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Smart_members 
{

	/* Important globel variables */ 
	public $totalFields;
	public $param_array;
	public $errors;
	public $formSettings;

	public function __construct()
	{

		$this->param_array = array();

		/* Neeful Helper classes */
		ee()->load->helper(array('security', 'string', 'form', 'url'));
		/* Neeful model classes */
		ee()->load->model('smart_members_model','smModel');

		/* Neeful Library classes */
		ee()->load->library('encrypt');
		ee()->load->library('sm_custom_validation', null, 'smValidation');
		ee()->load->library('sm_lib', null ,'sm');
		ee()->load->library('member_fields_lib', null ,'mf');

		/* Current form setting variable */
		$this->formSettings = ee()->smModel->getFormSettings();


	}

	/* Register User Form TAG */
	public function register($source = "registration")
	{

		/*Check the new registrations are allowed or not.*/
		if($data = ee()->sm->allowRegister()){return $data;}

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata 	= ee()->TMPL->tagdata;
		$tagparams 	= ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$staticFields = ee()->sm->registrationFieldInitialize();

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors 		= ee()->TMPL->fetch_param('wrap_errors');
		$this->totalFields 	= ee()->sm->listFieldsInDetail('',$wrap_errors, $tagdata, $staticFields, $source);
		$this->totalFields['field:timezone'] = ee()->localize->timezone_menu($this->totalFields['timezone'], 'timezone');
		
		/*Inside var tag pair fields for select dropdown, checkboxes etc.*/
		$tagdata = ee()->sm->setupVarFields($tagdata, $tagparams);
		$tagdata = ee()->sm->addLabels($tagdata);
		$tagdata = ee()->sm->addGeneralContent($tagdata);
		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action" => "sm_registration", "source" => $source));

	}

	/*Registration form ACTION Method*/
	public function sm_registration($source = "registration")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);
		
		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Process the post data submission if no error found*/
			$ret = ee()->sm->processRegistration($this->param_array);
			if($ret !== true)
			{

				$this->errors = array();
				foreach ($ret as $key => $value)
				{
					$this->errors[$key] = $value;
				}
				
				$this->handlingErrors($source);

			}
			else
			{

				/*Email Procedure for new registration*/
				if(ee()->config->item('req_mbr_activation') == "none")
				{

					/*Send immediate Email if user not moving on pending group*/
					$member_id = ee()->smModel->getMemberFieldFromEmail('member_id',array('email'=>$_POST['email']));

					$msg_data = ee()->sm->configRegistrationEmail($member_id);
					$ret = ee()->sm->sendEmail($msg_data, "registration");
				}
				else
				{

					/*Save member id for send email when he activate by admin or self email*/
					$member_id = ee()->smModel->getMemberFieldFromEmail('member_id',array('email'=>$_POST['email']));
					$group_id = ee()->sm->group_id;
					ee()->smModel->saveMemberForWelcomeEmail($member_id,$group_id);

				}
			
				/*Redirect on return_url page after finally submission form*/
				$this->redirect_success($source);

			}

		}

	}

	/*Reset Password Form*/
	public function edit()
	{

		/*Check for restrictions*/
		if (ee()->session->userdata('member_id') == 0)
		{
			return lang('not_logged_in');
		}

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata 	= ee()->TMPL->tagdata;
		$tagparams 	= ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		if(isset($tagparams['member_id']))
		{

			$valid = ee()->smModel->getMemberFieldFromEmail('member_id', array('member_id' => $tagparams['member_id']));
			if(! $valid)
			{
				return lang('not_a_valid_member');
			}
			
			if(isset($tagparams['allowed_admin_groups']) && $tagparams['allowed_admin_groups'] != "")
			{
				$tagparams['allowed_admin_groups'] = explode("|", $tagparams['allowed_admin_groups']);
			}
			else
			{
				$tagparams['allowed_admin_groups'] = array('1');
			}

			//ee6
			if(! in_array(ee()->session->userdata(SM_APP_VER_L6 ? 'group_id' : 'role_id'), $tagparams['allowed_admin_groups']))
			{
				return lang('you_are_not_allowed_to_edit_memebrs');
			}

		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$staticFields = ee()->sm->registrationFieldInitialize('edit_profile');

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = ee()->TMPL->fetch_param('wrap_errors');

		if(isset($tagparams['member_id']))
		{
			ee()->sm->member_id = $tagparams['member_id'];
		}
		$this->totalFields = ee()->sm->listFieldsInDetail(true, $wrap_errors, $tagdata, $staticFields, "edit_profile");
		$this->totalFields['field:timezone'] = ee()->localize->timezone_menu($this->totalFields['timezone'], 'timezone');

		ee()->sm->member_id = ee()->session->userdata('member_id');
		
		/*Inside var tag pair fields for select dropdown, checkboxes etc.*/
		$tagdata = ee()->sm->setupVarFields($tagdata, $tagparams);
		
		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action" => "sm_update_profile", "source" => "edit_profile"));

	}

	/*Update profile form ACTION Method*/
	public function sm_update_profile($source = "edit_profile")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);
		
		/*Validate form*/
		if(isset($this->param_array['member_id']))
		{
			ee()->sm->member_id = $this->param_array['member_id'];
		}
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);

		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{
			/*Process the post data submission if no error found*/
			$ret = ee()->sm->processUpdateProfile($this->param_array);

			if($ret !== true)
			{

				$this->errors = array();
				foreach ($ret as $key => $value)
				{
					$this->errors[$key] = $value;
				}
				
				$this->handlingErrors($source);

			}
			else
			{
				/*Redirect on return_url page after finally submission form*/
				$this->redirect_success($source);
			}

		}

	}

	/*Login User from Tag*/
	public function login($source = "login")
	{
		
		/* store tagdata and tagparams on variables to easy use*/
		$tagdata = ee()->TMPL->tagdata;
		$tagparams = ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$static_fields = ee()->sm->loginFieldInitialize();

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = isset($tagparams['wrap_errors']) ? $tagparams['wrap_errors'] : "";
		$this->totalFields = ee()->sm->listFieldsInDetail('',$wrap_errors, $tagdata,$static_fields, $source);

		/*Inside var tag pair fields for select dropdown, checkboxes etc.*/
		$tagdata = ee()->sm->setupVarFields($tagdata, $tagparams);

		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action"=>"sm_login", "source"=> $source));

	}

	/*Reset Password form ACTION Method*/
	public function sm_login($source = "login")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		//for ee6: if we found group id then we set role id in post
		if(isset($_POST['group_id'])){
			$_POST['role_id'] = $_POST['group_id'];
		}
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);
		
		//
		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Get Login field*/
			$field = ""; $val = "";
			
			if(isset($_POST['email']))
			{
				$field 	= $_POST['email'];
				$val 	= "email";
			}
			elseif (isset($_POST['username']))
			{
				$field = $_POST['username'];
				$val 	= "username";
			}

			/*If field is NULL (Not possible. But extra condition to double sure)*/
			if($field === "")
			{
				return ee()->output->show_user_error(false, lang('not_authorized'));
			}

			$member_data = ee()->smModel->memberStaticFields(array($val => $field));

			if($member_data === false)
			{
				return ee()->output->show_user_error(false, lang('invalid_'.$val));
			}

			if($member_data[0]['group_id'] == 4)
			{
				return ee()->output->show_user_error(false, lang('mbr_account_not_active'));
			}
			elseif(isset($_POST['group_id']))
			{
				//too hanel group id in ee6
				$member_data[0]['group_id'] = $member_data[0]['role_id'];
				if($_POST['group_id'] != $member_data[0]['group_id'])
				{
					return ee()->output->show_user_error(false, str_replace('%s', lang('group_id') , lang('group_id_not_found')));
				}
			}
			elseif(isset($_POST['role_id']))
			{
				//for ee6
				if($_POST['role_id'] != $member_data[0]['role_id'])
				{
					return ee()->output->show_user_error(false, str_replace('%s', lang('role_id') , lang('group_id_not_found')));
				}
			}

			if($member_data !== false)
			{
				if (ee()->session->check_password_lockout($field) === true)
				{
					return ee()->output->show_user_error(false, str_replace("%d", ee()->config->item('password_lockout_interval'), lang('password_lockout_in_effect')));
				}
			}
			
			/*Process the post data submission if no error found*/
			if(ee()->sm->processLogin($this->param_array, $member_data[0]) !== true)
			{
				ee()->output->show_user_error(false, lang('not_authorized'));
			}

			/*Redirect on return_url page after finally submission form*/
			$this->redirect_success($source);

		}

	}

	/*Login User from Social media*/
	public function social_login($source = "social_login")
	{

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata 	= ee()->TMPL->tagdata;
		$tagparams 	= ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		$default_id = 'sm_social_login_form';
		$default_error_reporting = "outline";

		if(isset($tagparams['attr:id']))
		{
			if($tagparams['attr:id'] != "")
			{
				$tagparams['attr:id'] = $default_id;
			}
		}
		else
		{
			$tagparams['attr:id'] = $default_id;
		}

		if(isset($tagparams['popup']) && $tagparams['popup'] == "yes")
		{
			$tagparams['error_reporting'] = $default_error_reporting;
		}
		
		/*Default static and Dynamic field pre arrange ARRAY*/
		$static_fields = ee()->sm->socialLoginFieldInitialize();
		
		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = isset($tagparams['wrap_errors']) ? $tagparams['wrap_errors'] : "";	
		$this->totalFields = ee()->sm->listFieldsInDetail('', $wrap_errors, $tagdata, $static_fields, $source);

		/*Inside var tag pair fields for select dropdown, checkboxes etc.*/
		$tagdata = ee()->sm->setupVarFields($tagdata, $tagparams, $this->totalFields);
		
		$script = "";
		if(isset($tagparams['popup']) && $tagparams['popup'] == "yes")
		{
			$script = "<script type='text/javascript'>
			window.onload = function () {
				var myForm = document.getElementById('".$tagparams['attr:id']."');
				myForm.onsubmit = function() {
					var w = window.open('about:blank','Popup_Window','toolbar=0,scrollbars=0,location=0,statusbar=0,menubar=0,resizable=0,width=600,height=600,left = 0,top = 0');
					this.target = 'Popup_Window';
				};
			} </script>";
		}

		/*Call default generate form with current form variables*/
		return $script . $this->generateForm($tagdata, $tagparams, array("action"=>"sm_social_form", "source"=> $source));

	}

	/*Reset login form ACTION Method*/
	public function sm_social_form($source = "social_login")
	{
		
		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);

		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Load needful classes*/
			if(! class_exists('social_login_model'))
			{
				ee()->load->model('social_login_model', 'slModel');
			}

			/*get action ID of popup form to process login*/
			$action_id = ee()->smModel->getActionID("sm_social_popup");

			/*Determine all possible paramters into query string*/
			$return = "";
			if(isset($this->param_array['return']))
			{
				$return = "&return=".$this->param_array['return'];
			}

			$no_email_return = "";
			if(isset($this->param_array['no_email_return']))
			{
				$no_email_return = "&no_email_return=".$this->param_array['no_email_return'];
			}

			$popup = "";
			if(isset($this->param_array['popup']) && $this->param_array['popup'] == "yes")
			{
				$popup = "&popup=".$this->param_array['popup'];
			}

			$secure_return = "";
			if(isset($this->param_array['secure_return']) && $this->param_array['secure_return'] == "yes")
			{
				$secure_return = "&secure_return=".$this->param_array['secure_return'];
			}
			
			$remember_me = "";
			if(isset($this->param_array['remember_me']) && $this->param_array['remember_me'] == "yes")
			{
				$remember_me = "&remember_me=".$this->param_array['remember_me'];
			}

			$url = "?ACT=".$action_id."&provider=".ee()->input->get_post('providers').$return.$no_email_return.$popup.$secure_return.$remember_me;

			$url = ee()->functions->create_url($url);

			/**
	        * Hook to run Before call the social login popup function to start social login process
			* @param $url
			* @return $url
	        */
			if (ee()->extensions->active_hook('sm_before_social_login') === TRUE)
			{

				$tmp = ee()->extensions->call('sm_before_social_login', $url);
				if(ee()->extensions->end_script === TRUE) return;

				if($tmp != "")
				{
					$url = $tmp;
					unset($tmp);
				}

			}

			ee()->functions->redirect($url);

		}

	}

	/*Social login action to communicate with social media*/
	function sm_social_popup($source = "social_login")
	{

		/*Load neeful files*/
		require_once( "hybrid/Auth.php" );
		require_once( "hybrid/vendor/autoload.php" );
			
		/*Define neful variables*/
		$provider 			= ee()->input->get_post('provider');
		$return 			= ee()->input->get_post('return');
		$no_email_return 	= ee()->input->get_post('no_email_return');
		$popup 				= ee()->input->get_post('popup');
		$secure_return 		= ee()->input->get_post('secure_return');
		$remember_me 		= ee()->input->get_post('remember_me');

		/*Show error if provider is missing*/
		if($provider == "")
		{
			$this->errors['provider'] = lang('provider_not_defined');
			$this->handlingErrors($source);
		}

		/*Load helpful models*/
		if(! class_exists('social_login_model'))
		{
			ee()->load->model('social_login_model', 'slModel');
		}

		/*Load helpful libraries*/
		if(! class_exists('social_login_lib'))
		{
			ee()->load->library('social_login_lib', null, 'sl');
		}

		/*Get provider data from provider short name*/
		$selectWrapper = array();
		$selectWrapper = ee()->slModel->getProvidersList(array($provider));
		$data = $selectWrapper[0];
		unset($selectWrapper);
		
		/*$action_id = ee()->smModel->getActionID("sm_social_api");
		$url = ee()->config->item('base_url')."?ACT=". $action_id . "&provider=".$data['provider'];*/

		if(isset($data['settings']['call_back_url']) && $data['settings']['call_back_url'] == 2)
		{
			$callback_url = ee()->slModel->slCallbackURL();
		}
		else
		{
			$callback_url =  ee()->functions->create_url("?ACT=".ee()->smModel->getActionID("sm_social_api"));
		}

		/*Define config array to process social login*/
		$config = array(
			"base_url" => $callback_url,
			"providers" => array (
				$data['provider']	=> array (
					"enabled"	=> true,
					"keys"    	=> array ( 
						"id" 		=> $data['key'], 
						"key" 		=> $data['key'], 
						"secret" 	=> $data['secret']
					),
					"display" 	=> "popup",
					"includeEmail" 	=> true, //solved the issue of email not getting from twitter on the 18 jan 2021
				)
			));

		/*Premission parameter in case of provider is Slack*/
		if($data['provider'] == "Slack")
		{
			$config['providers']['Slack']['scope'] = "users:read";
		}

		/*Trust forward parameter in case of provider is facebook*/
		if($data['provider'] == "Facebook")
		{
			$config['providers']['Facebook']['trustForwarded'] = true;
		}

		/*Start communication with social media*/
		try
		{

			/*Create new object with pre defined config*/
			$hybridauth = new Hybrid_Auth( $config );

			/*Authenticate user with social media*/
			$hybrid_data = $hybridauth->authenticate($data['provider']);

			/*Get profile array of user fetched from social media*/
			$user_profile = $hybrid_data->getUserProfile();

			/*Process social login, either register as new member or simply login as old one*/
			$member_id = ee()->sl->process_social_login($user_profile, $data);

			/*error if fail to get member ID*/
			if($member_id == "" || $member_id == 0)
			{
				$this->errors['provider'] = lang('social_login_error');
				$this->handlingErrors($source);
			}

			/*Create object od all possible member fields*/
			$selectWrapper = array();
			$selectWrapper = ee()->smModel->memberStaticFields(array('member_id' => $member_id), "*");
			$row = (object) $selectWrapper[0];
			unset($selectWrapper);

			if(SM_APP_VER_L6){
				//for ee5 or
				$group_role_selection = 'group_id';
			}else{
				//for ee6
				$group_role_selection = 'role_id';
			}
			/*Create session if user is not banned, in panding or member ID is missing */
			if(! ($row->$group_role_selection == 0 || $row->$group_role_selection == 2 || $row->$group_role_selection == 4))
			{

				ee()->load->library('auth');

				$sess = new Auth_result($row);

				/*Remember user to prevent login each time*/
				if($remember_me == "yes")
				{
					$sess->remember_me(3600 * 24 * 365);
				}

				/*Create session*/
				$sess->start_session();

			}

			/*Return page check */
			if($no_email_return != "" && ($row->email == "" || $row->email == NULL))
			{
				$return = $no_email_return;
			}
			
			/*Generating return URL*/
			$return_url = ($return != "") ? $return : ee()->config->site_url();
			$return_url = ee()->functions->create_url($return_url);

			if ($secure_return == "yes")
			{
				$return_url = str_replace('http://', 'https://', $return_url);
			}

			/**
	        * Hook to run After social login
			* @param $return_url
			* @return $return_url
	        */
			if (ee()->extensions->active_hook('sm_after_social_login') === TRUE)
			{

				$tmp = ee()->extensions->call('sm_after_social_login', $return_url);
				if(ee()->extensions->end_script === TRUE) return;

				if($tmp != "")
				{
					$return_url = $tmp;
					unset($tmp);
				}

			}

			/*Close popup if open*/
			if($popup == "yes")
			{
				echo '<script> window.opener.location.replace("'.$return_url.'"); window.self.close(); </script>';
			}
			else
			{
				ee()->functions->redirect($return_url);
			}

		}
		catch( Exception $e )
		{
			/*Show error if data communication throws any error*/
			$this->errors['provider'] = $e->getMessage();
			$this->handlingErrors($source);
		}

	}

	/*Social login API to be called as callback URL*/
	function sm_social_api()
	{

		require_once( "hybrid/Auth.php" );
		require_once( "hybrid/Endpoint.php" );
		require_once( "hybrid/vendor/autoload.php" );

		Hybrid_Endpoint::process();

		/*if(isset($_GET['provider']) && $_GET['provider'] == "Live")
		{
			$_SERVER['QUERY_STRING'] = 'hauth.done=Live';
		}*/

	}

	/*Forgot password Form*/
	public function forgot_password($source = "forgot_password")
	{

		/*Check for restrictions*/
		if (ee()->session->userdata('member_id') != 0)
		{
			return lang('already_logged_in');
		}

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata = ee()->TMPL->tagdata;
		$tagparams = ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$static_fields = ee()->sm->forgotPasswordFieldInitialize($tagparams);

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = isset($tagparams['wrap_errors']) ? $tagparams['wrap_errors'] : "";
		$this->totalFields = ee()->sm->listFieldsInDetail('',$wrap_errors, $tagdata,$static_fields, $source);

		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action"=>"sm_forgot_password", "source"=> $source));

	}

	/*Forgot Password form ACTION Method*/
	public function sm_forgot_password($source = "forgot_password")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);
		
		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Process the post data submission if no error found*/
			ee()->sm->processForgotPassword($this->param_array);

			/*Redirect on return_url page after finally submission form*/
			$this->redirect_success($source);

		}

	}

	/*Reset Password Form*/
	public function reset_password($source = "reset_password")
	{

		/*Check for restrictions*/
		if (ee()->session->userdata('member_id') != 0)
		{
			return lang('already_logged_in');
		}

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata = ee()->TMPL->tagdata;
		$tagparams = ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Get reset code from parameter and identify for avaibility of reset code*/
		$resetcode = isset($tagparams['reset_code']) ? $tagparams['reset_code'] : "";

		if(ee()->smModel->identifyResetToken($resetcode) === FALSE)
		{
			return ee()->TMPL->no_results();
		}

		/*Check reset code is expired or not*/
		$exp_hour = $this->formSettings['reset_key_expiration_hours'] != 0 ? (int) $this->formSettings['reset_key_expiration_hours'] : 24;

		if(ee()->localize->now > (ee()->smModel->resetPasswordFields('date', array('resetcode' => $resetcode)) + $exp_hour *60*60))
		{
			return lang('resetcode_expired');
		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$static_fields = ee()->sm->resetPasswordFieldInitialize();

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = isset($tagparams['wrap_errors']) ? $tagparams['wrap_errors'] : "";
		$this->totalFields = ee()->sm->listFieldsInDetail('',$wrap_errors, $tagdata,$static_fields, $source);

		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action"=>"sm_reset_password", "source"=> $source));

	}

	/*Reset Password form ACTION Method*/
	public function sm_reset_password($source = "reset_password")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);
		
		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Process the post data submission if no error found*/
			ee()->sm->processResetPassword($this->param_array);

			/*Redirect on return_url page after finally submission form*/
			$this->redirect_success($source);

		}

	}

	/*Reset Password Form*/
	public function delete($source = "delete_profile")
	{

		/*Check for restrictions*/
		if (ee()->session->userdata('member_id') == 0)
		{
			return lang('not_logged_in');
		}

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata = ee()->TMPL->tagdata;
		$tagparams = ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Default static and Dynamic field pre arrange ARRAY*/
		$static_fields = ee()->sm->deleteProfileFieldInitialize();

		/*Initialize blank array of every possible fields of current form*/
		$wrap_errors = isset($tagparams['wrap_errors']) ? $tagparams['wrap_errors'] : "";
		$this->totalFields = ee()->sm->listFieldsInDetail('',$wrap_errors, $tagdata,$static_fields, $source);

		/*Call default generate form with current form variables*/
		return $this->generateForm($tagdata, $tagparams, array("action" => "sm_delete_profile", "source" => $source));

	}

	/*Delete Profile form ACTION Method*/
	public function sm_delete_profile($source = "delete_profile")
	{

		/*Call Default method load before every form start submission*/
		$this->startFormSubmission($source);

		/*Validate form*/
		$this->errors = ee()->sm->initializeFormValidation($this->param_array, $source);
		
		/*Move to Error handling function if found any error in page submission POST data*/
		if($this->errors !== true)
		{
			$this->handlingErrors($source);
		}
		else
		{

			/*Process the post data submission if no error found*/
			$ret = ee()->sm->processDeleteProfile($this->param_array);

			if($ret !== true)
			{
				ee()->output->show_user_error(false, $ret);
			}

			/*Redirect on return_url page after finally submission form*/
			$this->redirect_success($source);

		}

	}

	/*Logout LINK*/
	public function logout()
	{

		/* store tagdata and tagparams on variables to easy use*/
		$tagdata = ee()->TMPL->tagdata;
		$tagparams = ee()->TMPL->tagparams;
		if(! is_array($tagparams))
		{
			$tagparams = array();
		}

		/*Add XID in url if needed*/
		if (ee()->config->item('secure_forms') == 'y')
		{
			$tagparams['XID'] = XID_SECURE_HASH;
		}

		/*Final URL*/
		$url = ee()->functions->fetch_site_index().QUERY_MARKER.'ACT='.ee()->functions->fetch_action_id(__CLASS__, 'sm_logout');

		/*Load template parsing library*/
		if(! class_exists('template_parse'))
        {
        	ee()->load->library('template_parse', null, 'tmpl_parse');
        }

        foreach ($tagparams as $key => $value) {
        	$selectWrapper = array();
        	$selectWrapper = ee()->tmpl_parse->template_parser(array('member_id' => '0', 'subject' => '0', 'word_wrap' => '0', 'mailtype' => '0', 'registration_template' => 0, 'message_body' => $value));
        	$tagparams[$key] = $selectWrapper['msg_body'];
        	unset($selectWrapper);
        }

		if (!empty($tagparams))
		{
			$url .= AMP.http_build_query($tagparams);
		}

		/**
        * Hook to run before LOGOUT link generates
		* @param $url
		* @return $url
        */
		if (ee()->extensions->active_hook('sm_before_logout_link') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_before_logout_link', $url);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$url = $tmp;
				unset($tmp);
			}

		}

		/*Check the code calling method is single tage or pair variable and set logic accordingly*/
		if($tagdata == "")
		{
			return $url;
		}
		else
		{

			$this->totalFields['url'] = $url;
			return ee()->TMPL->parse_variables_row($tagdata, $this->totalFields);

		}

	}

	/*Logout URL ACTION Method*/
	public function sm_logout()
	{

		/*Assign every GET parameters to param array as an object*/
		foreach ($_GET as $key => $value)
		{
			$this->param_array[$key] = $value;
		}

		/**
        * Hook to run before Logout process
		* @param $this->param_array
		* @return $this->param_array
        */
		if (ee()->extensions->active_hook('sm_logout_start') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_logout_start', $this->param_array);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$this->param_array = $tmp;
				unset($tmp);
			}

		}

		/*Process the post data submission if no error found*/
		if(ee()->sm->processLogout())
		{
			/*Redirect on return_url page after finally submission form*/
			$this->redirect_success('logout');
		}

	}

	/*Error handling method (display errors or redirect to page)*/
	public function handlingErrors($source="")
	{

		/**
        * Hook to run if error occured in form submission
		* @param $source [form status i.e,"registration", "edit_profile", "forgot_password", "reset_password", "login"]
		* @param $this->errors
		* @return $this->errors
        */
		if (ee()->extensions->active_hook('sm_error_in_form') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_error_in_form', $source, $this->errors);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$this->errors = $tmp;
				unset($tmp);
			}

		}

		/*Check error reporing is set as inline or outline*/
		$_error_reporting = isset($this->param_array['error_reporting']) ? $this->param_array['error_reporting'] : 'outline';

		/*Move if actual error is not present*/
		if ($this->errors === TRUE) 
		{

			/*redirect to user's URL. if not entered in parameter then redirect to Last URL*/
			$return_url = isset($this->param_array['return']) ? $this->param_array['return'] : ee()->sm->history(0);
			$return_url = ee()->functions->create_url($return_url);
			ee()->functions->redirect($return_url);

		}
		elseif ($_error_reporting == 'inline') 
		{
			/*Regenerate the whole page with errors if error reporting is set to inline*/
			return ee()->core->generate_page();
		}

		/*show error in EE default style if error reporting is not set to inline*/
		if($source == "social_login" || $source == "export" || $source == "import")
		{

			$message = "<ul>";
			foreach ($this->errors as $key => $value)
			{
				$message .= "<li><p><pre>".$value."</p></li>";
			}
			$message .= "<ul>";

			$data = array(	
				'title' 	=> ee()->lang->line('submission_error'),
				'heading'	=> ee()->lang->line('submission_error'),
				'content'	=> $message
				);
			
			return ee()->output->show_message($data, FALSE);

		}
		else
		{
			
			/*Add Field name as prefix to identify the error Field by name */
			$this->errors = ee()->sm->errorPrefix($this->errors);
			
			/*show error in EE default style if error reporting is not set to inline*/
			return ee()->output->show_user_error(false, $this->errors);
			
		}

	}

	/*Profile Tag Execution*/
	public function profile($data="")
	{

		/**
        * Hook to run before view profile
		* @param $params
		* @return $params
        */
		if (ee()->extensions->active_hook('sm_view_profile_start') === TRUE)
		{
			ee()->extensions->call('sm_view_profile_start');
			if(ee()->extensions->end_script === TRUE) return;
		}

		/*Set the data of profile*/
		//add one false paramter to not get some group
		return ee()->sm->profileExtract(false,ee()->TMPL->tagparams, ee()->TMPL->tagdata, false);
	}

	/*Globel generate form method*/
	public function generateForm($tagdata, $tagparams, $parmas)
	{

		$recaptcha_url = "";

		if(ee()->session->userdata('member_id') == 0 || (ee()->config->item('captcha_require_members') == "" || ee()->config->item('captcha_require_members') == "y"))
		{

			/*If Recaptcha API entered in backend and enabled from front end form. Generate recaptcha*/
			if($this->formSettings['enable_recaptcha'] == "Y" && $this->formSettings['recaptcha_site_key'] != "" && $this->formSettings['recaptcha_secret'] != "" && isset($tagparams['enable_recaptcha']) && $tagparams['enable_recaptcha'] == 'yes')
			{

				/*API js*/
				$recaptcha_url = "<script src='https://www.google.com/recaptcha/api.js'></script>";

				/*Initialize field*/
				$this->totalFields['recaptcha'] = "<div class='g-recaptcha' data-sitekey='{$this->formSettings['recaptcha_site_key']}'></div>";

			}
			elseif((isset($tagparams['enable_recaptcha']) && $tagparams['enable_recaptcha'] == 'yes') || ($parmas['source'] == "registration" && (ee()->config->item('use_membership_captcha') == 'y' || ee()->config->item('require_captcha') == 'y')))
			{
				$this->totalFields['captcha'] = ee('Captcha')->create();
			}

		}

		$fieldDir = ee()->mf->parseDirectory();
		if($fieldDir !== false)
		{
			$this->totalFields = array_merge($this->totalFields, $fieldDir);
		}

		/**
        * Hook to run before form generates
		* @param $parmas['source']  [form status i.e,"registration", "edit_profile", "forgot_password", "reset_password", "login"]
		* @param $this->totalFields
		* @return $this->totalFields
        */
		if (ee()->extensions->active_hook('sm_build_form_start') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_build_form_start', $parmas['source'], $this->totalFields);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$this->totalFields = $tmp;
				unset($tmp);
			}

		}
		
		/*Load template parsing library*/
		if(isset($tagparams['return']))
		{
			if(! class_exists('template_parse'))
	        {
	        	ee()->load->library('template_parse', null, 'tmpl_parse');
	        }

	    	$selectWrapper = ee()->tmpl_parse->template_parser(array('member_id' => '0', 'subject' => '0', 'word_wrap' => '0', 'mailtype' => '0', 'registration_template' => 0, 'message_body' => $tagparams['return']));
	    	$tagparams['return'] = $selectWrapper['msg_body'];
	    	unset($selectWrapper);
		}

		/*Setting up the hidden variables for current form*/
		$vars['hidden_fields']['ACT'] 			= ee()->functions->fetch_action_id(__CLASS__, $parmas['action']);
		$vars['hidden_fields']['params'] 		= ee('Encrypt')->encode(serialize($tagparams), ee()->config->item('session_crypt_key'));
		$vars['hidden_fields']['csrf_token'] 	= XID_SECURE_HASH;
		$vars['hidden_fields']['XID'] 			= XID_SECURE_HASH;

		/*Main Action URL of form*/
		$vars['action'] = ee()->functions->create_url(ee()->uri->uri_string);

		/*force https if form is secure*/
		if (isset($tagparams['secure_action']) && $tagparams['secure_action'] == 'yes')
		{
			$vars['action'] = str_replace('http://', 'https://', $vars['action']);
		}

		/*Method to be called on submit jquery*/
		$vars['onsubmit'] = isset($tagparams['on_submit']) ? $tagparams['on_submit'] : "";

		/*Is form is secure*/
		$vars['secure'] = isset($tagparams['secure_action']) ? $tagparams['secure_action'] == "yes" ? TRUE : FALSE : FALSE;

		/*Attributes listed in code parameters*/
		$form_parameters = "";
		foreach($tagparams as $key => $value)
		{

			$exp_key = explode(':', $key);

			if($exp_key[0] == 'attr')
			{
				$form_parameters .= $exp_key[1]."='".$value."' ";
			}

		}

		/*Setting up enctype*/
		$vars['enctype'] = $form_parameters.' enctype="multipart/form-data"';

		/**
        * Hook to run if error occured in form submission
		* @param $vars
		* @return $vars
        */
		if (ee()->extensions->active_hook('sm_build_form_end') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_build_form_end', $parmas['source'], $vars);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$vars = $tmp;
				unset($tmp);
			}

		}

		$tagdata = ee()->TMPL->parse_variables_row($tagdata, $this->totalFields);
		$tagdata = ee()->sm->addLabels($tagdata);
		$tagdata = ee()->sm->addGeneralContent($tagdata);
		//echo "<pre>";print_r($vars);exit;
		/*Return final form*/
		return $recaptcha_url . ee()->functions->form_declaration($vars) . $tagdata . '</form>';

	}

	/*Default method call every time any form submits*/
	function startFormSubmission($source = "")
	{

		if(ee()->sm->checkAccessLevel() === false)
		{
			ee()->output->show_user_error(false, lang('not_authorized'));
		}

		/*Get params and decode the value to use in regisration process*/
		$this->param_array = unserialize(ee('Encrypt')->decode(ee()->input->get_post('params'), ee()->config->item('session_crypt_key')));

		/**
        * Hook to run when form submits
		* @param $this->param_array
		* @return $this->param_array
        */
		if (ee()->extensions->active_hook('sm_submit_form_start') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_submit_form_start', $source, $this->param_array);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$this->param_array = $tmp;
				unset($tmp);
			}

		}

	}

	/*Globel method call every time after final submition of forms ends*/
	function redirect_success($source = "")
	{

		/*Generating return URL*/
		$return_url = isset($this->param_array['return']) ? $this->param_array['return'] : "";
		$return_url = ee()->functions->create_url($return_url);

		if (isset($this->param_array['secure_return']) && $this->param_array['secure_return'] == 'yes')
		{

			$return_url = str_replace('http://', 'https://', $return_url);
		}

		/**
        * Hook to run when form submit successfully (Before redirect after submission of form)
		* @param $return_url
		* @return $return_url
        */
		if (ee()->extensions->active_hook('sm_submit_form_end') === TRUE)
		{

			$tmp = ee()->extensions->call('sm_submit_form_end', $source, $return_url);
			if(ee()->extensions->end_script === TRUE) return;

			if($tmp != "")
			{
				$return_url = $tmp;
				unset($tmp);
			}

		}

		/*Redirect when the form submits successfully*/
		ee()->functions->redirect($return_url);

	}

	/*Export the data from outside of EE as action URL*/
	function sm_export($source = "export")
	{

        ee()->load->library('sm_ie_lib', null, 'smie');
		
		/*Fetch current member ID and token of export*/
		$member_id 	= ee()->session->userdata('member_id');
		$token 		= ee()->input->get_post('token', true);

		/*Throw error if token is not found*/
		if($token == "")
        {
        	$this->errors['provider'] = lang('token_not_set');
			$this->handlingErrors($source);
        }

        /*Check the requested token is vaild or not*/
        $data = ee()->ieModel->checkExportToken($token);
        if($data === false)
        {
        	$this->errors['provider'] = lang('wrong_token');
			$this->handlingErrors($source);
        }
        $data = $data[0];

        /*Check the conditional dependancies and throw error if not match criteria*/
        if($member_id == 0 && $data['download_without_login'] == "n")
        {
        	$this->errors['provider'] = lang('cannot_download_without_login');
			$this->handlingErrors($source);
        }

        if($data['type'] == 'private' && $member_id != $data['member_id'])
        {
        	$this->errors['provider'] = lang('export_is_private');
			$this->handlingErrors($source);
        } 

        /*Unserialize settings*/
        $data['settings'] = unserialize(base64_decode($data['settings']));
        
        /*Increase counter after download the export*/
    	ee()->ieModel->increaseCounter($token);
        
        /*Generate export function*/
    	$ret = ee()->smie->generateExport($data, 'outside');

    	if(isset($ret['error']))
    	{
    		$this->errors['provider'] = $ret['error'];
			$this->handlingErrors($source);
    	}
    	else
    	{
    		$this->errors['provider'] = lang('something_wrong_download_export');
			$this->handlingErrors($source);
    	}

	}

	/*Import the data from outside of EE as action URL*/
	function sm_import($source = "import")
	{

		/*Load helpful classes*/
        ee()->load->library('sm_ie_lib', null, 'smie');
        
        /*Define helpful variables*/
        $token  	= ee()->input->get_post('token');
        $batch  	= ee()->input->get_post('batch');
        $status 	= ee()->input->get_post('status');

        /*Check basic dependancy of import and throw error if not match with criteria*/
        $this->sm_import_dependancy($token, $source);

        /*If batch not found it is 1st batch*/
        if($batch == "")
        {
            $batch = 0;
        }

        /*If first time load, unset old sessions*/
        if($status == "")
        {
            ee()->smie->unsetSession();
        }

        /*Run Import function of IE library*/
		$ret = ee()->smie->processRunImport($token, $batch, 'outsite');

		if($ret !== false)
        {

        	/*Import success function ID*/
        	$action_id = ee()->smModel->getActionID("sm_import_success");

        	/*Make a query string*/
            if($ret['return'] === true && $ret['status'] == "completed")
            {
        		$url = "?ACT=". $action_id . "&" . "token=" . $token . "&status=" . $ret['status'];
            }
            elseif($ret['return'] === true)
            {
        		$url = "?ACT=". $action_id . "&" . "status=" . $ret["status"] . "&batch=" . $ret["batch"] . "&token=" . $token;
            }

            /*Create URL*/
        	$url = ee()->functions->create_url($url);

        	/*Redirect import success*/
        	ee()->functions->redirect($url);

        }

	}

	/*Function to be called after import each batch*/
	function sm_import_success($source = "import")
	{

		/*Load helpful classes*/
        ee()->load->model('import_export_model','ieModel');
        ee()->load->library('sm_ie_lib', null, 'smie');

        /*Define hepful variables*/
		$token  	= ee()->input->get_post('token');
        $batch  	= ee()->input->get_post('batch');
        $status 	= ee()->input->get_post('status');
        $extra_data = "";
        $vars 		= array();
        
        /*Check basic dependancy of import and throw error if not match with criteria*/
        $this->sm_import_dependancy($token, $source);

        /*Append css and js files for beeter view of page*/
        $extra_data .= "<link href='" . URL_THIRD_THEMES . "smart_members/css/bootstrap.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
        $extra_data .= "<link href='" . URL_THIRD_THEMES . "smart_members/css/jquery.dataTables.min.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
        $extra_data .= "<link href='" . URL_THIRD_THEMES . "smart_members/css/screen.css' type='text/css' media='screen'  rel='stylesheet'/>\n";
        $extra_data .= "<script src='" . URL_THIRD_THEMES . "smart_members/js/jquery.min.js'></script>\n";
        $extra_data .= "<script src='" . URL_THIRD_THEMES . "smart_members/js/bootstrap.min.js'></script>\n";
        $extra_data .= "<script src='" . URL_THIRD_THEMES . "smart_members/js/jquery.dataTables.min.js'></script>\n";

        $vars['extra_data'] = $extra_data;
        $vars['method'] = "none";

        /*reload the page with another batch if all data isnt exported*/
        if($status == "pending" && $batch != "")
        {
        	$action_id = ee()->smModel->getActionID("sm_import");
            $vars['redirect_import']  = $url = "?ACT=". $action_id . "&status=" . $status . "&batch=" . $batch . "&token=" . $token;
        }
        else
        {
            $vars['redirect_import'] = false;
        }

        /*Setup fields to be show in table*/
        $vars['loading_image']        = URL_THIRD_THEMES."smart_members/images/indicator.gif";
        $vars['total_members']        = ee()->smie->session('total_members_' . $token);
        $vars['imported_members']     = ee()->smie->session('imported_members_' . $token);
        $vars['updated_members']      = ee()->smie->session('updated_members_' . $token);
        $vars['recreated_members']    = ee()->smie->session('recreated_members_' . $token);
        $vars['skipped_members']      = ee()->smie->session('skipped_members_' . $token);
        $vars['memory_usage']         = ee()->smie->session('memory_usage_' . $token);
        $vars['total_memory_usage']   = ee()->smie->session('total_memory_usage_' . $token);
        $vars['time_taken']           = ee()->smie->session('time_taken_' . $token);
        $vars['total_time_taken']     = ee()->smie->session('total_time_taken_' . $token);
        $vars['data']                 = unserialize(ee()->smie->session('ret_' . $token));

        /*Set column header of table*/
        $columns = array(
            'member_id'     => array('header' => lang('member_id')),
            'group_id'      => array('header' => lang( (SM_APP_VER_L6) ? 'group_id' : 'role_id')), //ee6
            'screen_name'   => array('header' => lang('screen_name')),
            'username'      => array('header' => lang('username')),
            'email'         => array('header' => lang('email')),
            // 'view_profile'  => array('header' => lang('view_profile'))
            );

        /*Data of insert table*/
        if(isset($vars['data']['insert']))
        {
            ee()->smie->setColumns($columns);
            ee()->smie->setData($vars['data']['insert']);
            $vars['insert_data'] = ee()->smie->generate();
        }

        /*Data of update table*/
        if(isset($vars['data']['update']))
        {
            ee()->smie->setColumns($columns);
            ee()->smie->setData($vars['data']['update']);
            $vars['update_data'] = ee()->smie->generate();
        }

        /*Data of recreated member table*/
        if(isset($vars['data']['delete']))
        {
            ee()->smie->setColumns($columns);
            ee()->smie->setData($vars['data']['delete']);
            $vars['delete_data'] = ee()->smie->generate();
        }

        /*Unset all the data to save memory*/
        unset($data);

        /*Return to the view*/
        return ee()->load->view('run_import_success_frontend', $vars);

	}

	/*Check basic dependancy of import*/
	function sm_import_dependancy($token, $source)
    {

    	/*Current member ID*/
        $member_id 	= ee()->session->userdata('member_id');

        /*Throw error id any of the following criteria fails*/
    	if($token == "")
        {
            $this->errors['provider'] = "Token not set!";
            $this->handlingErrors($source);
        }

        $data = ee()->ieModel->checkImportToken($token);
        if($data === false)
        {
            $this->errors['provider'] = "Wrong token entered!";
            $this->handlingErrors($source);
        }
        $data = $data[0];

        if($member_id == 0 && $data['import_without_login'] == "n")
        {
            $this->errors['provider'] = "You cannot proceed import without logged in!";
            $this->handlingErrors($source);
        }

        if($data['type'] == 'private' && $member_id != $data['member_id'])
        {
            $this->errors['provider'] = "This import is Private!";
            $this->handlingErrors($source);
        } 

    }

} 
?>