<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class Members extends AbstractForm
{
    protected $field_options = [];

    protected $checkout_registration_options = [
        'auto-login' => 'member_auto_login',
        'use_ee_settings' => 'member_use_ee_settings',
    ];

    protected $member_fields = [
        'first_name',
        'last_name',
        'address',
        'address2',
        'city',
        'state',
        'zip',
        'country',
        'country_code',
        'company',
        'phone',
        'email_address',
        'use_billing_info',
        'shipping_first_name',
        'shipping_last_name',
        'shipping_phone',
        'shipping_address',
        'shipping_address2',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'shipping_country_code',
        'shipping_company',
        'language',
        'shipping_option',
        'region',
    ];

    public function __construct()
    {
        parent::__construct();
        ee()->load->helper('inflector');

        foreach ($this->checkout_registration_options as $key => $value) {
            $this->checkout_registration_options[$key] = strip_tags(lang($value));
        }

        $this->field_options = ['' => '----'];

        $m_fields = ee('Model')
            ->get('MemberField')
            ->fields('m_field_name', 'm_field_id', 'm_field_label')
            ->order('m_field_name', 'asc');

        if ($m_fields->count() == 0) {
            $this->field_options = [];
        } else {
            foreach ($m_fields->all() as $row) {
                $this->field_options[$row->m_field_id] = $row->m_field_label;
            }
        }
    }

    public function generate(): array
    {
        $form = [
            [
                'title' => 'members_save_data',
                'desc' => 'members_saving_instructions',
                'fields' => [
                    'allow_products_more_than_once' => [
                        'name' => 'save_member_data',
                        'type' => 'select',
                        'value' => $this->get('save_member_data'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'member_login_options_header',
                'desc' => 'member_login_options_description',
                'fields' => [
                    'checkout_registration_options' => [
                        'name' => 'checkout_registration_options',
                        'type' => 'select',
                        'value' => $this->get('checkout_registration_options'),
                        'required' => true,
                        'choices' => $this->checkout_registration_options,
                    ],
                ],
            ],
        ];

        if ($this->isProfileEditActive()) {
            $form[] = [
                'title' => 'members_use_profile_edit',
                'desc' => 'profile_edit_saving_instructions',
                'fields' => [
                    'use_profile_edit' => [
                        'name' => 'use_profile_edit',
                        'type' => 'select',
                        'value' => $this->get('use_profile_edit', '0'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ];
        }

        $form = ['general_settings_header' => $form];

        $fields = [];
        $add_field_url = ee('CP/URL')->make('cp/members/fields/create')->compile();

        foreach ($this->member_fields as $field) {
            // God forgive me but porting means bringing the weird :(
            $field_name = 'member_' . $field . '_field';
            $options = $this->field_options;
            if ($field === 'email_address') {
                $field = 'member_email_address_field';
                $options = [lang('members_built_in_fields') => ['email' => lang('email')]];
                if ($this->field_options) {
                    $options[lang('members_custom_fields')] = $this->field_options;
                }
            }

            $fields[] = [
                'title' => humanize($field) . " ($field)",
                'fields' => [
                    $field_name => [
                        'name' => $field_name,
                        'type' => 'select',
                        'value' => $this->get($field_name),
                        'choices' => $options,
                        'no_results' => [
                            'text' => lang('ct.route.nothing_here'),
                            'link_href' => $add_field_url,
                            'link_text' => lang('ct.route.add_member_fields'),
                        ],
                    ],
                ],
            ];
        }

        $form['member_configuration_header'] = $fields;

        return $form;
    }

    /**
     * Determines if Profile:Edit has any Extension hooks going
     * @return bool
     */
    protected function isProfileEditActive(): bool
    {
        return $profileEditActive = isset(ee()->extensions->extensions['channel_form_submit_entry_start'][10]['Profile_ext']) ? true : false;
    }
}
