<?php

namespace CartThrob\Controllers\Cp\Routes\Settings;

use CartThrob\Controllers\Cp\AbstractPluginRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Taxes as SettingsForm;
use EllisLab\ExpressionEngine\Library\CP\Table;

class Taxes extends AbstractPluginRoute
{
    /**
     * @var string
     */
    protected $route_path = 'settings/taxes';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.header.taxes_options';

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
        $tax_plugin = $this->settings->get('cartthrob', 'tax_plugin');
        $form = $this->getForm();
        $defaults = [];
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
        $vars['table'] = $this->getTable($tax_plugin);
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';
        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/tax_settings', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }

    protected function getTable($tax_plugin)
    {
        $plugins = $this->getTaxPlugins();
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
            'active' => ['sort' => false, 'encode' => false],
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $vars = [];
        $table->setNoResultsText(sprintf(lang('no_found'), lang('tax_plugins')));
        $data = [];
        foreach ($plugins as $plugin) {
            $url = ee('CP/URL')->make($this->base_url . '/tax-plugins/edit', ['plugin' => base64_encode(element('classname', $plugin))]);
            $note = lang(element('note', $plugin));
            $status = ($tax_plugin == element('classname', $plugin) ? 'yes' : 'no');
            $status_css = ($status == 'yes' ? 'st-open' : 'st-pending');
            $data[] = [
                [
                    'content' => $plugin['fulltext_title'],
                    'href' => $url,
                ],
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
