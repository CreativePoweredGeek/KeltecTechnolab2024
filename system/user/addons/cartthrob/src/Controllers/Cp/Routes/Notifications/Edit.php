<?php

namespace CartThrob\Controllers\Cp\Routes\Notifications;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Notifications\Edit as SettingsForm;
use CartThrob\Plugins\Notification\NotificationPlugin;

class Edit extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'notifications/edit';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/notifications';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.notification.edit';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        $form = new SettingsForm();
        $orders_channel = $this->settings->get('cartthrob', 'orders_channel');
        if ($orders_channel) {
            $form->setChannelId($orders_channel);
        }

        return $form;
    }

    /**
     * @param $id
     * @return AbstractRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractRoute
    {
        $notifications = $this->settings->get('cartthrob', 'notifications');
        if (!isset($notifications[$id]) || !is_array($notifications[$id])) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('invalid_notification'))
                ->defer();
            ee()->functions->redirect($this->url('settings/notifications'));
        }

        $form = $this->getForm();
        $defaults = $notifications[$id];

        $vars = [];
        $form->setData($defaults);
        $plugin_type = $_POST['type'] ?? $defaults['type']; // in case they swapped during POST
        $plugin = ee('cartthrob:NotificationsService')->getPluginByType($plugin_type);
        if (!$plugin instanceof NotificationPlugin) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asWarning()
                ->withTitle(lang('invalid_notification'))
                ->defer();
            ee()->functions->redirect($this->url('settings/notifications'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $plugin->validate($_POST);
            if ($result->isValid()) {
                $settings['notifications'] = $this->settings->get('cartthrob', 'notifications');
                $settings['notifications'][$id] = $_POST;

                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url($this->getRoutePath($id)));
                }
            } else {
                $defaults = array_merge($defaults, $_POST);
                $form->setData($defaults);
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('validation_settings_failed'))
                    ->now();
            }
        }

        ee()->cp->add_js_script([
            'file' => ['cp/form_group'],
        ]);

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath($id));
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/notifications', true), 'nav_notifications');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
