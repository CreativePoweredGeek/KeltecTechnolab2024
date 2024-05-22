<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * CartthrobStatus Model
 */
class CartthrobStatus extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_status';

    protected $id;
    protected $entry_id;
    protected $session_id;
    protected $status;
    protected $inventory_processed;
    protected $discounts_processed;
    protected $error_message;
    protected $transaction_id;
    protected $cart;
    protected $cart_id;

    protected static $_relationships = [
        'Entry' => [
            'model' => 'ee:ChannelEntry',
            'type' => 'belongsTo',
            'from_key' => 'entry_id',
            'to_key' => 'entry_id',
            'inverse' => [
                'name' => 'CartthrobStatus',
                'type' => 'HasOne',
                'from_key' => 'entry_id',
                'to_key' => 'entry_id',
            ],
        ],
    ];

    protected function get__mapped_order_status()
    {
        if (ee()->cartthrob->store->config('orders_status_' . $this->status) !== false) {
            return ee()->cartthrob->store->config('orders_status_' . $this->status);
        }

        if (ee()->cartthrob->store->config('orders_' . $this->status . '_status') !== false) {
            return ee()->cartthrob->store->config('orders_' . $this->status . '_status');
        }

        return ee()->cartthrob->store->config('orders_default_status');
    }
}
