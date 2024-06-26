<?php

$lang = [
	// -------------------------------------------
	//  classes/Hop_minifizer_config.php
	// -------------------------------------------
	'config_prop_not_valid' => '`%s` is not a valid setting.',
	'config_settings_manual_override' => 'Settings have been manually passed.',
	'config_settings_using_defaults' => 'Could not find any settings to use. Trying defaults.',
	'config_extension_manually_inject' => 'Manually injected into extension hooks.',
	'config_settings_saved' => 'Settings have been saved in session cache. Settings came from: %s',
	'config_sanitise_non_array' => 'Trying to sanitise a non-array of settings.',
	'config_settings_from_config' => 'Settings taken from EE config.',
	'config_settings_config_array_empty' => 'Settings taken from EE config must be a non-empty array.',
	'config_settings_config_not_found' => 'No settings found in EE config.',
	'config_settings_legacy_warning' => 'Your Hop Minifizer config is using the "legacy" setup from 1.x, please see docs for more.',
	'config_settings_from_legacy' => 'Settings taken from EE config "legacy".',
	'config_settings_from_db' => 'Settings retrieved from database.',
	'config_settings_db_not_found' => 'No settings found in database.',
	'config_settings_legacy_global_var_warning' => 'Hop Minifizer is using the "legacy" setup from 1.x, setting via the global vars, which has been deprecated. Please see docs for more.',
	'config_settings_from_legacy' => 'Settings taken from EE global vars "legacy".',
	'config_settings_legacy_not_found' => 'No settings found in EE global vars as "legacy" format.',

	// -------------------------------------------
	//  Extensions CP
	// -------------------------------------------
	'hop_minifizer_settings' => 'Hop Minifizer Settings',
	'preferences' => 'Preferences',
	'advanced_config' => 'Advanced Preferences',
	'basic_config' => 'Basic Preferences',
	'optional' => 'optional',
	'config_location_warning' => '<strong class="notice">Hop Minifizer appears to be configured elsewhere.</strong> There is likely no need to have this extension installed. Consult the <a href="https://www.hopstudios.com/software/Hop_minifizer/" title="Hop Minifizer Docs">docs</a> for more.',
	'config_location_warning_title' => 'Notice',

	'save' => 'Save Settings',
	'auto' => 'Auto',
	'curl' => 'cURL',
	'fgc' => 'file_get_contents()',

	'sha1' => 'SHA-1',
	'md5' => 'MD5',
	'sanitize' => 'Sanitize',

	'base_path' => 'Base Path',
	'base_path_note' => 'The location on your webserver where your <i>source</i> CSS and JS files sit.<br />Optional, defaults to FCPATH constant (the root path to your site).',
	'base_path_hint' => 'e.g. ' . rtrim(FCPATH, '/'),

	'base_url' => 'Base URL',
	'base_url_note' => 'The base URL from which your <i>source</i> CSS and JS files are served.<br />Optional, defaults to Site URL.',
	'base_url_hint' => 'e.g. ' . rtrim(get_instance()->config->item('base_url'), '/'),

	'cache_path' => 'Cache Path',
	'cache_path_note' => 'Assumed to be absolute, but will also test as relative to the Base Path.<br />If left blank, will guess `cache`.',
	'cache_path_hint' => 'e.g. ' . rtrim(FCPATH, '/') . '/cache',

	'cache_url' => 'Cache URL',
	'cache_url_note' => 'Assumed to be a fully qualified URL, but will also test as relative to the Base URL.<br />If left blank, will guess `cache`.',
	'cache_url_hint' => 'e.g. ' . rtrim(get_instance()->config->item('base_url'), '/') . '/cache',

	'cachebust' => 'Cache-Busting',
	'cachebust_note' => 'Update this to a unique string to force Hop Minifizer to create a new cache file.<br />Optional, and for most scenarios unneccessary. Consult the <a href="https://www.hopstudios.com/software/Hop_minifizer/" title="Hop Minifizer Docs">docs</a> for more.',
	'cachebust_hint' => 'e.g. `1.0.0`.',
	
	'cleanup' => 'Cleanup Expired Caches',
	'cleanup_note' => '<strong>Use with caution.</strong> When enabled, Hop Minifizer will automatically delete any cache file it determines has expired. Consult the <a href="https://www.hopstudios.com/software/Hop_minifizer/" title="Hop Minifizer Docs">docs</a> for more.',

	'combine' => 'Combine Assets',
	'combine_note' => 'Specify which types of assets to combine.',
	
	'css_prepend_mode' => 'CSS Prepend Mode',
	'css_prepend_mode_note' => 'By default when minifying CSS, Hop Minifizer will rewrite <i>relative</i> image & @import URLs into absolute URLs. Turn OFF to skip this step.',

	'css_prepend_url' => 'CSS Prepend URL',
	'css_prepend_url_note' => 'The URL to use when `CSS Prepend Mode` is ON.<br />Optional, by default uses the Base URL.',
	'css_prepend_url_hint' => 'e.g. ' . rtrim(get_instance()->config->item('base_url'), '/'),

	'debug' => 'Turn on debug mode',

	'disable' => 'Disable Hop Minifizer entirely?',

	'minify' => 'Minify Assets',
	'minify_note' => 'Specify which types of assets to run through minification engine.<br />Note: HTML minification only available for EE2.4+',

	'hash_method' => 'Filename Hash Algorithm',
	'hash_method_note' => 'Choose which algorithm to create the cache filename.<br />`Sanitize` is only recommended during development; filenames will not exceed 200 characters in length.',

	'remote_mode' => 'Remote file mode?',
	'remote_mode_note' => 'Specify how to fetch remote and {stylesheet=} URLs. \'Auto\' mode will try cURL first.',

	'minify_html_hook' => 'Hook for HTML Minification',
	'minify_html_hook_note' => 'If you have CE Cache installed, you may run HTML minification during its ce_cache_pre_save hook. Consult the <a href="https://www.hopstudios.com/software/Hop_minifizer/" title="Hop Minifizer Docs">docs</a> for more.',
	'template_post_parse' => 'EE - template_post_parse',
	'ce_cache_pre_save' => 'CE Cache - ce_cache_pre_save',
	
	'css_library' => 'CSS Library',
	'css_library_note' => 'Specify which library to use for CSS minification. Defaults to Minify.',
	'minify' => 'Minify',
	'cssmin' => 'CSSMin',
	
	'js_library' => 'JS Library',
	'js_library_note' => 'Specify which library to use for JS minification. Defaults to JSMin.',
	'jsmin' => 'JSMin',
	'jsminplus' => 'JSMinPlus',	

	'save_gz' => 'Generate gzip?',
	'save_gz_note' => 'Requires the Zlib compression library, and modifications to your htaccess file. Consult the <a href="http://johndwells.github.io/Hop_minifizer/" title="Hop Minifizer Docs">docs</a> for more.',

	'amazon_s3_settings' => 'Amazon S3 Settings',
	'access_key_id' => 'Access Key Id',
	'access_key_id_note' => 'Access key id',
	'secret_access_key' => 'Secret Access Key',
	'secret_access_key_note' => 'Secret access key',
	'bucket' => 'Bucket',
	'api_region' => 'Region',
	'api_region_note' => 'The API region to which request are going to be sent. (Buckets are attached to a region by default. This setting is meant for API calls purposes only. More info about regions click <a href="https://docs.aws.amazon.com/general/latest/gr/rande.html" target="_blank">here</a>)',
	'bucket_note' => 'The bucket name to use',
	'location' => 'Location',
	'amazon_s3_location_note' => 'The region for the bucket',
	'url_prefix' => 'URL Prefix',
	'url_prefix_note' => 'URL prefix to use',
	'folder' => 'Folder',
	'amazon_s3_folder_note' => 'folder to use for your source',

	'test' => 'Test',
	'folder_not_exists' => 'The specified S3 folder does not exist',
	'bucket_not_exists' => 'The specified S3 bucket does not exist',
	'license' => 'License',
	'' => ''
];