<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Orders extends AbstractForm
{
    /**
     * @var string[]
     */
    protected $rules = [
        'last_order_number' => 'required|isNatural',
        'orders_default_status' => 'required',
        'orders_processing_status' => 'required',
        'orders_failed_status' => 'required',
        'orders_declined_status' => 'required',
        'orders_status_pending' => 'required',
        'orders_status_expired' => 'required',
        'orders_status_canceled' => 'required',
        'orders_status_voided' => 'required',
        'orders_status_refunded' => 'required',
        'orders_status_reversed' => 'required',
        'orders_status_offsite' => 'required',
        'orders_items_field' => 'required',
        'orders_subtotal_field' => 'required',
        'orders_subtotal_plus_tax_field' => 'required',
        'orders_tax_field' => 'required',
        'orders_discount_field' => 'required',
        'orders_total_field' => 'required',
        'orders_transaction_id' => 'required',
        'orders_coupon_codes' => 'required',
        'orders_payment_gateway' => 'required',
        'orders_error_message_field' => 'required',
        'orders_language_field' => 'required',
        'orders_customer_name' => 'required',
        'orders_customer_email' => 'required',
    ];

    protected $orders_async_method_options = [
        0 => 'disabled',
        1 => 'orders_async_method_http',
        2 => 'orders_async_method_cron',
    ];

    public function __construct()
    {
        parent::__construct();

        foreach ($this->orders_async_method_options as $key => $value) {
            $this->orders_async_method_options[$key] = lang($value);
        }
    }

    /**
     * @return \array[][]
     */
    public function generate(): array
    {
        $edit_url = ee('CP/URL')->make($this->base_url . '/orders/set-channel');
        $order_channel_id = $this->data['orders_channel'] ?? $this->channel_id ?? null;
        $field_no_results = [
            'text' => lang('ct.route.nothing_here'),
            'link_href' => ee('CP/URL')->make('fields', ['group_id' => $this->getChannelFieldGroupId($order_channel_id)])->compile(),
            'link_text' => lang('ct.route.add_channel_field'),
        ];

        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'ct.route.form.orders_channel_name.note',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<a href="' . $edit_url . '">(' . $this->getChannelId() . ') ' . $this->getChannelTitle() . '</a>',
                    ],
                ],
            ],
            [
                'title' => 'save_orders',
                'desc' => 'orders_saving_instructions',
                'fields' => [
                    'save_orders' => [
                        'name' => 'save_orders',
                        'type' => 'select',
                        'value' => $this->get('save_orders'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'order_numbers',
                'desc' => 'order_numbers_instructions',
                'fields' => [
                    'orders_sequential_order_numbers' => [
                        'name' => 'orders_sequential_order_numbers',
                        'type' => 'select',
                        'value' => $this->get('orders_sequential_order_numbers'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'orders_title_prefix',
                'desc' => '',
                'fields' => [
                    'orders_title_prefix' => [
                        'name' => 'orders_title_prefix',
                        'type' => 'text',
                        'value' => $this->get('orders_title_prefix'),
                    ],
                ],
            ],
            [
                'title' => 'orders_title_suffix',
                'desc' => '',
                'fields' => [
                    'orders_title_suffix' => [
                        'name' => 'orders_title_suffix',
                        'type' => 'text',
                        'value' => $this->get('orders_title_suffix'),
                    ],
                ],
            ],
            [
                'title' => 'orders_url_title_prefix',
                'desc' => '',
                'fields' => [
                    'orders_url_title_prefix' => [
                        'name' => 'orders_url_title_prefix',
                        'type' => 'text',
                        'value' => $this->get('orders_url_title_prefix'),
                    ],
                ],
            ],
            [
                'title' => 'orders_url_title_suffix',
                'desc' => '',
                'fields' => [
                    'orders_url_title_suffix' => [
                        'name' => 'orders_url_title_suffix',
                        'type' => 'text',
                        'value' => $this->get('orders_url_title_suffix'),
                    ],
                ],
            ],
            [
                'title' => 'orders_convert_country_code',
                'desc' => 'orders_convert_country_code_instructions',
                'fields' => [
                    'orders_convert_country_code' => [
                        'name' => 'orders_convert_country_code',
                        'type' => 'select',
                        'value' => $this->get('orders_convert_country_code'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_last_order_number',
                'desc' => 'global_settings_last_order_number_description',
                'fields' => [
                    'last_order_number' => [
                        'name' => 'last_order_number',
                        'type' => 'text',
                        'required' => true,
                        'value' => $this->get('last_order_number'),
                    ],
                ],
            ],
            [
                'title' => 'update_inventory_when_editing_order',
                'desc' => 'update_inventory_when_editing_order_description',
                'fields' => [
                    'update_inventory_when_editing_order' => [
                        'name' => 'update_inventory_when_editing_order',
                        'type' => 'select',
                        'value' => $this->get('update_inventory_when_editing_order'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        $form = ['general_settings_header' => $form];

        $form['orders_status_field'] = [
            [
                'title' => 'orders_default_status',
                'desc' => 'orders_set_status',
                'fields' => [
                    'orders_default_status' => [
                        'name' => 'orders_default_status',
                        'type' => 'select',
                        'value' => $this->get('orders_default_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_processing_status',
                'desc' => 'orders_set_processing_status',
                'fields' => [
                    'orders_processing_status' => [
                        'name' => 'orders_processing_status',
                        'type' => 'select',
                        'value' => $this->get('orders_processing_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_failed_status',
                'desc' => '',
                'fields' => [
                    'orders_failed_status' => [
                        'name' => 'orders_failed_status',
                        'type' => 'select',
                        'value' => $this->get('orders_failed_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'orders_declined_status',
                'desc' => '',
                'fields' => [
                    'orders_declined_status' => [
                        'name' => 'orders_declined_status',
                        'type' => 'select',
                        'value' => $this->get('orders_declined_status'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_pending',
                'desc' => '',
                'fields' => [
                    'orders_status_pending' => [
                        'name' => 'orders_status_pending',
                        'type' => 'select',
                        'value' => $this->get('orders_status_pending'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_expired',
                'desc' => '',
                'fields' => [
                    'orders_status_expired' => [
                        'name' => 'orders_status_expired',
                        'type' => 'select',
                        'value' => $this->get('orders_status_expired'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_canceled',
                'desc' => '',
                'fields' => [
                    'orders_status_canceled' => [
                        'name' => 'orders_status_canceled',
                        'type' => 'select',
                        'value' => $this->get('orders_status_canceled'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_voided',
                'desc' => '',
                'fields' => [
                    'orders_status_voided' => [
                        'name' => 'orders_status_voided',
                        'type' => 'select',
                        'value' => $this->get('orders_status_voided'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_refunded',
                'desc' => '',
                'fields' => [
                    'orders_status_refunded' => [
                        'name' => 'orders_status_refunded',
                        'type' => 'select',
                        'value' => $this->get('orders_status_refunded'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_reversed',
                'desc' => '',
                'fields' => [
                    'orders_status_reversed' => [
                        'name' => 'orders_status_reversed',
                        'type' => 'select',
                        'value' => $this->get('orders_status_reversed'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
            [
                'title' => 'status_offsite',
                'desc' => '',
                'fields' => [
                    'orders_status_offsite' => [
                        'name' => 'orders_status_offsite',
                        'type' => 'select',
                        'value' => $this->get('orders_status_offsite'),
                        'required' => true,
                        'choices' => $this->getChannelStatuses(true),
                    ],
                ],
            ],
        ];

        $form['order_data_fields'] = [
            [
                'title' => 'orders_items_field',
                'desc' => 'orders_items_field_instructions',
                'fields' => [
                    'orders_items_field' => [
                        'name' => 'orders_items_field',
                        'type' => 'select',
                        'value' => $this->get('orders_items_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_subtotal_field',
                'desc' => '',
                'fields' => [
                    'orders_subtotal_field' => [
                        'name' => 'orders_subtotal_field',
                        'type' => 'select',
                        'value' => $this->get('orders_subtotal_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_subtotal_plus_tax_field',
                'desc' => '',
                'fields' => [
                    'orders_subtotal_plus_tax_field' => [
                        'name' => 'orders_subtotal_plus_tax_field',
                        'type' => 'select',
                        'value' => $this->get('orders_subtotal_plus_tax_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_tax_field',
                'desc' => 'orders_tax_instructions',
                'fields' => [
                    'orders_tax_field' => [
                        'name' => 'orders_tax_field',
                        'type' => 'select',
                        'value' => $this->get('orders_tax_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_discount_field',
                'desc' => 'orders_discount_field_instructions',
                'fields' => [
                    'orders_discount_field' => [
                        'name' => 'orders_discount_field',
                        'type' => 'select',
                        'value' => $this->get('orders_discount_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_total_field',
                'desc' => '',
                'fields' => [
                    'orders_total_field' => [
                        'name' => 'orders_total_field',
                        'type' => 'select',
                        'value' => $this->get('orders_total_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_transaction_id',
                'desc' => 'orders_transaction_id_instructions',
                'fields' => [
                    'orders_transaction_id' => [
                        'name' => 'orders_transaction_id',
                        'type' => 'select',
                        'value' => $this->get('orders_transaction_id'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_card_type',
                'desc' => 'orders_card_type_instructions',
                'fields' => [
                    'orders_card_type' => [
                        'name' => 'orders_card_type',
                        'type' => 'select',
                        'value' => $this->get('orders_card_type'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_last_four_digits',
                'desc' => 'orders_last_four_digits_instructions',
                'fields' => [
                    'orders_last_four_digits' => [
                        'name' => 'orders_last_four_digits',
                        'type' => 'select',
                        'value' => $this->get('orders_last_four_digits'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_coupon_codes',
                'desc' => 'orders_coupon_codes_instructions',
                'fields' => [
                    'orders_coupon_codes' => [
                        'name' => 'orders_coupon_codes',
                        'type' => 'select',
                        'value' => $this->get('orders_coupon_codes'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_payment_gateway',
                'desc' => '',
                'fields' => [
                    'orders_payment_gateway' => [
                        'name' => 'orders_payment_gateway',
                        'type' => 'select',
                        'value' => $this->get('orders_payment_gateway'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_error_message_field',
                'desc' => 'orders_error_message_field_instructions',
                'fields' => [
                    'orders_error_message_field' => [
                        'name' => 'orders_error_message_field',
                        'type' => 'select',
                        'value' => $this->get('orders_error_message_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_language_field',
                'desc' => 'orders_language_field_instructions',
                'fields' => [
                    'orders_language_field' => [
                        'name' => 'orders_language_field',
                        'type' => 'select',
                        'value' => $this->get('orders_language_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_customer_name',
                'desc' => 'orders_customer_name_instructions',
                'fields' => [
                    'orders_customer_name' => [
                        'name' => 'orders_customer_name',
                        'type' => 'select',
                        'value' => $this->get('orders_customer_name'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_customer_email',
                'desc' => 'orders_customer_email_instructions',
                'fields' => [
                    'orders_customer_email' => [
                        'name' => 'orders_customer_email',
                        'type' => 'select',
                        'value' => $this->get('orders_customer_email'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_customer_ip_address',
                'desc' => '',
                'fields' => [
                    'orders_customer_ip_address' => [
                        'name' => 'orders_customer_ip_address',
                        'type' => 'select',
                        'value' => $this->get('orders_customer_ip_address'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_customer_phone',
                'desc' => '',
                'fields' => [
                    'orders_customer_phone' => [
                        'name' => 'orders_customer_phone',
                        'type' => 'select',
                        'value' => $this->get('orders_customer_phone'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_site_id',
                'desc' => '',
                'fields' => [
                    'orders_site_id' => [
                        'name' => 'orders_site_id',
                        'type' => 'select',
                        'value' => $this->get('orders_site_id'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_subscription_id',
                'desc' => '',
                'fields' => [
                    'orders_subscription_id' => [
                        'name' => 'orders_subscription_id',
                        'type' => 'select',
                        'value' => $this->get('orders_subscription_id'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_vault_id',
                'desc' => '',
                'fields' => [
                    'orders_vault_id' => [
                        'name' => 'orders_vault_id',
                        'type' => 'select',
                        'value' => $this->get('orders_vault_id'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
        ];

        $form['ct.route.header.order_shipping_fields'] = [
            [
                'title' => 'orders_shipping_method',
                'desc' => 'orders_shipping_method_instructions',
                'fields' => [
                    'orders_shipping_option' => [
                        'name' => 'orders_shipping_option',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_option'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_field',
                'desc' => 'orders_shipping_field_instructions',
                'fields' => [
                    'orders_shipping_field' => [
                        'name' => 'orders_shipping_field',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_plus_tax_field',
                'desc' => '',
                'fields' => [
                    'orders_shipping_plus_tax_field' => [
                        'name' => 'orders_shipping_plus_tax_field',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_plus_tax_field'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_full_shipping_address',
                'desc' => '',
                'fields' => [
                    'orders_full_shipping_address' => [
                        'name' => 'orders_full_shipping_address',
                        'type' => 'select',
                        'value' => $this->get('orders_full_shipping_address'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_first_name',
                'desc' => '',
                'fields' => [
                    'orders_shipping_first_name' => [
                        'name' => 'orders_shipping_first_name',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_first_name'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_last_name',
                'desc' => '',
                'fields' => [
                    'orders_shipping_last_name' => [
                        'name' => 'orders_shipping_last_name',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_last_name'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_phone',
                'desc' => '',
                'fields' => [
                    'orders_shipping_phone' => [
                        'name' => 'orders_shipping_phone',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_phone'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_company',
                'desc' => '',
                'fields' => [
                    'orders_shipping_company' => [
                        'name' => 'orders_shipping_company',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_company'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_address',
                'desc' => '',
                'fields' => [
                    'orders_shipping_address' => [
                        'name' => 'orders_shipping_address',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_address'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_address2',
                'desc' => '',
                'fields' => [
                    'orders_shipping_address2' => [
                        'name' => 'orders_shipping_address2',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_address2'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_city',
                'desc' => '',
                'fields' => [
                    'orders_shipping_city' => [
                        'name' => 'orders_shipping_city',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_city'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_state',
                'desc' => '',
                'fields' => [
                    'orders_shipping_state' => [
                        'name' => 'orders_shipping_state',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_state'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_zip',
                'desc' => '',
                'fields' => [
                    'orders_shipping_zip' => [
                        'name' => 'orders_shipping_zip',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_zip'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_country',
                'desc' => '',
                'fields' => [
                    'orders_shipping_country' => [
                        'name' => 'orders_shipping_country',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_country'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_shipping_country_code',
                'desc' => '',
                'fields' => [
                    'orders_shipping_country_code' => [
                        'name' => 'orders_shipping_country_code',
                        'type' => 'select',
                        'value' => $this->get('orders_shipping_country_code'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
        ];

        $form['ct.route.header.order_billing_fields'] = [
            [
                'title' => 'orders_full_billing_address',
                'desc' => 'orders_full_billing_address_instructions',
                'fields' => [
                    'orders_full_billing_address' => [
                        'name' => 'orders_full_billing_address',
                        'type' => 'select',
                        'value' => $this->get('orders_full_billing_address'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_first_name',
                'desc' => '',
                'fields' => [
                    'orders_billing_first_name' => [
                        'name' => 'orders_billing_first_name',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_first_name'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_last_name',
                'desc' => '',
                'fields' => [
                    'orders_billing_last_name' => [
                        'name' => 'orders_billing_last_name',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_last_name'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_company',
                'desc' => '',
                'fields' => [
                    'orders_billing_company' => [
                        'name' => 'orders_billing_company',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_company'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_address',
                'desc' => '',
                'fields' => [
                    'orders_billing_address' => [
                        'name' => 'orders_billing_address',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_address'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_address2',
                'desc' => '',
                'fields' => [
                    'orders_billing_address2' => [
                        'name' => 'orders_billing_address2',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_address2'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_city',
                'desc' => '',
                'fields' => [
                    'orders_billing_city' => [
                        'name' => 'orders_billing_city',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_city'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_state',
                'desc' => '',
                'fields' => [
                    'orders_billing_state' => [
                        'name' => 'orders_billing_state',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_state'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_zip',
                'desc' => '',
                'fields' => [
                    'orders_billing_zip' => [
                        'name' => 'orders_billing_zip',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_zip'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_billing_country',
                'desc' => '',
                'fields' => [
                    'orders_billing_country' => [
                        'name' => 'orders_billing_country',
                        'type' => 'select',
                        'value' => $this->get('orders_billing_country'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
            [
                'title' => 'orders_country_code',
                'desc' => '',
                'fields' => [
                    'orders_country_code' => [
                        'name' => 'orders_country_code',
                        'type' => 'select',
                        'value' => $this->get('orders_country_code'),
                        'choices' => $this->getChannelFieldOptions(),
                        'no_results' => $field_no_results,
                    ],
                ],
            ],
        ];

        $setting = (int)($this->get('orders_async_method') ?? 0);
        $url = htmlentities(ee()->paths->build_action_url('Cartthrob', 'consume_async_job_action', ['limit' => 5]), ENT_QUOTES, 'UTF-8', false);
        $form['orders_async_method'] = [
            [
                'desc' => sprintf(lang('orders_async_method_description'), $url),
                'fields' => [
                    'orders_async_method' => [
                        'name' => 'orders_async_method',
                        'type' => 'select',
                        'value' => $this->get('orders_async_method'),
                        'choices' => $this->orders_async_method_options,
                    ],
                ],
            ],
            [
                'title' => 'orders_async_worker_base_url',
                'desc' => 'orders_async_worker_base_url_description',
                'fields' => [
                    'orders_async_worker_base_url' => [
                        'name' => 'orders_async_worker_base_url',
                        'type' => 'text',
                        'value' => $this->get('orders_async_worker_base_url'),
                    ],
                ],
            ],
        ];

        return $form;
    }
}
