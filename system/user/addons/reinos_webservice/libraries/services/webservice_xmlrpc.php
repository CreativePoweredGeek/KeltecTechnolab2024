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

class Webservice_xmlrpc
{
	/*
	*	The username
	*/
	public $username;

	/*
	*	the password
	*/
	public $password;

	/*
	*	The postdata
	*/
	public $post_data;

	/*
	*	the channel
	*/
	public $post_data_channel;


	// ----------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct()
	{
		/* ---------------------------------
		/*  Specify Functions
		/* ---------------------------------*/
		$functions = array();

		$apis = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->load_apis();
		foreach($apis['apis'] as $api)
		{
			foreach($api->methods as $method)
			{
				$return_array = array();
				foreach($method->soap as $val)
				{
					$return_array[$val->name] = $val->type;
				}

				$functions[$method->method] = array('function' => 'Webservice_xmlrpc::call_method');

			}
		}

		/** ---------------------------------
		/**  Instantiate the Server Class
		/** ---------------------------------*/
		$s = new PhpXmlRpc\Server($functions);
		$s->service();

		die();
	}

	// ----------------------------------------------------------------------

	/**
	 * call the method
	 *
	 * @param none
	 * @return void
	 */
	public static function call_method($xmlrpcmsg)
	{
		//get the method
		$method_name = $xmlrpcmsg->method();

		//load the libs
		ee()->load->helper('webservice_helper');
		ee()->load->library('webservice_base_api');
		ee()->load->helper('url');

		$return_data = array(
			'message'           => '',
			'code_http'         => 200,
			'success'			=> false
		);

		//load all the methods
		$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class($method_name);

		//caching specific
		$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, $method_name); //is this method cachable?
		$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, $method_name); //needs the cache to be flushed after the call

		//get the api settings
		$api_settings = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

		//no settings, no api
		if(!$api_settings)
		{
			return Webservice_xmlrpc::response(array_merge($return_data, array(
				'message' => 'API does not exist'
			)));
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
				return Webservice_xmlrpc::response(array_merge($return_data, array(
					'message' => 'API does not exist'
				)));
			}

			//load the api class
			ee()->load->library('api/'.$api_name.'/'.$class);
		}

		//we deal with a third party api for the webservice
		else
		{
			//set the class
			$class = 'reinos_webservice_'.$api_name.'_api_static';

			//check if the file exists
			if(!file_exists($api_settings->path.'/libraries/'.$class.'.php'))
			{
				//return response
				return Webservice_xmlrpc::response(array_merge($return_data, array(
					'message' => 'API does not exist'
				)));
			}

			//load the package path
			ee()->load->add_package_path($api_settings->path.'/');
			//load the api class
			ee()->load->library($class);
		}

		// check if method exists
		if (!method_exists(ucfirst($class), $method_name))
		{
			//return response
			return Webservice_xmlrpc::response(array_merge($return_data, array(
				'message' => 'Method does not exist'
			)));
		}

		/** ---------------------------------------
		/** From here we do some Specific things
		/** ---------------------------------------*/

		//get the paramaters
		$vars['auth'] = Webservice_xmlrpc::parseStructData($xmlrpcmsg->getParam(0));
		$vars['data'] = Webservice_xmlrpc::parseStructData($xmlrpcmsg->getParam(1));

		$error_auth = false;

        //set the site_id
        $site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

        //if the api needs to be auth, do it here
        if(isset($api_settings->auth) && (bool) $api_settings->auth)
        {
            /** ---------------------------------------
            /**  Run some default checks
            /**  if the site id is given then switch to that site, otherwise use site_id = 1
            /** ---------------------------------------*/
            $default_checks = ee()->webservice_base_api->default_checks($vars['auth'], $method_name, $site_id);

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
				$key = 'webservice/xmlrpc/'.$api_name.'/'.$method_name.'/'.md5(uri_string().'/?'.http_build_query($vars['data']));

				// Attempt to grab the local cached file
				$cached = ee()->cache->get($key);

				//found a cached item
				if ( ! $cached)
				{
					//call the method
					$result = call_user_func(array($class, $method_name), $vars['data'], 'xmlrpc');

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
				$result = call_user_func(array($class, $method_name), $vars['data'], 'xmlrpc');
			}

			//check if the cache need to be cleared
			if($method_is_clear_cache)
			{
				ee()->cache->delete('/webservice/xmlrpc/'.$api_name.'/');
			}

            //unset the response txt
            unset($result['response']);

            //merge with default result
            $return_data = array_merge($return_data, $result);
        }

        //add a log
		ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, $method_name, 'xmlrpc');

		//unset the http code
        if(isset($return_data['code_http']))
        {
            $http_code = $return_data['code_http'];
            unset($return_data['code_http']);
        }

        //return
        return Webservice_xmlrpc::response($return_data, $http_code);
	}

	// ----------------------------------------------------------------------

	/**
	 * response
	 *
	 * @param none
	 * @return void
	 */
	public static function response($result, $http_code = 200)
	{
		//good call?
		if($http_code == 200)
		{
			$response = array(
				'success'	=> new PhpXmlRpc\Value((int)$result['success'], 'int'),
				'message'	=> new PhpXmlRpc\Value($result['message'], 'string'),
				'metadata'	=> new PhpXmlRpc\Value('', 'string')
			);

			//format metadata if needed
			if(isset($result['metadata'])) {
				foreach($result['metadata'] as $k => $v)
				{
					$result['metadata'][$k] = new PhpXmlRpc\Value($v, 'string');
				}

				$response['metadata'] = new PhpXmlRpc\Value($result['metadata'], 'struct');
			}

			//is there an id returnend by an create invoke
			if(isset($result['id']))
			{
				$response['id'] = new PhpXmlRpc\Value($result['id'], 'string');
			}

			//grab the data and assing it to the response array
			if(!empty($result['data']))
			{
				$values = array();
				foreach($result['data'] as $key=>$entry)
				{
					//format the entry fields
					foreach($entry as $k => $v)
					{
						$entry[$k] = new PhpXmlRpc\Value($v, 'string');
					}

					$values[$key] = new PhpXmlRpc\Value($entry, 'struct');
				}

				$response['data'] = new PhpXmlRpc\Value($values, 'array');
			}

			//return data

			$response = new PhpXmlRpc\Value($response, 'struct');
			return new PhpXmlRpc\Response($response);
		}
		//error?
		else
		{
			return new PhpXmlRpc\Response('', $http_code, $result['message']);
		}
	}

	// ----------------------------------------------------------------------

	/**
	 * Parse StructData
	 *
	 * @param $val
	 * @return array
	 */
	public static function parseStructData($val) {
		$data = array();
		while (list($key, $v) = $val->structEach())
		{
		    $data[$key] = $v->scalarval();

		    if(is_array($data[$key]))
            {
                foreach($data[$key] as $_k => $_v)
                {
                    $data[$key][$_k] = $_v->scalarval();
                }
            }
		}

		return $data;
	}

	// ----------------------------------------------------------------------

}
