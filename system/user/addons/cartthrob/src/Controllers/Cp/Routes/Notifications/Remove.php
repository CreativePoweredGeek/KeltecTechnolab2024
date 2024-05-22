<?php

namespace CartThrob\Controllers\Cp\Routes\Notifications;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Notifications\Remove as SettingsForm;

class Remove extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'notifications/remove';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/notifications';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.notification.remove';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        $form = new SettingsForm();

        return $form->setSettings($this->settings);
    }

    /**
     * @param false $id
     * @return AbstractSettingsRoute
     */
    public function process($id = false): AbstractSettingsRoute
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
        $defaults = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ee()->input->post('confirm') == 'y') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                // process settings
                // @todo abstract this nonsense
                if (isset($notifications[$id])) {
                    unset($notifications[$id]);
                }

                $settings['notifications'] = $notifications;
                // nonsense end

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

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath($id));
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('remove');
        $vars['save_btn_text_working'] = 'removing';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/notifications', true), 'nav_notifications');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
