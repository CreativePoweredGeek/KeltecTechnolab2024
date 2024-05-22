<?php

use CartThrob\Plugins\Notification\NotificationPlugin;

class Cartthrob_dummy_notification extends NotificationPlugin
{
    /**
     * @var string
     */
    public $title = 'Dummy Notification';

    /**
     * @var string
     */
    public $short_title = 'dummy';

    /**
     * @var string
     */
    public string $type = 'dummy';

    /**
     * @var
     */
    public $overview;

    /**
     * @var
     */
    public $note;

    /**
     * @var array
     */
    public $settings = [
        [
            'name' => 'test_field',
            'short_name' => 'test_field',
            'note' => 'ct.route.notification.test_field_note',
            'type' => 'text',
        ],
        [
            'name' => 'test_field_name',
            'note' => 'ct.route.notification.test_field_name_note',
            'short_name' => 'test_field_name',
            'type' => 'text',
        ],
        [
            'name' => 'test_field_to',
            'note' => 'ct.route.notification.test_field_to_note',
            'short_name' => 'test_field_to',
            'type' => 'text',
        ],
    ];

    /**
     * Custom validation rules
     * @var string[]
     */
    protected array $rules = [];

    /**
     * @param mixed $message
     * @return bool
     */
    public function deliver(mixed $message): bool
    {
        // go on my friend
        return true;
    }
}
