<?php

namespace CartThrob\Controllers\Cp\Routes\TaxDb;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\TaxDb\Add as TaxEditForm;

class Add extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'tax-db/add';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.tax.standalone_tax_database.add';

    /**
     * @var string
     */
    protected $active_sidebar = 'settings/taxes';

    /**
     * @param false $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        $tax = ee('Model')
            ->make('cartthrob:Tax');
        $form = new TaxEditForm();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tax->set($_POST);
            $result = $tax->validate();
            if ($result->isValid()) {
                $tax->save();
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('ct.route.tax.standalone_tax_database.created'))
                    ->defer();

                ee()->functions->redirect($this->url('tax-db'));
                exit;
            } else {
                $form->setData($_POST);
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

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/taxes', true), 'ct.route.header.taxes_options');
        $this->addBreadcrumb($this->url('tax-db', true), 'ct.route.tax.standalone_tax_database');
        $this->addBreadcrumb($this->url($this->getRoutePath(), true), $this->getCpPageTitle());
        $this->setBody('routes/form', $vars);

        return $this;
    }
}
