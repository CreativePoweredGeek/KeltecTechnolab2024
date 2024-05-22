<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Sm_custom_validation
{

	public $validator;

	/* Initialize constructor */
	public function __construct()
	{
		$this->validator = ee('Validation')->make();
		$this->callAllValidationMethods();
	}

	/**
	* All validation rule define here
	* @return true if Everythig is okay other wise return error
	**/
	function callAllValidationMethods()
	{

		//rule define for unique short name of social feed 
		$this->validator->defineRule('allowedFile', function($key, $value, $parameters)
        {
        	if( ! in_array($value, $parameters) )
        	{
        		return 'only_csv_and_xml_allowed';
        	}
        	
        	return true; 
        });

        $this->validator->defineRule('checkFileExistance', function($key, $value, $parameters)
        {
        	
        	if(filter_var($value, FILTER_VALIDATE_URL))
	        { 
	            if(! ee()->smie->checkURL($value))
	            {
	            	return 'file_not_found_or_unreadable_path';
	            }
	        }
	        else
	        {
	        	if(! (file_exists($value) && is_readable($value)))
	            {
	                return 'file_not_found_or_unreadable_path';
	            }
	        }

	        return true;

        });

        $this->validator->defineRule('is_unique', function($key, $value, $parameters)
        {

			if( $parameters != "" || (is_array($parameters) && count($parameters) != 0) )
	        {
	        	$details = explode('.', $parameters[0]);

	            if(count($details) == 2)
	            {

	                if(ee()->db->table_exists($details[0]))
	                {

	                    ee()->db->select($details[1]);
	                    ee()->db->from($details[0]);
	                    ee()->db->where($details[1],$value);

	                    $num_rows = ee()->db->get()->num_rows;

	                    if($num_rows != 0)
	                    {
	                        return lang('is_unique_already_taken');
	                    }
	                    else
	                    {
	                    	return true;
	                    }

	                }
	                else
	                {
	                    return lang('is_unique_tbl_not_exists');
	                }

	            }
	            else
	            {
	                return lang('is_unique_no_param');
	            }

	        }
	        else
	        {
	            return lang('is_unique_no_param');
	        }

        });

        $this->validator->defineRule('is_unique_profile', function($key, $value, $parameters)
        {

        	if( $parameters != "" || (is_array($parameters) && count($parameters) != 0) )
	        {

	            $details = explode('.', $parameters[0]);

	            if(count($details) == 2)
	            {

	                if(ee()->db->table_exists($details[0]))
	                {

	                    ee()->db->select($details[1]);
	                    ee()->db->from($details[0]);
	                    ee()->db->where($details[1], $value);

	                    if(ee()->sm->member_id != 0 && ( $details[0] == "members" || $details[0] == "exp_members"))
	                    {
	                        ee()->db->where("member_id != ", ee()->sm->member_id);
	                    }

	                    $num_rows = ee()->db->get()->num_rows;

	                    if($num_rows != 0)
	                    {
	                        return lang('is_unique_already_taken');
	                    }
	                    else
	                    {
	                    	return true;
	                    }

	                }
	                else
	                {
	                    return lang('is_unique_tbl_not_exists');
	                }

	            }
	            else
	            {
	                return lang('is_unique_no_param');  
	            }

	        }
	        else
	        {
	            return lang('is_unique_no_param');
	        }

        });

        $this->validator->defineRule('sm_recaptcha_validate', function($key, $value, $parameters)
        {

        	$recaptchaResponse  = trim($value);
	        $userIp             = ee()->input->ip_address();
	        
	        if(! class_exists('smart_members_model'))
	        {
	            ee()->load->model('smart_members_model', 'smModel');
	        }

	        $secret = ee()->smModel->getReCaptchaSecret();

	        if($secret === false || $secret === NULL || $secret === "")
	        {
	            return lang('sm_recaptcha_validate');
	        }
	        
	        $url = "https://www.google.com/recaptcha/api/siteverify?secret=".$secret."&response=".$recaptchaResponse."&remoteip;=".$userIp;

	        $ch = curl_init(); 
	        curl_setopt($ch, CURLOPT_URL, $url); 
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

	        $response = curl_exec($ch);

	        curl_close($ch);

	        $status= json_decode($response, true);
	        if($status['success'])
	        {
	            return true;
	        }
	        else
	        {
	            return lang('sm_recaptcha_validate');
	        }	
        
        });

        $this->validator->defineRule('match_group', function($key, $value, $parameters)
        {

        	ee()->db->select(SM_APP_VER_L6 ? 'group_id' : 'role_id');
	        ee()->db->from('members');

	        if(isset($_POST['email']))
	        {
	            ee()->db->where('email', $_POST['email']);
	        }
	        else
	        {
	            ee()->db->where('username', $_POST['username']);
	        }

	        $get = ee()->db->get();
	        $num_rows = $get->num_rows;

	        if($num_rows == 0)
	        {
	            return lang('group_id_not_found'); //ee6 : no need to change
	        }
	        else
	        {
	            if($get->row(SM_APP_VER_L6 ? 'group_id' : 'role_id' ) != $value)
	            {
	                return lang('group_id_not_found'); //ee6
	            }
	        }

	        return true;
        
        });

        $this->validator->defineRule('sm_captcha_validate', function($key, $value, $parameters)
        {

        	ee()->db->from('captcha');
	        ee()->db->where('word', $value);
	        ee()->db->where('ip_address', ee()->input->ip_address());
	        ee()->db->where('date > UNIX_TIMESTAMP()-7200');

	        $count = ee()->db->count_all_results();
	        if($count > 0)
	        {
	        	return true;
	        }
	        else
	        {
	        	return lang('sm_captcha_validate');
	        }
        
        });
        
        $this->validator->defineRule('chk_img_height_width', function($key, $value, $parameters)
        {

        	$details 	= explode('.', $parameters[0]);
	        $filename 	= $details[0];
	        $prefix 	= $details[1];

	        $selectWrapper = array();
	        $selectWrapper = getimagesize($_FILES[$filename]["tmp_name"]);
	        $width 	= $selectWrapper[0];
	        $height = $selectWrapper[1];
	        unset($selectWrapper);

	        $allowed_width 	= ee()->config->item($prefix.'_max_width');
	        $allowed_height = ee()->config->item($prefix.'_max_height');
	        
	        if(($width > $allowed_width) || ($height > $allowed_height)) 
	        {
	            unset($_POST[$filename]);
	            return 'This field cannot exceeds ' .$allowed_height . ' x ' .$allowed_width;
	        }
	        else 
	        {
	            return true;
	        }
		
		});

        $this->validator->defineRule('chk_img_size', function($key, $value, $parameters)
        {

        	$details 		= explode('.',$parameters[0]);
	        $filename 		= $details[0];
	        $prefix 		= $details[1];
	        $filesize 		= $_FILES[$filename]['size']/1024;
	        $max_filesize 	= ee()->config->item($prefix . '_max_kb');

	        if($filesize > $max_filesize) 
	        {
	            unset($_POST[$filename]);
	        	return 'This field cannot exceeds more than ' . $max_filesize . ' kb';
	        }

	        return true;

        });

        $this->validator->defineRule('chk_img_type', function($key, $value, $parameters)
        {

        	$details 		= explode('.',$parameters[0]);
	        $filename 		= $details[0];
	        $allowed_mime 	= explode(',', $details[1]);
	        $allowed_mime 	= array_map('trim',$allowed_mime);

	        $selectWrapper 	= array();
	        $selectWrapper 	= getimagesize($_FILES[$filename]["tmp_name"]);
	        if(in_array($selectWrapper['mime'], $allowed_mime))
	        {
	            unset($selectWrapper);
	            return true;
	        }
	        else
	        {
	        	//REMOVE Is image/
	        	$details[1] = str_replace('image/', '', $details[1]);
	            unset($_POST[$filename]);
	            return 'Allowed mime types in this Filed is: ' . $details[1];
	        }

		});

		$this->validator->defineRule('auth_password', function($key, $value, $parameters)
        {

        	if(! class_exists('auth'))
	        {
	            ee()->load->library('auth');
	        }

	        if(! class_exists('smart_members_model'))
	        {
	            ee()->load->model('smart_members_model', 'smModel');
	        }
	        
	        $username = ee()->smModel->getMemberFieldFromEmail('username', array('member_id' => ee()->session->userdata('member_id')));
	        $label = ee()->auth->authenticate_username($username, $value);
	        
	        if($label === false)
	        {
	            return lang('auth_password');
	        }

	        return true;

		});

		$this->validator->defineRule('check_field', function($key, $value, $parameters)
		{

			ee()->db->select($parameters[0]);
	        ee()->db->from('members');
	        ee()->db->where($parameters[0], $value);

	        $num_rows = ee()->db->get()->num_rows;

	        if($num_rows == 0)
	        {
	            return lang('invalid_' . $parameters[0]);
	        }

	        return true;

		});

		$this->validator->defineRule('auth_login', function($key, $value, $parameters)
		{

			if(! class_exists('auth'))
	        {
	            ee()->load->library('auth');
	        }

	        $label = false;
	        if(isset($_POST['email']))
	        {
	            $label = ee()->auth->authenticate_email($_POST['email'], $value);
	        }
	        elseif(isset($_POST['username']))
	        {
	            $label = ee()->auth->authenticate_username($_POST['username'], $value);
	        }

	        if($label === false)
	        {
	            return lang('auth_login');
	        }

	        return true;

		});

		$this->validator->defineRule('enable', function($key, $value, $parameters)
		{

			//ee6 => does not need check for the enable_avatars
			if(ee()->config->item('enable_' . $parameters[0]) != "y" && 'enable_' . $parameters[0] != 'enable_avatars')
	        {
	            return lang('not_enabled');
	        }

	        return true;

		});

		$this->validator->defineRule('allow_avatar_uploads', function($key, $value, $parameters)
		{

			//for eee5 only , ee6 remove this setting
			if(SM_APP_VER_L6){
				if(ee()->config->item('allow_avatar_uploads') != "y")
		        {
		            return lang('no_allowed_avatar_uploads');
		        }
			}
		    return true;

		});

		$this->validator->defineRule('sig_allow_img_upload', function($key, $value, $parameters)
		{

			if(ee()->config->item('sig_allow_img_upload') != "y")
			{
				return lang('no_allowed_sig_img_upload');
			}

			return true;

		});

		$this->validator->defineRule('chk_provider', function($key, $value, $parameters)
		{

			if(! class_exists('social_login_model'))
	        {
	            ee()->load->model('social_login_model', 'slModel');
	        }

	        $data = ee()->slModel->getProvidersList(array($_POST['providers']));
	        if($data === false)
	        {
	            return str_replace('%s', $_POST['providers'], lang('chk_provider'));
	        }

	        return true;

		});

		$this->validator->defineRule('_valid_fieldname', function($key, $value, $parameters)
		{

			ee()->lang->loadfile('admin_content');
	        if (in_array($value, ee()->cp->invalid_custom_field_names()))
	        {
	            return lang('reserved_word');
	        }

	        if (preg_match('/[^a-z0-9\_\-]/i', $value))
	        {
	            return lang('invalid_characters');
	        }

	        // Is the field name taken?
	        ee()->db->where('m_field_name', $value);
	        ee()->db->from('member_fields');
	        $count =  ee()->db->count_all_results();

	        if (($parameters[0] == 'n' OR ($parameters[0] == 'y' && $value != ee()->input->post('cur_field_name')))
	            && $count  > 0)
	        {
	            return lang('duplicate_field_name');
	        }

	        return TRUE;

		});

		$this->validator->defineRule('valid_email', function($key, $value, $parameters)
		{
			if(! (bool) filter_var($value, FILTER_VALIDATE_EMAIL))
			{
				return lang('valid_email');
			}

			return true;
		});

		$this->validator->defineRule('valid_username', function($key, $value, $parameters)
		{
			if (preg_match("/[\|'\"!<>\{\}]/", $value))
			{
				return 'invalid_characters_in_username';
			}
			
			// Is username min length correct?
			$un_length = ee()->config->item('un_min_len');
			if (strlen($value) < ee()->config->item('un_min_len'))
			{
				return sprintf(lang('username_too_short'), $un_length);
			}

			if (strlen($value) > USERNAME_MAX_LENGTH)
			{
				return 'username_too_long';
			}

			return true;
		});

		$this->validator->defineRule('valid_password', function($key, $value, $parameters)
		{
			$pw_length = ee()->config->item('pw_min_len');
			if (strlen($value) < $pw_length)
			{
				return sprintf(lang('password_too_short'), $pw_length);
			}

			// Is password max length correct?
			if (strlen($value) > PASSWORD_MAX_LENGTH)
			{
				return 'password_too_long';
			}

			//  Make UN/PW lowercase for testing
			$lc_user = strtolower(ee()->input->post('username', true));
			if($lc_user != "")
			{
				$lc_pass = strtolower($value);
				$nm_pass = strtr($lc_pass, 'elos', '3105');

				if ($lc_user == $lc_pass OR $lc_user == strrev($lc_pass) OR $lc_user == $nm_pass OR $lc_user == strrev($nm_pass))
				{
					return 'password_based_on_username';
				}
			}

			// Are secure passwords required?
			if (ee()->config->item('require_secure_passwords') == 'y')
			{
				$count = array('uc' => 0, 'lc' => 0, 'num' => 0);

				$pass = preg_quote($value, "/");

				$len = strlen($pass);

				for ($i = 0; $i < $len; $i++)
				{
					$n = substr($pass, $i, 1);

					if (preg_match("/^[[:upper:]]$/", $n))
					{
						$count['uc']++;
					}
					elseif (preg_match("/^[[:lower:]]$/", $n))
					{
						$count['lc']++;
					}
					elseif (preg_match("/^[[:digit:]]$/", $n))
					{
						$count['num']++;
					}
				}

				foreach ($count as $val)
				{
					if ($val == 0)
					{
						return 'not_secure_password';
					}
				}
			}

			// Does password exist in dictionary?
			// TODO: move out of form validation library
			if(isset($lc_pass))
			{
				ee()->load->library('form_validation');
				if (ee()->form_validation->_lookup_dictionary_word($lc_pass) == TRUE)
				{
					return 'password_in_dictionary';
				}
			}

			return TRUE;
		});

		$this->validator->defineRule('valid_screen_name', function($key, $value, $parameters)
		{
			if (preg_match('/[\{\}<>]/', $value))
			{
				return 'disallowed_screen_chars';
			}

			if (strlen($value) > USERNAME_MAX_LENGTH)
			{
				return 'screenname_too_long';
			}

			return TRUE;
		});
	}
}
