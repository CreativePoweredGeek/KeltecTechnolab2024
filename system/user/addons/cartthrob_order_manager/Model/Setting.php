<?php

namespace CartThrob\OrderManager\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Setting Model
 */
class Setting extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_order_manager_settings';

    protected $id;
    protected $site_id;
    protected $key;
    protected $value;

    protected static $_typed_columns = [
        'value' => 'serialized',
    ];
}
