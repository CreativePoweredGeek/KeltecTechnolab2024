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

class Webservice_ce_heading_element
{
    public $name = 'heading';

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
    public function webservice_ce_save($data = null, $is_new = false, $entry_id = 0)
    {
        return $data;
    }

    // ----------------------------------------------------------------------

    /**
     * Handles any custom logic after an entry is saved.
     *
     * @param  mixed $data
     * @param  array $inserted_data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_post_save($data = null, $inserted_data = array(), $entry_id = 0)
    {

    }

    // ----------------------------------------------------------------

    /**
     * Validate the field
     *
     * @param  mixed $data
     * @param  bool $is_new
     * @return bool
     */
    public function webservice_ce_validate($data = null, $is_new = false, $entry_id = 0)
    {
        //$this->validate_error = '';
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
    public function webservice_ce_pre_process($data = null, $entry_id = 0)
    {
        $heading_data = unserialize($data);
        return '<'.$heading_data['heading_data']['heading'].'>'.$heading_data['heading_data']['content'].'</'.$heading_data['heading_data']['heading'].'>';
    }

    // ----------------------------------------------------------------------

    /**
     * delete field data, before the entry is deleted
     *
     * Hint: EE will mostly do everything for you, because the delete() function will trigger
     *
     * @param  mixed $data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_delete($data = null, $entry_id = 0)
    {

    }

    // ----------------------------------------------------------------------

    /**
     * delete field data, after the entry is deleted
     *
     * Hint: EE will mostly do everything for you, because the delete() function will trigger
     *
     * @param  mixed $data
     * @param  int $entry_id
     * @return void
     */
    public function webservice_ce_post_delete($data = null, $entry_id = 0)
    {

    }
}
