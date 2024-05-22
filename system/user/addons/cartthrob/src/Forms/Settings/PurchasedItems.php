<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class PurchasedItems extends AbstractForm
{
    protected $rules = [
        'save_purchased_items' => 'validateOrdersSaved',
    ];

    public function generate(): array
    {
        $edit_url = ee('CP/URL')->make($this->base_url . '/purchased-items/set-channel');
        $order_channel_id = $this->data['purchased_items_channel'] ?? $this->channel_id ?? null;
        $field_no_results = [
            'text' => lang('ct.route.nothing_here'),
            'link_href' => ee('CP/URL')->make('fields', ['group_id' => $this->getChannelFieldGroupId($order_channel_id)])->compile(),
            'link_text' => lang('ct.route.add_channel_field'),
        ];
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'ct.route.the_channel_that_stores_purchased_item_details',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<a href="' . $edit_url . '">(' . $this->getChannelId() . ') ' . $this->getChannelTitle() . '</a>',
                    ],
                ],
            ],
            [
                'title' => 'save_purchased_items',
                'desc' => '',
                'fields' => [
                    'save_purchased_items' => [
                        'name' => 'save_purchased_items',
                        'type' => 'select',
                        'value' => $this->get('save_purchased_items'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'save_packages_too',
                'desc' => 'save_packages_too_note',
                'fields' => [
                    'save_packages_too' => [
                        'name' => 'save_packages_too',
                        'type' => 'select',
                        'value' => $this->get('save_packages_too'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'purchased_items_title_prefix',
                'desc' => '',
                'fields' => [
                    'purchased_items_title_prefix' => [
                        'name' => 'purchased_items_title_prefix',
                        'type' => 'text',
                        'value' => $this->get('purchased_items_title_prefix'),
                    ],
                ],
            ],
        ];

        $form = ['general_settings_header' => $form];

        $form['ct.route.header.purchased_items_status'] = [
            [
                'title' => 'orders_default_status',
                'desc' => 'purchased_items_set_status',
                'fields' => [
                    'purchased_items_default_status' => [
                        'name' => 'purchased_items_default_status',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_default_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_processing_status',
                'desc' => '',
                'fields' => [
                    'purchased_items_processing_status' => [
                        'name' => 'purchased_items_processing_status',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_processing_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_declined_status',
                'desc' => '',
                'fields' => [
                    'purchased_items_declined_status' => [
                        'name' => 'purchased_items_declined_status',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_declined_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_failed_status',
                'desc' => '',
                'fields' => [
                    'purchased_items_failed_status' => [
                        'name' => 'purchased_items_failed_status',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_failed_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_pending',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_pending' => [
                        'name' => 'purchased_items_status_pending',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_pending'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_expired',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_expired' => [
                        'name' => 'purchased_items_status_expired',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_expired'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_canceled',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_canceled' => [
                        'name' => 'purchased_items_status_canceled',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_canceled'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_voided',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_voided' => [
                        'name' => 'purchased_items_status_voided',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_voided'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_refunded',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_refunded' => [
                        'name' => 'purchased_items_status_refunded',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_refunded'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_reversed',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_reversed' => [
                        'name' => 'purchased_items_status_reversed',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_reversed'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_offsite',
                'desc' => '',
                'fields' => [
                    'purchased_items_status_offsite' => [
                        'name' => 'purchased_items_status_offsite',
                        'type' => 'select',
                        'value' => $this->get('purchased_items_status_offsite'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
        ];

        $fields = [];
        $todo = [
            'purchased_items_id_field',
            'purchased_items_quantity_field',
            'purchased_items_price_field',
            'purchased_items_order_id_field',
            'purchased_items_package_id_field',
            'purchased_items_sub_id_field',
            'purchased_items_discount_field',
            'purchased_items_license_number_field',
        ];
        foreach ($todo as $field) {
            $fields[] = [
                'title' => $field,
                'desc' => '',
                'fields' => [
                    $field => [
                        'name' => $field,
                        'type' => 'select',
                        'value' => $this->get($field),
                        'required' => true,
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ];
        }

        $fields[] = [
            'title' => 'purchased_items_license_number_type',
            'desc' => '',
            'fields' => [
                'purchased_items_license_number_type' => [
                    'name' => 'purchased_items_license_number_type',
                    'type' => 'select',
                    'value' => $this->get('purchased_items_license_number_type'),
                    'required' => true,
                    'choices' => ['uuid' => lang('license_number_uuid')],
                ],
            ],
        ];

        $form['ct.route.header.purchased_items_fields'] = $fields;

        return $form;
    }
}
