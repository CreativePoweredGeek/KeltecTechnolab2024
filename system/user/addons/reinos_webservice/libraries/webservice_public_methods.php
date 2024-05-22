<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

class Webservice_public_methods
{

	// ----------------------------------------------------------------------
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		//load the helper
		ee()->load->helper('webservice_helper');
        ee()->load->library('webservice_base_api');
	}

    // --------------------------------------------------------------------
        
    /**
     * Dynamic calling
     */
    public function __call($name, $arguments)
    {
		if(isset($arguments[0]))
		{
			return $this->method($name, $arguments[0]);
		}

		return array(
			'message'           => 'Input data required',
			'success'			=> false
		);
    }

	// --------------------------------------------------------------------

	/**
	 * Call Method
	 * @param string $method
	 * @param array $vars
	 * @param array $inlog
	 * @return array
	 */
    public function method($method = '', $vars = array(), $inlog = array())
    {
		//load all the methods
		$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class($method);

		//caching specific
		$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, $method); //is this method cachable?
		$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, $method); //needs the cache to be flushed after the call

		//get the api settings
		$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

		//no settings, no api
		if(!$api_settings)
		{
			//return response
			return array(
				'message' => 'API does not exist'
			);
		}

		//set the class
		$class = 'webservice_'.$api_name.'_static';

		//load from the webservice packages
		if(strstr($api_settings->path, 'reinos_webservice/libraries/api/') != false)
		{
			//check if the file exists
			if(!file_exists($api_settings->path.'/'.$class.'.php'))
			{
				//return response
				return array(
					'message' => 'API does not exist'
				);
			}

			//load the api class
			ee()->load->library('api/'.$api_name.'/'.$class);
		}

		//we deal with a third party api for the webservice
		else
		{
			//set the class
			$class = 'webservice_'.$api_name.'_api_static';

			//check if the file exists
			if(!file_exists($api_settings->path.'/libraries/'.$class.'.php'))
			{
				//return response
				return array(
					'message' => 'API does not exist'
				);
			}

			//load the package path
			ee()->load->add_package_path($api_settings->path.'/');
			//load the api class
			ee()->load->library($class);
		}

		// check if method exists
		if (!method_exists(ucfirst($class), $method))
		{
			return array(
				'message' => 'Method does not exist'
			);
		}

		/** ---------------------------------------
		/** From here we do some Specific things
		/** ---------------------------------------*/
        
        $error_auth = false;
        $return_data = array(
			'message'           => '',
			'code_http'         => 200,
			'success'			=> false
        );

		//do we have an auth array and a data array?
		$vars['auth'] = isset($vars['auth']) ? $vars['auth'] : array();
		$vars['data'] = isset($vars['data']) ? $vars['data'] : array();

		//quick check if we miss data array
		if(empty($vars['data']))
		{
			$return_data['message'] = 'Missing the data array';
		}

		//good to go
		else
		{
			//set the site_id
			$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			//if the api needs to be auth, do it here
			if(isset($api_settings->auth) && (bool) $api_settings->auth)
			{
				/** ---------------------------------------
				/**  Run some default checks
				/**  if the site id is given then switch to that site, otherwise use site_id = 1
				/** ---------------------------------------*/
				$default_checks = ee()->webservice_base_api->default_checks($vars['auth'], $method, $site_id);

				if( ! $default_checks['succes'])
				{
					$error_auth = true;
					$return_data = array_merge($return_data, $default_checks['message']);
				}
			}

			if($error_auth === false)
			{
				//cache enabled?
				if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
				{
					//cache key
					$key = 'webservice/rest/'.$api_name.'/'.$method.'/'.md5(uri_string().'/?'.http_build_query($vars['data']));

					// Attempt to grab the local cached file
					$cached = ee()->cache->get($key);

					//found a cached item
					if ( ! $cached)
					{
						//call the method
						$result = call_user_func(array($class, $method), $vars['data'], 'rest');

						// Cache version information for a day
						ee()->cache->save(
							$key,
							$result,
							ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache_time', 86400)
						);
					}
					else
					{
						//call the method
						$result = $cached;
					}
				}

				//no caching
				else
				{
					//call the method
					$result = call_user_func(array($class, $method), $vars['data'], 'custom');
				}

				//check if the cache need to be cleared
				if($method_is_clear_cache)
				{
					ee()->cache->delete('/webservice/rest/'.$api_name.'/');
				}

				//unset the response txt
				if(isset($result['response']))
				{
					unset($result['response']);
				}

				//merge with the default values
				$return_data = array_merge($return_data, $result);
			}
		}

        //add a log
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, $method, 'custom');
        
        //unset the http code
        if(isset($return_data['code_http']))
        {
            $http_code = $return_data['code_http'];
            unset($return_data['code_http']);
        }
        
        //return
        return $return_data;
    }
}
