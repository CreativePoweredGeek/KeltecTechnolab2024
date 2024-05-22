<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * Permission Item Model
 */
class PermissionItem extends AbstractModel
{
    protected static $_validation_rules = [
        'title' => 'required',
        'status' => 'required',
    ];

    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_permission_items';

    protected $id;
    protected $status;
    protected $access_overview;
    protected $description;
    protected $title;
}
