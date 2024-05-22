<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

class NotificationLog extends Model
{
    protected static $_validation_rules = [
        'title' => 'required',
        'event' => 'required',
        'type' => 'required',
    ];

    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_notification_log';

    protected $id;
    protected $title;
    protected $event;
    protected $type;
    protected $template;
    protected $status_start;
    protected $status_end;
    protected $settings;
    protected $variables;
    protected $send_date;
    protected $member_id;
    protected $order_id;

    protected static $_events = [
        'beforeInsert',
        'beforeUpdate',
    ];

    protected static $_relationships = [
        'Member' => [
            'type' => 'BelongsTo',
            'model' => 'ee:Member',
            'from_key' => 'member_id',
            'to_key' => 'member_id',
        ],
        'Order' => [
            'type' => 'BelongsTo',
            'model' => 'ee:ChannelEntry',
            'from_key' => 'order_id',
            'to_key' => 'entry_id',
        ],
    ];

    protected static $_typed_columns = [
        'variables' => 'json',
        'settings' => 'json',
    ];

    /**
     * Preprocess create
     * @return void
     */
    public function onBeforeInsert()
    {
        if ($this->getProperty('send_date') == '') {
            $this->setProperty('send_date', ee()->localize->now);
        }

        if ($this->getProperty('order_id') == '') {
            $variables = $this->getProperty('variables');
            $order_id = $variables['order_id'] ?? null;
            $this->setProperty('order_id', $order_id);
        }

        if ($this->getProperty('member_id') == '') {
            $member_id = $variables['member_id'] ?? null;
            $this->setProperty('member_id', ee()->session->userdata('member_id'));
        }
    }
}
