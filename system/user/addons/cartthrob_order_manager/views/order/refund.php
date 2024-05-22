<?php

    $data = [
        'base_url' => ee('CP/URL')->make($base_url . '/refund'),
        'cp_page_title' => lang('ct.om.refunds'),
        'save_btn_text' => lang('submit'),
        'save_btn_text_working' => lang('submit')
    ];

    $data['sections'] = [
        [
            [
                'title' => 'type',
                'fields' => [
                    'type' => [
                        'type' => 'select',
                        'choices' => [
                            'full' => 'Full Refund',
                            'partial' => 'Partial Refund'
                        ],
                        'group_toggle' => [
                            'full' => 'full_field_group',
                            'partial' => 'partial_field_group'
                        ]
                    ]
                ]
            ]
        ],
        'full_field_group' => [
            'group' => 'full_field_group',
            'label' => lang('ct.om.full_refund'),
            'settings' => [
                [
                    'title' => lang('ct.om.total'),
                    'fields' => [
                        'total' => [
                            'type' => 'text',
                            'disabled' => true,
                            'value' => $view['orders_total']
                        ],
                    ],
                ],
                [
                    'fields' => [
                        'total' => [
                            'type' => 'hidden',
                            'value' => $view['orders_total']
                        ],
                        'id' => [
                            'type' => 'hidden',
                            'value' => $view['entry_id']
                        ],
                    ],
                ],
            ]
        ],
        'partial_field_group' => [
            'group' => 'partial_field_group',
            'label' => lang('ct.om.partial_refund'),
            'settings' => [
                [
                    'fields' => [
                        'notice' => [
                            'type' => 'html',
                            'content' => ee('CP/Alert')->makeInline('security')
                                ->asAttention()
                                ->withTitle(lang('ct.om.important'))
                                ->addToBody(lang('ct.om.refund_inventor_adjustment_note'))
                                ->cannotClose()
                                ->render()
                        ],
                        'id' => [
                            'type' => 'hidden',
                            'value' => $view['entry_id']
                        ],
                    ]
                ],
                [
                    'title' => lang('ct.om.refund_subtotal'),
                    'fields' => [
                        'subtotal' => [
                            'type' => 'text',
                            'value' => $view['orders_subtotal']
                        ],
                    ],
                ],
                [
                    'title' => lang('ct.om.refund_tax'),
                    'fields' => [
                        'tax' => [
                            'type' => 'text',
                            'value' => $view['orders_tax']
                        ],
                    ],
                ],
                [
                    'title' => lang('ct.om.refund_shipping'),
                    'fields' => [
                        'shipping' => [
                            'type' => 'text',
                            'value' => $view['orders_shipping']
                        ],
                    ],
                ],
            ]
        ]
    ];

    if(!empty($refund_form_extras['full_field_group'])) {
        $data['sections']['full_field_group'] = array_merge_recursive($refund_form_extras['full_field_group'], $data['sections']['full_field_group']);
    }

    if(!empty($refund_form_extras['partial_field_group'])) {
        $data['sections']['partial_field_group'] = array_merge_recursive($data['sections']['partial_field_group'], $refund_form_extras['partial_field_group']);
    }
?>

<?= ee('View')->make('ee:_shared/form')->render($data); ?>