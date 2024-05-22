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

/**
 * Include the config file
 */
require_once PATH_THIRD.'reinos_webservice/config.php';

class Reinos_webservice {
    /**
     * the Actions
     *
     * @return unknown_type
     */
    public function act_route()
    {
        //needed in some cases
        header('Access-Control-Allow-Origin: *');

        // Load Library
        if (class_exists(REINOS_WEBSERVICE_CLASS.'_ACT') != TRUE) include 'act.'.REINOS_WEBSERVICE_MAP.'.php';

        //set the class name
        $class = REINOS_WEBSERVICE_CLASS.'_ACT';

        //call it
        $ACT = new $class;

        $ACT->init();

        exit;
    }

    // ----------------------------------------------------------------

    public function __call($name, $args)
    {
        $class = '\\Reinos\\'.REINOS_WEBSERVICE_NAMESPACE_CLASS.'\\Core\\Tag\\' . ee(REINOS_WEBSERVICE_SERVICE_NAME.':Helper')->studlyCase($name);

        //class not exists
        if (class_exists($class) === false) {
            ee()->TMPL->log_item("Tag Not Processed: Method Inexistent or Module Not Installed");

            $error  = ee()->lang->line('error_tag_module_processing');
            $error .= '<br /><br />';
            $error .= htmlspecialchars(LD);
            $error .= 'exp:'.REINOS_WEBSERVICE_MAP.':'.$name;
            $error .= htmlspecialchars(RD);
            $error .= '<br /><br />';
            $error .= str_replace('%x', REINOS_WEBSERVICE_MAP, str_replace('%y', $name, ee()->lang->line('error_fix_module_processing')));
            ee()->output->fatal_error($error);
        }

        //call the class
        if (empty($args)) {
            $tag = new $class(ee()->TMPL->tagdata, ee()->TMPL->tagparams);
        } else {
            $tag = new $class($args[0], $args[1]);
        }

        //return it when calling the parse function
        return $tag->parse();
    }
}
