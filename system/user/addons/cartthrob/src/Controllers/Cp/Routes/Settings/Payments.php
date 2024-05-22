<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Payments as SettingsForm;
use ExpressionEngine\Library\CP\Table;

class Payments extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/payments';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.payments_options';

    /**
     * @return AbstractForm
     */
    protected function getForm(): AbstractForm
    {
        return new SettingsForm();
    }

    /**
     * @param false $id
     * @return AbstractPluginRoute
     */
    public function process($id = false): AbstractPluginRoute
    {
        $payment_plugin = $this->settings->get('cartthrob', 'payment_gateway');
        $available_gateways = $this->settings->get('cartthrob', 'available_gateways');
        $form = $this->getForm();
        $defaults = [];
        $enabled_gateways = $this->settings->get('cartthrob', 'enabled_gateways');
        $form->setData($this->settings->settings('cartthrob'));
        $form->setPaymentGateways($this->getPaymentGateways());
        $form->setEnabledGateways($enabled_gateways);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $result = $form->validate($_POST);
            if ($result->isValid()) {
                // we gotta do a little magic to convert EE array format to what CartThrob expects :/
                if (ee()->input->post('available_gateways')) {
                    $available_gateways = [];
                    foreach (ee()->input->post('available_gateways') as $key => $value) {
                        $available_gateways[$value] = 1;
                    }

                    $_POST['available_gateways'] = $available_gateways;
                }
                // didn't hurt! that didn't hurt me *wipes tear

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
        $vars['table'] = $this->getTable($payment_plugin);
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';
        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/payment_settings', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }

    /**
     * @param $payment_plugin
     * @return mixed
     */
    protected function getTable($payment_plugin): array
    {
        $plugins = $this->getPaymentGateways();
        $table = ee('CP/Table', [
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => true,
            'sort_dir' => 'desc',
            'sort_col' => 'id',
            'class' => 'notifications',
        ]);

        $table->setColumns([
            'plugin_name' => ['sort' => false],
            'details' => ['sort' => false, 'encode' => false],
            'active' => ['sort' => false, 'encode' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $vars = [];
        $enabled_gateways = $this->settings->get('cartthrob', 'enabled_gateways');
        $table->setNoResultsText(sprintf(lang('no_found'), lang('shipping_plugins')));
        $data = [];
        foreach ($plugins as $plugin) {
            $class_name = element('classname', $plugin);
            $url = ee('CP/URL')->make($this->base_url . '/payment-plugins/edit/' . $class_name);
            $note = lang(element('note', $plugin));
            $status = (in_array($class_name, $enabled_gateways) ? 'yes' : 'no');
            $status_css = ($status == 'yes' ? 'st-open' : 'st-pending');
            $data[] = [
                [
                    'content' => $plugin['title'],
                    'href' => $url,
                ],
                $note,
                "<span class='" . $status_css . "'>" . lang($status) . '</span>',
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                ]],
            ];
        }

        $table->setData($data);

        $base_url = ee('CP/URL')->make($this->base_url);

        return $table->viewData($base_url);
    }
}
