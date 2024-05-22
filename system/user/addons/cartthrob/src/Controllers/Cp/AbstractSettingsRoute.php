<?php

namespace CartThrob\Controllers\Cp;

use CartThrob\Exceptions\Controllers\Cp\RouteException;
use CartThrob\Forms\AbstractForm;

abstract class AbstractSettingsRoute extends AbstractRoute
{
    protected $tabs_view = true;

    protected int $channel_id;

    /**
     * Should contain the specific Settings form elements
     * @return AbstractForm
     */
    abstract protected function getForm(): AbstractForm;

    /**
     * @param $id
     * @return $this
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
        $vars['save_btn_text'] = lang('save');
        $vars['save_btn_text_working'] = 'saving';

        if ($this->tabs_view) {
            $tabs = [];
            foreach ($vars['sections'] as $name => $settings) {
                $tabs[$name] = ee('View')->make('ee:_shared/form/section')
                    ->render(array_merge(['name' => false, 'settings' => $settings], $vars));
            }

            $vars['tabs'] = $tabs;
            $vars['sections'] = [];
        }

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true, ['f']), 'Settings');

        return $this;
    }
}
