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

class Webservice_playa_ft
{
	public $name = 'playa';

	private $tmp_entry_id = 42949672;
	
	// ----------------------------------------------------------------

	/**
	 * Preps the data for saving
	 * 
	 * @param  mixed $data  
	 * @param  bool $is_new
	 * @param  int $entry_id
	 * @return void            
	 */
	public function webservice_save($data = null, $is_new = false, $entry_id = 0)
	{
		//print_r($this->field_settings);return;
		if(!empty($data) && is_array($data))
		{
			//only one item allowed
			if($this->field_settings['multi'] != 'y')
			{
				$data = array_slice($data, 0, 1);
			}

			//if not new, an update (doh ;-), delete the old ones
			if($is_new == false)
			{
				ee()->db->where('parent_entry_id', $entry_id);
				ee()->db->delete('playa_relationships');
			}
			
			//set the insert data
			$insert_data = array();

			//loop over the items
			foreach($data as $order => $row)
			{
				$insert_data['selections'][] = $row;
			}
			
			return $insert_data;
		}

		//return nothing, when there is nothing
		return '';
		
		/*
			Array ( [selections] => Array ( [0] => [1] => 37 [2] => 38 [3] => 36 [4] => 42 ) )
		*/
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Preprocess the data to be returned
	 * 
	 * @param  mixed $data 
	 * @param  string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param  string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_matrix($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
	}

	// ----------------------------------------------------------------------
	
	/**
	 * get the channel id for an entry_id
	 * 
	 */
	public function channel_id($entry_id = 0) 
	{
		ee()->db->select('channel_id');
		ee()->db->where('entry_id', $entry_id);
		$query = ee()->db->get('channel_titles');

		if($query->num_rows() > 0)
		{
			return $query->row()->channel_id;
		}

		return false;
	}
	
	// ----------------------------------------------------------------------
	
	/**
	 * Get the entry title
	 * 
	 */
	public function get_entry_title($entry_id = 0) 
	{
		ee()->db->select('title');
		ee()->db->where('entry_id', $entry_id);
		$query = ee()->db->get('channel_titles');

		if($query->num_rows() > 0)
		{
			return $query->row()->title;
		}

		return false;
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	private function _parse_data($data = null, $free_access = false, $entry_id = 0)
	{
		//get the data
		ee()->db->select('child_entry_id as `entry_id`, rel_order as `order`');
		ee()->db->where('parent_entry_id', $entry_id);
		$query = ee()->db->get('playa_relationships');

		if($query->num_rows() > 0)
		{
			$return = array();
			foreach($query->result_array() as $row)
			{
				if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_rel_data'))
				{
					$return[] = ee()->webservice_entry_lib->get_entry($row['entry_id'], array('*'), true);
				}
				else
				{
					$return[] = $row;
				}
			}

			return $return;
		}

		return $data;
	}
}
