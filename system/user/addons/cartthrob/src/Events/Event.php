<?php

namespace CartThrob\Events;

class Event
{
    public const TYPE_LOW_STOCK = 'low_stock';
    public const TYPE_STATUS_CHANGE = 'status_change';
    public const TYPE_ORDER_PROCESSING = 'processing';
    public const TYPE_ORDER_COMPLETED = 'completed';
    public const TYPE_ORDER_DECLINED = 'declined';
    public const TYPE_ORDER_FAILED = 'failed';
}
