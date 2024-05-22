<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// our helper will require_once() everything else we need
require_once PATH_THIRD . 'hop_minifizer/classes/Hop_minifizer_helper.php';
require_once PATH_THIRD . 'hop_minifizer/vendor/autoload.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\AWSException;
use Aws\S3\S3Client;

class Hop_minifizer_mcp {

	public $module_name		= HOP_MINIFIZER_NAME;
	public $short_name		= HOP_MINIFIZER_SHORT_NAME;
	public $version			= HOP_MINIFIZER_VERSION;

	/**
	 * Our magical config class
	 */
	public $config;

	/**
	 * Reference to our cache
	 */
	public $cache;

	/**
	 * Constructor
	 *
	 * NOTE: We never use the $settings variable passed to us,
	 * because we want our Hop_minifizer_config object to always be in charge.
	 *
	 * @param 	mixed	Settings array - only passed when activating a hook
	 * @return void
	 */
	public function __construct($settings = [])
	{
		// grab a reference to our cache
		$this->cache =& Hop_minifizer_helper::cache();

		// grab instance of our config object
        $this->config = Hop_minifizer_helper::config();
        
	}

	/*
	 * Build the navigation menu for the module
	 */
	private function _buildNav()
	{
		$sidebar = ee('CP/Sidebar')->make();

		$sd_div = $sidebar->addHeader(lang('hop_minifizer_settings'), ee('CP/URL', 'addons/settings/hop_minifizer'));
		$sd_div = $sidebar->addHeader(lang('license') . ($this->_checkLicenseValid() ? '<span class="st-open" style="float: right;">Valid</span>' : '<span class="st-closed" style="float: right;">Unlicensed</span>'), ee('CP/URL', 'addons/settings/' . $this->short_name . '/license'));
	}


