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

namespace Reinos\Webservice\Service;

require_once PATH_THIRD.'reinos_webservice/config.php';

class UploadDir
{
    /**
     * Log constructor.
     */
    public function __construct()
    {
        //create dir if not exists
        if(!is_dir(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('tmp_dir')) && ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('tmp_dir') != '')
        {
            @mkdir(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('tmp_dir'), 0777, true);
        }
        //chmod to write mode
        @chmod(ee(REINOS_WEBSERVICE_SERVICE_NAME.':Settings')->item('tmp_dir'), 0777);
    }


}
