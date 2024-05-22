<?php

namespace CartThrob\Controllers\Cp\Routes;

use CartThrob\Controllers\Cp\AbstractRoute;
use ExpressionEngine\Library\CP\Table;

class Vaults extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'vaults';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.vaults';

    /**
     * @param $id
     * @return AbstractRoute
     * @throws \CartThrob\Exceptions\Controllers\Cp\RouteException
     */
    public function process($id = false): AbstractRoute
    {
        $sort_col = ee('Request')->get('sort_col') ?: 'ct.vault.id';
        $sort_dir = ee('Request')->get('sort_dir') ?: 'desc';
        $this->per_page = ee('Request')->get('perpage') ?: $this->per_page;

        $query = [
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
        ];
        $base_url = ee('CP/URL')->make($this->base_url . '/vaults', $query);

        $table = ee('CP/Table', [
            'lang_cols' => true,
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'class' => 'vault-manager',
            'limit' => $this->per_page,
        ]);

        $vars['cp_page_title'] = lang('ct.title');
        $table->setColumns([
            'ct.vault.id',
            'ct.vault.member_id',
            'ct.vault.token',
            'ct.vault.gateway',
            'ct.manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('ct.vaults')));

        $vaults = ee('Model')
            ->get('cartthrob:Vault');

        $page = ((int)ee('Request')->get('page')) ?: 1;
        $offset = ($page - 1) * $this->per_page; // Offset is 0 indexed

        // Handle Pagination
        $totalVaults = $vaults->count();

        $vaults->limit($this->per_page)
            ->offset($offset);

        $data = [];

        $sort_map = [
            'ct.vault.id' => 'id',
            'ct.vault.member_id' => 'member_id',
            'ct.vault.token' => 'token',
            'ct.vault.gateway' => 'gateway',
        ];

        $vaults->order($sort_map[$sort_col], $sort_dir);
        foreach ($vaults->all() as $vault) {
            $url = ee('CP/URL')->make($this->base_url . '/vaults/edit/' . $vault->getId());
            $memberInfo = lang('ct.vault.guest');
            if (is_object($vault->Member)) {
                $memberInfo = [
                    'content' => "{$vault->Member->screen_name} ({$vault->Member->member_id})",
                    'href' => ee('CP/URL')->make('members/profile/settings', ['id' => $vault->Member->member_id]),
                ];
            }

            $data[] = [
                [
                    'content' => $vault->getId(),
                    'href' => $url,
                ],
                $memberInfo,
                $vault->token,
                $vault->gateway,
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                    'remove' => [
                        'href' => ee('CP/URL')->make($this->base_url . '/vaults/delete/' . $vault->getId()),
                        'title' => lang('settings'),
                    ],
                ]],
            ];
        }

        $table->setData($data);
        $vars['table'] = $table->viewData($base_url);
        $vars['pagination'] = ee('CP/Pagination', $totalVaults)
            ->perPage($this->per_page)
            ->currentPage($page)
            ->render($base_url);

        $vars['base_url'] = $base_url;

        $table->setData($data);

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/view-vaults', $vars);
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
