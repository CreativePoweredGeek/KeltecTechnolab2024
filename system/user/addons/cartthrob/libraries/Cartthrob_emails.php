<?php

use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Dependency\Illuminate\Support\Collection;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 * @property Template $TMPL
 */
class Cartthrob_emails
{
    public $email_event;

    /**
     * Cartthrob_emails constructor.
     */
    public function __construct()
    {
        ee()->load->model('cartthrob_settings_model');

        if (!isset(ee()->TMPL)) {
            ee()->load->library('template', null, 'TMPL');
        }

        ee()->load->library('template_helper');
    }

    /**
     * @param string $event
     * @param string $statusStart
     * @param string $statusEnd
     * @return array
     */
    public function get_email_for_event($event, $statusStart = null, $statusEnd = null)
    {
        $emails = [];
        $this->email_event = $event;

        if (!ee()->config->item('cartthrob:notifications')) {
            return $emails;
        }

        foreach (ee()->config->item('cartthrob:notifications') as $notification) {
            if ($notification['type'] == 'email' &&
                $this->eventMatchesNotification($event, $notification)) {
                $emails[] = $this->prepareEmailData($notification);
            } elseif ($notification['type'] == 'email' &&
                $this->statusChangeNeedsNotification($statusStart, $statusEnd, $notification)) {
                $this->email_event = 'status_change';
                $emails[] = $this->prepareEmailData($notification);
            }
        }

        return $emails;
    }

    /**
     * Utility function, sends an email using the EE Core email class.
     *
     * Two ways to use:
     *
     * a) send_email($from, $from_name, $to, $subject, $message, $plaintext, $variables, $constants, $message_template)
     *
     * b) send_email(array('from' => $from, 'from_name' => $from_name, 'to' => $to, 'subject' => $subject, 'message' => $message, 'plaintext' => $plaintext), $variables, $message_template)
     *
     * @param string|array $from (name) or an array containing information from above
     * @param string $from_name
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param bool $plaintext
     * @param array $variables
     * @param array $constants
     * @param string $message_template
     */
    public function sendEmail($from = null, $from_name = '', $to = '', $subject = '', $message = '', $plaintext = false, $variables = [], $constants = null, $message_template = null)
    {
        if (is_array($from)) {
            $args = func_get_args();
            $params = $args[0];
            $variables = Arr::get($args, 1, []);
            $from_name = '';

            foreach ($params as $key => $value) {
                ${$key} = $value;
            }
        }

        ee()->load->library('email');

        $mailtype = ($plaintext) ? 'text' : 'html';

        // if it's an array.. it's possible it MIGHT not contain the from name in it!
        if (!$from || is_array($from)) {
            $from = ee()->config->item('webmaster_email');
        }
        if (!$from_name) {
            $from_name = ee()->config->item('webmaster_name');
        }
        if (!isset($from_reply_to)) {
            $from_reply_to = $from;
        }
        if (!isset($from_reply_to_name)) {
            $from_reply_to_name = $from_name;
        }

        if (is_null($constants)) {
            if (!isset($variables['order_id'])) {
                $variables['order_id'] = '';
            }

            // default behavior, for backwards compat.
            $constants = [
                'ORDER_ID' => $variables['order_id'],
                '{order_id}' => $variables['order_id'],
            ];
        }

        // / @TODO? Added 5.18, due to issue with Lea A's site choking on parsing the second email
        unset(ee()->TMPL);
        ee()->load->library('template', null, 'TMPL');

        $from = $this->parse($from, $variables);
        $from_name = $this->parse($from_name, $variables);

        $from_reply_to = $this->parse($from_reply_to, $variables);
        $from_reply_to_name = $this->parse($from_reply_to_name, $variables);

        $to = $this->parse($to, $variables);
        $subject = $this->parse($subject, $variables, $constants);
        $message = $this->parse($message, $variables, $constants, $runTemplateEngine = true, $message_template);

        if (ee()->extensions->active_hook('cartthrob_send_email')) {
            ee()->extensions->call('cartthrob_send_email', $from, $from_name, $to, $subject, $message, $plaintext, $variables, $constants, $message_template);

            if (ee()->extensions->end_script === true) {
                return;
            }
        }

        ee()->email->clear();
        ee()->email->initialize(['mailtype' => $mailtype, 'validate' => true]);
        ee()->email
            ->from($from, $from_name)
            ->to($to)
            ->reply_to($from_reply_to, $from_reply_to_name)
            ->subject($subject)
            ->message($message);

        $logEmailSetting = ee()->cartthrob->store->config('log_email');

        if (in_array($logEmailSetting, ['log_only', 'log_and_send'])) {
            $this->log($from, $from_name, $to, $subject, $message, $this->email_event, $message_template);
        }

        if (in_array($logEmailSetting, ['no', 'log_and_send'])) {
            ee()->email->send();

            if ($logEmailSetting == 'log_and_send') {
                @ob_start();
                echo ee()->email->print_debugger();
                $message = @ob_get_clean();

                $this->log($from, $from_name, $to, 'debug: ' . $subject, $message, 'email debug: ' . $this->email_event, $message_template);
            }

            ee()->email->clear();
        }
    }

