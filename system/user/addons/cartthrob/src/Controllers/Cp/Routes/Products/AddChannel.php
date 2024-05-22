<?php

namespace CartThrob\Controllers\Cp\Routes\Products;

use CartThrob\Controllers\Cp\AbstractSettingsRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Products\AddChannel as SettingsForm;

class AddChannel extends AbstractSettingsRoute
{
    /**
     * @var string
     */
    protected $route_path = 'products/add-channel';

    protected $active_sidebar = 'settings/products';

    protected string $cp_page_title = 'ct.route.header.product_add_channel';

    public function getForm(): AbstractForm
    {
        $form = new SettingsForm();

        return $form->setSettings($this->settings);
    }

    public function process($id = false): AbstractSettingsRoute
    {
        $product_fields = $this->settings->get('cartthrob', 'product_channel_fields');
        // validate $id?

        $form = $this->getForm();
        $form->setChannelId($id);
        $defaults = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                // process settings
                // @todo abstract this nonsense

                $product_channels = $this->settings->get('cartthrob', 'product_channels');
                if (!in_array(ee()->input->post('product_channel'), $product_channels)) {
                    $product_channels[] = ee()->input->post('product_channel');
                }

                $settings['product_channels'] = $product_channels;
                // nonsense end

                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url('products/edit-channel/' . ee()->input->post('product_channel')));
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
        $vars['save_btn_text'] = lang('ct.route.add');
        $vars['save_btn_text_working'] = 'ct.route.adding';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/products', true), 'product_options_header');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
