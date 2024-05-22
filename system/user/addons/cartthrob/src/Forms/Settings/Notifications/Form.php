<?php

namespace CartThrob\Forms\Settings\Notifications;

use CartThrob\Forms\Settings\Plugin;

class Form extends Plugin
{
    /**
     * @var string[]
     */
    protected $email_type_options = [
        'html' => 'send_html_email',
        'text' => 'send_text_email',
    ];

    public function __construct()
    {
        parent::__construct();

        foreach ($this->email_type_options as $key => $value) {
            $this->email_type_options[$key] = strip_tags(lang($value));
        }
    }

    /**
     * @return \array[][]
     */
    public function generate(): array
    {
        $form = [
            [
                'title' => 'ct.route.notification.title',
                'desc' => 'ct.route.notification.title_note',
                'fields' => [
                    'title' => [
                        'name' => 'title',
                        'type' => 'text',
                        'value' => $this->get('title'),
                    ],
                ],
            ],
            [
                'title' => 'ct.route.notification.template',
                'desc' => 'ct.route.notification.template_note',
                'fields' => [
                    'template' => [
                        'name' => 'template',
                        'type' => 'select',
                        'value' => $this->get('template'),
                        'required' => true,
                        'choices' => $this->templateOptions(),
                    ],
                ],
            ],
            [
                'title' => 'ct.route.notification.event',
                'desc' => 'ct.route.notification.email_event_note',
                'fields' => [
                    'event' => [
                        'name' => 'event',
                        'type' => 'select',
                        'value' => $this->get('event'),
                        'required' => true,
                        'choices' => $this->eventOptions(),
                        'group_toggle' => [
                            'status_change' => 'status_change_options',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'ct.route.notification.starting_status',
                'desc' => 'ct.route.notification.starting_status_note',
                'group' => 'status_change_options',
                'fields' => [
                    'starting_status' => [
                        'name' => 'starting_status',
                        'type' => 'select',
                        'required' => true,
                        'value' => $this->get('starting_status'),
                        'choices' => $this->getChannelStatuses(),
                    ],
                ],
            ],
            [
                'title' => 'ct.route.notification.ending_status',
                'desc' => 'ct.route.notification.ending_status_note',
                'group' => 'status_change_options',
                'fields' => [
                    'ending_status' => [
                        'name' => 'ending_status',
                        'type' => 'select',
                        'required' => true,
                        'value' => $this->get('ending_status'),
                        'choices' => $this->getChannelStatuses(),
                    ],
                ],
            ],
            [
                'title' => 'ct.route.notification.type',
                'desc' => 'ct.route.notification.type_note',
                'fields' => [
                    'type' => [
                        'name' => 'type',
                        'type' => 'select',
                        'value' => $this->get('type'),
                        'required' => true,
                        'choices' => $this->typeOptions(),
                        'group_toggle' => $this->getTypeGroupToggle(),
                    ],
                ],
            ],
        ];

        $plugins = ee('cartthrob:NotificationsService')->getAllPlugins();
        $fields = [];
        foreach ($plugins as $plugin) {
            $group = $plugin->type . '_options';
            foreach ($plugin->settings as $setting) {
                $type = element('type', $setting);
                $method = $type . 'Field';
                if (method_exists($this, $method)) {
                    $setting['group'] = $group;
                    $fields[] = $this->$method($setting);
                } elseif ($type == 'add_to_head') {
                    if (strpos($setting['default'], '<script') !== false) {
                        ee()->cp->add_to_foot($setting['default']);
                    } else {
                        ee()->cp->add_to_head($setting['default']);
                    }
                } else {
                    echo $type;
                    echo '<br />';
                }
            }
        }

        $form = [$form];
        $form['ct.route.notification.fields'] = $fields;

        return $form;
    }

    /**
     * @return array|\string[][]
     */
    protected function eventOptions(): array
    {
        $externalAppEvents = [];
        foreach (ee()->db->get('cartthrob_notification_events')->result() as $row) {
            $externalAppEvents[$row->application . '_' . $row->notification_event] = lang($row->application) . ': ' . lang($row->notification_event);
        }

        $emailEvents = [
            lang('payment_triggers') => [
                'completed' => lang('ct_completed'),
                'declined' => lang('ct_declined'),
                'failed' => lang('ct_failed'),
                'offsite' => lang('ct_offsite'),
                'processing' => lang('ct_processing'),
                'refunded' => lang('ct_refunded'),
                'expired' => lang('ct_expired'),
                'canceled' => lang('ct_canceled'),
                'pending' => lang('ct_pending'),
            ],
            lang('other_events') => [
                'low_stock' => lang('ct_low_stock'),
                'status_change' => lang('status_change'),
            ],
        ];

        if ($externalAppEvents) {
            $emailEvents[lang('application_events')] = $externalAppEvents;
        }

        return $emailEvents;
    }

    /**
     * @return array
     */
    protected function typeOptions(): array
    {
        $plugins = ee('cartthrob:NotificationsService')->getAllPlugins();
        $return = [];
        if ($plugins) {
            foreach ($plugins as $plugin) {
                $return[$plugin->type] = lang($plugin->title);
            }
        }

        return $return;
    }

    /**
     * @return array
     */
    protected function getTypeGroupToggle(): array
    {
        $options = $this->typeOptions();
        $return = [];
        foreach ($options as $key => $value) {
            $return[$key] = $key . '_options';
        }

        return $return;
    }

    /**
     * @return array
     */
    protected function templateOptions(): array
    {
        $templates = [];

        ee()->load->model('template_model');

        $query = ee()->template_model->get_templates();

        foreach ($query->result() as $row) {
            $templates[$row->group_name . '/' . $row->template_name] = $row->group_name . '/' . $row->template_name;
        }

        return $templates;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateNotificationSafeEmail(string $name, $value, $params, $object)
    {
        $allowed = ['{customer_email}'];
        $emails = explode(',', $value);
        foreach ($emails as $email) {
            if (!in_array($email, $allowed)) {
                $email = trim($email);
                if ($email != filter_var($email, FILTER_SANITIZE_EMAIL) or !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return 'ct.route.notification.error.invalid_email';
                }
            }
        }

        return true;
    }

    /**
     * @param string $name
     * @param $value
     * @param $params
     * @param $object
     * @return bool|string
     */
    public function validateSingleItem(string $name, $value, $params, $object)
    {
        $emails = explode(',', $value);
        if (count($emails) == 1) {
            return true;
        }

        return 'ct.route.notification.error.only_one_email_allowed';
    }
}
