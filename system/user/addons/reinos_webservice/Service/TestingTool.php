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

class TestingTool
{

    public $post_data;

    /**
     * Constructor
     */
    public function __construct()
    {
        ee()->load->model('webservice_model');
        ee()->webservice_model->insert_user_agent();
        ini_set( 'soap.wsdl_cache_enabled', 0);
    }

    // ----------------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function init()
    {
        //set return var
        $return = '';

        //remove the prexis and clean the post data
        $this->convertPost();

        //what type we have to serve
        if(isset($this->post_data['type']) && isset($this->post_data['method']) && isset($this->post_data['path']))
        {
            switch($this->post_data['type'])
            {
                case 'soap': $return = $this->soap($this->post_data['method']); break;
                case 'xmlrpc': $return = $this->xmlrpc($this->post_data['method']); break;
                case 'rest': $return = $this->rest($this->post_data['method']); break;
                case 'custom': $return = $this->custom($this->post_data['method']); break;
            }
        }

        return $return;
    }

    // ----------------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function soap($type = '')
    {
        $client = new \SoapClient($this->post_data['path'], array('trace' => 1));

        try
        {
            $reponse = $client->{$this->post_data['method']}(array(
                'session_id' => ee()->session->userdata('session_id')
            ), $this->format_data());


        }
        catch (Exception $e)
        {
            var_dump($e);
            return array(
                $client->__getLastRequestHeaders(),
                $client->__getLastRequest(),
                $client->__getLastResponseHeaders(),
                $client->__getLastResponse()
            );
        }

        return array(
            'response' => $reponse,
            'request' => "
\$client = new SoapClient(".$this->post_data['path'].", array('trace' => 1));

\$reponse = \$client->".$this->post_data['method']."(array(
		'session_id' => ".ee()->session->userdata('session_id')."
	), 
	".print_r($this->format_data(), true).");
			",
            'service' => 'SOAP'
        );
    }

    // ----------------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function xmlrpc($type = '')
    {
        $c = new \PhpXmlRpc\Client($this->post_data['path']);
        $encoder = new \PhpXmlRpc\Encoder();

        $x = new \PhpXmlRpc\Request($this->post_data['method'], array(
            $encoder->encode(array(
                'session_id' => ee()->session->userdata('session_id')
            )),
            $encoder->encode($this->format_data()),
        ));

        $c->return_type = 'phpvals';
        $r = $c->send($x);

        return array(
            'response' => $r,
            'request' => "
\$c = new PhpXmlRpc\\Client(\$this->post_data['path']);

\$x = new PhpXmlRpc\\Request(\$this->post_data['method'], array(
	PhpXmlRpc\\Encoder::encode(array(
		'session_id' => ee()->session->userdata('session_id')
	)),
	PhpXmlRpc\\Encoder::encode(".print_r($this->format_data(), true)."),
));

\$c->return_type = 'phpvals';
\$r = \$c->send(\$x);
			",
            'service' => 'XMLRPC'
        );
    }

    // ----------------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function rest($type = '')
    {
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Curl')->option('FAILONERROR', false);
        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Curl')->create($this->post_data['path']);

        $data = array(
            'auth' => array(
                'session_id' => ee()->session->userdata('session_id')
            ),
            'data' => $this->format_data()
        );

        ee(REINOS_WEBSERVICE_SERVICE_NAME.':Curl')->post(http_build_query($data));

        $return = ee(REINOS_WEBSERVICE_SERVICE_NAME.':Curl')->execute();

        return array(
            'response' => $return,
            "request" => "
ee('".REINOS_WEBSERVICE_SERVICE_NAME.":Curl')->option('FAILONERROR', false);
ee('".REINOS_WEBSERVICE_SERVICE_NAME.":Curl')->create(".$this->post_data['path'].");

\$data = array(
	'auth' => array(
		'session_id' => ".ee()->session->userdata('session_id')."
	),
	'data' => ".print_r($this->format_data(), true)."
);

ee('".REINOS_WEBSERVICE_SERVICE_NAME.":Curl')->post(http_build_query(\$data));

\$response = ee('".REINOS_WEBSERVICE_SERVICE_NAME.":Curl')->execute();
			",
            'service' => 'REST',
            'url' => $this->post_data['path'].'?'.str_replace(array('%5B', '%5D'), array('[', ']'), http_build_query($data))
        );
    }

    // ----------------------------------------------------------------------

    /**
     * Insert the settings to the database
     *
     * @param none
     * @return void
     */
    public function custom($type = '')
    {
        ee()->load->library('webservice_public_methods');

        $reponse = ee()->webservice_public_methods->{$this->post_data['method']}(array(
            'auth' => array(
                'session_id' => ee()->session->userdata('session_id')
            ),
            'data' => $this->format_data()
        ));

        return array(
            'response' => $reponse,
            'service' => 'CUSTOM'
        );
    }

    function format_data()
    {
        $data = array(
            'site_id' => ee()->config->item('site_id')
        );

        $extra = isset($this->post_data['field:extra']) && $this->post_data['field:extra'] != '' ? eval("return ".$this->post_data['field:extra'].';') : array() ;
        unset($this->post_data['field:extra']);

        foreach($this->post_data as $key=>$val)
        {
            if(preg_match('/field:/', $key, $match))
            {
                $key_correctly = str_replace($match[0], '', $key);
                $data[$key_correctly] = $this->post_data[$key];
            }
        }

        //attach extra data
        return array_merge($data, $extra);
    }

    function convertPost()
    {
        foreach($_POST as $key=>$val)
        {
            $new_key = str_replace(array('soap:', 'xmlrpc:', 'rest:', 'custom:'), '', $key);
            $this->post_data[$new_key] = ee()->input->post($key);
        }

    }

}
