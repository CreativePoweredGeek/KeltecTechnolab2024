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

class Webservice_entry_static
{
	// ----------------------------------------------------------------------
	
	/**
	 * Insert a entry in the database
	 *
	 * @param none
	 * @return void
	 */
	static function create_entry($data, $type = '')
	{		
		//load the entry class
		ee()->load->library('api/entry/webservice_entry');

		//post the data to the service
		$return_data = ee()->webservice_entry->create_entry($data);
		
		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		//format soap
		if($type == 'soap')
		{
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
			}
		}

		//return result
		return $return_data;
	}

	// ----------------------------------------------------------------------
	
	/**
	 * read a entry
	 *
	 * @param none
	 * @return void
	 */
	static function read_entry($data, $type = '')
	{
		//load the entry class
		ee()->load->library('api/entry/webservice_entry');

		//post the data to the service
		$return_data = ee()->webservice_entry->read_entry($data);

		//var_dump($return_data);exit;
		if($type == 'soap')
		{
			if(isset($return_data['data']))
			{	
				$return_data['data'] = webservice_format_soap_data($return_data['data'], 'entry_list');
			}
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
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
	
	// ----------------------------------------------------------------------
	
	/**
	 * Update a entry in the database
	 *
	 * @param none
	 * @return void
	 */
	static function update_entry($data, $type = '')
	{		
		//load the entry class
		ee()->load->library('api/entry/webservice_entry');
		
		//post the data to the service
		$return_data = ee()->webservice_entry->update_entry($data);

		//format the array, because we cannot do nested arrays
		if($type != 'rest'  && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		if($type == 'soap')
		{
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
			}
		}

		//return result
		return $return_data;
	}	 
	
	// ----------------------------------------------------------------------
	
	/**
	 * Delete a entry
	 *
	 * @param none
	 * @return void
	 */
	static function delete_entry($data, $type = '')
	{	
		//load the entry class
		ee()->load->library('api/entry/webservice_entry');

		//post the data to the service
		$return_data = ee()->webservice_entry->delete_entry($data);
		
		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		if($type == 'soap')
		{
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
			}
		}
		
		//return result
		return $return_data;
	}	

	// ----------------------------------------------------------------------
	
	/**
	 * search a entry
	 *
	 * @param none
	 * @return void
	 */
	static function search_entry($data, $type = '')
	{	
		//load the entry class
		ee()->load->library('api/entry/webservice_entry');

		//post the data to the service
		$return_data = ee()->webservice_entry->search_entry($data);

		if($type == 'soap')
		{
			if(isset($return_data['data']))
			{
				$return_data['data'] = webservice_format_soap_data($return_data['data'], 'entry_list');
			}
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
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
}
