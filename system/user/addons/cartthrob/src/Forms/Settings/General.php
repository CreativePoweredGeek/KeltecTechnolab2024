<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;
use ExpressionEngine\Service\Validation\Validator;

class General extends AbstractForm
{
    /**
     * The Validation rules that'll be ran
     * @var string[]
     */
    protected $rules = [
        'logged_in' => 'required',
        'default_member_id' => 'isNaturalNoZero|validateMemberExists|validateMemberIsSuperAdmin',
        'show_debug' => 'required',
        'number_format_defaults_decimals' => 'required|isNatural',
        'number_format_defaults_dec_point' => 'required',
        'number_format_defaults_thousands_sep' => 'required',
        // 'number_format_defaults_prefix' => 'required',
        'number_format_defaults_prefix_position' => 'required',
        'number_format_defaults_currency_code' => 'required',
        'rounding_nearest_value' => 'whenRoudingDefaultIs[round_nearest]|required|numeric',
        'rounding_default' => 'required',
        'license_number' => 'required',
    ];

    /**
     * The Options to use for rounding configuration
     * @var string[]
     */
    protected $rounding_nearest = [
        'standard' => 'rounding_standard',
        'round_up' => 'round_up',
        'round_down' => 'round_down',
        'round_nearest' => 'round_nearest',
    ];

    protected $number_format_defaults_prefix_position = [
        'AFTER' => 'AFTER',
        'BEFORE' => 'BEFORE',
    ];

