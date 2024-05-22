<?php

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
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

namespace Reinos\Webservice\Service;

require_once PATH_THIRD.'reinos_webservice/config.php';

class ApiLog
{

    /**
     * Log constructor.
     */
    public function __construct()
    {}

    /**
     * Add a log
     *
     * @param string $username
     * @param array $data
     * @param string $method
     * @param string $service
     */
    public function addLog($username = '', $data = array(), $method = '', $service = '')
    {
        $need_to_log = false;

        if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug') == 1)
        {
            $need_to_log = true;
        }

        //log
        if($need_to_log)
        {
            //inset the data
            ee('Model')->make(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->set(array(
                'site_id' => ee()->config->item('site_id'),
                'time' => ee()->localize->now,
                'username' => $username,
                'service' => $service,
                'ip' => ee()->input->ip_address(),
                'log_number' => (isset($data['code']) ? $data['code'] : 0),
                'msg' => (isset($data['message']) ? $data['message'] : ''),
                'method' => $method,
                'total_queries' => ee('Database')->getLog()->getQueryCount(),
                'queries' => base64_encode(serialize(ee('Database')->getLog()->getQueries())),
                'data' => base64_encode(serialize($data))
            ))->save();

            //cleanup
            ee('Model')->get(REINOS_WEBSERVICE_SERVICE_NAME.':ApiLog')->order('log_id', 'desc')->offset(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug_max_logs'))->limit(123456789)->delete();
        }
    }

    //--------------------------------------------------------------------------------

}