	public function index()
	{
		$this->_buildNav();

        $current = $this->config;

        if (!empty($_POST)) {
            $this->_save_settings();
        }

		if (($this->config->location != 'db')) {
			ee('CP/Alert')->makeInline('config-warning')
				->asWarning()
				->cannotClose()
				->withTitle(lang('config_location_warning_title'))
				->addToBody(lang('config_location_warning'))
				->now();
		}

		// view vars
		$vars = [
			'cp_page_title' => lang('preferences'),
			'base_url' => ee('CP/URL')->make('addons/settings/hop_minifizer'),
			'save_btn_text' => 'btn_save_settings',
			'save_btn_text_working' => 'btn_saving',
			'sections' => [
				'basic_config' => [
					[
						'title' => 'disable',
						'fields' => [
							'disable' => [
								'type' => 'yes_no',
								'value' => $current->disable
							]
						]
					],
					[
						'title' => 'debug',
						'fields' => [
							'debug' => [
								'type' => 'yes_no',
								'value' => $current->debug
							]
						]
					],
					[
						'title' => 'cache_path',
						'desc' => 'cache_path_note',
						'fields' => [
							'cache_path' => [
								'type' => 'text',
								'value' => $current->cache_path
							]
						]
					],
					[
						'title' => 'cache_url',
						'desc' => 'cache_url_note',
						'fields' => [
							'cache_url' => [
								'type' => 'text',
								'value' => $current->cache_url
							]
						]
					],
					[
						'title' => 'combine',
						'desc' => 'combine_note',
						'fields' => [
							'combine_css' => [
								'type' => 'checkbox',
								'choices' => [
									'yes' => 'CSS'
								],
								'value' => $current->combine_css
							],
							'combine_js' => [
								'type' => 'checkbox',
								'choices' => [
									'yes' => 'JS'
								],
								'value' => $current->combine_js
							]
						]
					],
					[
						'title' => 'minify',
						'desc' => 'minify_note',
						'fields' => [
							'minify_css' => [
								'type' => 'checkbox',
								'choices' => [
									'yes' => 'CSS'
								],
								'value' => $current->minify_css
							],
							'minify_js' => [
								'type' => 'checkbox',
								'choices' => [
									'yes' => 'JS'
								],
								'value' => $current->minify_js
							],
							'minify_html' => [
								'type' => 'checkbox',
								'choices' => [
									'yes' => 'HTML'
								],
								'value' => $current->minify_html
							]
						]
					]
				],
				'advanced_config' => [
					[
						'title' => 'base_path',
						'desc' => 'base_path_note',
						'fields' => [
							'base_path' => [
								'type' => 'text',
								'value' => $current->base_path
							]
						]
					],
					[
						'title' => 'base_url',
						'desc' => 'base_url_note',
						'fields' => [
							'base_url' => [
								'type' => 'text',
								'value' => $current->base_url
							]
						]
					],
					[
						'title' => 'cachebust',
						'desc' => 'cachebust_note',
						'fields' => [
							'cachebust' => [
								'type' => 'text',
								'value' => $current->cachebust
							]
						]
					],
					[
						'title' => 'cleanup',
						'desc' => 'cleanup_note',
						'fields' => [
							'cleanup' => [
								'type' => 'yes_no',
								'value' => $current->cleanup
							]
						]
					],
					[
						'title' => 'hash_method',
						'desc' => 'hash_method_note',
						'fields' => [
							'hash_method' => [
								'type' => 'select',
								'choices' => [
									'sha1' => lang('sha1'),
									'md5' => lang('md5'),
									'sanitize' => lang('sanitize')
								],
								'value' => $current->hash_method
							]
						]
					],
					[
						'title' => 'css_prepend_mode',
						'desc' => 'css_prepend_mode_note',
						'fields' => [
							'css_prepend_mode' => [
								'type' => 'inline_radio',
								'choices' => [
									'no' => lang('Off'),
									'yes' => lang('On')
								],
								'value' => $current->css_prepend_mode
							]
						]
					],
					[
						'title' => 'css_prepend_url',
						'desc' => 'css_prepend_url_note',
						'fields' => [
							'css_prepend_url' => [
								'type' => 'text',
								'value' => $current->css_prepend_url
							]
						]
					],
					[
						'title' => 'css_library',
						'desc' => 'css_library_note',
						'fields' => [
							'css_library' => [
								'type' => 'select',
								'choices' => [
									'minify' => lang('minify'),
									'cssmin' => lang('cssmin')
								],
								'value' => $current->css_library
							]
						]
					],
					[
						'title' => 'js_library',
						'desc' => 'js_library_note',
						'fields' => [
							'js_library' => [
								'type' => 'select',
								'choices' => [
									'jsmin' => lang('jsmin'),
									'jsminplus' => lang('jsminplus')
								],
								'value' => $current->js_library
							]
						]
					],
					[
						'title' => 'remote_mode',
						'desc' => 'remote_mode_note',
						'fields' => [
							'remote_mode' => [
								'type' => 'select',
								'choices' => [
									'auto' => lang('auto'),
									'curl' => lang('curl'),
									'fgc' => lang('fgc')
								],
								'value' => $current->remote_mode
							]
						]
					],
					[
						'title' => 'save_gz',
						'desc' => 'save_gz_note',
						'fields' => [
							'save_gz' => [
								'type' => 'yes_no',
								'value' => $current->save_gz
							]
						]
					],
					[
						'title' => 'minify_html_hook',
						'desc' => 'minify_html_hook_note',
						'fields' => [
							'minify_html_hook' => [
								'type' => 'select',
								'choices' => [
									'template_post_parse' => lang('template_post_parse'),
									'ce_cache_pre_save' => lang('ce_cache_pre_save')
								],
								'value' => $current->minify_html_hook
							]
						]
					]
				],
				'amazon_s3_settings' => [
					[
						'title' => 'access_key_id',
						'desc' => 'access_key_id_note',
						'fields' => [
							'amazon_s3_access_key_id' => [
								'type' => 'text',
								'value' => $current->amazon_s3_access_key_id
							]
						]
					],
					[
						'title' => 'secret_access_key',
						'desc' => 'secret_access_key_note',
						'fields' => [
							'amazon_s3_secret_access_key' => [
								'type' => 'text',
								'value' => $current->amazon_s3_secret_access_key
							]
						]
					],
					[
						'title' => 'bucket',
						'desc' => 'bucket_note',
						'fields' => [
							'amazon_s3_bucket' => [
								'type' => 'text',
								'value' => $current->amazon_s3_bucket
							]
						]
					],
					[
						'title' => 'api_region',
						'desc' => 'api_region_note',
						'fields' => [
							'amazon_s3_api_region' => [
								'type' => 'text',
								'value' => $current->amazon_s3_api_region
							]
						]
					],
					[
						'title' => 'folder',
						'desc' => 'amazon_s3_folder_note',
						'fields' => [
							'amazon_s3_folder' => [
								'type' => 'text',
								'value' => $current->amazon_s3_folder
							],
							'callback_url' => [
								'type'          => 'html',
								'content'       => '<div class="verify-settings-btn"><a href="' . $this->_get_action_url('test_amazon_access_keys') . '" class="btn action" target="_blank">Test Amazon S3 settings</a></div>',
								'margin_top'    => 0, 
							]
						]
					],
				]
			],
        ];

		// return our view
		return ee('View')->make('hop_minifizer:settings_form')->render($vars);
	}

