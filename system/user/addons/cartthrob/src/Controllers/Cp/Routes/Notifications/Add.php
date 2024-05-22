<?php

namespace CartThrob\Controllers\Cp\Routes\Notifications;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Notifications\Add as SettingsForm;
use CartThrob\Plugins\Notification\NotificationPlugin;

class Add extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'notifications/add';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/notifications';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.notification.add_notification';

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
     * @param false $id
     * @return AbstractSettingsRoute
     */
    public function process($id = false): AbstractRoute
    {
        $form = $this->getForm();
        $defaults = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $plugin_type = $_POST['type'] ?? $defaults['type']; // in case they swapped during POST
            $plugin = ee('cartthrob:NotificationsService')->getPluginByType($plugin_type);
            if (!$plugin instanceof NotificationPlugin) {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asWarning()
                    ->withTitle(lang('invalid_notification'))
                    ->defer();
                ee()->functions->redirect($this->url('settings/notifications'));
            }

            $result = $plugin->validate($_POST);
            if ($result->isValid()) {
                $settings['notifications'] = $this->settings->get('cartthrob', 'notifications');
                $settings['notifications'][] = $_POST;

                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url('settings/notifications'));
                }
            } else {
                $defaults = array_merge($_POST, $defaults);
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
        $vars['save_btn_text'] = lang('ct.route.notification.create');
        $vars['save_btn_text_working'] = 'creating';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/notifications', true), 'nav_notifications');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
