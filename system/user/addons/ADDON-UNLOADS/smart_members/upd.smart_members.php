<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Zealousweb\SmartMembersPRO\ZealCore\EE_Upd;
require PATH_THIRD.'smart_members/config.php';

class Smart_members_upd extends EE_Upd
{

    public $version         = SM_VER;
    public $settings        = array();

    public $module_name    = SM_MOD_NAME;

    // Constructor
    // public function __construct()
    // {
    //     ee()->load->dbforge();
    // }

    /**
     * Install the module
     *
     * @return boolean TRUE
     */
    public function install()
    {
        
        $mod_data = array(
            'module_name'           => $this->module_name,
            'module_version'        => $this->version,
            'has_cp_backend'        => "y",
            'has_publish_fields'    => 'n'
            );
        ee()->db->insert('modules', $mod_data);
        
        $fields = array(
            'id' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => FALSE,
                'auto_increment'=> TRUE
                ),
            'email_as_username' => array(
                'type'          => 'varchar',
                'constraint'    => '1',
                'null'          => TRUE
                ),
            'registration_template' => array(
                'type'          => 'int',
                'constraint'    => '2',
                'unsigned'      => TRUE,
                'null'          => TRUE
                ),
            'reset_key_expiration_hours' => array(
                'type'          => 'int',
                'constraint'    => '5',
                'unsigned'      => TRUE,
                'null'          => TRUE
                ),
            'screen_name_override' => array(
                'type'          => 'varchar',
                'constraint'    => '1',
                'null'          => TRUE
                ),
            'screen_name_field' => array(
                'type'          => 'varchar',
                'constraint'    => '100',
                'null'          => TRUE
                ),
            /*Recaptcha fields*/

            'enable_recaptcha' => array(
                'type' => 'varchar',
                'constraint' => '1',
                'null' => TRUE
                ),
            'recaptcha_site_key' => array(
                'type' => 'varchar',
                'constraint' => '150',
                'null' => TRUE
                ),
            'recaptcha_secret' => array(
                'type' => 'varchar',
                'constraint' => '150',
                'null' => TRUE
                ),

            /*Inside email fields*/
            'registration_email_subject' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => TRUE
                ),
            'registration_email_body' => array(
                'type' => 'mediumtext',
                'null' => TRUE
                ),
            'registration_email_word_wrap' => array(
                'type' => 'varchar',
                'constraint' => '10',
                'null' => TRUE
                ),
            'registration_email_mail_type' => array(
                'type' => 'varchar',
                'constraint' => '10',
                'null' => TRUE
                ),
            'forgot_pass_email_subject' => array(
                'type' => 'mediumtext',
                'null' => TRUE
                ),
            'forgot_pass_word_wrap' => array(
                'type' => 'varchar',
                'constraint' => '10',
                'null' => TRUE
                ),
            'forgot_pass_mail_type' => array(
                'type' => 'varchar',
                'constraint' => '10',
                'null' => TRUE
                ),
            'reset_password_template' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => TRUE
                ),
            'forgot_pass_email_body' => array(
                'type' => 'mediumtext',
                'null' => TRUE
                ),
            'registration_email_template' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => TRUE
                ),
            'forgot_pass_email_template' => array(
                'type' => 'varchar',
                'constraint' => '200',
                'null' => TRUE
                ),
            'self_activation' => array(
                'type' => 'mediumtext',
                'null' => TRUE
                ),
            /*Social login Callback URL*/
            'sl_callback_url' => array(
                'type' => 'varchar',
                'constraint' => '500',
                'null' => TRUE
                )
            );

        ee()->dbforge->add_field($fields);

        ee()->dbforge->add_key('id', TRUE);
        ee()->dbforge->create_table('smart_members_settings');

        $insert_basic_settings = array(
            'email_as_username'             => 'N', 
            'registration_template'         => 0, 
            'reset_key_expiration_hours'    => 24, 
            'screen_name_override'          => 'N', 
            'screen_name_field'             => NULL, 
            'enable_recaptcha'              => 'N',
            'registration_email_subject'    => 'Thank you for the registration in {site_name}',
            'registration_email_word_wrap'  => 'yes',
            'registration_email_mail_type'  => 'text',
            'registration_email_body'       => "{exp:smart_members:profile}\nHello {screen_name},\n\nThank you for register with {site_name}. You can visit site from this link: {site_url}\n\nYour details are as follows :\n{sm_list_all_fields}\n  {field_label} :  {field_value}\n{/sm_list_all_fields}\n\nThank you,\nGorup {site_name}\n{/exp:smart_members:profile}",
            'forgot_pass_email_subject'     => 'Reset password request',
            'forgot_pass_word_wrap'         => 'yes',
            'forgot_pass_mail_type'         => 'text',
            'forgot_pass_email_body'        => "Hello {screen_name},\n\nClick on below URL to reset your password :\n{reset_url}\n\nThank you\nGroup {site_name}",
            'sl_callback_url'               => $this->calcCallbackURL()
            );

        ee()->db->insert('smart_members_settings', $insert_basic_settings);  

        $fields = array(
            'id' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => FALSE,
                'auto_increment'=> TRUE
                ),
            'member_id' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => FALSE,
                ),
            'group_id' => array(
                'type'          => 'int',
                'constraint'    => '10',
                'unsigned'      => TRUE,
                'null'          => FALSE,
                ),
            'email_sent' => array(
                'type'          => 'varchar',
                'constraint'    => '10',
                'null'          => TRUE
                ),
            );
        
        ee()->dbforge->add_field($fields);
        ee()->dbforge->add_key('id', TRUE);
        ee()->dbforge->create_table('smart_members_email_list');

        /*Default method*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_default_method'
            );
        ee()->db->insert('actions', $data);

        /*Registration form*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_registration'
            );
        ee()->db->insert('actions', $data);
        
        /*Login form*/
        $data = array(
            'class'     => 'Smart_members',
            'method'    => 'sm_login'
            );
        ee()->db->insert('actions', $data);

        /*Logout form*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_logout'
            );
        ee()->db->insert('actions', $data);

        /*Update profile*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_update_profile'
            );
        ee()->db->insert('actions', $data);

        /*Delete Profile*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_delete_profile'
            );
        ee()->db->insert('actions', $data);

        /*Forgot Password*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_forgot_password'
            );
        ee()->db->insert('actions', $data);

        /*Reset Password*/
        $data = array(
            'class'     => $this->module_name,
            'method'    => 'sm_reset_password'
            );
        ee()->db->insert('actions', $data);
        
        $this->activate_extension();

        $this->importExport();

        $this->socialSettings();

        $this->customMemberFieldTypes();

       // $this->removeLicenseEntry();

        return TRUE;

    }

    function activate_extension($settings = array())
    {
        $this->settings = $settings;
    
        $hooks = array(
            "cp_members_validate_members"       => "cp_members_validate_members",
            "member_register_validate_members"  => "member_register_validate_members",
            /*"cp_menu_array"                   => "cp_menu_array",*/
        );

        $data_class = str_replace('_upd', '_ext', __CLASS__);
        foreach ($hooks as $hook => $method) {
            $data = array(
                'class'     => $data_class,
                'method'    => $method,
                'hook'      => $hook,
                'settings'  => "",
                'priority'  => 10,
                'version'   => $this->version,
                'enabled'   => 'y'
            );
        
            ee()->db->insert('extensions', $data);
        }

    }

    function importExport()
    {
        if(! ee()->db->table_exists("smart_members_exports"))
        {

            $fields = array(
                'id' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => FALSE,
                    'auto_increment'=> TRUE
                    ),
                'member_id' => array(
                    'type'          => 'int',
                    'constraint'    => '5',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'created_date' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'last_modified' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'export_counts' => array(
                    'type'          => 'int',
                    'constraint'    => '5',
                    'unsigned'      => TRUE,
                    'null'          => TRUE,
                    'default'       => 0
                    ),
                'token' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'download_without_login' => array(
                    'type'          => 'varchar',
                    'constraint'    => '1',
                    'null'          => TRUE
                    ),
                'type' => array(
                    'type'          => 'varchar',
                    'constraint'    => '10',
                    'null'          => TRUE
                    ),
                'format' => array(
                    'type'          => 'varchar',
                    'constraint'    => '3',
                    'null'          => TRUE
                    ),
                'settings' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),
                'status' => array(
                    'type'          => 'varchar',
                    'constraint'    => '10',
                    'null'          => TRUE
                    ),
                );

            ee()->dbforge->add_field($fields);

            ee()->dbforge->add_key('id', TRUE);
            ee()->dbforge->create_table('smart_members_exports');

            /*Reset Password*/
            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_export'
                );
            ee()->db->insert('actions', $data);

        }

        if(! ee()->db->table_exists("smart_members_imports"))
        {

            $fields = array(
                'id' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => FALSE,
                    'auto_increment'=> TRUE
                    ),
                'member_id' => array(
                    'type'          => 'int',
                    'constraint'    => '5',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'created_date' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'last_modified' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => TRUE
                    ),
                'import_counts' => array(
                    'type'          => 'int',
                    'constraint'    => '5',
                    'unsigned'      => TRUE,
                    'null'          => TRUE,
                    'default'       => 0
                    ),
                'token' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'import_without_login' => array(
                    'type'          => 'varchar',
                    'constraint'    => '1',
                    'null'          => TRUE
                    ),
                'type' => array(
                    'type'          => 'varchar',
                    'constraint'    => '10',
                    'null'          => TRUE
                    ),
                'format' => array(
                    'type'          => 'varchar',
                    'constraint'    => '3',
                    'null'          => TRUE
                    ),
                'settings' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),

                'status' => array(
                    'type'          => 'varchar',
                    'constraint'    => '10',
                    'null'          => TRUE
                    ),
                );

            ee()->dbforge->add_field($fields);

            ee()->dbforge->add_key('id', TRUE);
            ee()->dbforge->create_table('smart_members_imports');

            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_import'
                );
            ee()->db->insert('actions', $data);

            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_import_success'
                );
            ee()->db->insert('actions', $data);

        }

    }

    function socialSettings()
    {
        if(! ee()->db->table_exists("smart_members_social_settings"))
        {
            
            $fields = array(
                'id' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => FALSE,
                    'auto_increment'=> TRUE
                    ),
                'provider' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'short_name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '150',
                    'null'          => TRUE
                    ),
                'key' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),
                'secret' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),
                'settings' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),
                );

            ee()->dbforge->add_field($fields);

            ee()->dbforge->add_key('id', TRUE);
            ee()->dbforge->create_table('smart_members_social_settings');

            $insert_basic_settings = array(
                'provider'      => 'Facebook', 
                'short_name'    => 'facebook', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Facebook',
                        'call_back_url'         => '1',
                        'Key_label'             => "Application ID",
                        'secret_label'          => "Application Secret",
                        'custom_field_label'    => "Custom field holding Facebook username",
                        'pending_if_no_email'   => 'N',
                        'email_as_username'     => 'N', 
                        'member_group'          => "5",
                        'custom_field_uname'    => '', 
                        'dev_url'               => "https://developers.facebook.com/quickstarts/?platform=web",
                        'more_info'             => SM_MORE_INFO_URL."#facebook",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings); 

            $insert_basic_settings = array(
                'provider'      => 'Twitter', 
                'short_name'    => 'twitter', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Twitter',
                        'call_back_url'         => '1',
                        'Key_label'             => "Consumer key",
                        'secret_label'          => "Consumer Secret",
                        'custom_field_label'    => "Custom field holding Twitter username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://apps.twitter.com/app/new",
                        'more_info'             => SM_MORE_INFO_URL."#twitter",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings); 

            $insert_basic_settings = array(
                'provider'      => 'Google', 
                'short_name'    => 'google', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Google',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Google username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://console.developers.google.com/",
                        'more_info'             => SM_MORE_INFO_URL."#google",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Live', 
                'short_name'    => 'live', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Live',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Microsoft Live username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://apps.dev.microsoft.com/#/appList/create/sapi",
                        'more_info'             => SM_MORE_INFO_URL."#live",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Yahoo', 
                'short_name'    => 'yahoo', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Yahoo',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Yahoo username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://developer.yahoo.com/apps/create/",
                        'more_info'             => SM_MORE_INFO_URL."#yahoo",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Foursquare', 
                'short_name'    => 'foursquare', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Foursquare',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Foursquare username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://foursquare.com/developers/apps",
                        'more_info'             => SM_MORE_INFO_URL."#foursquare",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'GitHub', 
                'short_name'    => 'github', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'GitHub',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding GitHub username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://github.com/settings/applications/new",
                        'more_info'             => SM_MORE_INFO_URL."#github",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'px500', 
                'short_name'    => 'px500', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'px500',
                        'call_back_url'         => '1',
                        'Key_label'             => "Customer ID",
                        'secret_label'          => "Customer Secret",
                        'custom_field_label'    => "Custom field holding px500 username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://500px.com/settings/applications",
                        'more_info'             => SM_MORE_INFO_URL."#px500",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);
            
            $insert_basic_settings = array(
                'provider'      => 'BitBucket', 
                'short_name'    => 'bitbucket', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'BitBucket',
                        'call_back_url'         => '1',
                        'Key_label'             => "Customer Key",
                        'secret_label'          => "Customer Secret",
                        'custom_field_label'    => "Custom field holding BitBucket username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://bitbucket.org/account/user/testing_eecms/api",
                        'more_info'             => SM_MORE_INFO_URL."#bitbucket",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Disqus', 
                'short_name'    => 'disqus', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Disqus',
                        'call_back_url'         => '1',
                        'Key_label'             => "Public Key",
                        'secret_label'          => "Secret Key",
                        'custom_field_label'    => "Custom field holding Disqus username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://disqus.com/api/applications/register/",
                        'more_info'             => SM_MORE_INFO_URL."#disqus",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Dribbble', 
                'short_name'    => 'dribbble', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Dribbble',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Dribbble username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://dribbble.com/account/applications/new",
                        'more_info'             => SM_MORE_INFO_URL."#dribbble",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Dropbox', 
                'short_name'    => 'dropbox', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Dropbox',
                        'call_back_url'         => '1',
                        'Key_label'             => "App Key",
                        'secret_label'          => "App Secret",
                        'custom_field_label'    => "Custom field holding Dropbox username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://www.dropbox.com/developers/apps/create",
                        'more_info'             => SM_MORE_INFO_URL."#dropbox",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'GitLab', 
                'short_name'    => 'gitlab', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'GitLab',
                        'call_back_url'         => '1',
                        'Key_label'             => "Application ID",
                        'secret_label'          => "Application Secret",
                        'custom_field_label'    => "Custom field holding GitLab username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://gitlab.com/oauth/applications",
                        'more_info'             => SM_MORE_INFO_URL."#gitlab",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Instagram', 
                'short_name'    => 'instagram', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Instagram',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Instagram username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://www.instagram.com/developer/clients/register/",
                        'more_info'             => SM_MORE_INFO_URL."#instagram",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'LastFM', 
                'short_name'    => 'lastfm', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'LastFM',
                        'call_back_url'         => '1',
                        'Key_label'             => "API key",
                        'secret_label'          => "Shared Secret",
                        'custom_field_label'    => "Custom field holding LastFM username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "http://www.last.fm/api/account/create",
                        'more_info'             => SM_MORE_INFO_URL."#lastfm",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'MailChimp', 
                'short_name'    => 'mailchimp', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'MailChimp',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding MailChimp username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://us14.admin.mailchimp.com/account/oauth2/client/",
                        'more_info'             => SM_MORE_INFO_URL."#mailchimp",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Slack', 
                'short_name'    => 'slack', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Slack',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Slack username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://api.slack.com/apps/new",
                        'more_info'             => SM_MORE_INFO_URL."#slack",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'SoundCloud', 
                'short_name'    => 'soundcloud', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'SoundCloud',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding SoundCloud username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "http://soundcloud.com/you/apps/new",
                        'more_info'             => SM_MORE_INFO_URL."#soundcloud",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            $insert_basic_settings = array(
                'provider'      => 'Vimeo', 
                'short_name'    => 'vimeo', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Vimeo',
                        'call_back_url'         => '1',
                        'Key_label'             => "Client ID",
                        'secret_label'          => "Client Secret",
                        'custom_field_label'    => "Custom field holding Vimeo username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://developer.vimeo.com/apps/new",
                        'more_info'             => SM_MORE_INFO_URL."#vimeo",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);
            
            $insert_basic_settings = array(
                'provider'      => 'Tumblr', 
                'short_name'    => 'tumblr', 
                'settings'      => serialize(
                    array(
                        'label'                 => 'Tumblr',
                        'call_back_url'         => '1',
                        'Key_label'             => "OAuth consumer key",
                        'secret_label'          => "OAuth consumer secret",
                        'custom_field_label'    => "Custom field holding Tumblr username",
                        'pending_if_no_email'   => 'N', 
                        'email_as_username'     => 'N',
                        'member_group'          => "5",
                        'custom_field_uname'    => '',  
                        'dev_url'               => "https://www.tumblr.com/oauth/register",
                        'more_info'             => SM_MORE_INFO_URL."#tumblr",
                        )
                    ), 
                );
            ee()->db->insert('smart_members_social_settings', $insert_basic_settings);

            if(! ee()->db->field_exists('social_id', 'members'))
            {

                /*Add fields to main setting table*/
                $fields = array(
                    'social_id'         => array(
                        'type'          => 'varchar',
                        'constraint'    => '100',
                        'null'          => TRUE
                        )
                    );
                ee()->dbforge->add_column('members', $fields);

            }

            /*Social login form submit*/
            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_social_form'
                );
            ee()->db->insert('actions', $data);

            /*Social login popup method*/
            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_social_popup'
                );
            ee()->db->insert('actions', $data);

            /*API for Social login popup method*/
            $data = array(
                'class'     => $this->module_name,
                'method'    => 'sm_social_api'
                );
            ee()->db->insert('actions', $data);

        }

    }

    function customMemberFieldTypes()
    {
        if(! ee()->db->table_exists("smart_members_field_types"))
        {

            $fields = array(
                'id' => array(
                    'type'          => 'int',
                    'constraint'    => '10',
                    'unsigned'      => TRUE,
                    'null'          => FALSE,
                    'auto_increment'=> TRUE
                    ),
                'field_type' => array(
                    'type'          => 'varchar',
                    'constraint'    => '50',
                    'null'          => TRUE
                    ),
                'field_name' => array(
                    'type'          => 'varchar',
                    'constraint'    => '50',
                    'null'          => TRUE
                    ),
                'settings' => array(
                    'type'          => 'mediumtext',
                    'null'          => TRUE
                    ),

                );

            ee()->dbforge->add_field($fields);

            ee()->dbforge->add_key('id', TRUE);
            ee()->dbforge->create_table('smart_members_field_types');

            $data = array(
                'field_type'    => 'radio', 
                'field_name'    => 'Radio Buttons', 
                'settings'      => serialize(
                    array(
                        'save_in'   => 'text',
                        'label'     => 'Options for Radio Buttons',
                        'label2'    => '',
                        )
                    ), 
                );
            ee()->db->insert('smart_members_field_types', $data); 

            $data = array(
                'field_type'    => 'checkboxes', 
                'field_name'    => 'Checkboxes', 
                'settings'      => serialize(
                    array(
                        'save_in'   => 'textarea',
                        'label'     => 'Options for Checkboxes',
                        'label2'    => '',
                        )
                    ), 
                );
            ee()->db->insert('smart_members_field_types', $data);

            $data = array(
                'field_type'    => 'multi_select', 
                'field_name'    => 'Multi Select', 
                'settings'      => serialize(
                    array(
                        'save_in'   => 'textarea',
                        'label'     => 'Options for Multi Select',
                        'label2'    => '',
                        )
                    ), 
                );
            ee()->db->insert('smart_members_field_types', $data);

            $data = array(
                'field_type'    => 'file', 
                'field_name'    => 'File', 
                'settings'      => serialize(
                    array(
                        'save_in'   => 'text',
                        'label'     => 'Allowed file type',
                        'label2'    => 'Allowed directory',
                        )
                    ), 
                );
            ee()->db->insert('smart_members_field_types', $data);

        }

        //to here out side in 3.0.1 due to error from ee2 upgrade issue
        if(! ee()->db->field_exists('m_sm_settings', 'member_fields'))
        {

            /*Add fields to main setting table*/
            $fields = array(
                'm_sm_settings'       => array(
                    'type' => 'mediumtext',
                    'null' => TRUE
                    )
                );
            ee()->dbforge->add_column('member_fields', $fields);

        }

    }
    
    /**
    * Uninstall the module
    *
    * @return boolean TRUE
    */
    public function uninstall()
    {
       
        $this->removeLicenseEntry();
        ee()->db->select('module_id');
        $query = ee()->db->get_where('modules', array( 'module_name' => $this->module_name ) );
        
        if(SM_APP_VER_L6){
            //only for ee5
            ee()->db->where('module_id', $query->row('module_id'));
            ee()->db->delete('module_member_groups');
        }  
        
        ee()->db->where('module_name', $this->module_name);
        ee()->db->delete('modules');
        
        ee()->db->where('class', $this->module_name);
        ee()->db->delete('actions');
        
        ee()->db->where('class', $this->module_name.'_mcp');
        ee()->db->delete('actions');

        ee()->dbforge->drop_table('smart_members_settings');
        ee()->dbforge->drop_table('smart_members_email_list');
        ee()->dbforge->drop_table('smart_members_exports');
        ee()->dbforge->drop_table('smart_members_social_settings');
        ee()->dbforge->drop_table('smart_members_imports');
        ee()->dbforge->drop_table('smart_members_field_types');
        
        if(ee()->db->field_exists('social_id', 'members'))
        {
            ee()->dbforge->drop_column('members', 'social_id');
        }
        
        if(ee()->db->field_exists('token_id', 'members'))
        {
            ee()->dbforge->drop_column('members', 'token_id');
        }

        if(ee()->db->field_exists('m_sm_settings', 'member_fields'))
        {
            ee()->dbforge->drop_column('member_fields', 'm_sm_settings');
        }

        $this->disable_extension();
        return TRUE;

    }

    function disable_extension()
    {
        $data_class = str_replace('_upd', '_ext', __CLASS__);
        ee()->db->where('class', $data_class);
        ee()->db->delete('extensions');
    }
    
    /**
    * Update the module
    *
    * @return boolean
    */
    public function update($current = '')
    {
        if ($current == $this->version) {
            // No updates
            return FALSE;
        }
        
        $this->importExport();

        $this->socialSettings();

        $this->customMemberFieldTypes();
            

        /* Updates for  v 2.0.0 Start */
        if(! ee()->db->field_exists('sl_callback_url', 'smart_members_settings'))
        {
            /*Social login Callback URL*/
            $fields = array(
                'sl_callback_url' => array(
                    'type' => 'varchar',
                    'constraint' => '500',
                    'null' => TRUE
                )
            );
            ee()->dbforge->add_column('smart_members_settings', $fields);

            ee()->db->update('smart_members_settings', array( 'sl_callback_url' => $this->calcCallbackURL() ));
        }

        if(! ee()->db->field_exists('self_activation', 'smart_members_settings'))
        {
            $fields = array(
                'self_activation' => array(
                    'type' => 'mediumtext',
                    'null' => TRUE
                ),
            );
            ee()->dbforge->add_column('smart_members_settings', $fields);
        }

        /* Updates for  v 2.0.0 Start */
        if(! ee()->db->field_exists('format', 'smart_members_imports'))
        {

            /*Add fields to main setting table*/
            $fields = array(
                'format' => array(
                    'type'          => 'varchar',
                    'constraint'    => '3',
                    'null'          => TRUE
                ),
            );
            ee()->dbforge->add_column('smart_members_imports', $fields);

        }

        if(! ee()->db->field_exists('settings', 'smart_members_imports'))
        {

            /*Add fields to main setting table*/
            $fields = array(
                'settings' => array(
                    'type' => 'mediumtext',
                    'null' => TRUE
                ),
            );
            ee()->dbforge->add_column('smart_members_imports', $fields);

        }

        if(ee()->db->field_exists('import_settings', 'smart_members_imports'))
        {
            ee()->dbforge->drop_column('smart_members_imports', 'import_settings');
        }
        if(ee()->db->field_exists('field_settings', 'smart_members_imports'))
        {
            ee()->dbforge->drop_column('smart_members_imports', 'field_settings');
        }
        if(ee()->db->field_exists('meta_settings', 'smart_members_imports'))
        {
            ee()->dbforge->drop_column('smart_members_imports', 'meta_settings');
        }
        /* Updates for v 2.0.0 End */
        $this->UpdateExistLicenseKey();
        
        return TRUE;

    }
    
    function calcCallbackURL()
    {
        $final_url = trim(str_replace($_SERVER['DOCUMENT_ROOT'], "", PATH_THIRD), '/');
        $base_url = rtrim(ee()->config->item('base_url'), "/");
        $base_name = basename($base_url);
        
        $selectWrapper = array();
        $selectWrapper = explode('/', $final_url);
        if($base_name == $selectWrapper[0])
        {
            $base_url = str_replace($base_name, "", $base_url);
        }
        unset($selectWrapper);
        
        return trim($base_url, "/") . "/" . trim($final_url, "/") . '/' . 'smart_members/api.php';
    }
}

/* End of file upd.smart_members.php */
/* Location: /system/expressionengine/third_party/smart_members/upd.smart_members.php */ 
?>