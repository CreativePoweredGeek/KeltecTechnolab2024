<?php


/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
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

namespace Reinos\Webservice\Service;

/**
 * Include the config file
 */
require_once(PATH_THIRD.'reinos_webservice/config.php');

class ApiHelper
{
	// --------------------------------------------------------------------

	/**
	 * Load the apis based on their dir name
	 */
	public function load_apis()
	{
		//get from cache
		if ( isset(ee()->session->cache[REINOS_WEBSERVICE_MAP]['apis']))
		{
			return ee()->session->cache[REINOS_WEBSERVICE_MAP]['apis'];
		}

		$apis = array();

		ee()->load->helper('file');
		ee()->load->helper('directory');

		$path = PATH_THIRD.'reinos_webservice/libraries/api';
		$dirs = directory_map($path);

		foreach ($dirs as $key=>$dir)
		{
			if(is_array($dir))
			{
				foreach($dir as $file)
				{
					if($file == 'settings.json')
					{
						$json = file_get_contents($path.'/'.$key.'/settings.json');
						$json = json_decode($json);
						$json->path = $path.'/'.$key;

						//is enabled?
						if(isset($json->enabled) && $json->enabled)
						{
							//set a quick array for the methods
							$json->_methods = array();
							foreach($json->methods as $method)
							{
								$json->_methods[$json->name] = $method->method;
								$apis['_methods_class'][$method->method] = $json->name;
							}

							$apis['apis'][$json->name] = $json;
						}
					}
				}
			}
		}

		//also look in the other maps for webservice stuff

		$path = PATH_THIRD;
		$dirs = directory_map($path, 2);

		foreach ($dirs as $key=>$dir)
		{
			if(is_array($dir))
			{
				foreach($dir as $file)
				{
					if($file == 'webservice_settings.json')
					{
						$json = file_get_contents($path.$key.'/webservice_settings.json');
						$json = json_decode($json);
						$json->path = $path.$key;

						//is enabled?
						if(isset($json->enabled) && $json->enabled && !isset($apis['apis'][$json->name]))
						{
							//set a quick array for the methods
							$json->_methods = array();
							foreach($json->methods as $method)
							{
								$json->_methods[$json->name] = $method->method;
								$apis['_methods_class'][$method->method] = $json->name;
							}

							$apis['apis'][$json->name] = $json;
						}
					}
				}
			}
		}

		//save as session
		ee()->session->cache[REINOS_WEBSERVICE_MAP]['apis'] = $apis;

		return $apis;
	}

	// --------------------------------------------------------------------

	/**
	 * Search for the api method
	 */
	public function search_api_method_class($method = '')
	{
		$apis = $this->load_apis();
		if(isset($apis['_methods_class'][$method]))
		{
			return $apis['_methods_class'][$method];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Load the apis based on their dir name
	 */
	public function get_api_names()
	{
		$apis = $this->load_apis();

		$return = array();
		foreach($apis['apis'] as $val)
		{
			if($val->public == false)
			{
				$return[$val->name] = $val->name.(isset($val->version) ? ' v'.$val->version.'' : ''). ' ('.$val->label.')';
			}
		}

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Load the apis based on their dir name
	 */
	public function get_api_free_names()
	{
		$apis = $this->load_apis();

		$return = array();
		foreach($apis['apis'] as $val)
		{
			foreach($val->methods as $method)
			{
				if(isset($method->free_api) && $method->free_api)
				{
					$return[$method->method] = $val->name.'/'.$method->method;
				}
			}
		}

		return $return;
	}

	// --------------------------------------------------------------------

	/**
	 * Load api data
	 */
	public function get_api_data($api_name = '', $method_name = '')
	{
		$apis = $this->load_apis();

		if($api_name != '' && isset($apis['apis'][$api_name]))
		{
			foreach($apis['apis'][$api_name]->methods as $method)
			{
				if($method->method == $method_name)
				{
					return $method;
				}
			}
		}

		return false;
	}

	// --------------------------------------------------------------------

	/**
	 * check if cachable
	 */
	public function method_is_cachable($api_name = '', $method_name = '')
	{
		$data = $this->get_api_data($api_name, $method_name);

		return isset($data->cachable) ? $data->cachable : false;
	}

	// --------------------------------------------------------------------

	/**
	 * check if the cache need to be flusche
	 */
	public function method_is_clear_cache($api_name = '', $method_name = '')
	{
		$data = $this->get_api_data($api_name, $method_name);

		return isset($data->clear_cache) ? $data->clear_cache : false;
	}

	// --------------------------------------------------------------------

	/**
	 * Load the apis based on their dir name
	 */
	public function get_api($name = '')
	{
		$apis = $this->load_apis();

		if($name != '' && isset($apis['apis'][$name]))
		{
			return $apis['apis'][$name];
		}

		return false;
	}
}
