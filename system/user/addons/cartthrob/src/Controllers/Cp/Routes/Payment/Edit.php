<?php

namespace CartThrob\Controllers\Cp\Routes\Payment;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\Settings\Payments\Plugin as SettingsForm;

class Edit extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'payment-plugins/edit';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/payments';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.payment_edit';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        $defaults = $this->settings->get('cartthrob', $id . '_settings');
        $defaults['enabled'] = $this->settings->get('cartthrob', 'payment_gateway') == $id ? '1' : '0';
        $plugin = $this->getPaymentPlugin($id);
        if (!$plugin) {
            ee()->functions->redirect($this->url('settings/payments'));
            exit;
        }

        $this->cp_page_title = $plugin['title'];
        $form = new SettingsForm();
        $form->setPluginData($plugin);
        $enabled_gateways = $this->settings->get('cartthrob', 'enabled_gateways');
        $form->setEnabledGateways($enabled_gateways);

        $vars = [];
        $form->setData($defaults);
        $enabled_gateways = $this->settings->get('cartthrob', 'enabled_gateways');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $plugin_obj = new $plugin['classname']();
            $form->setData($_POST);
            $result = $plugin_obj->validate($_POST);
            if ($result->isValid()) {
                $settings[$id . '_settings'] = $form->preparePluginData($plugin, $_POST);
                if (ee()->input->post('enabled') == 1) {
                    $enabled_gateways[$id] = $id;
                } else {
                    if (isset($enabled_gateways[$id])) {
                        unset($enabled_gateways[$id]);
                    }
                }

                $settings['enabled_gateways'] = $enabled_gateways;
                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url('settings/payments'));
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

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath($id));
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/payments', true), 'ct.route.header.payments_options');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
