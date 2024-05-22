<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Notifications extends AbstractForm
{
    protected $log_notification_options = [
        'no' => 'no',
        'log_only' => 'log_only',
        'log_and_send' => 'log_and_send',
    ];

    public function __construct()
    {
        parent::__construct();

        foreach ($this->log_notification_options as $key => $value) {
            $this->log_notification_options[$key] = strip_tags(lang($value));
        }
    }

    public function generate(): array
    {
        $form = [
            [
                'title' => 'log_email',
                'desc' => 'log_email_note',
                'fields' => [
                    'log_email' => [
                        'name' => 'log_email',
                        'type' => 'select',
                        'value' => $this->get('log_email'),
                        'required' => true,
                        'choices' => $this->log_notification_options,
                    ],
                ],
            ],
            [
                'title' => 'log_notifications',
                'desc' => 'log_notifications_note',
                'fields' => [
                    'log_notifications' => [
                        'name' => 'log_notifications',
                        'type' => 'select',
                        'value' => $this->get('log_notifications'),
                        'required' => true,
                        'choices' => $this->log_notification_options,
                    ],
                ],
            ],
        ];

        return [$form];
    }
}
