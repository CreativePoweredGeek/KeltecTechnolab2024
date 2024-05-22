<?php

namespace CartThrob\Controllers\Cp\Routes\ImportExport;

use CartThrob\Controllers\Cp\AbstractRoute;

class Download extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'import-export/download';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        ee()->load->helper('download');
        force_download('cartthrob_settings.txt', serialize($this->settings->settings('cartthrob')));
        exit;
    }
}