    public function __construct()
    {
        parent::__construct();

        foreach ($this->rounding_nearest as $key => $value) {
            $this->rounding_nearest[$key] = strip_tags(lang($value));
        }
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $validator = ee('Validation')->make($this->rules);
        $data = $this->data;
        $validator->defineRule('whenRoudingDefaultIs', function ($key, $value, $parameters, $rule) use ($data) {
            return ($data['rounding_default'] == $parameters[0]) ? true : $rule->skip();
        });

        return $validator;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     */
    public function validateLicenseNumber(string $name, $value, $params, $object)
    {
        if (preg_match('/^[{]?[0-9a-fA-F]{8}-([0-9a-fA-F]{4}-){3}[0-9a-fA-F]{12}[}]?$/', $value)) {
            return true;
        }

        return 'invalid_license_number_value';
    }

    /**
     * @return \array[][]
     */
    public function generate(): array
    {
        $form = [
            [
                'title' => 'logged_in',
                'desc' => 'global_settings_login_description',
                'fields' => [
                    'status' => [
                        'name' => 'logged_in',
                        'type' => 'select',
                        'value' => $this->get('logged_in'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_default_member_id',
                'desc' => 'global_settings_default_member_id_description',
                'fields' => [
                    'default_member_id' => [
                        'name' => 'default_member_id',
                        'type' => 'text',
                        'value' => $this->get('default_member_id'),
                    ],
                ],
            ],
            [
                'title' => 'show_debug',
                'fields' => [
                    'show_debug' => [
                        'name' => 'show_debug',
                        'type' => 'select',
                        'value' => $this->get('show_debug'),
                        'required' => true,
                        'choices' => array_merge($this->options['yes_no'], ['super_admin' => lang('super_admins_only')]),
                    ],
                ],
            ],
            [
                'title' => 'global_settings_logging_enabled',
                'desc' => 'global_settings_logging_description',
                'fields' => [
                    'enable_logging' => [
                        'name' => 'enable_logging',
                        'type' => 'select',
                        'value' => $this->get('enable_logging'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_cp_menu_label',
                'desc' => 'global_settings_cp_menu_label_description',
                'fields' => [
                    'cp_menu_label' => [
                        'name' => 'cp_menu_label',
                        'type' => 'text',
                        'value' => $this->get('cp_menu_label'),
                    ],
                ],
            ],
            [
                'title' => 'global_settings_checkout_form_captcha',
                'desc' => 'global_settings_checkout_form_captcha_description',
                'fields' => [
                    'checkout_form_captcha' => [
                        'name' => 'checkout_form_captcha',
                        'type' => 'select',
                        'value' => $this->get('checkout_form_captcha'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_admin_checkout_groups',
                'desc' => 'global_settings_admin_checkout_groups_description',
                'fields' => [
                    'admin_checkout_groups' => [
                        'name' => 'admin_checkout_groups',
                        'type' => 'checkbox',
                        'value' => $this->get('admin_checkout_groups'),
                        'choices' => $this->roleOptions(),
                    ],
                    'sa_checkout_groups' => [
                        'type' => 'hidden',
                        'value' => 1,
                        'name' => 'admin_checkout_groups[]',
                    ],
                ],
            ],
        ];

        $form = ['general_settings_header' => $form];

        $form['number_format_defaults_heading'] = [
            [
                'title' => 'number_format_defaults_decimals',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_decimals' => [
                        'name' => 'number_format_defaults_decimals',
                        'type' => 'text',
                        'value' => $this->get('number_format_defaults_decimals'),
                    ],
                ],
            ],
            [
                'title' => 'number_format_defaults_dec_point',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_dec_point' => [
                        'name' => 'number_format_defaults_dec_point',
                        'type' => 'text',
                        'value' => $this->get('number_format_defaults_dec_point'),
                    ],
                ],
            ],
            [
                'title' => 'number_format_defaults_thousands_sep',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_thousands_sep' => [
                        'name' => 'number_format_defaults_thousands_sep',
                        'type' => 'text',
                        'value' => $this->get('number_format_defaults_thousands_sep'),
                    ],
                ],
            ],
            [
                'title' => 'number_format_defaults_prefix',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_prefix' => [
                        'name' => 'number_format_defaults_prefix',
                        'type' => 'text',
                        'value' => $this->get('number_format_defaults_prefix'),
                    ],
                ],
            ],
            [
                'title' => 'number_format_defaults_prefix_position',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_prefix_position' => [
                        'name' => 'number_format_defaults_prefix_position',
                        'type' => 'select',
                        'value' => $this->get('number_format_defaults_prefix_position'),
                        'required' => true,
                        'choices' => $this->number_format_defaults_prefix_position,
                    ],
                ],
            ],
            [
                'title' => 'number_format_defaults_currency_code',
                'desc' => '',
                'fields' => [
                    'number_format_defaults_currency_code' => [
                        'name' => 'number_format_defaults_currency_code',
                        'type' => 'text',
                        'required' => true,
                        'value' => $this->get('number_format_defaults_currency_code'),
                    ],
                ],
            ],
            [
                'title' => 'round_to',
                'desc' => '',
                'fields' => [
                    'rounding_default' => [
                        'name' => 'rounding_default',
                        'type' => 'radio',
                        'value' => $this->get('rounding_default'),
                        'required' => true,
                        'choices' => $this->rounding_nearest,
                    ],
                ],
            ],
            [
                'title' => 'rounding_nearest_value',
                'desc' => '',
                'fields' => [
                    'rounding_nearest_value' => [
                        'name' => 'rounding_nearest_value',
                        'type' => 'text',
                        'value' => $this->get('rounding_nearest_value'),
                    ],
                ],
            ],
        ];

        $form['default_location_header'] = [
            [
                'title' => '',
                'desc' => '',
                'fields' => [
                    'sub_id' => [
                        'type' => 'html',
                        'content' => lang('default_location_default_display_description'),
                    ],
                ],
            ],
            [
                'title' => 'default_location_state',
                'desc' => '',
                'fields' => [
                    'default_location_state' => [
                        'name' => 'default_location[state]',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getStateOptions(),
                        'value' => element('state', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_zip',
                'desc' => '',
                'fields' => [
                    'default_location_zip' => [
                        'name' => 'default_location[zip]',
                        'type' => 'text',
                        'required' => true,
                        'value' => element('zip', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_country_code',
                'desc' => '',
                'fields' => [
                    'default_location_country_code' => [
                        'name' => 'default_location[country_code]',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getCountryOptions(),
                        'value' => element('country_code', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_region',
                'desc' => '',
                'fields' => [
                    'default_location_region' => [
                        'name' => 'default_location[region]',
                        'type' => 'text',
                        'value' => element('region', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_shipping_state',
                'desc' => '',
                'fields' => [
                    'default_location_shipping_state' => [
                        'name' => 'default_location[shipping_state]',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getStateOptions(),
                        'value' => element('shipping_state', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_shipping_zip',
                'desc' => '',
                'fields' => [
                    'default_location_shipping_zip' => [
                        'name' => 'default_location[shipping_zip]',
                        'type' => 'text',
                        'required' => true,
                        'value' => element('shipping_zip', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_shipping_country_code',
                'desc' => '',
                'fields' => [
                    'default_location_shipping_country_code' => [
                        'name' => 'default_location[shipping_country_code]',
                        'type' => 'select',
                        'required' => true,
                        'choices' => $this->getCountryOptions(),
                        'value' => element('shipping_country_code', element('default_location', $this->data)),
                    ],
                ],
            ],
            [
                'title' => 'default_location_shipping_region',
                'desc' => '',
                'fields' => [
                    'default_location_shipping_region' => [
                        'name' => 'default_location[shipping_region]',
                        'type' => 'text',
                        'value' => element('shipping_region', element('default_location', $this->data)),
                    ],
                ],
            ],
        ];

        $form['locales_header'] = [
            [
                'title' => 'locales_countries',
                'desc' => '',
                'fields' => [
                    'locales_countries' => [
                        'name' => 'locales_countries',
                        'type' => 'checkbox',
                        'value' => $this->get('locales_countries'),
                        'choices' => $this->getCountryOptions(false),
                    ],
                ],
            ],
        ];

        $form['msm'] = [
            [
                'title' => 'msm_show_all',
                'desc' => 'msm_show_all_description',
                'fields' => [
                    'msm_show_all' => [
                        'name' => 'msm_show_all',
                        'type' => 'select',
                        'value' => $this->get('msm_show_all'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        $form['set_license_number_header'] = [
            [
                'title' => 'license_number_label',
                'desc' => '',
                'fields' => [
                    'license_number' => [
                        'name' => 'license_number',
                        'type' => 'text',
                        'value' => $this->get('license_number'),
                    ],
                ],
            ],
        ];

        return $form;
    }
}
