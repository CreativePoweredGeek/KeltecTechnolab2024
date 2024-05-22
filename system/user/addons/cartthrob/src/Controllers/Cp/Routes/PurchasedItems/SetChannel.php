<?php

namespace CartThrob\Controllers\Cp\Routes\PurchasedItems;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\PurchasedItems\SetChannel as SettingsForm;

class SetChannel extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'purchased-items/set-channel';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/purchased-items';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.purchased_items_set_channel';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        $form = new SettingsForm();

        return $form->setSettings($this->settings);
    }

    /**
     * @param $id
     * @return AbstractSettingsRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractSettingsRoute
    {
        $defaults['purchased_items_channel'] = $this->settings->get('cartthrob', 'purchased_items_channel');
        // validate $id?

        $form = $this->getForm();
        $form->setData($defaults);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                $settings['purchased_items_channel'] = ee()->input->post('purchased_items_channel');
                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url('settings/purchased-items'));
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
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/purchased-items', true), 'nav_purchased_items');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
