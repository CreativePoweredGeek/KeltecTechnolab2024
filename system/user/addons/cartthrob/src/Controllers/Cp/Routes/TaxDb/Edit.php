<?php

namespace CartThrob\Controllers\Cp\Routes\TaxDb;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\TaxDb\Edit as TaxEditForm;
use CartThrob\Model\Tax as TaxModel;

class Edit extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'tax-db/edit';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.tax.standalone_tax_database.edit';

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
            ->get('cartthrob:Tax')
            ->filter('id', $id)
            ->first();

        if (!$tax instanceof TaxModel) {
            ee('CP/Alert')->makeInline('shared-form')
                ->asIssue()
                ->withTitle(lang('ct.route.tax.standalone_tax_database.not_found'))
                ->defer();
            ee()->functions->redirect($this->url('tax-db'));
        }

        $form = new TaxEditForm();
        $form->setData($tax->toArray());
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tax->set($_POST);
            $result = $tax->validate();
            if ($result->isValid()) {
                $tax->save();
                ee('CP/Alert')->makeInline('shared-form')
                    ->asSuccess()
                    ->withTitle(lang('ct.route.tax.standalone_tax_database.updated'))
                    ->defer();

                ee()->functions->redirect($this->url('tax-db'));
                exit;
            } else {
                $form->setData($_POST);
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('ct.sub.error.update_plan'))
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
