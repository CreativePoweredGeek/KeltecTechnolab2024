<?php

namespace CartThrob\Controllers\Cp\Routes\Vaults;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\Vault as VaultForm;

class Create extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'vaults/create';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.create.vault';

    /**
     * @var string
     */
    protected $active_sidebar = 'vaults';

    /**
     * @param $id
     * @return AbstractRoute
     */
    public function process($id = false): AbstractRoute
    {
        $vars['cp_page_title'] = lang('ct.create.vault');
        $vars['base_url'] = ee('CP/URL')->make($this->base_url . '/vaults/create');

        $form = new VaultForm();
        $vault = ee('Model')
            ->make('cartthrob:Vault');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form->setData($_POST);
            $vault->set($_POST);
            $result = $vault->validate();
            if ($result->isValid()) {
                $vault->save();
                ee('CP/Alert')->makeInline('vault-create')
                    ->asSuccess()
                    ->withTitle(lang('ct.vault_created'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
                exit;
            } else {
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('ct.error.create.vault'))
                    ->now();
            }
        }

        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('ct.save');
        $vars['save_btn_text_working'] = 'btn_saving';

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/form', $vars);
        $this->addBreadcrumb($this->url('vaults'), 'ct.vaults');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
