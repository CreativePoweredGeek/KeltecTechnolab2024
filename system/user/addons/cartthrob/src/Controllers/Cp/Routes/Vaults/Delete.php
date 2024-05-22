<?php

namespace CartThrob\Controllers\Cp\Routes\Vaults;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Model\Vault as VaultModel;

class Delete extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'vaults/delete';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.vault.delete';

    /**
     * @var string
     */
    protected $active_sidebar = 'vaults';

    /**
     * Delete Vault route
     * @param $id
     * @return array
     */
    public function process($id = false): AbstractRoute
    {
        if (is_null($id)) {
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
        }

        $vault = ee('Model')
            ->get('cartthrob:Vault')
            ->filter('id', $id)
            ->first();

        if (!$vault instanceof VaultModel) {
            ee('CP/Alert')->makeBanner('vault-delete')
                ->asIssue()
                ->withTitle(lang('ct.vault.not_found'))
                ->defer();
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
        }

        if (!empty($_POST) && ee()->input->post('confirm') == 'y') {
            $vault->delete();

            ee('CP/Alert')->makeBanner('vault-delete')
                ->asSuccess()
                ->withTitle(lang('ct.vault.deleted'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/vaults'));
        }

        $memberInfo = lang('ct.vault.guest');
        if (is_object($vault->Member)) {
            $memberInfo = [
                'content' => "{$vault->Member->screen_name} ({$vault->Member->member_id})",
                'href' => ee('CP/URL')->make('members/profile/settings', ['id' => $vault->Member->member_id]),
            ];
        }

        $orderInfo = lang('ct.na');
        if (is_object($vault->Entry)) {
            $orderInfo = [
                'content' => "{$vault->Entry->title} ({$vault->Entry->entry_id})",
                'href' => ee('CP/URL')->make('publish/edit/entry/' . $vault->Entry->entry_id),
            ];
        }

        $vars = [
            'cp_page_title' => lang('ct.vault.delete'),
            'base_url' => ee('CP/URL')->make($this->base_url . '/vaults/delete/' . $vault->id),
            'save_btn_text' => lang('ct.delete'),
            'save_btn_text_working' => lang('ct.deleting'),
            'orderInfo' => $orderInfo,
            'memberInfo' => $memberInfo,
            'vault' => $vault,
            'sections' => [],
        ];

        $vars['sections'][] = [
            [
                'title' => 'ct.vault.confirm_delete',
                'desc' => 'ct.vault.delete.note',
                'caution' => true,
                'fields' => [
                    'confirm' => [
                        'name' => 'confirm',
                        'short_name' => 'confirm',
                        'type' => 'yes_no',
                    ],
                ],
            ],
        ];

        $this->setHeading(lang($this->getCpPageTitle()));
        $this->setBody('routes/delete-vault', $vars);
        $this->addBreadcrumb($this->url('vaults'), 'ct.vaults');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
