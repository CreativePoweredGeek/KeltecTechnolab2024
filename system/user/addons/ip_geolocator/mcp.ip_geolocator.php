<?php


/**
 * 
 * IP Geo Locator Control Panel
 * 
 * @package     IP Geo Locator
 * @author      Anthony Mellor <anthonymellor@climbingturn.co.uk>
 * @version     2.1.0
 * @since       1.0.0
 * @copyright   Copyright (c)2019 Anthony Mellor
 * @link        https://www.climbingturn.co.uk
 * 
 */
class Ip_geolocator_mcp {



    public function index()
    {

        $vars = ['page_title' => 'IP Geo Locator'];

        return ee('View')->make('ip_geolocator:index')->render($vars);
    }


}