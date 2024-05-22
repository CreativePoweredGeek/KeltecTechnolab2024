<?php

namespace CartThrob\Controllers\Cp\Routes\Actions;

use CartThrob\Controllers\Cp\AbstractActionRoute;
use CartThrob\Controllers\Cp\AbstractRoute;

class GarbageCollection extends AbstractActionRoute
{
    /**
     * @var string
     */
    protected $route_path = 'actions/garbage-collection';

    /**
     * @param $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        header('X-Robots-Tag: noindex');
        ee('cartthrob:GarbageCollectionService')->run();
        exit;

        return $this;
    }
}
