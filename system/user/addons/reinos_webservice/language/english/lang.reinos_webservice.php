<?php

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
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
 * THIS CODE AND INFORMATION ARE PROVIDED 'AS IS' WITHOUT WARRANTY OF ANY
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

/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

$lang = array(
    REINOS_WEBSERVICE_MAP."_module_name"							=> REINOS_WEBSERVICE_TITLE,
    REINOS_WEBSERVICE_MAP.'_module_description'					=> REINOS_WEBSERVICE_DESCRIPTION,

    REINOS_WEBSERVICE_MAP.'_overview'									=> 'Overview',
    REINOS_WEBSERVICE_MAP.'_settings'									=> 'Settings',
    REINOS_WEBSERVICE_MAP.'_members'									=> 'Members',
    REINOS_WEBSERVICE_MAP.'_documentation'								=> 'Documentation',
    REINOS_WEBSERVICE_MAP.'_show_channel'								=> 'Show Details',
    REINOS_WEBSERVICE_MAP.'_add_member'									=> 'Add new member',
    REINOS_WEBSERVICE_MAP.'_show_member'								=> 'Edit member',
    REINOS_WEBSERVICE_MAP.'_delete_member'								=> 'Delete member',
    REINOS_WEBSERVICE_MAP.'_apis'										=> 'Api(s)',
    'channel_preference'									=> 'Preference',
    'connection_type'										=> 'Connection Type',
    REINOS_WEBSERVICE_MAP.'_channel'									=> 'Channel',
    REINOS_WEBSERVICE_MAP.'_services'									=> 'Services',
    REINOS_WEBSERVICE_MAP.'_active'										=> 'Active',
    'entry_id_show_channel'									=> 'Show details',
    'url'													=> 'Service Url',
    'entry_status'											=> 'Entry status',
    REINOS_WEBSERVICE_MAP.'_documentation'								=> 'Documentation',
    REINOS_WEBSERVICE_MAP.'_module_description'							=> 'Webservice (SOAP/XMLRPC/REST) for select, insert, update and delete entry`s and many more',
    'entry_id_nodata'										=> 'No data',
    REINOS_WEBSERVICE_MAP.'_error_duplicated_channel'					=> 'Cannot save the channel because there is already a channel with the same settings.',
    REINOS_WEBSERVICE_MAP.'_delete_channel'								=> 'Delete',
    REINOS_WEBSERVICE_MAP.'_delete_check' 								=> 'Are you sure you want to delete this channel service?',
    REINOS_WEBSERVICE_MAP.'_delete_check_notice' 						=> 'THIS ACTION CANNOT BE UNDONE',
    REINOS_WEBSERVICE_MAP.'_delete_succes' 								=> 'Channel service deleted',
    REINOS_WEBSERVICE_MAP.'_delete_cache_check' 						=> 'Are you sure you want to clear the cache?',
    REINOS_WEBSERVICE_MAP.'_cache_cleared_succes' 						=> 'Cache cleared',
    'active' 												=> 'Active',
    REINOS_WEBSERVICE_MAP.'_debug' 								=> 'Debug/log settings',
    'debug' 												=> 'Debug',
    REINOS_WEBSERVICE_MAP.'_debug_max_logs' 						=> 'Maximum log records',
    'apis' 													=> 'Choose your API(s)',
    'free_apis'												=> 'Choose your free API Method(s)',
    'log_services' 											=> 'Log the service',
    REINOS_WEBSERVICE_MAP.'_preference' 								=> 'Preferences',
    REINOS_WEBSERVICE_MAP.'_license_key'                          => 'License key',
    REINOS_WEBSERVICE_MAP.'_license_setting'						=> 'License setting',
    REINOS_WEBSERVICE_MAP.'_license_settings'						=> 'License settings',
    REINOS_WEBSERVICE_MAP.'_license'							    => 'License',
    REINOS_WEBSERVICE_MAP.'_setting' 									=> 'Setting',
    REINOS_WEBSERVICE_MAP.'_cache_settings' 							=> 'Cache settings',
    REINOS_WEBSERVICE_MAP.'_member' 									=> 'Member',
    REINOS_WEBSERVICE_MAP.'_status_check'								=> 'Status Check',
    REINOS_WEBSERVICE_MAP.'_report_stats'							=> 'Report Statistics',
    REINOS_WEBSERVICE_MAP.'_free_apis'								=> 'Free Api(s)',
    REINOS_WEBSERVICE_MAP.'_ip_blacklist'							=> 'IP Blacklist',
    REINOS_WEBSERVICE_MAP.'_ip_whitelist'							=> 'IP Whitelist',
    REINOS_WEBSERVICE_MAP.'_testing_tools'							=> 'Testing tools',
    REINOS_WEBSERVICE_MAP.'_testing_tool_url'						=> 'Testing tool URL',
    REINOS_WEBSERVICE_MAP.'_parse_matrix_grid_data'				=> 'Parse Matrix/Grid Data',
    REINOS_WEBSERVICE_MAP.'_log_id'								=> 'Log ID',
    REINOS_WEBSERVICE_MAP.'_member_id'								=> 'Member ID',
    REINOS_WEBSERVICE_MAP.'_msg'									=> 'Message',
    REINOS_WEBSERVICE_MAP.'_api_logs'									=> 'Api Logs',
    REINOS_WEBSERVICE_MAP.'_method'								=> 'Method',
    REINOS_WEBSERVICE_MAP.'_nodata'								=> 'No data',
    REINOS_WEBSERVICE_MAP.'_username'								=> 'Username',
    REINOS_WEBSERVICE_MAP.'_service'								=> 'Service',
    REINOS_WEBSERVICE_MAP.'_log_number'							=> 'Code',
    REINOS_WEBSERVICE_MAP.'_no_user_selected'						=> 'You did not select a user',
    REINOS_WEBSERVICE_MAP.'_role'							=> 'Role',
    REINOS_WEBSERVICE_MAP.'_ip'									=> 'IP address',
    REINOS_WEBSERVICE_MAP.'_duplicated_keys_error'					=> 'Duplicated keys are removed.',
    REINOS_WEBSERVICE_MAP.'_shortkeys'								=> 'Shortkeys',
    REINOS_WEBSERVICE_MAP.'_shortkey'								=> 'Shortkey',
    REINOS_WEBSERVICE_MAP.'_edit'									=> 'Edit Key',
    REINOS_WEBSERVICE_MAP.'_url_trigger'							=> 'Url Trigger	',
    REINOS_WEBSERVICE_MAP.'_cache'									=> 'Cache Enabled',
    REINOS_WEBSERVICE_MAP.'_cache_time'							=> 'Cache Time',
    REINOS_WEBSERVICE_MAP.'_clear_cache_on_save'					=> 'Clear cache on save',
    REINOS_WEBSERVICE_MAP.'_super_admin_shortkey'					=> 'Super Admin shortkey',
    REINOS_WEBSERVICE_MAP.'_time'									=> 'Datetime',
    REINOS_WEBSERVICE_MAP.'_rest_output_header'					=> 'Rest output header',
    REINOS_WEBSERVICE_MAP.'_show_queries'							=> 'Show Queries',
    REINOS_WEBSERVICE_MAP.'_show'									=> 'Show',
    REINOS_WEBSERVICE_MAP.'_api_label'								=> 'API',
    REINOS_WEBSERVICE_MAP.'_un_install'							=> 'Un/installed',
    REINOS_WEBSERVICE_MAP.'_api_overview'							=> 'API overview',
    REINOS_WEBSERVICE_MAP.'_clear_cache'							=> 'Clear cache',
    REINOS_WEBSERVICE_MAP.'_parse_rel_data'						=> 'Parse Relationship Data',
    REINOS_WEBSERVICE_MAP.'_show_feature_entries'					=> 'Show feature items',
    REINOS_WEBSERVICE_MAP.'_show_expiration_entries'				=> 'Show expired entries',
    REINOS_WEBSERVICE_MAP.'_round_date'							=> 'Round date',
    REINOS_WEBSERVICE_MAP.'_offset_time'							=> 'Offset time',
    REINOS_WEBSERVICE_MAP.'_entry_settings'						=> 'Entry specific settings',
    REINOS_WEBSERVICE_MAP.'_license_key'							=> 'License key',
    REINOS_WEBSERVICE_MAP.'_debug_settings'						=> 'Debug settings',
    REINOS_WEBSERVICE_MAP.'_http_user_agent'						=> 'HTTP User Agent',
    'wgt_webservice_logs_title'								=> 'Webservice Logs',
    'wgt_webservice_logs_name'								=> 'Webservice Logs',
    'wgt_webservice_logs_description'						=> 'Showing the latest Entry Logs',
    REINOS_WEBSERVICE_MAP.'_site_id_strict'								=> 'Strict site_id',

    'nav_webservice'										=> 'Webservice',
    'nav_webservice_settings'								=> 'Settings',
    'nav_webservice_testing_tool'							=> 'Testing tool',
    'nav_webservice_logs'									=> 'Logs',
    'nav_webservice_shortkey'								=> 'Shortkeys',
    'nav_webservice_api_overview'							=> 'API Overview',
    'nav_webservice_status'									=> 'Status',
    'nav_webservice_api_documentation'						=> 'Documentation',
    'nav_webservice_overview'								=> 'Overview',

    'general_settings'										=> 'General Settings',

    //logs
    REINOS_WEBSERVICE_MAP.'_column_time'						=> 'Time',
    REINOS_WEBSERVICE_MAP.'_column_username'						=> 'Username',
    REINOS_WEBSERVICE_MAP.'_column_ip'						=> 'IP',
    REINOS_WEBSERVICE_MAP.'_column_service'						=> 'Service',
    REINOS_WEBSERVICE_MAP.'_column_method'						=> 'Method',
    REINOS_WEBSERVICE_MAP.'_column_msg'						=> 'Message',
    REINOS_WEBSERVICE_MAP.'_column_total_queries'					=> 'Total queries',
    REINOS_WEBSERVICE_MAP.'_parse_service_classes'					=> 'Parse service classes',

    REINOS_WEBSERVICE_MAP.'_delete'							=> 'Delete',
    REINOS_WEBSERVICE_MAP.'_delete_notice'							=> 'You`re about to permanently delete those records',
    REINOS_WEBSERVICE_MAP.'_log'							=> 'Logging',
    REINOS_WEBSERVICE_MAP.'_log_settings'							=> 'Logging Settings',
    REINOS_WEBSERVICE_MAP.'_delete_all_logs'							=> 'Delete all logs',
    'column_log_id'									=> '#',
    'column_severity'									=> 'Severity',
    'column_time'									=> 'Time',
    'column_message'									=> 'Message',
);