    /**
     * Parses CONSTANTS, {variables}, and can optionally run the template enging
     *
     * @param string $template the template to parse
     * @param array $variables array('foo' => 'bar')  ==  {foo} => bar
     * @param array $constants arrray('FOO' => 'bar')  ==  FOO => bar
     * @param bool $runTemplateEngine whether or not to run the full template engine
     * @param string $templateToFetch template_group/template to fetch
     * @return string|string[]
     */
    public function parse($template, $variables = [], $constants = [], $runTemplateEngine = false, $templateToFetch = '')
    {
        if ($runTemplateEngine) {
            ee()->load->library('template_helper');

            if ($templateToFetch && is_string($templateToFetch)) {
                $templateInfo = ee()->template_helper->fetch_template($templateToFetch, true);

                foreach ($constants as $key => $value) {
                    if (is_array($value)) {
                        continue;
                    }

                    $templateInfo['template_data'] = str_replace($key, $value, $templateInfo['template_data']);
                }

                $template = ee()->template_helper->parse_template(
                    $templateInfo['template_data'],
                    $variables,
                    $templateInfo['parse_php'],
                    $templateInfo['php_parse_location'],
                    $templateInfo['template_type']
                );
            } else {
                foreach ($constants as $key => $value) {
                    if (is_array($value)) {
                        continue;
                    }

                    $template = str_replace($key, $value, $template);
                }

                $template = ee()->template_helper->parse_template($template, $variables);
            }
        } else {
            foreach ($constants as $key => $value) {
                if (is_array($value)) {
                    continue;
                }

                $template = str_replace($key, $value, $template);
            }

            if ($variables) {
                $template = ee()->TMPL->parse_variables($template, [$variables]);
            }
        }

        return $template;
    }

    /**
     * @param $from
     * @param $fromName
     * @param $to
     * @param $subject
     * @param $message
     * @param string|null $emailEvent
     * @param string|null $messageTemplate
     */
    public function log($from, $fromName, $to, $subject, $message, $emailEvent = null, $messageTemplate = null)
    {
        ee()->load->helper('array');

        $fields = ee()->db->list_fields('cartthrob_email_log');
        $data = new Collection([
            'from' => $from,
            'from_name' => $fromName,
            'to' => $to,
            'message_template' => $messageTemplate,
            'subject' => $subject,
            'email_event' => $emailEvent,
            'message' => $message,
            'send_date' => ee()->localize->now,
        ]);

        ee()->db->insert(
            'cartthrob_email_log',
            $data
                ->filter(function ($value, $key) use ($fields) { // Filter non-relevant data
                    return in_array($key, $fields);
                })
                ->map(function ($value) { // XSS clean up
                    return ee('Security/XSS')->clean($value);
                })
                ->all()
        );
    }

