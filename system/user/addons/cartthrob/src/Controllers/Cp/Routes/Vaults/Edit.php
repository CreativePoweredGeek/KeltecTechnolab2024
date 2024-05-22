<?php

namespace CartThrob\Controllers\Cp\Routes\Vaults;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Forms\Vault as VaultForm;
use CartThrob\Model\Vault as VaultModel;

class Edit extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'vaults/edit';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.vault.edit';

    /**
     * @var string
     */
    protected $active_sidebar = 'vaults';

    /**
     * Edits a Vault
     * @param int $id
     * @return array
     */
    public function process($id = false): AbstractRoute
    {
        $vars['base_url'] = ee('CP/URL')->make($this->base_url . '/vaults/edit/' . $id);

        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('id', $id)
            ->first();

        if (!$vault instanceof VaultModel) {
            ee('CP/Alert')->makeBanner('vault-edit')
                ->asIssue()
                ->withTitle(lang('ct.vault.not_found'))
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
        }

        $form = new VaultForm();
        $form->setData($vault->toArray());
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vault->set($_POST);
            $result = $vault->validate();
            if ($result->isValid()) {
                $vault->save();
                ee('CP/Alert')->makeInline('vaults')
                    ->asSuccess()
                    ->withTitle(lang('ct.vault.updated'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
                exit;
            } else {
                $form->setData($_POST);
                $vars['errors'] = $result;
                ee('CP/Alert')->makeInline('shared-form')
                    ->asIssue()
                    ->withTitle(lang('ct.error.update_vault'))
                    ->now();
            }
        }

        $vars['sections'] = $form->generate();
        $vars['save_btn_text'] = lang('ct.save');
        $vars['save_btn_text_working'] = 'btn_saving';

        $this->setHeading(lang($this->getCpPageTitle()));
        $this->setBody('routes/form', $vars);
        $this->addBreadcrumb($this->url('vaults'), 'ct.vaults');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
