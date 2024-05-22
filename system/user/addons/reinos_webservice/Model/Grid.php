<?php

/**
 * shortcut model
 *
 * @package		webservice
 * @category	Modules
 * @author		Rein de Vries <info@reinos.nl>
 * @link		http://reinos.nl/add-ons/entry-api
 * @license  	http://reinos.nl/add-ons/commercial-license
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 */

namespace Reinos\Webservice\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class Grid_{n} extends Model {

    protected static $_primary_key = 'row_id';
    protected static $_table_name = 'channel_grid_field_{n}';

    protected static $_relationships = array(
        'Entry' => array(
            'type'      => 'BelongsTo',
            'model'     => 'ee:ChannelEntry',
            'weak'    => true
        ),
    );

    protected $row_id;
    protected $entry_id;
    protected $row_order;
    {fields}
}
