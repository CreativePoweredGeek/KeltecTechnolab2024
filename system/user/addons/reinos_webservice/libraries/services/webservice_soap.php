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

class Webservice_soap
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

	/*
	*	Soap server
	*/
	private $server;


	// ----------------------------------------------------------------------

	/**
	 * Constructor
	 */
	public function __construct()
	{
	    //set the classname
		$className = 'soapMethodClass';

		//show the WSDL
		//create the class
		if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_service_classes'))
		{
			$className = $this->createClass($className);
		}

		//create the function dynamic
		require_once PATH_THIRD.'reinos_webservice/libraries/services/soap/'.$className.'.php';
		require_once PATH_THIRD.'reinos_webservice/libraries/services/soap/complexTypes.php';

		//WSDL mode
		if(isset($_GET['wsdl'])) {
			$autodiscover = new Zend\Soap\AutoDiscover(new Zend\Soap\Wsdl\ComplexTypeStrategy\ArrayOfTypeComplex);
			$autodiscover
				->setClass($className)
				->setUri(ee()->functions->create_url(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger').'/soap'))
				->setServiceName('EE_Webservice');

			header('Content-type: application/xml');
			$wsdl = $autodiscover->generate();

			exit($wsdl->toXml());
		}

		//non WSDL mode
		else
		{
			$server = new Zend\Soap\Server(null, array(
				'cache_wsdl' => WSDL_CACHE_NONE,
				'classmap' => array('ObjectList' => 'ObjectList', 'AssociativeArray' => 'AssociativeArray', 'Associative' => 'Associative'),
				'uri' => reduce_double_slashes(ee()->config->item('site_url').'/index.php/'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger')).'/soap'
			));
			$server->setObject(new $className());
			// Handle a request:
			$server->handle();
			exit;
		}
	}

	// ----------------------------------------------------------------------

	/**
	 * Generate the class for the webservice with all the methods
	 *
	 * @param string $className
	 * @return string
	 */
	public function createClass($className = 'soapMethodClass')
	{
		//get all the api settings
		$apis = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->load_apis();
		$classStrcuture = "<?php class $className {
		";

		//loop over the methods and create a class for it.
		foreach($apis['apis'] as $api)
		{
			foreach($api->methods as $method)
			{

				//dynamic class creating
				$classStrcuture .= '
				/**
				 * '.$method->name.'
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function ' . $method->method . "(\$auth = array(), \$data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					\$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('{$method->method}');

					//caching specific
					\$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable(\$api_name, '{$method->method}'); //is this method cachable?
					\$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache(\$api_name, '{$method->method}'); //needs the cache to be flushed after the call

					//get the api settings
					\$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api(\$api_name);

					//no settings, no api
					if(!\$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
					}

					//set the class
					\$class = 'webservice_'.\$api_name.'_static';

					//load from the webservice packages
					if(strstr(\$api_settings->path, 'reinos_webservice/libraries/api/') != false)
					{
						//check if the file exists
						if(!file_exists(\$api_settings->path.'/'.\$class.'.php'))
						{
							//return response
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the api class
						ee()->load->library('api/'.\$api_name.'/'.\$class);
					}

					//we deal with a third party api for the webservice
					else
					{
						//set the class
						\$class = 'reinos_webservice_'.\$api_name.'_api_static';

						//check if the file exists
						if(!file_exists(\$api_settings->path.'/libraries/'.\$class.'.php'))
						{
							//return response
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path(\$api_settings->path.'/');
						//load the api class
						ee()->load->library(\$class);
					}

					// check if method exists
					if (!method_exists(ucfirst(\$class), '{$method->method}'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
					}

					/** ---------------------------------------
					/** From here we do some Specific things
					/** ---------------------------------------*/

			        \$error_auth = false;
			        \$return_data = array(
			            'message'           => '',
			            'code_http'         => 200,
			            'success'			=> false
			        );
						
					//set the site_id
					\$site_id = \$vars['data']['site_id'] = isset(\$vars['data']['site_id']) ? \$vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset(\$api_settings->auth) && (bool) \$api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            \$default_checks = ee()->webservice_base_api->default_checks(\$auth, '{$method->method}', \$site_id);
			
			            if( ! \$default_checks['succes'])
			            { 
			                \$error_auth = true;
			                \$return_data = array_merge(\$return_data, \$default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	\$data = \$auth;
			        }

		         	if(\$error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && \$method_is_cachable)
						{
							//cache key
							\$key = 'webservice/soap/'.\$api_name.'/{$method->method}/'.md5(uri_string().'/?'.http_build_query(\$data));

							// Attempt to grab the local cached file
							\$cached = ee()->cache->get(\$key);

							//found a cached item
							if ( ! \$cached)
							{
								//call the method
								\$result = call_user_func(array(\$class, '{$method->method}'), \$data, 'soap');

								// Cache version information for a day
								ee()->cache->save(
									\$key,
									\$result,
									ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache_time', 86400)
								);
							}
							else
							{
								//call the method
								\$result = \$cached;
							}
						}

						//no caching
						else
						{
							//call the method
							\$result = call_user_func(array(\$class, '{$method->method}'), \$data, 'soap');
						}

						//check if the cache need to be cleared
						if(\$method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.\$api_name.'/');
						}

			            //unset the response txt
			            unset(\$result['response']);

			            //merge with default values
			            \$return_data = array_merge(\$return_data, \$result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), \$return_data, '{$method->method}', 'soap');

			        //unset the http code
			        unset(\$return_data['code_http']);

					//convert success value to int
					\$return_data['success'] = (int)\$return_data['success'];

			        //return result
			        return \$return_data;
				}";
			}
		}

		$classStrcuture .= "}";

		file_put_contents(PATH_THIRD.'reinos_webservice/libraries/services/soap/'.$className.'.php', $classStrcuture);

		return $className;
	}
}
