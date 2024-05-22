<?php

namespace HopStudios\HopMinifizer\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

/**
 * Hop Studios Config Model class
 *
 * @package   Hop Studios
 * @author    Hop Studios <tech@hopstudios.com>
 * @copyright Copyright (c) Copyright (c) 2019 Hop Studios
 */

class Config extends Model
{
    protected static $_primary_key = 'setting_id';
    protected static $_table_name = 'hop_minifizer_settings';

    protected static $_typed_columns = [
        'value' => 'base64Serialized',
    ];

    protected $setting_id;
    protected $setting_name;
    protected $value;
}