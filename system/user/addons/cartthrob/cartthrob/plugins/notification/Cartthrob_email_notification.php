<?php

use CartThrob\Forms\Settings\Notifications\Form as NotificationsForm;
use CartThrob\Plugins\Notification\NotificationPlugin;
use ExpressionEngine\Service\Validation\Validator;

class Cartthrob_email_notification extends NotificationPlugin
{
    /**
     * @var string
     */
    public $title = 'Email Notification';

    /**
     * @var string
     */
    public $short_title = 'email';

    /**
     * @var string
     */
    public string $type = 'email';

    /**
     * @var
     */
    public $overview;

    /**
     * @var
     */
    public $note;

    /**
     * @var array
     */
    public $settings = [
        [
            'name' => 'email_from',
            'short_name' => 'email_from',
            'note' => 'ct.route.notification.email_from_note',
            'type' => 'text',
        ],
        [
            'name' => 'email_from_name',
            'note' => 'ct.route.notification.email_from_name_note',
            'short_name' => 'email_from_name',
            'type' => 'text',
        ],
        [
            'name' => 'email_to',
            'note' => 'ct.route.notification.email_to_note',
            'short_name' => 'email_to',
            'type' => 'text',
        ],
        [
            'name' => 'email_subject',
            'short_name' => 'email_subject',
            'type' => 'text',
            'note' => 'ct.route.notification.email_subject_note',
        ],
        [
            'name' => 'email_reply_to_name',
            'short_name' => 'email_reply_to_name',
            'note' => 'ct.route.notification.email_reply_to_name_note',
            'type' => 'text',
        ],
        [
            'name' => 'email_reply_to',
            'note' => 'ct.route.notification.email_reply_to_note',
            'short_name' => 'email_reply_to',
            'type' => 'text',
        ],
        [
            'name' => 'email_type',
            'short_name' => 'email_type',
            'note' => 'ct.route.notification.email_type_note',
            'type' => 'radio',
            'default' => 'html',
            'options' => [
                'html' => 'HTML',
                'text' => 'Plain Text',
            ],
        ],
    ];

    /**
     * @var string[]
     */
    protected array $rules = [
        'email_subject' => 'required',
        'email_from_name' => 'required',
        'email_from' => 'required|validateNotificationSafeEmail|validateSingleItem',
        'email_reply_to_name' => 'required',
        'email_reply_to' => 'required|validateNotificationSafeEmail|validateSingleItem',
        'email_to' => 'required|validateNotificationSafeEmail',
        'email_type' => 'required',
    ];

    /**
     * @param array $data
     * @return bool
     */
    public function deliver(array $data): bool
    {
        ee()->load->library('cartthrob_emails');
        $email_data = $this->prepareEmailData() + $data;
        ee()->cartthrob_emails->sendEmail(
            $this->getSetting('email_from'),
            $this->getSetting('email_from_name'),
            $this->getSetting('email_to'),
            $this->getSetting('email_subject'),
            $message = '',
            $plaintext = false,
            $email_data,
            null,
            $this->getSetting('template')
        );

        return true;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function preProcess(array $data): array
    {
        if (!empty($this->data['email_to']) && strpos($this->data['email_to'], '{customer_email}') !== false) {
            if (ee()->cartthrob->store->config('orders_customer_email') && !empty($data['field_id_' . ee()->cartthrob->store->config('orders_customer_email')])) {
                $this->data['email_to'] = str_replace('{customer_email}', $data['field_id_' . ee()->cartthrob->store->config('orders_customer_email')], $this->data['email_to']);
            } elseif (ee()->cartthrob->store->config('orders_customer_email') && !empty($data['customer_email'])) {
                $this->data['email_to'] = str_replace('{customer_email}', $data['customer_email'], $this->data['email_to']);
            }
        }

        if (!empty($this->data['email_from']) && $this->data['email_from'] == '{customer_email}') {
            if (ee()->cartthrob->store->config('orders_customer_email') && !empty($data['field_id_' . ee()->cartthrob->store->config('orders_customer_email')])) {
                $this->data['email_from'] = $data['field_id_' . ee()->cartthrob->store->config('orders_customer_email')];
            } elseif (ee()->cartthrob->store->config('orders_customer_email') && !empty($data['customer_email'])) {
                $this->data['email_from'] = str_replace('{customer_email}', $data['customer_email'], $this->data['email_from']);
            }
        }

        if (!empty($this->data['email_from_name']) && $this->data['email_from_name'] == '{customer_name}') {
            if (ee()->cartthrob->store->config('orders_customer_name') && !empty($data['field_id_' . ee()->cartthrob->store->config('orders_customer_name')])) {
                $this->data['email_from_name'] = $data['field_id_' . ee()->cartthrob->store->config('orders_customer_name')];
            } elseif (ee()->cartthrob->store->config('orders_customer_email') && !empty($data['customer_name'])) {
                $this->data['email_from_name'] = str_replace('{customer_name}', $data['customer_name'], $this->data['email_from_name']);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function prepareEmailData(): array
    {
        return [
            'from' => $this->getSetting('email_from'),
            'from_name' => $this->getSetting('email_from_name'),
            'from_reply_to' => empty($this->getSetting('email_reply_to')) ? $this->getSetting('email_from') : $this->getSetting('email_reply_to'),
            'from_reply_to_name' => empty($this->getSetting('email_reply_to_name')) ? $this->getSetting('email_from_name') : $this->getSetting('email_reply_to_name'),
            'to' => $this->getSetting('email_to'),
            'message_template' => $this->getSetting('template'),
            'subject' => $this->getSetting('email_subject'),
            'plaintext' => $this->getSetting('email_type') == 'text',
        ];
    }

    /**
     * @return Validator
     */
    protected function getValidator(): Validator
    {
        $rules = $this->rules + $this->_rules;
        $validator = ee('Validation')->make($rules);
        $data = $this->data;
        $validator->defineRule('validateSingleItem', function ($key, $value, $parameters, $rule) {
            $notifications = new NotificationsForm();

            return $notifications->validateSingleItem($key, $value, $parameters, $rule);
        });

        $validator->defineRule('validateNotificationSafeEmail', function ($key, $value, $parameters, $rule) {
            $notifications = new NotificationsForm();

            return $notifications->validateNotificationSafeEmail($key, $value, $parameters, $rule);
        });

        return $validator;
    }
}
