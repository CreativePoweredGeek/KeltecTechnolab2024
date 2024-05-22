<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Discounts extends AbstractForm
{
    public function generate(): array
    {
        $edit_url = ee('CP/URL')->make($this->base_url . '/discounts/set-channel');
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'the_channel_that_stores_discounts_details',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<a href="' . $edit_url . '">(' . $this->getChannelId() . ') ' . $this->getChannelTitle() . '</a>',
                    ],
                ],
            ],
            [
                'title' => 'discount_type',
                'desc' => 'discount_type_note',
                'fields' => [
                    'discount_type' => [
                        'name' => 'discount_type',
                        'type' => 'select',
                        'value' => $this->get('discount_type'),
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
            [
                'title' => 'exempt_discount_from_tax',
                'desc' => 'exempt_discount_from_tax_note',
                'fields' => [
                    'exempt_discount_from_tax' => [
                        'name' => 'exempt_discount_from_tax',
                        'type' => 'select',
                        'value' => $this->get('exempt_discount_from_tax'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        return ['discounts_settings_form_description' => $form];
    }
}
