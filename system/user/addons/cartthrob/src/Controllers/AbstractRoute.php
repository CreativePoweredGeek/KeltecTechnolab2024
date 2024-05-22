<?php

namespace CartThrob\Controllers;

use CartThrob\Exceptions\Controllers\RouteException;

abstract class AbstractRoute
{
    /**
     * The shortname for the add-on this is attached to
     * @var string
     */
    protected string $module_name = '';

    /**
     * @return string
     * @throws RouteException
     */
    protected function getModuleName(): string
    {
        if ($this->module_name == '') {
            throw new RouteException("Your `module_name` property hasn't been setup!");
        }

        return $this->module_name;
    }
}
