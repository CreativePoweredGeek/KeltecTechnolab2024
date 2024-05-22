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

class Webservice_auth_static
{
	//-------------------------------------------------------------------------

	/**
	 * authenticate_token method
	 * @param array $data
	 * @param string $type
	 * @return
	 * @internal param array $auth
	 */
	public static function authenticate($data = array(), $type = '')
	{
		//load the entry class
		ee()->load->library('api/auth/webservice_auth');

		//post the data to the service
		$return_data = ee()->webservice_auth->authenticate($data);

		//var_dump($return_data);exit;
		if($type == 'soap')
		{
			if(isset($return_data['data']))
			{
				$return_data['data'] = webservice_format_soap_data($return_data['data'], 'entry_list');
			}
		}

		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		//return result
		return $return_data;
	}

	//-------------------------------------------------------------------------

	/**
	 * authenticate_username method
	 *
	 * ALIAS
	 *
	 * @param array $data
	 * @param string $type
	 * @return
	 * @internal param array $auth
	 */
	public static function authenticate_username($data = array(), $type = '')
	{
		return self::authenticate($data, $type);
	}

	//-------------------------------------------------------------------------

	/**
     * authenticate_username method
    */
//	 public static function authenticate_email($data = array(), $type = '')
//	 {
//	 	//load the entry class
//	 	ee()->load->library('api/auth/webservice_auth');
//
//	 	//post the data to the service
//	 	$return_data = ee()->webservice_auth->authenticate_email($data, $type);
//
//	 	//unset the response txt
//	 	unset($return_data['response']);
//
//	 	//return result
//	 	return $return_data;
//	 }

	//-------------------------------------------------------------------------

	/**
     * authenticate_username method
    */
//	 public static function authenticate_member_id($data = array(), $type = '')
//	 {
//	 	//load the entry class
//	 	ee()->load->library('api/auth/webservice_auth');
//
//	 	//post the data to the service
//	 	$return_data = ee()->webservice_auth->authenticate_member_id($data, $type);
//
//	 	//unset the response txt
//	 	unset($return_data['response']);
//
//	 	//return result
//	 	return $return_data;
//	 }

	//-------------------------------------------------------------------------

	/**
     * authenticate_username method
    */
//	 public static function authenticate_session_id($data = array(), $type = '')
//	 {
//	 	//load the entry class
//	 	ee()->load->library('api/auth/webservice_auth');
//
//	 	//post the data to the service
//	 	$return_data = ee()->webservice_auth->authenticate_session_id($data, $type);
//
//	 	//unset the response txt
//	 	unset($return_data['response']);
//
//	 	//return result
//	 	return $return_data;
//	 }

	//-------------------------------------------------------------------------

	/**
	 * authenticate_token method
	 */
//	public static function authenticate($data = array(), $type = '')
//	{
//		//load the entry class
//		ee()->load->library('api/auth/webservice_auth');
//
//		//post the data to the service
//		$return_data = ee()->webservice_auth->authenticate($data, $type);
//
//		//unset the response txt
//		unset($return_data['response']);
//
//		//return result
//		return $return_data;
//	}
}
