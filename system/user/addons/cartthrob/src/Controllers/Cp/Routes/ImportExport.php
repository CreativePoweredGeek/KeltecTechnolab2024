<?php

namespace CartThrob\Controllers\Cp\Routes;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\AbstractForm;
use CartThrob\Forms\Settings\Import as SettingsForm;

class ImportExport extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'import-export';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.nav.settings_files';

    /**
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        return new SettingsForm();
    }

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        ee()->load->helpers('data_formatting');
        $form = $this->getForm();
        $form->setExportUrl($this->url('import-export/download'));
        $vars = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_FILES);
            $result = $form->validate($_FILES);
            if ($result->isValid()) {
                $tmp_name = element('tmp_name', $_FILES['settings']);
                $new_settings = read_file($tmp_name);
                $settings = _unserialize($new_settings);
                if ($this->settings->save('cartthrob', $settings)) {
                    ee('CP/Alert')->makeInline('shared-form')
                        ->asSuccess()
                        ->withTitle(lang('settings_saved'))
                        ->defer();
                    ee()->functions->redirect($this->url($this->getRoutePath()));
                }
            } else {
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('validation_settings_failed'))
                    ->defer();
                ee()->functions->redirect($this->url($this->getRoutePath()));
            }
        }

        $vars['cp_page_title'] = lang($this->getCpPageTitle());
        $vars['base_url'] = $this->url($this->getRoutePath($id));
        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('import');
        $vars['save_btn_text_working'] = 'importing';
        $vars['has_file_input'] = true;

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url($this->getRoutePath($id), false), $this->getCpPageTitle());

        return $this;
    }
}
