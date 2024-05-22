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

class Webservice_matrix_ft
{
	public $name = 'matrix';

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
		if(!empty($data) && is_array($data))
		{
			//set the rows
			$rows = isset($data['rows']) ? $data['rows'] : array();
			
			//set the revision
			$revision = isset($data['trigger_revisions']) ? $data['trigger_revisions'] : 1;
		
			//get the col settings
			$matrix_settings = $this->get_matrix_settings($this->field_data['field_id']);
			
			//max rows?
			if($this->field_settings['max_rows'] != '' && (count($rows) > $this->field_settings['max_rows']) )
			{
				$rows = array_slice($rows, 0, $this->field_settings['max_rows']);
			}

			//if not new, an update (doh ;-), delete the old ones
			if($is_new == false)
			{
				ee()->db->where('entry_id', $entry_id);
				ee()->db->delete('matrix_data');
			}
			
			//set the order array
			$order_array = array();
			
			//set the insert array
			$insert_array = array();
			
			//loop over the items
			
			foreach($rows as $order => $row)
			{//echo $order.'-';
				//set the order
				$order_array[] = 'row_new_'.$order;
				
				//loop over the fields
				foreach($row as $key => $val)
				{
					//grid insert array goed maken
					$insert_array['row_new_'.$order]['col_id_'.$this->get_matrix_col_id($this->field_data['field_id'], $key)] = $val;
				}
			}
			
			//combine the data
			return  array_merge(array('row_order' => $order_array), $insert_array, array('trigger_revisions' => $revision));
		}
		
		//return nothing when there is nothing
		return '';
		
		//format data Matrix
		/*return array(
			'row_order' => array(
				'row_new_0',
				'row_new_1'
			),
			'row_new_0' => array(
				'col_id_7' => 'test veld 1',
				'col_id_8' => 'test veld 2'
			),
			'row_new_1' => array(
				'col_id_7' => 'test veld 1',
				'col_id_8' => 'test veld 2'
			),
			'trigger_revisions' => 1
		);*/
	}

	// ----------------------------------------------------------------

	/**
	 * Validate the field
	 * 
	 * @param  mixed $data 
	 * @param  bool $is_new 
	 * @return void            
	 */
	public function webservice_validate($data = null, $is_new = false)
	{
		//validate the min rows
		if($this->field_settings['min_rows'] != '' && (count($data) < $this->field_settings['min_rows']) )
		{
			$this->validate_error = 'You must add a min of '.$this->field_settings['min_rows'].' rows';
			return false;
		}

		//validate the min rows
		if($this->field_settings['max_rows'] != '' && (count($data) > $this->field_settings['max_rows']) )
		{
			$this->validate_error = 'You reach the limit of '.$this->field_settings['max_rows'].' rows';
			return false;
		}

		return true;
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
		//get the data
		ee()->db->where('entry_id', $entry_id);
		ee()->db->order_by('row_order', 'asc');
		$query = ee()->db->get('matrix_data');


		$return = array();

		//format the data
		if($query->num_rows() > 0)
		{
			foreach($query->result_array() as $key=>$val)
			{
				//print_r($val);
				foreach($val as $k=>$v)
				{
					//attach order_id
					if($k == 'row_order')
					{
						$return[$key][$k] = $v;
					}

					//set the name
					else if(preg_match('/col_id_/', $k))
					{
						//get settings
						$col_settings = $this->get_matrix_col_field_settings($this->field_data['field_id'], str_replace('col_id_', '', $k));

						$k = $this->get_matrix_col_field_name($this->field_data['field_id'], str_replace('col_id_', '', $k));

						//if $k is empty, skip this one
						if($k == '')
						{
							continue;
						}

						/** ---------------------------------------
						/**  Process the data
						/** ---------------------------------------*/
						if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('parse_matrix_grid_data'))
						{
							$v = ee()->webservice_fieldtype->pre_process_matrix($v, $col_settings['col_type'], $col_settings['col_name'], $col_settings, 'search_entry', $entry_id);
						}

						/* -------------------------------------------
						/* 'read_entry_matrix' hook.
						/*  - Added: 3.5.1
						*/
						$return[$key][$k] = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->add_hook('read_entry_matrix', $v, false, $col_settings, $val['row_id']);
						/** ---------------------------------------*/
					}
				}
			}

			return $return;
		}

		// return $data;
		return $data;
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Get the grid settings
	 * 
	 * @return void
	 */
	public function get_matrix_settings($field_id = 0) 
	{
		ee()->db->where('field_id', $field_id);
		$query = ee()->db->get('matrix_cols');

		$matrix_settings = array();

		if($query->num_rows() > 0)
		{
			foreach($query->result() as $k => $row)
			{
				foreach($row as $key => $val)
				{
					$matrix_settings[$k][$key] = $key == 'col_settings' ? (array) json_decode($val) : $val;
				}
			}
		}

		return $matrix_settings;
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Get the grid settings
	 * 
	 * @return void
	 */
	public function get_matrix_col_id($field_id = 0, $col_name = '') 
	{
		ee()->db->where('field_id', $field_id);
		ee()->db->where('col_name', $col_name);
		$query = ee()->db->get('matrix_cols');

		if($query->num_rows() > 0)
		{
			return $query->row()->col_id;
		}

		return '';
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Get the matrix field_name
	 * 
	 * @return void
	 */
	public function get_matrix_col_field_name($field_id = 0, $col_id = 0) 
	{
		ee()->db->where('field_id', $field_id);
		ee()->db->where('col_id', $col_id);
		$query = ee()->db->get('matrix_cols');

		if($query->num_rows() > 0)
		{
			return $query->row()->col_name;
		}

		return '';
	}

	// ----------------------------------------------------------------------
	
	/**
	 * Get the Matrix field_type
	 * 
	 * @return void
	 */
	public function get_matrix_col_field_settings($field_id = 0, $col_id = 0) 
	{
		ee()->db->where('field_id', $field_id);
		ee()->db->where('col_id', $col_id);
		$query = ee()->db->get('matrix_cols');

		if($query->num_rows() > 0)
		{
			return $query->row_array();
		}

		return array();
	}

}
