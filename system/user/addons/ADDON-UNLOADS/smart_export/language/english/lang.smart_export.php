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
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$lang = array(

    /* Required for ADD-ONS > MODULES page */
    'smart_export_module_name'          => 'Smart Export PRO',
    'smart_export_module_description'   => 'Export entries in XML or CSV file format with given filters.',
    
    /* Required for CONTROL PANEL pages */
    'mod_name'                          => 'smart_export',
    'export_manager'                    => 'Export Manager',
    'mod_desc'                          => 'Export entries in XML or CSV file format with given filters.',
    'lable_title_index'                 => 'Smart Export PRO',
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
    'smart_export'                      => 'Smart Export PRO',
    'nav_smart_export'                  => 'Smart Export PRO',
    'export_list'                       => 'Export List',
    'save_export'                       => 'Save Export',
    'export_form_title'                 => 'Edit Export Settings',
    'create_new_export'                 => 'Create new export',

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
    'access_export_withou_login_desc'   => 'Allowing YES will allow anyone to download the exort with URL without LOGIN <br> In case of "Private" type it will not allow to download without login to your own account.',
    'export_type'                       => 'Export Type',
    'export_type_desc'                  => 'Set the value to Public will show the export in another members export list to.<br> Private export will show in only your export list and download by you only.',
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
    'export_procedure_desc'             => 'Select AJAX if you have a lot of entries to export. That will export in batchs of certain entries at a time and you will not get memory limit issue or internal server error.',
    'normal'                            => 'Normal',
    'ajax'                              => 'Ajax',
    'batches'                           => 'Batches',
    'batches_desc'                      => 'Select how many entries you want to export at a time. (All entries will export in batches but will save in signle file. You can download that file once all batches exported.)',

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
    'encode_content_desc'               => 'You can set this parameter to encode or decode exported content. Encode / Decode will be perform for encode into UTF-8 or decode from UTF-8',
    'encode_utf_8'                      => 'Encode into UTF-8',
    'decode_utf_8'                      => 'Decode From UTF-8',
    'no_encode_decode'                  => 'No Encode / Decode',
    'convert_all_dates_label'           => 'Convert all timestamp dates',
    'convert_all_dates_desc'            => 'Pass valid date format to convert timestamp to valid date formats. You can refer <a href="http://php.net/manual/en/function.date.php" target="_blank">this URL</a> for all PHP date formats. (ex: Y-m-d H:i:s). Leave blank to keep exporting in timestamp format.',
    'covert_html_entities_label'        => 'Encode HTML tags',
    'covert_html_entities_desc'         => 'Enable this settings will convert HTML tags to HTML entities (ex: ' .htmlspecialchars("<p> => &lt;p&gt") . ')',
    'csv_settings'                      => 'CSV Settings',
    'separator_for_array_entities_label'=> 'Separator for single dimension array',
    'separator_for_array_entities_desc' => 'If you have assets or channel images for example. All images will be separated by your separator. ex: (abc.png | def.png)',
    'encode_for_array_label'            => 'Convert type for Multi dimension array',
    'encode_for_array_desc'             => 'Convert type for Multi dimension array to fit in single column.',
    'xml_settings'                      => 'XML Settings',
    'root_tag_name_label'               => 'Root Tag name',
    'root_tag_name_desc'                => 'Tag for main root entity to wrap all data array in.',
    'element_tags_name_label'           => 'Element Tag name',
    'element_tags_name_desc'            => 'Tag name for individual set of given data.',
    'general_settings_udpated_success'  => 'General Settings have been updated successfully',
    'force_export_file_label'           => 'Download file anyway if there is no entries found to export?',
    'force_export_file_desc'            => 'If Enabled, It will download even a blank file. If Disabled, It will show standard EE error.',
    ''                                  => ''
);