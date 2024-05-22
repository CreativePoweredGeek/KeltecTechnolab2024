<?php

namespace CartThrob\Model;

use ExpressionEngine\Service\Model\Model;

/**
 * CartThrob NotificationEvent Model
 */
class NotificationEvent extends Model
{
    protected static $_primary_key = 'id';
    protected static $_table_name = 'cartthrob_notification_events';

    protected $id;
    protected $application;
    protected $notification_event;
}
