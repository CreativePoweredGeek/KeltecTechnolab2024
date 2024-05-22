<?php

namespace CartThrob\Forms\TaxDb;

use CartThrob\Forms\AbstractForm;

class Remove extends AbstractForm
{
    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.tax.standalone_tax_database.confirm_removal',
                'desc' => 'ct.route.tax.standalone_tax_database.confirm_removal_note',
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
