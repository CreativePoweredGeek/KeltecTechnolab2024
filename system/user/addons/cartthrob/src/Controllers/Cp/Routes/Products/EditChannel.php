<?php

namespace CartThrob\Controllers\Cp\Routes\Products;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Products\EditChannel as SettingsForm;

class EditChannel extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'products/edit-channel';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/products';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.product_edit_channel';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
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
        $product_fields = $this->settings->get('cartthrob', 'product_channel_fields');
        // validate $id?

        $form = $this->getForm();
        $form->setChannelId($id);
        $defaults = [];
        if (isset($product_fields[$id])) {
            $defaults = $product_fields[$id];
        }

        $vars = [];
        $form->setData($defaults);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                $settings['product_channel_fields'] = $product_fields;
                $settings['product_channel_fields'][$id] = $_POST;
                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url($this->getRoutePath($id)));
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
        $this->addBreadcrumb($this->url('settings/products', true), 'product_options_header');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
