<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Notifications as SettingsForm;
use ExpressionEngine\Library\CP\Table;

class Notifications extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/notifications';

    /**
     * @var string
     */
    protected string $cp_page_title = 'nav_notifications';

    /**
     * @return AbstractForm
     */
    protected function getForm(): AbstractForm
    {
        return new SettingsForm();
    }

    /**
     * @param $id
     * @return AbstractSettingsRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractSettingsRoute
    {
        $form = $this->getForm();
        $defaults = [];
        $vars = [];
        $form->setData($this->settings->settings('cartthrob'));
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                if ($this->settings->save('cartthrob', $_POST)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url($this->getRoutePath()));
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
        $vars['base_url'] = $this->url($this->getRoutePath());
        $vars['sections'] = $form->generate();
        $vars['table'] = $this->getTable();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/notification_settings', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }

    protected function getTable()
    {
        $notifications = $this->settings->get('cartthrob', 'notifications') ?? [];
        $table = ee('CP/Table', [
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => true,
            'sort_dir' => 'desc',
            'sort_col' => 'id',
            'class' => 'notifications',
        ]);

        $table->setColumns([
            'ct.route.notification.title' => ['sort' => false],
            'ct.route.notification.event' => ['sort' => false],
            'ct.route.notification.type' => ['sort' => false],
            'ct.route.notification.email_template' => ['sort' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('notifications')));

        $data = [];
        foreach ($notifications as $key => $notification) {
            $url = ee('CP/URL')->make($this->base_url . '/notifications/edit/' . $key);
            $event = element('event', $notification) != '' ? element('event', $notification) : 'order_status_change';
            $data[] = [
                [
                    'content' => element('title', $notification),
                    'href' => $url,
                ],
                $event,
                element('type', $notification),
                element('template', $notification),
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                    'remove' => [
                        'href' => ee('CP/URL')->make($this->base_url . '/notifications/remove/' . $key),
                        'title' => lang('delete'),
                    ],
                    'copy' => [
                        'href' => ee('CP/URL')->make($this->base_url . '/notifications/copy/' . $key),
                        'title' => lang('copy'),
                    ],
                ]],
            ];
        }

        $table->setData($data);

        $base_url = ee('CP/URL')->make($this->base_url);

        return $table->viewData($base_url);
    }
}
