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
require_once(PATH_THIRD.'reinos_webservice/config.php');

class Webservice_lib
{

	public function __construct()
	{
        //get the action id
        $this->act = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->fetch_action_id(REINOS_WEBSERVICE_CLASS, 'act_route');
        $this->act_url = ee()->functions->fetch_site_index(0, 0).QUERY_MARKER.'ACT='.$this->act;

		//load model
		ee()->load->model(REINOS_WEBSERVICE_MAP.'_model');

		//load the channel data
        ee()->load->library('typography');
        ee()->load->library('api');
        ee()->legacy_api->instantiate('channel_fields');

		//load the settings
		ee()->load->library(REINOS_WEBSERVICE_MAP.'_settings');

		//load logger
		ee()->load->library('logger');

		//load the apis LIB
		ee()->load->library(REINOS_WEBSERVICE_MAP.'_apis_lib');

		//load the entry LIB
		ee()->load->library(REINOS_WEBSERVICE_MAP.'_entry_lib');

        //load helper
        ee()->load->helper('webservice_helper');
			
		//check the tmp path
		ee()->load->helper('file');
	}
}
