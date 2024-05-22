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

class Webservice_ansel_ft
{
	public $name = 'ansel';
	
	// ----------------------------------------------------------------

	/**
	 * Preps the data for saving
	 *
	 * Hint: you only have to format the data likes the publish page
	 * 
	 * @param  mixed $data  
	 * @param  bool $is_new
	 * @param  int $entry_id
	 * @return mixed string            
	 */
//	public function webservice_save($data = null, $is_new = false, $entry_id = 0)
//	{
//
//		return $data;
//	}


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
        try {
            $results = ee('Model')->get('ansel:Image')->filter('content_type', 'channel')->filter('content_id', $entry_id)->filter('field_id', $this->field_id);

            $return_data = array();

            if($results->count() > 0)
            {
                foreach($results->all() as $file)
                {
                    $tmp = $file->toArray();
                    $tmp['sourceUrl'] = $file->getSourceUrl();
                    $tmp['basename'] = $file->getBasename();
                    $tmp['url'] = str_replace(')}', '', $file->getUrl());
                    $tmp['highQualityUrl'] = $file->getHighQualityUrl();
                    $tmp['thumbPath'] = $file->getThumbPath();
                    $tmp['thumbUrl'] = $file->getThumbUrl();
                    $tmp['eeThumbUrl'] = $file->getEeThumbUrl();
                    $tmp['originalUrl'] = $file->getOriginalUrl();

                    $return_data[] = $tmp;
                }
            }

            $data = $return_data;
        }
        catch (Exception $e) {}


        return $data;
    }
}
