<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Coupons extends AbstractForm
{
    /**
     * @return \array[][]
     */
    public function generate(): array
    {
        $edit_url = ee('CP/URL')->make($this->base_url . '/coupons/set-channel');
        $form = [
            [
                'title' => 'ct.route.channel_name',
                'desc' => 'the_channel_that_stores_coupons_details',
                'caution' => true,
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => '<a href="' . $edit_url . '">(' . $this->getChannelId() . ') ' . $this->getChannelTitle() . '</a>',
                    ],
                ],
            ],
            [
                'title' => 'coupon_code_field',
                'desc' => 'coupon_code_field_note',
                'fields' => [
                    'coupon_code_field' => [
                        'name' => 'coupon_code_field',
                        'type' => 'select',
                        'value' => $this->get('coupon_code_field'),
                        'choices' => $this->getChannelFieldOptions() + ['title' => lang('title')],
                    ],
                ],
            ],
            [
                'title' => 'coupon_code_type',
                'desc' => 'coupon_code_type_note',
                'fields' => [
                    'coupon_code_type' => [
                        'name' => 'coupon_code_type',
                        'type' => 'select',
                        'value' => $this->get('coupon_code_type'),
                        'required' => true,
                        'choices' => $this->getChannelFieldOptions(),
                    ],
                ],
            ],
        ];

        return ['coupons_settings_form_description' => $form];
    }
}
