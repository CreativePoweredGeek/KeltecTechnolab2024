<?php

/**
 * @author        Rein de Vries <support@reinos.nl>
 * @link        https://addons.reinos.nl
 * @copyright    Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

//contants
if (!defined('REINOS_WEBSERVICE_TITLE')) {
    define('REINOS_WEBSERVICE_TITLE', 'Webservice (by Reinos)');
    define('REINOS_WEBSERVICE_CLASS', 'Reinos_webservice');
    define('REINOS_WEBSERVICE_SERVICE_NAME', 'reinos_webservice'); //service name
    define('REINOS_WEBSERVICE_NAMESPACE_CLASS', 'Webservice'); //service namespace
    define('REINOS_WEBSERVICE_MAP', 'reinos_webservice');
    define('REINOS_WEBSERVICE_VERSION', '8.1.3');
    define(
        'REINOS_WEBSERVICE_DESCRIPTION',
        'Webservice (SOAP/XMLRPC/REST) for select, insert, update and delete entries (and many more)'
    );
    define('REINOS_WEBSERVICE_DOCS', 'https://addons.reinos.nl/webservice');
    define('REINOS_WEBSERVICE_AUTHOR', 'Rein de Vries');
    define('REINOS_WEBSERVICE_AUTHOR_URL', 'https://addons.reinos.nl/');
    define('REINOS_WEBSERVICE_PHP_MIN_VERSION', '7.1');
    define('REINOS_WEBSERVICE_LICENSE_ENABLED', true);
}

//configs
$config['name'] = REINOS_WEBSERVICE_TITLE;
$config['version'] = REINOS_WEBSERVICE_VERSION;

//load compat file
require_once(PATH_THIRD . REINOS_WEBSERVICE_MAP . '/compat.php');
require_once PATH_THIRD . REINOS_WEBSERVICE_MAP . '/vendor/autoload.php';

if (!class_exists('Reinos_webservice_config')) {
    class Reinos_webservice_config
    {
        public static function updates()
        {
            return array(
                //	'4.1',
            );
        }
        //service methods
        public static function services()
        {
            return array(
                'soap' => 'SOAP',
                'xmlrpc' => 'XML-RPC',
                'rest' => 'REST',
                'custom' => 'Custom (manually via PHP code)'
            );
        }

        //enabled disables
        public static function service_active()
        {
            return array(
                '1' => 'Active',
                '0' => 'Inactive',
            );
        }

        //Debug
        public static function service_debug()
        {
            return array(
                '1' => 'Yes',
                '0' => 'No',
            );
        }

        //Default Post
        public static function default_post()
        {
            return array(
                'license_key' => '',
                'license_reinos_member_id' => '',
                'license_valid' => false,
                'license_report_date' => time(),
                'log_severity' => 'errors',
                'debug'                           => true,
                'debug_max_logs'                => 10000,
                'no_inlog_channels'             => '',
                'tmp_dir'                        => PATH_THIRD . 'reinos_webservice/_tmp',
                'ip_blacklist'                    => '',
                //'ip_whitelist'				=> '',
                'free_apis'                        => serialize(array('')),
                'rest_auth'                     => 'none',
                'url_trigger'                    => 'webservice',
                'super_admin_shortkey'            => '',
                'rest_output_header'            => '',
                'site_id_strict'                => false,
                'testing_tool_url'                => 'http://' . $_SERVER['SERVER_NAME'],
                'cache'                            => false,
                'cache_time'                    => 86400,
                //'clear_cache_on_save'			=> false,
                'parse_rel_data'                => false,
                'parse_matrix_grid_data'        => false,
                'show_feature_entries'            => true,
                'show_expiration_entries'        => true,
                'offset_time'                    => '',
                'round_date'                    => 'no',
                'http_user_agent'                => '',
                'parse_service_classes'            => false
            );
        }

        //overrides
        public static function overide_settings()
        {
            return  array();
        }

        // Backwards-compatibility with pre-2.6 Localize class
        public static function format_date_fn()
        {
            return (version_compare(APP_VER, '2.6', '>=')) ? 'format_date' : 'decode_date';
        }

        public static function fieldtype_settings()
        {
            return array(
                array(
                    'label' => lang('license'),
                    'name' => 'license',
                    'type' => 't', // s=select, m=multiselect t=text
                    //'options' => array('No', 'Yes'),
                    'def_value' => '',
                    'global' => true, //show on the global settings page
                ),

            );
        }

        //the service errors
        public static function service_error()
        {
            return array(
                //success
                'succes_create' => array(
                    'response'             => 'ok',
                    'message'            => 'Created successfully',
                    //'code'				=> 200,
                    'code_http'            => 200,
                ),

                'succes_read' => array(
                    'response'             => 'ok',
                    'message'            => 'Successfully readed',
                    //'code'				=> 200,
                    'code_http'            => 200,
                ),

                'succes_update' => array(
                    'response'             => 'ok',
                    'message'            => 'Successfully updated',
                    //'code'				=> 200,
                    'code_http'            => 200,
                ),

                'succes_delete' => array(
                    'response'             => 'ok',
                    'message'            => 'Successfully deleted',
                    //'code'				=> 200,
                    'code_http'            => 200,
                ),

                'succes_auth' => array(
                    'message'            => 'Auth success',
                    //'code'				=> 200,
                    'code_http'            => 200,
                ),

                //-------------------------------------------------------------

                //errors API/Services
                'error_access' => array(
                    'message'            => 'You are not authorized to use this service',
                    //'code'				=> 5201,
                    'code_http'            => 200,
                ),

                'error_inactive' => array(
                    'message'            => 'Service is not running',
                    //'code'				=> 5202,
                    'code_http'            => 200,
                ),

                'error_api' => array(
                    //'code'				=> 5203, //general api error
                    'code_http'            => 200,
                ),

                'error_api_type' => array(
                    'message'            => 'This API is not active for this services',
                    //'code'				=> 5204,
                    'code_http'            => 200,
                ),

                'error_api_ip' => array(
                    'message'            => 'This IP (' . $_SERVER['REMOTE_ADDR'] . ') has no access',
                    //'code'				=> 5205,
                    'code_http'            => 200,
                ),
                'error_auth' => array(
                    'message'            => 'Auth error',
                    //'code'				=> 5206,
                    'code_http'            => 200,
                ),
                'error_license' => array(
                    'message'            => 'Oeps! The ' . REINOS_WEBSERVICE_TITLE . ' has an incorrect License. Grab a license from addons.reinos.nl and fill the license in the CP',
                    //'code'				=> 5207,
                    'code_http'            => 200,
                ),
            );
        }

        public static function content_types()
        {
            return array(
                'json' => 'application/json',
                'xml' => 'text/xml',
                'array' => 'php/array',
                'default' => 'text/html',
            );
        }

        public static function mysql_table_data()
        {
            return array(
                'settings' => array(
                    'keys' => array(
                        array('settings_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'settings_id'    => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'auto_increment'    => TRUE
                        ),
                        'site_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'var'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'value'  => array(
                            'type'             => 'text'
                        ),
                    )
                ),

                'members' => array(
                    'keys' => array(
                        array('webservice_member_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'webservice_member_id'    => array(
                            'type'                => 'int',
                            'constraint'        => 7,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                            'auto_increment'    => TRUE,
                        ),
                        'member_id'    => array(
                            'type'                => 'int',
                            'constraint'        => 7,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                        ),
                        'role_id'    => array(
                            'type'                => 'int',
                            'constraint'        => 7,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                        ),
                        'services'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '255',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                        'apis'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '255',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                        'shortkeys'  => array(
                            'type'                 => 'text',
                            'null'                => TRUE,
                        ),
                        'active'    => array(
                            'type'                => 'int',
                            'constraint'        => 1,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                        ),
                        'type'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '20',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                        'auth'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '40',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                    )
                ),

                'logs' => array(
                    'keys' => array(
                        array('log_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'log_id'    => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'auto_increment'    => TRUE
                        ),
                        'site_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'severity'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => 'notice'
                        ),
                        'time'  => array(
                            'type'            => 'int',
                            'constraint'        => 10,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'message'  => array(
                            'type'             => 'text'
                        ),
                    )
                ),
                'api_logs' => array(
                    'keys' => array(
                        array('log_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'log_id'    => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'auto_increment'    => TRUE
                        ),
                        'site_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'username'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'time'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '150',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'service'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'ip'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'log_number'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'method'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'msg'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'total_queries'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'queries'  => array(
                            'type'                 => 'mediumtext',
                            'null'                => FALSE,
                        ),
                        'data'  => array(
                            'type'                 => 'mediumtext',
                            'null'                => FALSE,
                        ),
                    )
                ),

                'shortkeys' => array(
                    'keys' => array(
                        array('shortkey_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'shortkey_id'    => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'auto_increment'    => TRUE
                        ),
                        'site_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'webservice_member_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'shortkey'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        )
                    )
                ),

                'sessions' => array(
                    'keys' => array(
                        array('user_agent_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'user_agent_id'    => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'auto_increment'    => TRUE
                        ),
                        'session_id'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '255',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'user_agent'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'timestamp'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        )
                    )
                ),

                'keys' => array(
                    'keys' => array(
                        array('key_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'key_id'    => array(
                            'type'                => 'int',
                            'constraint'        => 7,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                            'auto_increment'    => TRUE,
                        ),
                        'site_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'webservice_member_id'  => array(
                            'type'            => 'int',
                            'constraint'        => 7,
                            'unsigned'        => TRUE,
                            'null'            => FALSE,
                            'default'            => 0
                        ),
                        'key'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        ),
                        'secret'  => array(
                            'type'             => 'varchar',
                            'constraint'        => '200',
                            'null'            => FALSE,
                            'default'            => ''
                        )
                    )
                ),

                'tokens' => array(
                    'keys' => array(
                        array('token_id', true), //primary
                        //array('field_name'),
                    ),
                    'fields' => array(
                        'token_id'    => array(
                            'type'                => 'int',
                            'constraint'        => 7,
                            'unsigned'            => TRUE,
                            'null'                => FALSE,
                            'auto_increment'    => TRUE,
                        ),
                        'session_id'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '255',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                        'token'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '255',
                            'null'                => FALSE,
                            'default'            => ''
                        ),
                        'expire_after'  => array(
                            'type'                 => 'varchar',
                            'constraint'        => '255',
                            'null'                => FALSE,
                            'default'            => ''
                        )
                    )
                ),
            );
        }
    }
}
