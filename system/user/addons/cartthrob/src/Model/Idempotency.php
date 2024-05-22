<?php

namespace CartThrob\Model;

class Idempotency extends AbstractModel
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_idempotency';

    protected $id;
    protected $member_id;
    protected $guid;
    protected $status;
    protected $return_path;
    protected $payload;
    protected $create_date;

    protected static $_relationships = [
        'Member' => [
            'type' => 'BelongsTo',
            'model' => 'ee:Member',
            'from_key' => 'member_id',
            'to_key' => 'member_id',
        ],
    ];
}
