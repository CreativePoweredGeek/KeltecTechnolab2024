<?php class soapMethodClass {
		
				/**
				 * Authenticate by username
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function authenticate_username($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('authenticate_username');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'authenticate_username'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'authenticate_username'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'authenticate_username'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'authenticate_username', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/authenticate_username/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'authenticate_username'), $data, 'soap');

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
							$result = call_user_func(array($class, 'authenticate_username'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'authenticate_username', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Authenticate
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function authenticate($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('authenticate');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'authenticate'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'authenticate'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'authenticate'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'authenticate', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/authenticate/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'authenticate'), $data, 'soap');

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
							$result = call_user_func(array($class, 'authenticate'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'authenticate', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a new entry
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_entry($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_entry');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_entry'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_entry'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_entry'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_entry', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_entry/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_entry'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_entry'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_entry', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read an entry
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_entry($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_entry');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_entry'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_entry'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_entry'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_entry', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_entry/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_entry'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_entry'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_entry', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update an entry
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_entry($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_entry');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_entry'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_entry'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_entry'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_entry', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_entry/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_entry'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_entry'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_entry', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete an entry
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_entry($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_entry');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_entry'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_entry'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_entry'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_entry', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_entry/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_entry'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_entry'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_entry', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Search entries
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function search_entry($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('search_entry');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'search_entry'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'search_entry'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'search_entry'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'search_entry', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/search_entry/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'search_entry'), $data, 'soap');

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
							$result = call_user_func(array($class, 'search_entry'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'search_entry', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Show an Ad
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function show_adman($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('show_adman');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'show_adman'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'show_adman'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'show_adman'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'show_adman', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/show_adman/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'show_adman'), $data, 'soap');

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
							$result = call_user_func(array($class, 'show_adman'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'show_adman', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a category
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_category($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_category');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_category'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_category'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_category'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_category', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_category/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_category'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_category'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_category', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read a category
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_category($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_category');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_category'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_category'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_category'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_category', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_category/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_category'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_category'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_category', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update a category
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_category($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_category');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_category'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_category'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_category'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_category', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_category/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_category'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_category'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_category', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete a category
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_category($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_category');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_category'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_category'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_category'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_category', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_category/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_category'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_category'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_category', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a category group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_category_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_category_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_category_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_category_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_category_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_category_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_category_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_category_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_category_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_category_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read a category group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_category_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_category_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_category_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_category_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_category_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_category_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_category_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_category_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_category_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_category_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update a category group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_category_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_category_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_category_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_category_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_category_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_category_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_category_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_category_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_category_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_category_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete a category group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_category_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_category_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_category_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_category_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_category_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_category_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_category_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_category_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_category_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_category_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a Channel
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_channel($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_channel');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_channel'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_channel'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_channel'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_channel', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_channel/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_channel'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_channel'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_channel', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read a channel
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_channel($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_channel');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_channel'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_channel'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_channel'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_channel', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_channel/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_channel'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_channel'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_channel', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update a channel
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_channel($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_channel');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_channel'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_channel'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_channel'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_channel', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_channel/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_channel'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_channel'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_channel', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete a channel
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_channel($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_channel');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_channel'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_channel'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_channel'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_channel', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_channel/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_channel'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_channel'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_channel', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Search a channel
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function search_channel($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('search_channel');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'search_channel'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'search_channel'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'search_channel'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'search_channel', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/search_channel/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'search_channel'), $data, 'soap');

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
							$result = call_user_func(array($class, 'search_channel'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'search_channel', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a new Comment
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_comment($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_comment');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_comment'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_comment'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_comment'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_comment', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_comment/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_comment'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_comment'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_comment', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read an comment
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_comment($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_comment');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_comment'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_comment'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_comment'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_comment', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_comment/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_comment'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_comment'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_comment', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update an comment
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_comment($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_comment');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_comment'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_comment'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_comment'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_comment', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_comment/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_comment'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_comment'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_comment', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete an comment
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_comment($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_comment');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_comment'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_comment'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_comment'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_comment', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_comment/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_comment'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_comment'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_comment', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Search an comment
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function search_comment($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('search_comment');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'search_comment'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'search_comment'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'search_comment'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'search_comment', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/search_comment/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'search_comment'), $data, 'soap');

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
							$result = call_user_func(array($class, 'search_comment'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'search_comment', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Get a specific config
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_config($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_config');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_config'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_config'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_config'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_config', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_config/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_config'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_config'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_config', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create Member
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_member($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_member');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_member'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_member'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_member'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_member', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_member/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_member'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_member'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_member', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read Member
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_member($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_member');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_member'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_member'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_member'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_member', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_member/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_member'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_member'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_member', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update Member
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_member($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_member');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_member'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_member'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_member'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_member', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_member/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_member'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_member'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_member', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete member
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_member($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_member');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_member'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_member'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_member'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_member', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_member/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_member'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_member'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_member', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create Member Group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_member_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_member_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_member_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_member_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_member_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_member_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_member_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_member_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_member_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_member_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read Member Group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_member_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_member_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_member_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_member_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_member_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_member_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_member_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_member_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_member_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_member_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update Member Group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_member_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_member_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_member_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_member_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_member_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_member_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_member_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_member_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_member_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_member_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete member Group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_member_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_member_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_member_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_member_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_member_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_member_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_member_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_member_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_member_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_member_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Reset Member Password
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function reset_member_password($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('reset_member_password');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'reset_member_password'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'reset_member_password'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'reset_member_password'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'reset_member_password', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/reset_member_password/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'reset_member_password'), $data, 'soap');

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
							$result = call_user_func(array($class, 'reset_member_password'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'reset_member_password', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Resend Member Activation
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function resend_member_activation($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('resend_member_activation');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'resend_member_activation'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'resend_member_activation'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'resend_member_activation'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'resend_member_activation', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/resend_member_activation/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'resend_member_activation'), $data, 'soap');

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
							$result = call_user_func(array($class, 'resend_member_activation'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'resend_member_activation', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Create a template group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function create_template_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('create_template_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'create_template_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'create_template_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'create_template_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'create_template_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/create_template_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'create_template_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'create_template_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'create_template_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Read a template group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function read_template_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('read_template_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'read_template_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'read_template_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'read_template_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'read_template_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/read_template_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'read_template_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'read_template_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'read_template_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update a template group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_template_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_template_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_template_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_template_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_template_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_template_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_template_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_template_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_template_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_template_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Delete a template group
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function delete_template_group($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('delete_template_group');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'delete_template_group'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'delete_template_group'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'delete_template_group'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'delete_template_group', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/delete_template_group/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'delete_template_group'), $data, 'soap');

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
							$result = call_user_func(array($class, 'delete_template_group'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'delete_template_group', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Update view_count
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function update_view_count($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('update_view_count');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'update_view_count'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'update_view_count'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'update_view_count'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'update_view_count', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/update_view_count/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'update_view_count'), $data, 'soap');

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
							$result = call_user_func(array($class, 'update_view_count'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'update_view_count', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}
				/**
				 * Increment the view_count
				 *
				 * @param array $auth
				 * @param array $data
				 * @return array
				 */
				function increment_view_count($auth = array(), $data = array()) 
				{
					ee()->load->helper('webservice_helper');
					ee()->load->library('webservice_base_api');

					ee()->load->helper('url');

					//load all the methods
					$api_name = ee()->webservice_base_api->api_type = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->search_api_method_class('increment_view_count');

					//caching specific
					$method_is_cachable = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_cachable($api_name, 'increment_view_count'); //is this method cachable?
					$method_is_clear_cache = ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->method_is_clear_cache($api_name, 'increment_view_count'); //needs the cache to be flushed after the call

					//get the api settings
					$api_settings =  ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiHelper')->get_api($api_name);

					//no settings, no api
					if(!$api_settings)
					{
						//return response
						return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
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
							return new soap_fault('Client', '', 'API does not exist');
						}

						//load the package path
						ee()->load->add_package_path($api_settings->path.'/');
						//load the api class
						ee()->load->library($class);
					}

					// check if method exists
					if (!method_exists(ucfirst($class), 'increment_view_count'))
					{
						return new soap_fault('Client', '', 'Method does not exist');
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
						
					//set the site_id
					$site_id = $vars['data']['site_id'] = isset($vars['data']['site_id']) ? $vars['data']['site_id'] : 1;

			        //if the api needs to be auth, do it here
			        if(isset($api_settings->auth) && (bool) $api_settings->auth)
			        {
			            /** ---------------------------------------
			            /**  Run some default checks
			            /**  if the site id is given then switch to that site, otherwise use site_id = 1
			            /** ---------------------------------------*/
			            $default_checks = ee()->webservice_base_api->default_checks($auth, 'increment_view_count', $site_id);
			
			            if( ! $default_checks['succes'])
			            { 
			                $error_auth = true;
			                $return_data = array_merge($return_data, $default_checks['message']);
			            }
			        }

			        //then the first array is not auth but data
			        else
			        {
			        	$data = $auth;
			        }

		         	if($error_auth === false)
			        {
			        	 //cache enabled?
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1 && $method_is_cachable)
						{
							//cache key
							$key = 'webservice/soap/'.$api_name.'/increment_view_count/'.md5(uri_string().'/?'.http_build_query($data));

							// Attempt to grab the local cached file
							$cached = ee()->cache->get($key);

							//found a cached item
							if ( ! $cached)
							{
								//call the method
								$result = call_user_func(array($class, 'increment_view_count'), $data, 'soap');

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
							$result = call_user_func(array($class, 'increment_view_count'), $data, 'soap');
						}

						//check if the cache need to be cleared
						if($method_is_clear_cache)
						{
							ee()->cache->delete('/webservice/soap/'.$api_name.'/');
						}

			            //unset the response txt
			            unset($result['response']);

			            //merge with default values
			            $return_data = array_merge($return_data, $result);
			        }

			        //add a log
			        ee(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->addLog(ee()->session->userdata('username'), $return_data, 'increment_view_count', 'soap');

			        //unset the http code
			        unset($return_data['code_http']);

					//convert success value to int
					$return_data['success'] = (int)$return_data['success'];

			        //return result
			        return $return_data;
				}}