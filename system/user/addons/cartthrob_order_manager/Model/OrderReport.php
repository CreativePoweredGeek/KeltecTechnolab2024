<?php

namespace CartThrob\OrderManager\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * OrderReport Model
 */
class OrderReport extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_order_manager_reports';

    protected $id;
    protected $report_title;
    protected $type;
    protected $settings;

    protected static $_typed_columns = [
        'settings' => 'serialized',
    ];
}
