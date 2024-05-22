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

class Webservice_auth
{
	/**
	 * authenticate
	 * @param $data
	 * @return array
	 * @internal param $auth
	 */
	public function authenticate($data)
	{
		$base_api = new webservice_base_api();
		$new_session = isset($data['new_session']) ? true : false;
		$ret_auth = $base_api->auth($data, $new_session);

		if(!$ret_auth || !$ret_auth['success'])
		{
			return array(
				'message' => 'cannot auth with given data',
			);
		}

		unset($ret_auth['success']);

		return array(
			'message' => 'successfully auth',
			'success' => true,
			'data' => array($ret_auth)
		);
	}

	//-------------------------------------------------------------------------

	/**
	 * authenticate_username
	 * @param $auth
	 * @param $data
	 * @return array
	 */
//	public function authenticate($data)
//	{
//		$base_api = new webservice_base_api();
//
//		exit;
//
//		$new_session = isset($data['new_session']) ? true : false;
//		$ret_auth = $base_api->auth($data, $new_session);
//
//		if(!$ret_auth['success'])
//		{
//			return array(
//				'message' => 'cannot auth with given data',
//			);
//		}
//
//		unset($ret_auth['success']);
//
//		return array(
//			'message' => 'successfully auth',
//			'success' => true,
//			'data' => array($ret_auth)
//		);
//	}



	//-------------------------------------------------------------------------

	/**
     * authenticate_email
    */
//	 public function authenticate_email($data = array())
//	 {
//	 }

	//-------------------------------------------------------------------------

	/**
     * authenticate_member_id
    */
//	 public function authenticate_member_id($data = array())
//	 {
//	 	$base_api = new webservice_base_api();
//		$new_session = isset($data['create_new_session']) ? true : false;
//		$auth = $base_api->auth($data, $new_session);
//	 	return $base_api->auth_data;
//	 }

	//-------------------------------------------------------------------------

	/**
     * authenticate_session_id
    */
//	 public function authenticate_session_id($data = array())
//	 {
//	 	$base_api = new webservice_base_api();
//		$new_session = isset($data['create_new_session']) ? true : false;
//		$auth = $base_api->auth($data, $new_session);
//	 	var_dump($base_api->error_str);
//	 	//return $base_api->auth_data;
//	 }

}

