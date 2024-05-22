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
class CpJsEnd extends AbstractHook
{
    public function execute()
    {
        //get data from the lastCall if needed
        $js = $this->lastCall;

        //get the license status
        if(REINOS_WEBSERVICE_LICENSE_ENABLED)
        {
            $js = $js.' EE.'.REINOS_WEBSERVICE_MAP.'_license_enabled = true;'."\n";
            $js = $js.' EE.'.REINOS_WEBSERVICE_MAP.'_license_valid = '.(ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->hasValidLicense() ? 'true' : 'false').';'."\n";
            $js = $js.' EE.'.REINOS_WEBSERVICE_MAP.'_license_entered = '.(ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->getLicense() == '' ? 'false' : 'true').';'."\n";
            $js = $js.' EE.'.REINOS_WEBSERVICE_MAP.'_license = "'.ee(REINOS_WEBSERVICE_SERVICE_NAME.':License')->getLicense().'";'."\n";
        }

        //attach js
        $js = $js.file_get_contents(PATH_THIRD.'/'.REINOS_WEBSERVICE_MAP.'/assets/javascript/cp_js_end.js');

        return $js;
    }
}
