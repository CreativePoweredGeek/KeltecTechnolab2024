<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2020 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2020 Reinos.nl Internet Media
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

class Reinos_webservice_member_api_static
{

	//-------------------------------------------------------------------------

	/**
     * create_member method
    */
	public static function create_member($data = array(), $type = '')
	{
		//load the entry class
		ee()->load->library('reinos_webservice_member_api');

		//post the data to the service
		$return_data = ee()->reinos_webservice_member_api->create_member($data, $type);

		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		//Format soap
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

	//-------------------------------------------------------------------------

	/**
     * create_member method
    */
	public static function read_member($data = array(), $type = '')
	{
		//load the entry class
		ee()->load->library('reinos_webservice_member_api');

		//post the data to the service
		$return_data = ee()->reinos_webservice_member_api->read_member($data, $type);

		//Format soap
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

		//unset the response txt
		unset($return_data['response']);

		//return result
		return $return_data;
	}

    //-------------------------------------------------------------------------

    /**
     * create_member method
     */
    public static function search_member($data = array(), $type = '')
    {
        //load the entry class
        ee()->load->library('reinos_webservice_member_api');

        //post the data to the service
        $return_data = ee()->reinos_webservice_member_api->search_member($data, $type);

        //Format soap
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

        //unset the response txt
        unset($return_data['response']);

        //return result
        return $return_data;
    }


    //-------------------------------------------------------------------------

	/**
     * update_member method
    */
	public static function update_member($data = array(), $type = '')
	{
		//load the entry class
		ee()->load->library('reinos_webservice_member_api');

		//post the data to the service
		$return_data = ee()->reinos_webservice_member_api->update_member($data, $type);

		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		//Format soap
		if($type == 'soap')
		{
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
			}
		}

		//unset the response txt
		unset($return_data['response']);

		//return result
		return $return_data;
	}

	//-------------------------------------------------------------------------

	/**
     * delete_member method
    */
	public static function delete_member($data = array(), $type = '')
	{
		//load the entry class
		ee()->load->library('reinos_webservice_member_api');

		//post the data to the service
		$return_data = ee()->reinos_webservice_member_api->delete_member($data, $type);

		//format the array, because we cannot do nested arrays
		if($type != 'rest' && isset($return_data['data']))
		{
			$return_data['data'] = webservice_format_data($return_data['data'], $type);
		}

		//Format soap
		if($type == 'soap')
		{
			if(isset($return_data['metadata']))
			{
				$return_data['metadata'] = webservice_format_soap_data($return_data['metadata']);
			}
		}

		//unset the response txt
		unset($return_data['response']);

		//return result
		return $return_data;
	}
}
