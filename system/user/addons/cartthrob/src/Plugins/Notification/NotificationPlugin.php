<?php

namespace CartThrob\Plugins\Notification;

use CartThrob\Plugins\Plugin;

abstract class NotificationPlugin extends Plugin
{
    /**
     * @var string
     */
    public string $event;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var string
     */
    public string $template;

    /**
     * @var int
     */
    public int $id;

    /**
     * @var string
     */
    public string $status_start = 'ANY';

    /**
     * @var string
     */
    public string $status_end = 'ANY';

    /**
     * @var string[]
     */
    protected $_rules = [
        'title' => 'required',
        'template' => 'required',
        'type' => 'required',
    ];

    /**
     * @param array $data
     * @return bool
     */
    abstract public function deliver(array $data): bool;

    /**
     * Placeholder to allow drivers to determine validity
     * @param array $data
     * @return bool
     */
    public function shouldSend(array $data): bool
    {
        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    public function send(array $data = []): bool
    {
        if (!$this->shouldSend($data)) {
            return false;
        }

        $logNotificationSetting = ee()->cartthrob->store->config('log_notifications');
        $data = $this->preProcess($data);
        if (in_array($logNotificationSetting, ['log_only', 'log_and_send'])) {
            $this->log($data);
        }

        if (ee()->extensions->active_hook('cartthrob_send_notification')) {
            ee()->extensions->call('cartthrob_send_notification', $this);

            if (ee()->extensions->end_script === true) {
                return true;
            }
        }

        if (in_array($logNotificationSetting, ['no', 'log_and_send'])) {
            $this->deliver($data);
        }

        return true;
    }

    /**
     * @param array $
     * @param mixed $message
     * @return void
     */
    private function log(array $variables): void
    {
        $data = $this->toArray();
        $data['variables'] = $variables;
        $data['settings'] = $this->data;
        $model = ee('Model')->make('cartthrob:NotificationLog');
        $model->set($data);
        if ($model->validate()) {
            $model->save();
        }
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setData(array $data): NotificationPlugin
    {
        $this->data = $data;
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * @param $key
     * @param bool $default
     * @return array|bool|mixed
     */
    public function getSetting($key, $default = false)
    {
        $settings = $this->data;

        if ($key === false) {
            return ($settings) ? $settings : $default;
        }

        return (isset($settings[$key])) ? $settings[$key] : $default;
    }

    /**
     * @param string $template
     * @param array $variables
     * @param array $constants
     * @param bool $runTemplateEngine
     * @param string $templateToFetch
     * @return mixed
     */
    protected function parse(string $template, array $variables = [], array $constants = [], bool $runTemplateEngine = false, string $templateToFetch = '')
    {
        ee()->load->library('Cartthrob_emails');

        return ee()->cartthrob_emails->parse($template, $variables, $constants, $runTemplateEngine, $templateToFetch);
    }

    /**
     * Allows for manipulations right before delivery
     * @param array $data
     * @return array
     */
    protected function preProcess(array $data): array
    {
        return $data;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'title' => $this->title,
            'event' => $this->event,
            'type' => $this->type,
            'template' => $this->template,
            'status_start' => $this->status_start,
        ];
    }
}
