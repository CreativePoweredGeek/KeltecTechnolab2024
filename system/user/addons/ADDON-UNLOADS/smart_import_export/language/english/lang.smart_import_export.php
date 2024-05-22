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
* @filesource   ./system/expressionengine/third_party/smart_import_export/language/english/lang.smart_import_export.php
*
*/
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(

    /* Required for ADD-ONS > MODULES page */
    'smart_import_export_module_name'          => 'Smart Import Export',
    'smart_import_export_module_description'   => 'Export entries in XML or CSV file format with given filters.',

    'smart_import_export_import_module_name'   => 'Smart Import Export',
    
    /* Required for CONTROL PANEL pages */
    'mod_name'                          => 'smart_import_export_import',
    'export_manager'                    => 'Export Manager',
    'mod_desc'                          => 'Export entries in XML or CSV file format with given filters.',
    'label_title_index'                 => 'Smart Import Export',
    'edit_selected'                     => 'Edit Selected',
    'delete_selected'                   => 'Delete Selected',
    'no_found'                          => 'No <b>%s</b> found.',
    'exports'                           => 'Exports',

    /* Required for FRONT END module */
    'submit'                            => 'Submit',
    'value'                             => 'Value',
    'setting'                           => 'Setting',
    'export'                            => 'Export',
    'import'                            => 'Import',
    'documentation'                     => 'Documentation',
    'smart_import_export'               => 'Smart Import Export',
    'nav_smart_import_export'           => 'Smart Import Export',
    'export_list'                       => 'Export List',
    'save_export'                       => 'Save Export',
    'export_form_title'                 => 'Edit Export Settings',
    'create_new_export'                 => 'Create New Export',

    /*Export form variables*/
    'channel'                           => 'Channel',
    'select_channel'                    => 'Select Channel',
    'all'                               => 'ALL',
    'default_fields'                    => 'Default Fields',
    'select_all_channel_fields'         => 'Select ALL Channel Fields',
    'custom_fields'                     => 'Custom Fields',
    'relationships_export_msg'          => 'To Identify Relationships When Import',
    'other_modules_to_export'           => 'Other data to be export with channel data.',
    'seo_lite'                          => 'SEO Lite',
    'pages'                             => 'Pages',
    'channel_not_exists'                => 'This channel does not exists!',
    'no_channel_group_assigned'         => 'No channel Group is assigned to this channel',
    'save_export_settings'              => 'Save export settings',
    'general_settings'                  => 'General Settings',
    'export_name'                       => 'Export Name',
    'export_name_desc'                  => 'Name of export that will show as label in export list.',
    'access_export_withou_login'        => 'Access export URL without Login ?',
    'access_export_withou_login_desc'   => 'If you select Yes it will allow anyone to download the export who have the URL. It will not require login credentials. <br> In case of private export type it will not allow to download export without login credentials of respected account.',
    'export_type'                       => 'Export Type',
    'export_type_desc'                  => 'If value is set to public, export can be shown in another member accounts too.<br> If value is set to private, export will be shown only in the member\'s export list and it can be downloaded only using credentials of that member only.',
    'private'                           => 'Private',
    'public'                            => 'Public',
    'no'                                => 'No',
    'yes'                               => 'Yes',
    'export_updated_successfully'       => 'Export Updated Successfully.',
    'export_saved_successfully'         => 'Export Saved Successfully.',
    'no_entries_found'                  => 'There is no entries in given channel to export!',
    'export_download_login_error'       => 'You cannot download this export without logged in!',
    'private_export'                    => 'This export is Private!',
    'export_procedure'                  => 'Export Procedure',
    'export_procedure_desc'             => 'Select AJAX if you have a lot of entries to export. That will export in batches of certain entries at a time and memory limit issue or internal server error will not occur.',
    'normal'                            => 'Normal',
    'ajax'                              => 'Ajax',
    'batches'                           => 'Batches',
    'batches_desc'                      => 'Select how many entries you want to export at a time. (All entries will export in batches but will save in a single file. You can download that file once all batches are exported.)',

    /*Default Export Fields*/
    'entry_id'                          => 'Entry ID',
    'site_id'                           => 'Site ID',
    'channel_id'                        => 'Channel ID',
    'author_id'                         => 'Author ID',
    'forum_topic_id'                    => 'Forum topic ID',
    'ip_address'                        => 'Ip address',
    'title'                             => 'Title',
    'url_title'                         => 'Url title',
    'status'                            => 'Status',
    'versioning_enabled'                => 'Versioning enabled',
    'view_count_one'                    => 'View count one',
    'view_count_two'                    => 'View count two',
    'view_count_three'                  => 'View count three',
    'view_count_four'                   => 'View count four',
    'allow_comments'                    => 'Allow comments',
    'sticky'                            => 'Sticky',
    'entry_date'                        => 'Entry date',
    'year'                              => 'Year',
    'month'                             => 'Month',
    'day'                               => 'Day',
    'expiration_date'                   => 'Expiration date',
    'comment_expiration_date'           => 'Comment expiration date',
    'edit_date'                         => 'Edit date',
    'recent_comment_date'               => 'Recent comment date',
    'comment_total'                     => 'Comment total',
    'categories'                        => 'Categories',
    'status_id'                         => 'Status ID',
    
    /*Export language variables*/
    'export'                            => 'Export',
    'export_form_title'                 => 'Create new export',
    'save_export'                       => 'Save export',
    'save_and_continue'                 => 'Save and continue',
    'export_url'                        => 'URL',
    'download'                          => 'Download',
    'export_popup_title'                => 'The URL to run this export from outside of the Control Panel',
    'download_popup_title'              => 'We are preparing your export file. Please wait until we generate your export.',
    'token_not_set'                     => 'Token ID you are passing is not available.',

    'id'                                => 'ID',
    'member_id'                         => 'Member ID',
    'name'                              => 'Name',
    'created_date'                      => 'Created date',
    'last_modified'                     => 'Last modified',
    'export_counts'                     => 'Counts',
    'token'                             => 'Token',
    'download_without_login'            => 'Download without login',
    'type'                              => 'Type',
    'format'                            => 'Format',
    'settings'                          => 'Settings',
    'status'                            => 'Status',
    'do_not_refresh'                    => 'Please do not refresh or leave the page.',
    'statastics'                        => 'Statastics',
    'total_entries_to_be_export'        => 'Total entries to be export',
    'total_exported_rows'               => 'Total exported rows',
    'limit_per_page'                    => 'Limit (per page)',
    'url'                               => 'URL',
    'error'                             => 'Error',
    'download_exported_file'            => 'Download exported file.',

    'export_deleted_successfully'       => 'Export(s) deleted Successfully.',
    'settings_updated'                  => 'Settings updated.',

    'encode_content_label'              => 'Encode or Decode Exported content?',
    'encode_content_desc'               => 'You can set this parameter to encode or decode exported content',
    'encode_utf_8'                      => 'Encode into UTF-8 ( Encode will be perform for encode into UTF-8 )',
    'decode_utf_8'                      => 'Decode From UTF-8 ( Decode will be perform for decode from UTF-8 )',
    'no_encode_decode'                  => 'No Encode / Decode',
    'convert_all_dates_label'           => 'Convert all timestamp dates',
    'convert_all_dates_desc'            => 'Pass valid date format to convert timestamp to valid date formats. You can refer to <a href="http://php.net/manual/en/function.date.php" target="_blank">this URL</a> for all PHP date formats. (ex: Y-m-d H:i:s). Leave blank to keep exporting in timestamp format.',
    'covert_html_entities_label'        => 'Encode HTML tags',
    'covert_html_entities_desc'         => 'Enable this setting to convert HTML tags to HTML entities (ex: ' .htmlspecialchars("<p> => &lt;p&gt") . ')',
    'csv_settings'                      => 'CSV Settings',
    'separator_for_array_entities_label'=> 'Seperator for single dimension array',
    'separator_for_array_entities_desc' => 'For example If you have assets or channel images, All images will be seperated by defined seperator. ex: (abc.png | def.png)',
    'encode_for_array_label'            => 'Convert type to fit Multi dimension array',
    'encode_for_array_desc'             => 'This is convert type to fit Multi dimension array in single column.',
    'xml_settings'                      => 'XML Settings',
    'root_tag_name_label'               => 'Root Tag name',
    'root_tag_name_desc'                => 'Tag for main root entity to wrap all data in array.',
    'element_tags_name_label'           => 'Element Tag name',
    'element_tags_name_desc'            => 'Tag name for individual set of given data.',
    'general_settings_udpated_success'  => 'General Settings have been updated successfully',
    'force_export_file_label'           => 'Download file anyway if there are no entries found to export?',
    'force_export_file_desc'            => 'If enabled, It will download a blank file. If disabled, It will show standard EE error.',
    'export_general_settings'           => 'Export General Settings',
    'disable_ob_function_label'         => 'Disable <i>ob_start</i> and <i>ob_clean</i> functions for export',
    'disable_ob_function_desc'         => 'If you face the issue of the non encoded characters in the export then enable this setting to disable the use of <i>ob_start</i> and <i>ob_clean</i> functions at time of export.',

    /*Import language variable*/
    'save_and_import'                   => 'Save & Import',
    'import_list'                       => 'Import List',
    'create_new_import'                 => 'Create New Import',
    'edit_import'                       => 'Edit Import',
    'import_general_settings'           => 'Import General Settings',

    'import_counts'                     => 'Import Counts',

    'import_file_type'                  => 'Import File Type',
    'import_file_type_desc'             => 'Select file type.',
    'import_channel'                    => 'Import Channel',
    'import_channel_desc'               => 'Select channel where you want to import the data.',
    'import_csv_file_name'              => 'File Name or URL of File',
    'import_json_file_name'             => 'File Name or URL of File',
    'import_file_desc'                  => 'Provide <mark>Full path location('.ee()->config->item('base_path').')</mark> of file or <mark>URL('.ee()->config->item('base_url').')</mark>',
    'import_csv_delimiter'              => 'Delimiter',
    'import_csv_delimiter_desc'         => 'Special character that seprates fields in the file.',
    'import_csv_encloser'               => 'Encloser',
    'import_csv_encloser_desc'          => 'Special character that enclose each field.',
    'import_csv_first_row'              => 'Fields of first row as titles',
    'import_csv_first_row_desc'         => 'Enable this field if first row contains the title',
    'import_xml_file_name'              => 'File Name or URL of File',                               
    'import_xml_file_desc'              => 'Provide <mark>Full path location</mark> of file or <mark>URL</mark>',                               
    'import_xml_path'                   => 'Element path',
    'import_xml_path_desc'              => 'The path within the XML to the element you want to import <mark>(eg, /root/element)</mark>',
    'import_json_path'                  => 'Element path',
    'import_json_path_desc'              => 'The path within the JSON to the element you want to import <mark>(eg, /root/element)</mark>',
    'file_source_json'                  => 'Source of the Json file',
    'path_json'                         => 'Define the element path of your content in the Json file',
    'setting_disply'                    => 'Setting Display',
    'file_source_csv'                   => 'Source File',
    'file_delimiter_csv'                => 'Delimiter',
    'file_encloser_csv'                 => 'Encloser',
    'import_csv_encloser_not'           => 'Your CSV file have no enclosure , right ?',
    'import_csv_encloser_not_desc'      => 'Some CSV file have no enclosure in this case you can choose this oprion to yes.',
    'file_first_row_csv'                => 'Fields of first row as titles',
    'title_label'                       => 'Title',
    'title_desc'                        => 'Title of entry.',
    'url_label'                         => 'URL Title',
    'url_desc'                          => 'URL of entry.',
    'date_label'                        => 'Date',
    'date_desc'                         => 'Creation date of the entry.',
    'entry_date_label'                  => 'Date',
    'entry_date_desc'                   => 'Creation date of the entry.',
    'expiry_date_label'                 => 'Expiration Date',
    'expiry_date_desc'                  => 'Expiration date of the entry.',
    'group_fields'                      => 'Group Fields',
    'individual_fields'                 => 'Individual Fields',
    'filed_to_check_label'              => 'Use this field to check for the duplicate entries',
    'filed_to_check_desc'               => 'Value of this field to check already entires exists in the channel.',
    'update_existing_label'             => 'Update existing entries',
    'update_existing_desc'              => 'If dupicate entries find then update it with data of import file.',
    'delete_old_label'                  => 'Delete old entries',
    'delete_old_desc'                   => 'Delete those entries which are not updated with timestamp.',
    'import_settings'                   => 'Import Settings',
    'name_label'                        => 'Name',
    'name_desc'                         => 'Name of this import setting',
    'access_import_without_login_label' => 'Access Import URL without login?',
    'access_import_without_login_desc'  => 'Allowing YES will allow anyone to run the import with URL without LOGIN',
    'import_type_label'                 => 'Import Type',
    'import_type_desc'                  => 'Set the value to Public will show the import in another members import list to.<br> Private import will show in only your import list and import by you only.',
    'import_comment_label'              => 'Comment',
    'import_comment_desc'               => 'Comment about this import setting.',
    'import_procedure_label'            => 'Import Procedure',
    'import_procedure_desc'             => 'Select AJAX if you have a lot of entries to import. That will import in batchs of certain entries at a time and you will not get memory limit issue or internal server error.',
    'batches_label'                     => 'Batches',
    'import_batches_desc'               => 'Select how many entries you want to import at a time. (All entries will import in batches. You import process will complete once all batches imported.)',

    /* Entry import */
    'next_batch_loading'                    => "Next batch is Loading.. <span>Please do not refresh or leave the page.</span>",
    'statastics'                            => 'Statistics',
    'total_row_to_perform_action'           => 'Total rows to perform Action',
    'total_inserted_entries'                => 'Total inserted entries',
    'total_updated_entries'                 => 'Total updated entries',
    'total_re_created_entries'              => 'Total re-created entries',
    'total_skipped_entries'                 => 'Total skipped entries',
    'memory_usage_for_this_batch'           => 'Memory usage for this batch',
    'total_memory_usage'                    => 'Total memory usage',
    'time_taken_for_this_batch_to_import'   => 'Time taken for this batch to import',
    'total_time_taken_to_import'            => 'Total Time taken to import',
    'entries_added'                         => 'Entries added',
    'entries_updated'                       => 'Entries updated',
    'entries_re_created'                    => 'Entries re-created',
    'total'                                 => 'Total',
    'minutes'                               => 'Minutes',
    'seconds'                               => 'Seconds',


    'file_source_xml'                       => 'XML file source',
    'path_xml'                              => 'XML element path',
    'file_not_available'                    => 'File not available at given location',
    'run_import_process'                    => 'Import processing',
    'run_import_success'                    => 'Import processing',

    'url_title_label'                       => 'URL Title',
    'url_title_desc'                        => 'URL title of channel entry',

    'import_file_type'                      => 'Import file type',
    'import_channel'                        => 'Channel Id',
    'file_source_csv'                       => 'CSV file source',
    'file_delimiter_csv'                    => 'CSV file Delimiter',
    'file_encloser_csv'                     => 'CSV file Encloser',
    'file_first_row_csv'                    => 'First row as a Label',
    'message_success'                       => 'Success',
    'import_deleted'                        => 'Import deleted',
    'imports_deleted'                       => 'Imports deleted',
    'import_deleted_success'                => 'Import deleted Successfully',
    'imports_deleted_success'               => 'Imports deleted Successfully',
    'duplicate_action_label'                => 'What action you want to perform when found duplicate',
    'duplicate_action_desc'                 => 'Action to perform if same entry found with above field.',   

    'no_import_configuration_data_found'    => 'No import configuration data found.',
    'no_import_setting_data_found'          => 'No import setting data found.',
    'file_does_not_exist_or_readable'       => 'File does not exist or not readable.',
    'select_alteast_title_urltitle_default_field'        => 'Select atleast title and url_title in default fields.',
    'select_both_title_urltitle_default_field'           => 'Select title and url_title both in default fields.'  ,

    'license'                                   => 'License',
    'cp_message_warn'                           => 'Warning!',
    'author_label'                              => 'Author (Select from the file)',
    'author_desc'                               => 'Author (author_id) of the entry. If the option is not selected then there will be no change to author for the existing entry.',
    'system_authors_label'                      => 'Author (Select from the system)',
    'system_authors_desc'                       => 'Author (author_id) of the entry. Author from the system. If the option is not selected then there will be no change to author for the existing entry.',
    'status_label'                              => 'Status',
    'status_desc'                               => 'Status of the entry. If the option is not selected then there will be no change to status for the existing entry.',

    //delete existing entries
    'delete_existing_entries'   => 'Delete existing entries',
    'delete_existing_entries_desc' => 'Delete entries from the selected channel that are not processed(updated or created) by this import.',
    'total_deleted_existing_entries'  => 'Total deleted existing entries',

    //cron feature
    'import_id'             => 'Import id',
    'import_cron_url'       => 'Import URL for cron',
    'import_popup_title'    => 'The URL to run this import from outside of the Control Panel',

    'only_valid_file_allowed' => 'Only valid file allowed',

    'third_party_xml'   => 'Third Praty XML',

    /* Subscription module */
    'subscription_key_required'             => 'Subscription key field is required.',
    'subscription_key_required_desc'        => 'Please enter subscription key.',
    'invalid_subscription_key'              => 'Invalid Subscription key.',
    'invalid_subscription_key_desc'         => 'Please enter a valid subscription key.',
    'valid_subscription_key'                => 'Subscription key is valid.',
    'valid_expired_subscription_key'        => 'Subscription key is valid but it is expired.',
    'subscription_key_added_successfully'   => 'Subscription key added successfully.',
    'subscription_key_fail'                 => 'Something went wrong.',
    'subscription_key_fail_desc'            => 'Please try again.',
    'subscription_key_expired'              => 'Addon subscription is expired.',
    'subscription_key_expired_desc'         => 'Please renew your subscription to get upcoming add-on updates.',
);