    /**
     * Send the member order confirmation email
     *
     * @param $to
     * @param array $order_data
     */
    public function send_confirmation_email($to, $order_data)
    {
        if (ee()->config->item('cartthrob:send_confirmation_email')) {
            $order_data['order_id'] = $order_data['entry_id'];

            unset($order_data['entry_id']);

            $this->sendEmail(
                ee()->config->item('cartthrob:email_order_confirmation_from'),
                ee()->config->item('cartthrob:email_order_confirmation_from_name'),
                $to,
                ee()->config->item('cartthrob:email_order_confirmation_subject'),
                ee()->config->item('cartthrob:email_order_confirmation'),
                ee()->config->item('cartthrob:email_order_confirmation_plaintext'),
                $order_data
            );
        }
    }

    /**
     * @param $to
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_customer_declined_email($to, $orderData)
    {
    }

    /**
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_admin_declined_email($orderData)
    {
    }

    /**
     * @param $to
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_customer_processing_email($to, $orderData)
    {
    }

    /**
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_admin_processing_email($orderData)
    {
    }

    /**
     * @param $to
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_customer_failed_email($to, $orderData)
    {
    }

    /**
     * @param $orderData
     * @TODO Implement or remove
     */
    public function send_admin_failed_email($orderData)
    {
    }

    /**
     * @param $entryId
     * @param $stockLevel
     */
    public function send_low_inventory_email($entryId, $stockLevel)
    {
        $variable_array['entry_id'] = $entryId;

        $constants = ['ENTRY_ID' => $entryId, 'STOCK_LEVEL' => $stockLevel];

        $this->sendEmail(
            ee()->config->item('cartthrob:email_inventory_notification_from'),
            ee()->config->item('cartthrob:email_inventory_notification_from_name'),
            ee()->config->item('cartthrob:low_stock_email'),
            ee()->config->item('cartthrob:email_inventory_notification_subject'),
            ee()->config->item('cartthrob:email_inventory_notification'),
            ee()->config->item('cartthrob:email_low_stock_notification_plaintext'),
            $variable_array,
            $constants
        );
    }

    /**
     * Send the admin order notification email
     *
     * @param array $orderData
     */
    public function send_admin_notification_email($orderData)
    {
        if (ee()->config->item('cartthrob:send_email')) {
            $orderData['order_id'] = $orderData['entry_id'];

            unset($orderData['entry_id']);

            $this->sendEmail(
                ee()->config->item('cartthrob:email_admin_notification_from'),
                ee()->config->item('cartthrob:email_admin_notification_from_name'),
                ee()->config->item('cartthrob:admin_email'),
                ee()->config->item('cartthrob:email_admin_notification_subject'),
                ee()->config->item('cartthrob:email_admin_notification'),
                ee()->config->item('cartthrob:email_admin_notification_plaintext'),
                $orderData
            );
        }
    }

    /**
     * @param $notification
     * @return array
     */
    private function prepareEmailData(array $notification): array
    {
        return [
            'from' => $notification['email_from'],
            'from_name' => $notification['email_from_name'],
            'from_reply_to' => empty($notification['email_reply_to']) ? $notification['email_from'] : $notification['email_reply_to'],
            'from_reply_to_name' => empty($notification['email_reply_to_name']) ? $notification['email_from_name'] : $notification['email_reply_to_name'],
            'to' => $notification['email_to'],
            'message_template' => $notification['email_template'],
            'subject' => $notification['email_subject'],
            'plaintext' => $notification['email_type'] == 'text',
        ];
    }

    /**
     * @param $event
     * @param $notification
     * @return bool
     */
    private function eventMatchesNotification($event, $notification): bool
    {
        return $event && !empty($notification['email_event']) && $notification['email_event'] == $event;
    }

    /**
     * Check for a order status change that has requested a notification
     *
     * @param string $statusStart
     * @param string $statusEnd
     * @param array $notification
     * @return bool
     */
    private function statusChangeNeedsNotification($statusStart, $statusEnd, $notification): bool
    {
        return empty($notification['email_event'])
            && $statusStart && $statusEnd
            && isset($notification['starting_status']) && in_array($notification['starting_status'], ['ANY', $statusStart])
            && isset($notification['ending_status']) && in_array($notification['ending_status'], ['ANY', $statusEnd])
            && $statusStart !== $statusEnd;
    }
}