	protected function _save_settings()
	{
		// grab our posted form
        $settings = $_POST;
        
		// checkboxes now come in as an array,
		// but we want to cast them to a string, so as to be compatible with our config service
		$checkboxes = [
			'combine_css',
			'combine_js',
			'minify_css',
			'minify_html',
			'minify_js'
		];

		foreach ($checkboxes as $key) {
			if (!empty($settings[$key])) {
				$settings[$key] = $settings[$key][0];
			} else {
				$settings[$key] = 'no';
			}
        }

		// run our $settings through sanitise_settings()
		$settings = $this->config->sanitise_settings(array_merge($this->config->get_allowed(), $settings));

		// update db
		foreach ($settings as $setting_name => $value) {
			$setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', $setting_name)->first();
			if ( ! empty($setting)) {
				$setting->value = $value;
			} else {
				$setting = ee('Model')->make($this->short_name . ':Config', ['setting_name' => $setting_name, 'value' => $value]);
			}
			$setting->save();
		}

		Hop_minifizer_helper::log('Extension settings have been saved.', 3);

		// save the environment
		unset($settings);

		// make an alert but defer until next request
		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('preferences_updated'))
			->addToBody(lang('preferences_updated_desc'))
            ->defer();

		// return to the settings form
		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/hop_minifizer'));
	}    

	/**
	 * Gets the hook method action id
	 *
	 * @param	String Hook method name
	 * @return	boolean	Returns FALSE or hook action id
	 */
	protected function _get_action_url($method) {
		$action_id = ee()->cp->fetch_action_id('Hop_minifizer', $method);

		if ( ! empty($action_id)) {
			return ee()->functions->fetch_site_index() . QUERY_MARKER . 'ACT=' . $action_id;
		}

		return false;
	}

	public function license() {
		$this->_buildNav();

		$vars = [];
		$vars['action_url'] = ee('CP/URL', 'addons/settings/' . $this->short_name . '/save_license');

		$license_setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license')->first();
		$vars['license_key'] = $license_setting->value != 'n/a' ? $license_setting->value : '';
		$vars['license_setting_id'] = !empty($license_setting->setting_id) ? $license_setting->setting_id : '';

		// Check if license is saved as valid
		$vars['license_valid'] = $this->_checkLicenseValid();

		$vars['license_agreement'] = 'https://www.hopstudios.com/software/' . $this->short_name . '/license';

		return [
			'heading'		=> lang('license'),
			'body'			=> ee('View')->make('' . $this->short_name . ':license')->render($vars),
			'breadcrumb'	=> [
				ee('CP/URL', 'addons/settings/' . $this->short_name . '')->compile() => $this->module_name
			]
		];
	}

	/*
	 * Save license action
	 */
	public function save_license() {

		$license_key = ee()->input->post('license_key');
		$license_setting_id = ee()->input->post('license_setting_id');

		if ($license_setting_id) {
			$license_setting = ee('Model')->get($this->short_name . ':Config')->filter('setting_id', $license_setting_id)->first();
		} else {
			$license_setting = ee('Model')->make($this->short_name . ':Config');
		}

		$license_setting->setting_name = 'license';
		$license_setting->value = $license_key;
		$license_setting->save();

		// Check if license is valid from hop license
		$is_valid = $this->_checkLicense($license_key);

		if ($is_valid == 'valid') {
			$license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
			if (empty($license_valid)) {
				$license_valid = ee('Model')->make($this->short_name . ':Config');
				$license_valid->setting_name = 'license_valid';
				$license_valid->value = 'valid license';
				$license_valid->save();
			}
		} else {
			$license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
			if ( ! empty($license_valid)) {
				$license_valid->delete();
			}
		}

		ee()->functions->redirect(ee('CP/URL')->make('addons/settings/' . $this->short_name . '/license'));
	}

	/*
	 * Check if license is valid
	 * Connect with hop studios license api
	 */
	private function _checkLicense($license_key) {

		$url = 'https://license.hopstudios.com/check/' . $this->short_name . '/' . $license_key;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($ch);

		// if ( ! $result) {
		// 	echo 'Curl error: ' . curl_error($ch);
		// }
		curl_close($ch);

		return $result;
	}

    /*
     * Quick check in the db to see if license is valid
     */
    private function _checkLicenseValid()
    {
        try {
            $license_valid = ee('Model')->get($this->short_name . ':Config')->filter('setting_name', 'license_valid')->first();
            if ( ! empty($license_valid)) {
                return $license_valid->value == 'valid license';
            }
        } catch (Exception $e) {
            // Make sure Hop License table is configured properly
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle('Error')
                ->addToBody('Please update ' . $this->module_name)
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make('addons'));
        }
        return false;
    }
}
