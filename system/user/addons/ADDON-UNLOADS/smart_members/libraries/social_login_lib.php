<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Social_login_lib
{

    public function __construct()
    {

        /*Setup instance to this class*/
        /*ee()->sl =& $this;*/

        /* Neeful Model classes */
        if(! class_exists('social_login_model'))
        {
            ee()->load->model('social_login_model', 'slModel');
        }

        if(! class_exists('smart_members_model'))
        {
            ee()->load->model('smart_members_model', 'smModel');
        }

    }

    /*Save method of social setting form.*/
    function saveSocialSettings()
    {

        /*Unset unnecessary post values*/
        unset($_POST['submit']);
        unset($_POST['XID']);
        unset($_POST['csrf_token']);

        foreach ($_POST as $key => $value)
        {

            $data = ee()->slModel->getSocialFormSettings($key);
            
            if($data !== false)
            {

                $data               = $data[0];
                $data['key']        = $value['key'];
                $data['secret']     = $value['secret'];
                $data['settings']   = unserialize($data['settings']);

                if(isset($data['settings']['member_group']))        $data['settings']['member_group']           = $value['settings']['member_group'];
                if(isset($data['settings']['pending_if_no_email'])) $data['settings']['pending_if_no_email']    = $value['settings']['pending_if_no_email'];
                if(isset($data['settings']['email_as_username']))   $data['settings']['email_as_username']      = $value['settings']['email_as_username'];
                if(isset($data['settings']['custom_field_uname']))  $data['settings']['custom_field_uname']     = $value['settings']['custom_field_uname'];
                if(isset($data['settings']['call_back_url']))       $data['settings']['call_back_url']          = $value['settings']['call_back_url'];

                $data['settings'] = serialize($data['settings']);

                /*Update social settings*/
                ee()->slModel->updateSocialSettingForm($key, $data);

            }
            
        }

        return true;

    }

    /**
    * Register new member function to be called after fetch data from social site
    *
    * @param $user_profile  (Array of User profile fetch from social media site after login)
    * @param $provider      (Array of provider to be saved)
    **/
    function register_member($user_profile, $provider)
    {

        /*Initialize needful variables*/
        $data           = array();
        $custom_field   = array();

        /*Set address string*/
       /* $data['location'] = $user_profile->address;
        if($user_profile->city != "")
        {

            if($data['location'] != "")
            {
                $data['location'] .= ", ";
            }

            $data['location'] .= $user_profile->city;

        }

        if($user_profile->country != "")
        {

            if($data['location'] != "")
            {
                $data['location'] .= ", ";
            }

            $data['location'] .= $user_profile->country;
            
        }

        if($user_profile->zip != "")
        {

            if($data['location'] != "")
            {
                $data['location'] .= " ";
            }

            $data['location'] .= $user_profile->zip;
            
        }*/

        /*Set group ID*/
        $data['group_id'] = "";

        if($provider['settings']['pending_if_no_email'] == "Y" && $user_profile->email == "")
        {
            $data['group_id'] = 4;
        }
        else
        {

            if($provider['settings']['member_group'] == "")
            {
                $data['group_id'] = 5;
            }
            else
            {
                $data['group_id'] = $provider['settings']['member_group'];
            }
            
        }
        
        /*Set email address if available*/
        if($user_profile->email)
        {
            $data['email']      = $user_profile->email;
        }

        /*Define other necessary fields*/
        $data['password']       = "";
        $data['unique_id']      = ee()->functions->random('encrypt');
        // $data['url']            = $user_profile->profileURL;
        $data['social_id']      = $user_profile->identifier;
        /*$data['bday_d']         = $user_profile->birthDay;
        $data['bday_m']         = $user_profile->birthMonth;
        $data['bday_y']         = $user_profile->birthYear;*/
        $data['ip_address']     = ee()->input->ip_address();
        $data['join_date']      = ee()->localize->now;
        $data['language']       = (ee()->config->item('deft_lang')) ? ee()->config->item('deft_lang') : 'english';
        $data['time_format']    = (ee()->config->item('time_format')) ? ee()->config->item('time_format') : 'us';
        $data['timezone']       = (ee()->config->item('default_site_timezone') && ee()->config->item('default_site_timezone') != '') ? ee()->config->item('default_site_timezone') : ee()->config->item('server_timezone');
        
        /*Set username*/
        $data['username']       = "";

        $displayName = $user_profile->displayName;

        /*Set custom field to save Social Username*/
        if($displayName != "" && $provider['settings']['custom_field_uname'] != "")
        {

            $field = "m_field_id_" . $provider['settings']['custom_field_uname'];

            if(ee()->slModel->checkRowExists("m_field_id", $provider['settings']['custom_field_uname'], 'member_fields'))
            {
                $data["m_field_id_" . $provider['settings']['custom_field_uname']] = $displayName;
            }

        }

        if($displayName == "")
        {
            $displayName = $user_profile->firstName;
        }

        if($displayName == "")
        {
            $displayName = $provider['provider'] . "@" . rand(999, 2000);
        }

        if($provider['settings']['email_as_username'] == "Y" && $user_profile->email != "")
        {
            $data['username']   = $user_profile->email;
        }
        else
        {
            $data['username']   = $this->generateUnique('username', $this->sanitize($displayName));
        }

        //add email from user so change at 13 jan 2021
        if(!isset($data['email'])){
            $data['email']   = $data['username'];
        }

        //screename need in ee6 so change at 13 jan 2021
        $data['screen_name']    = $this->generateUnique('screen_name', $displayName);

        if($data['social_id'] == "" || $data['social_id'] == NULL)
        {
            $data['social_id'] = rand(90000, 99999);
        }

        $member = ee('Model')->make('Member', $data);
        $member->save();
        $member_id = $member->member_id;

        //solved the issue of social id is not updated in above save function of member model at 13 jan 2021
        if($member_id != ''){
            ee()->slModel->updateMember($member_id, array('social_id' => $data['social_id']));
        }
                
        /*Save Images*/
        if($user_profile->photoURL != "")
        {
            $temp = $this->uploadMemberImages($member_id, $user_profile->photoURL, 'photo');
            if(isset($temp) && is_array($temp) && count($temp) > 0)
            {
                $member->set($temp);
            }

            $temp = $this->uploadMemberImages($member_id, $user_profile->photoURL, 'avatar');
            if(isset($temp) && is_array($temp) && count($temp) > 0)
            {
                $member->set($temp);
            }
        }

        $member->save();
        return $member_id;

    }

    /**
    * Upload image function to save static images comes from URL
    * Saves photo, avatar and signature images
    * @param $member_id  (Member ID of user to save the image)
    * @param $photoURL   (Full URL of image to fetch the image and save in our server)
    * @param $type       (Type of image [i.e., Photo, avatar or signature image])
    **/
    function uploadMemberImages($member_id, $photoURL, $type)
    {

        /*Define file name to save the image with*/
        $filename = $type."_". $member_id.".jpg";
        $photo_path = ee()->config->item($type.'_path');

        /*If type is avatar, extra folder for EE2*/
        /*if($type == "avatar")
        {
            $photo_path .= 'uploads/';
        }*/

        /*Define full file path where the image will save*/
        $filepath = $photo_path . $filename;

        /*Change file name if already found same name image*/
        while (file_exists($filepath))
        {
            $filename = $type.'_'.$member_id.'_'.rand(1, 100000).'.jpg';
            $filepath = $photo_path.$filename;
        }
        
        /*Initialize CURL*/
        $ch = curl_init();

        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off'))
        {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }
        else
        { 

            $rch = curl_copy_handle($ch);

            curl_setopt($rch, CURLOPT_HEADER, true);
            curl_setopt($rch, CURLOPT_NOBODY, true);
            curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
            curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);

            /*Modify URL till we get successful runnable URL*/
            do
            {

                curl_setopt($rch, CURLOPT_URL, $photoURL);
                $header = curl_exec($rch);

                if (curl_errno($rch)) 
                {
                    $code = false;
                }
                else 
                {

                    $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);

                    if ($code == 301 || $code == 302) 
                    {
                        preg_match('/Location:(.*?)\n/', $header, $matches);
                        $photoURL = trim(array_pop($matches));
                    } 
                    else 
                    {
                        $code = false;
                    }
                }

            } while ($code != false);

        }

        curl_setopt($ch, CURLOPT_URL, $photoURL);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

        /*Open file through CURL*/
        $fp = fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE);

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_exec($ch);

        /*Close CURL and FILE*/
        curl_close($ch);
        fclose($fp);

        $size = getimagesize($filepath);

        /*Change mime type if downloaded image is different mime type*/
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

        /*Final file path the image saved in*/
        $new_filepath = $photo_path.$filename;

        /*Get max height and width allowed in backend for this type of image*/
        $max_w  = (ee()->config->item($type.'_max_width') == '' OR ee()->config->item($type.'_max_width') == 0) ? 100 : ee()->config->item($type.'_max_width');
        $max_h  = (ee()->config->item($type.'_max_height') == '' OR ee()->config->item($type.'_max_height') == 0) ? 100 : ee()->config->item($type.'_max_height');
        
        /*Resize image if current file is higher than allowed file size*/
        if ($size[0] > $max_w && $size[1] > $max_h)
        {

            $config['source_image'] = $filepath;
            $config['new_image'] = $new_filepath;
            $config['maintain_ratio'] = TRUE;
            $config['width'] = $max_w;
            $config['height'] = $max_h;

            /*Load needful library classes*/
            if(! class_exists('image_lib'))
            {
                ee()->load->library('image_lib');
            }

            /*Init configuration*/
            ee()->image_lib->initialize($config);

            /*Resize the image*/
            ee()->image_lib->resize();


        }
        elseif ($new_filepath != $filepath)
        {
            /*Simply copy the image if no need to change the file size*/
            copy($filepath, $new_filepath);
        }

        /*If file successfully uploaded, save the file name in database*/
        if (file_exists($new_filepath))
        {

            /*Get size to save the info in DB*/
            $size = getimagesize($new_filepath);

            if ($size!==false)
            {

                /*Extra folder in avatar image is uploaded*/
                /*if($type == "avatar")
                {
                    $filename = "uploads/".$filename;
                }*/

                /*define array and save the data in DB*/
                return array($type . '_filename' => $filename, $type . '_width' => $size[0], $type . '_height' => $size[1]);

            }

        }

        return false;
    }

    /**
    * Change the name untill we found same name in DB
    * @param $variant    (The identifier [i.e., username, email])
    * @param $data       (The field string to perform Unique)
    * @param $member_id  (Member ID of user)
    * @return Final string with unique append
    **/
    function generateUnique($variant, $data, $member_id = "")
    {

        /*Initialize needful variables*/
        $temp = $data;
        $cnt = 0;
        
        /*Perform loop untill we get unique name*/
        while (1)
        {
            
            /*Rename each time we found same name and check in DB again*/
            if($cnt != 0)
            {
                $temp = $data . '_' . $cnt;
            }

            /*Fire query to DB to check the same field with same string is in DB or not*/
            $row = ee()->smModel->memberStaticFields(array($variant => $temp), $variant, $member_id);

            /*If unique found then break the loop*/
            if($row === FALSE)
            {
                break;
            }

            $cnt++;

        }

        /*Return the final string*/
        return $temp;

    }

    /**
    * Process social login form submission
    * @param $user_profile  (Array of User profile fetch from  social medial)
    * @param $data          (Array of Provider data)
    * @return member ID of user either after registration or login
    **/
    function process_social_login($user_profile, $data)
    {

        /*Initialize needful variables*/
        $member_id = "";

        /*Check if social provider return an email or not*/
        if(isset($user_profile->email) && $user_profile->email != "")
        {

            /*Check the duplication of email*/
            $row = ee()->smModel->memberStaticFields(array('email' => $user_profile->email), "member_id, social_id, username, email");

            /*If same email found*/
            if($row !== FALSE)
            {

                $row = $row[0];

                /*Update social ID to the database of member*/
                if($row['social_id'] == "" || $row['social_id'] == NULL)
                {
                    ee()->slModel->updateMember($row['member_id'], array('social_id' => $user_profile->identifier));
                }

                /*Set member ID variable to return the member ID of user*/
                $member_id = $row['member_id'];

            }
            else
            {
                /*If email duplication not found simply register the user with new account*/
                $member_id = $this->register_member($user_profile, $data);
            }

        }
        else
        {

            /*If social provider didn't send an email, check the user with social ID */
            $row = ee()->smModel->memberStaticFields(array('social_id' => $user_profile->identifier), "member_id");

            /*If no match found, register the user with new account*/
            if($row === FALSE)
            {
                $member_id = $this->register_member($user_profile, $data);
            }
            else
            {
                $member_id = $row[0]['member_id'];
            }

        }

        return $member_id;
        
    }

    /*sanitize function to convert string to remove extra spaces and unwanted special characters*/
    function sanitize($title)
    {

        $title = strip_tags($title);
        
        // Preserve escaped octets.
        $title = preg_replace('|%([a-fA-F0-9][a-fA-F0-9])|', '---$1---', $title);
        
        // Remove percent signs that are not part of an octet.
        $title = str_replace('%', '', $title);
        
        // Restore octets.
        $title = preg_replace('|---([a-fA-F0-9][a-fA-F0-9])---|', '%$1', $title);

        if ($this->seems_utf8($title))
        {

            if (function_exists('mb_strtolower'))
            {
                $title = mb_strtolower($title, 'UTF-8');
            }

            $title = $this->utf8_uri_encode($title, 200);

        }

        $title = strtolower($title);
        $title = preg_replace('/&.+?;/', '', $title); // kill entities
        $title = str_replace('.', '_', $title);
        $title = str_replace('-', '_', $title);

        $title = preg_replace('/[^%a-z0-9 _-]/', '', $title);
        $title = preg_replace('/\s+/', '_', $title);
        $title = preg_replace('|_+|', '_', $title);

        $title = trim($title, '_');

        return $title;

    }

    /*Genterate string in utf8. dependent function of sanitize*/
    function utf8_uri_encode( $utf8_string, $length = 0 )
    {

        $unicode = '';
        $values = array();
        $num_octets = 1;
        $unicode_length = 0;
        $string_length = strlen( $utf8_string );

        for ($i = 0; $i < $string_length; $i++ )
        {

            $value = ord( $utf8_string[ $i ] );

            if ( $value < 128 )
            {

                if ( $length && ( $unicode_length >= $length ) )
                {
                    break;
                }

                $unicode .= chr($value);
                $unicode_length++;

            }
            else
            {

                if ( count( $values ) == 0 ) $num_octets = ( $value < 224 ) ? 2 : 3;

                $values[] = $value;

                if ( $length && ( $unicode_length + ($num_octets * 3) ) > $length )
                {
                    break;
                }

                if ( count( $values ) == $num_octets )
                {

                    if ($num_octets == 3)
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]) . '%' . dechex($values[2]);
                        $unicode_length += 9;
                    }
                    else
                    {
                        $unicode .= '%' . dechex($values[0]) . '%' . dechex($values[1]);
                        $unicode_length += 6;
                    }

                    $values = array();
                    $num_octets = 1;

                }

            }

        }

        return $unicode;
        
    }

    /*dependent function of utf8_uri_encode*/
    function seems_utf8($str)
    {

        $length = strlen($str);

        for ($i=0; $i < $length; $i++)
        {

            $c = ord($str[$i]);

            if ($c < 0x80) $n = 0; /*0bbbbbbb*/
            elseif (($c & 0xE0) == 0xC0) $n=1; /*110bbbbb*/
            elseif (($c & 0xF0) == 0xE0) $n=2; /*1110bbbb*/
            elseif (($c & 0xF8) == 0xF0) $n=3; /*11110bbb*/
            elseif (($c & 0xFC) == 0xF8) $n=4; /*111110bb*/
            elseif (($c & 0xFE) == 0xFC) $n=5; /*1111110b*/
            else return false; /*Does not match any model*/
            
            for ($j=0; $j<$n; $j++)
            { 

                /*n bytes matching 10bbbbbb follow ?*/
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80))
                {
                    return false;
                }

            }

        }

        return true;

    }
    
}