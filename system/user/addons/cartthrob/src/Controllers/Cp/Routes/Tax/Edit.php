<?php

namespace CartThrob\Controllers\Cp\Routes\Tax;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\Settings\Plugin as SettingsForm;

class Edit extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'tax-plugins/edit';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/taxes';

    /**
     * @var string
     */
    protected string $cp_page_title = 'taxes_edit_header';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        $id = base64_decode(ee()->input->get('plugin'));
        $defaults = $this->settings->get('cartthrob', $id . '_settings');
        $defaults['enabled'] = $this->settings->get('cartthrob', 'tax_plugin') == $id ? '1' : '0';
        $plugin = $this->getTaxPlugin($id);
        if (!$plugin) {
            ee()->functions->redirect($this->url('settings/taxes'));
            exit;
        }

        $this->cp_page_title = $plugin['title'];
        $form = new SettingsForm();
        $form->setPluginData($plugin);

        $vars = [];
        $form->setData($defaults);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $plugin_obj = new $plugin['classname']();
            $form->setData($_POST);
            $result = $plugin_obj->validate($_POST);
            if ($result->isValid()) {
                $settings[$id . '_settings'] = $form->preparePluginData($plugin, $_POST);
                if (ee()->input->post('enabled') == 1) {
                    $settings['tax_plugin'] = $id;
                }

                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url('settings/taxes'));
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
        $vars['base_url'] = $this->url($this->getRoutePath(), true, ['plugin' => base64_encode($id)]);
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/taxes', true), 'ct.route.header.taxes_options');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
