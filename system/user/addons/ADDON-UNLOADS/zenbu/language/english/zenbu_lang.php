<?php

$lang = array(

	//----------------------------------------
	// Required for MODULES page
	//----------------------------------------

	"zenbu_module_name" =>
		"Zenbu",

	"zenbu_module_description" =>
		"Entry Manager",

	//----------------------------------------

	'entry_manager' => 'Entry Manager',

	'basic_fields'          => 'Basic fields',
	'custom_fields'         => 'Custom fields',
	'title'                 => 'Title',
	'entry_id'              => 'Entry ID',
	'url_title'             => 'URL Title',
	'status'                => 'Status',
	'author'                => 'Author',
	'status_id'             => 'Status',
	'author_id'             => 'Author',
	'screen_name'           => 'Screen Name',
	'channel_id'            => 'Channel',
	'category'              => 'Category',
	'categories'            => 'Categories',
	'sticky'                => 'Sticky',
	'preview'               => 'Preview',
	'preview_not_enabled'   => 'Preview not enabled for this channel',
	//----------------------------------------
	'dates'                 => 'Dates',
	'entry_date'            => 'Entry Date',
	'edit_date'             => 'Edit Date',
	'expiration_date'       => 'Expiration Date',
	//----------------------------------------
	'is'                    => 'is',
	'isNot'                 => 'is not',
	'contains'              => 'contains',
	'doesntContain'         => 'does not contain',
	'beginsWith'            => 'begins with',
	'doesntBeginWith'       => 'does not begin with',
	'endsWith'              => 'ends with',
	'doesntEndWith'         => 'does not end with',
	'containsExactly'       => 'contains exactly',
	'isEmpty'               => 'is empty',
	'isNotEmpty'            => 'is not empty',
	'isOn'                  => 'is on',
	'isOff'                 => 'is off',
	//----------------------------------------
	'inTheLast1'            => 'in the last day',
	'inTheLast3'            => 'in the last 3 days',
	'inTheLast7'            => 'in the last week',
	'inTheLast30'           => 'in the last month',
	'inTheLast180'          => 'in the last 6 months',
	'inTheLast365'          => 'in the last year',
	'inTheNext1'            => 'in the next day',
	'inTheNext3'            => 'in the next 3 days',
	'inTheNext7'            => 'in the next week',
	'inTheNext30'           => 'in the next month',
	'inTheNext180'          => 'in the next 6 months',
	'inTheNext365'          => 'in the next year',
	'betweenDates'          => 'between dates',
	//----------------------------------------
	'showing_x_of_x'        => 'Showing %x-%y of %z',
	'showing_all_x_results' => 'Showing all %x results',

	'saving'                        => 'Saving...',
	'saved'                         => 'Saved!',
	'error_see_console'             => 'Error, see console',
	'ran_into_an_error'             => 'Zenbu ran into an error',
	'open_the_hood'                 => 'Open the hood (view error details)',
	'close_the_hood'                => 'Close the hood',
	'let_the_developer_know'        => 'Please let the developer know of the following error details, and how/when these errors were triggered.',
	'delete_saved_searches_confirm' => 'Are you sure you want to delete the selected saved searches?',

	/**
	 * Display settings
	 */

	'open_display_settings_to_add_columns' => 'Open <a href="#" class="m-link" rel="modal-displaySettings" @click="prepareDisplaySettingsModelContent">Display Settings</a> to set up columns for this result listing',

	// Text/input etc fields
	'display_style'                        => 'Display Style',
	'plain_text'                           => 'Plain text',
	'html'                                 => 'HTML-formatted text',
	'limit_text'                           => 'Limit to text to... (number of characters)',

	// Date-like fields
	'date_format'                          => 'Date Format',

	// Toggle field
	'on_off'                               => 'ON/OFF',
	'yes_no'                               => 'Yes/No',
	'use_colored_labels'                   => 'Use Color Labels',

	// Relationship
	'show_entry_id'                        => 'Show Entry ID',

	// Author
	'link_to_profile'                      => 'Link to profile',

	// File
	'no_file'                              => 'No File',
	'base_thumb'                           => 'Base Thumbnail',
	'use_dimension'                        => 'Image dimension',
	'show_title'                           => 'Show Title',
	'show_description'                     => 'Show Description',
	'show_credit'                          => 'Show Credit',
	'show_location'                        => 'Show Location',
	'show_file_size'                       => 'Show File Size',

	/**
	 * Filter Settings
	 */
	'filter_settings'                      => 'Filter Settings',
	'filter_settings_info'                 => 'The following settings are saved per channel',
	'starting_limit'                       => 'Starting Limit (display per page)',
	'starting_limit_info'                  => 'If not returning to a previous search, this is the starting display per page setting.',
	'starting_order_by'                    => 'Starting Order By',
	'starting_order_by_info'               => 'If not returning to a previous search, this is the starting "order by" setting. Note: Support for ordering may vary depending on field type (especially third-party fieldtypes).',
	'starting_sort'                        => 'Starting Sort',
	'starting_sort_info'                   => 'If not returning to a previous search, this is the starting "sort" setting.',
	'asc'                                  => 'ascending',
	'desc'                                 => 'descending',

	// Copying to Member groups

	'copy_to_member_groups'         => 'Copy Display Settings to Member Groups',
	'copy_to_member_groups_warning' => 'If a user has their own display settings, those will take precedence over member group display settings.',
	'copy'                          => 'Copy',
	'confirm_copy'                  => 'Copy to Member Group',
	'cancel_copy'                   => 'Cancel copying',
	'copied'                        => 'Copied',

	/**
	 * Permissions
	 */

	'member_group_name'                => 'Member Group Name',
	'can_admin'                        => 'Can access Permissions',
	'can_admin_subtext'                => 'Allows access to the PERMISSIONS section (this page)',
	'can_copy_profile'                 => 'Can copy display and filter settings to other member groups',
	'can_copy_profile_subtext'         => 'Allows copying of display and filter settings to other member groups.',
	'can_access_settings'              => 'Can access Display Settings',
	'can_access_settings_subtext'      => 'Allows access to the DISPLAY SETTINGS section',
	'edit_replace'                     => 'Modify Edit links in top navigation & redirect to Zenbu',
	'replace_links_for_zenbu'          => 'Modify native EE links to Zenbu',
	'edit_replace_subtext'             => 'Replaces CP links pointing to the native EE entry list to the Zenbu entry list, as well as controls redirect back to Zenbu on Save & Close and Bulk Delete. The change takes effect on next page load. **NOTE**: The URLs are not modified when the current page is the native EE entry list section.',
	'can_view_group_searches'          => 'Can view own member group searches',
	'can_view_group_searches_subtext'  => 'In addition to individual saved searches, saved searches for the currently logged in member\'s group will be displayed. **However, users can still only manage their own saved searches.**',
	'can_admin_group_searches'         => 'Can manage all group searches',
	'can_admin_group_searches_subtext' => 'Allows editing and copying of a search to ANY other member group',


	'show_grid' => 'Show grid',
	'open_entry_to_view_grid' => 'Open entry to view Grid data',

	// END
	''          => '',
);
?>