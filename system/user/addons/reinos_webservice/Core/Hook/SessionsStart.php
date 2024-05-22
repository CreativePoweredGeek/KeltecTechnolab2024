<?php

namespace Reinos\Webservice\Core\Hook;

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
class SessionsStart extends AbstractHook
{
    public function execute($session)
    {
        if(REQ == 'PAGE' || REQ == 'ACTION')
        {
            //get the action id
            ee()->config->_global_vars[REINOS_WEBSERVICE_MAP.'_action_url'] = '?ACT='.ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->fetch_action_id(REINOS_WEBSERVICE_CLASS, 'act_route');
        }

        //load the helper class
        ee()->load->helper('webservice_helper');

        //just an page request?
        if (REQ == 'PAGE' && !empty($session))
        {
            //get the trigger
            $url_trigger = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('url_trigger', 'webservice');

            //is the first segment 'webservice'
            $is_webservice = ee()->uri->segment(1) == $url_trigger ? true : false;

            //is the request a page and is the first segment webservice?
            //than we need to trigger te services
            if($is_webservice)
            {
                //set the session to the var
                if(!isset(ee()->session))
                {
                    ee()->set('session', $session);
                }

                //MySQL cache?
                if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('cache') == 1)
                {
                    ee()->db->cache_on();
                }

                //set agent if missing
                $_SERVER['HTTP_USER_AGENT'] = ee()->input->user_agent() == false ? '0' : ee()->input->user_agent();

                //debug?
                if(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('debug'))
                {
                    //show better error reporting
                    error_reporting(E_ALL);
                    ini_set('display_errors', '1');

                    //set the DB to save the queries
                    ee()->db->save_queries = true;
                    ee('Database')->getLog()->saveQueries(true);
                }

                //based on the type, run the correct class
                switch(ee()->uri->segment(2))
                {
                    //SOAP service
                    case 'soap':
                        include_once PATH_THIRD .'reinos_webservice/libraries/services/webservice_soap.php';
                        new \Webservice_soap;
                        break;

                    //XML-RPC service
                    case 'xmlrpc':
                        include_once PATH_THIRD .'reinos_webservice/libraries/services/webservice_xmlrpc.php';
                        new \Webservice_xmlrpc;
                        break;

                    //REST services
                    case 'rest':
                        include_once PATH_THIRD .'reinos_webservice/libraries/services/webservice_rest.php';
                        new \Webservice_rest;
                        break;

                    //Test the services
                    case 'test':
                        include_once PATH_THIRD .'reinos_webservice/tests/webservice_test.php';
                        new \Webservice_test;
                        break;
                }

                //stop the whole process because we will not show futher more
                ee()->extensions->end_script = true;
                die();
            }
        }
    }
}
