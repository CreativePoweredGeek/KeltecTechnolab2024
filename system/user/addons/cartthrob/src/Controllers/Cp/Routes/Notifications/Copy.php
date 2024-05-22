<?php

namespace CartThrob\Controllers\Cp\Routes\Notifications;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Notifications\Edit as SettingsForm;

class Copy extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'notifications/copy';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/notifications';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.notification.copy';

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
     * @return AbstractSettingsRoute
     * @throws RouteException
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
        $defaults = $notifications[$id];

        $vars = [];
        $form->setData($defaults);

        ee()->cp->add_js_script([
            'file' => ['cp/form_group'],
        ]);

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url('notifications/add');
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
