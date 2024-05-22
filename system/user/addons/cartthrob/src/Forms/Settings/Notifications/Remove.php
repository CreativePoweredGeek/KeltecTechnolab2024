<?php

namespace CartThrob\Forms\Settings\Notifications;

use CartThrob\Forms\AbstractForm;

class Remove extends AbstractForm
{
    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.notification.confirm_removal',
                'desc' => 'ct.route.notification.confirm_removal_note',
                'caution' => true,
                'fields' => [
                    'confirm' => [
                        'name' => 'confirm',
                        'short_name' => 'confirm',
                        'type' => 'yes_no',
                    ],
                ],
            ],
        ];

        $form = [$form];

        return $form;
    }
}
