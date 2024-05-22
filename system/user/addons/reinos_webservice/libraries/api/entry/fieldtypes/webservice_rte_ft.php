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

class Webservice_rte_ft
{
	public $name = 'rte';

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
		return $this->parse_data($data, $this->field_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned for matrix
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_matrix($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->parse_data($data, $this->col_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * Preprocess the data to be returned for matrix
	 *
	 * @param  mixed $data
	 * @param bool|string $free_access
	 * @param  int $entry_id
	 * @return mixed string
	 */
	public function webservice_pre_process_grid($data = null, $free_access = false, $entry_id = 0)
	{
		return $this->parse_data($data, $this->col_data);
	}

	// ----------------------------------------------------------------------

	/**
	 * parse the data
	 */
	private function parse_data($data = null, $field_data)
	{
        ee()->load->library('typography');
        ee()->typography->initialize();
		$data = ee()->typography->parse_type(
			ee()->functions->encode_ee_tags(
				ee()->typography->parse_file_paths($data)
			),
			array(
				'text_format'	=> 'xhtml',
				'html_format'	=> $field_data['channel_settings']['channel_html_formatting'],
				'auto_links'	=> $field_data['channel_settings']['channel_auto_link_urls'],
				'allow_img_url' => $field_data['channel_settings']['channel_allow_img_urls']
			)
		);
		return $data;
	}
}
