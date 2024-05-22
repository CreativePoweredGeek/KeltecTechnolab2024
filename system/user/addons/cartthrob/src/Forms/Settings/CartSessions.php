<?php

namespace CartThrob\Forms\Settings;

use CartThrob\Forms\AbstractForm;

class CartSessions extends AbstractForm
{
    protected $rules = [
        'session_expire' => 'isNatural',
        'clear_cart_on_logout' => 'required',
    ];

    /**
     * The Options to use for Fingerprint method
     * @var string[]
     */
    protected $fingerprint_methods = [
        '0' => 'global_settings_session_fingerprint_method_0',
        '1' => 'global_settings_session_fingerprint_method_1',
        '2' => 'global_settings_session_fingerprint_method_2',
        '3' => 'global_settings_session_fingerprint_method_3',
        '4' => 'global_settings_session_fingerprint_method_4',
    ];

    public function __construct()
    {
        parent::__construct();
        foreach ($this->fingerprint_methods as $key => $value) {
            $this->fingerprint_methods[$key] = lang($value);
        }
    }

    public function generate(): array
    {
        $url = htmlentities(ee()->paths->build_action_url('Cartthrob_mcp', 'garbage_collection'), ENT_QUOTES, 'UTF-8', false);
        $form = [
            [
                'title' => 'global_settings_session_expire',
                'desc' => 'global_settings_session_description',
                'fields' => [
                    'session_expire' => [
                        'name' => 'session_expire',
                        'type' => 'text',
                        'required' => false,
                        'value' => $this->get('session_expire'),
                    ],
                ],
            ],
            [
                'title' => 'global_settings_clear_session',
                'desc' => 'global_settings_clear_session_description',
                'fields' => [
                    'clear_session_on_logout' => [
                        'name' => 'clear_session_on_logout',
                        'type' => 'select',
                        'value' => $this->get('clear_session_on_logout'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_session_use_fingerprint',
                'desc' => 'global_settings_session_use_fingerprint_description',
                'fields' => [
                    'session_use_fingerprint' => [
                        'name' => 'session_use_fingerprint',
                        'type' => 'select',
                        'value' => $this->get('session_use_fingerprint'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'global_settings_session_fingerprint_method',
                'desc' => 'global_settings_session_fingerprint_method_description',
                'caution' => true,
                'fields' => [
                    'session_fingerprint_method' => [
                        'name' => 'session_fingerprint_method',
                        'type' => 'select',
                        'value' => $this->get('session_fingerprint_method'),
                        'choices' => $this->fingerprint_methods,
                    ],
                ],
            ],
            [
                'title' => 'global_settings_garbage_collection_cron',
                'desc' => sprintf(lang('global_settings_garbage_collection_cron_description'), $url),
                'fields' => [
                    'garbage_collection_cron' => [
                        'name' => 'garbage_collection_cron',
                        'type' => 'select',
                        'value' => $this->get('garbage_collection_cron'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        $form = ['ct.route.header.session_settings' => $form];

        $form['ct.route.header.cart_settings'] = [
            [
                'title' => 'global_settings_clear_cart',
                'desc' => 'global_settings_clear_cart_description',
                'fields' => [
                    'clear_cart_on_logout' => [
                        'name' => 'clear_cart_on_logout',
                        'type' => 'select',
                        'value' => $this->get('clear_cart_on_logout'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
            [
                'title' => 'allow_empty_cart_checkout',
                'desc' => 'allow_empty_cart_checkout_description',
                'fields' => [
                    'allow_empty_cart_checkout' => [
                        'name' => 'allow_empty_cart_checkout',
                        'type' => 'select',
                        'value' => $this->get('allow_empty_cart_checkout'),
                        'required' => true,
                        'choices' => $this->options['yes_no'],
                    ],
                ],
            ],
        ];

        return $form;
    }
}
