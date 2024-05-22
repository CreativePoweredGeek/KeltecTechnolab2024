<?php

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

namespace Reinos\Webservice\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class ApiLog extends Model {

    protected static $_primary_key = 'log_id';
    protected static $_table_name = REINOS_WEBSERVICE_MAP.'_api_logs';

    protected $log_id;
    protected $site_id;
    protected $username;
    protected $time;
    protected $service;
    protected $ip;
    protected $log_number;
    protected $method;
    protected $msg;
    protected $total_queries;
    protected $queries;
    protected $data;
    

    //add log
    public function addLog() 
    {
        $need_to_log = false;

        if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug') == 1)
        {
            $need_to_log = true;
        }

        //global error?
//		if(!isset($servicedata->logging) && ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug') == 1)
//		{
//			$need_to_log = true;
//		}

        //no global logging
//		else if(!isset($servicedata->logging))
//		{
//			$need_to_log = false;
//		}

        //log all
//		else if($servicedata->logging == 2)
//		{
//			$need_to_log = true;
//		}

        //only success
//		else if($servicedata->logging == 1 && $data['code_http'] == 200)
//		{
//			$need_to_log = true;
//		}

        //log
        if($need_to_log)
        {
            ee()->db->insert('webservice_api_logs', array(
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
            ));

            //delete the old records
            $this->_delete_old_logs();
        }
    }

    // ----------------------------------------------------------------------

    /**
     * Delete the log records
     *
     * @return void
     */
    private function _delete_old_logs()
    {
        ee()->db->order_by('log_id', 'desc');
        ee()->db->select('log_id');
        ee()->db->limit(100000000000000, ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug_max_logs'));
        $result = ee()->db->get(REINOS_WEBSERVICE_MAP.'_api_logs');
        if($result->num_rows() > 0)
        {
            $log_ids = array();
            foreach($result->result() as $row)
            {
                $log_ids[] = $row->log_id;
            }

            ee()->db->where_in('log_id', $log_ids);
            ee()->db->delete(REINOS_WEBSERVICE_MAP.'_api_logs');
        }
    }
}
