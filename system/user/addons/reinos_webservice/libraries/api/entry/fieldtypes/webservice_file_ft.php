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

class Webservice_file_ft
{
	public $name = 'file';
	
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
		$data['filedata'] = base64_decode($data['filedata']);

		//load the filemanager
		ee()->load->library('filemanager');

		// Disable XSS Filtering
		ee()->filemanager->xss_clean_off();
		

		// Figure out the FULL file path
		$file_path = ee()->filemanager->clean_filename(
			$data['filename'],
			$data['dir_id'],
			array('ignore_dupes' => FALSE)
		);

		ee()->load->model('file_upload_preferences_model');

        $upload_prefs = ee()->file_upload_preferences_model->get_file_upload_preferences(null, $data['dir_id']);

		//set the correct filename
		$filename = basename($file_path);

		ee()->load->library('upload', array(
			'upload_path' => $upload_prefs['server_path']
		));

		//do the upload
		if(ee()->upload->raw_upload($filename, $data['filedata']))
		{			
			ee()->filemanager->xss_clean_off();
			// Send the file
			try {
				$result = ee()->filemanager->save_file(
					$file_path,
					$data['dir_id'],
					array(
						'title'     => $filename,
						'path'      => $upload_prefs['server_path'].dirname($file_path),
						'file_name' => $filename,
					)
				);
				return '{filedir_'.$data['dir_id'].'}'.$filename;
			} catch (Exception $e) {
				dumper($e);exit;
			}
		}

		return '';
	}

	// ----------------------------------------------------------------

	/**
	 * Validate the field
	 * 
	 * @param  string $data  
	 * @param  bool $is_new
	 * @return void            
	 */
	public function webservice_validate($data = null, $is_new = false)
	{
		//check if the data array is correct
		if(!isset($data['filename']) || $data['filename'] == '')
		{
			return 'data['.$this->field_name.'][filename] is missing';
		}

		//check if the data array is correct
		if(!isset($data['filedata']) || $data['filedata'] == '')
		{
			return 'data['.$this->field_name.'][filedata] is missing, this is the base64 encode file string.';
		}

		//Check if the filedata a base64 string is
		if(base64_encode(base64_decode($data['filedata'], true)) !== $data['filedata'])
		{
			return 'data['.$this->field_name.'][filedata] is not a base64 endoced.';
		}

		//check if the data array is correct
		if(!isset($data['dir_id']) || $data['dir_id'] == '')
		{
			return 'data['.$this->field_name.'][dir_id] is missing';
		}

		//get the dirs
		$file_dir_check = ee('Model')->get('UploadDestination')->filter('id', $data['dir_id']);

		//no dir?
		if($file_dir_check->count() == 0)
		{
			return 'data['.$this->field_name.'][dir_id] does not exists';
		}

		//get the data
		$file_dir_check = $file_dir_check->first();

		//permission?
		$member = ee('Model')->get('Member', ee()->session->userdata('member_id'))->first();
		if(!$file_dir_check->memberHasAccess($member))
		{
			return 'You dont have permission to this dir';
		}

		//path exitst
		if(!$file_dir_check->exists())
		{
			return 'data['.$this->field_name.'][dir_id] path does not exists';
		}

		//path isWritable
		if(!$file_dir_check->isWritable())
		{
			return 'data['.$this->field_name.'][dir_id] path is not irritable';
		}

		return true;
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
	public function webservice_pre_process($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
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
	public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
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
	public function webservice_pre_process_matrix($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->_parse_data($data, $free_access, $entry_id);
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
		ee()->load->library('file_field');
		$parsed_data = ee()->file_field->parse_field($data);
		if(isset($parsed_data['model_object'])) {
            unset($parsed_data['model_object']);
        }

		return $parsed_data;
	}
}
