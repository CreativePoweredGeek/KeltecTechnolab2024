<?php

namespace CartThrob\Controllers\Cp\Routes;

use CartThrob\Controllers\Cp\AbstractRoute;
use CartThrob\Exceptions\Controllers\Cp\RouteException;
use ExpressionEngine\Library\CP\Table;

class TaxDb extends AbstractRoute
{
    /**
     * @var string
     */
    protected $route_path = 'tax-db';

    /**
     * @var string
     */
    protected string $cp_page_title = 'ct.route.tax.standalone_tax_database';

    /**
     * @param $id
     * @return AbstractRoute
     * @throws RouteException
     */
    public function process($id = false): AbstractRoute
    {
        $sort_col = ee('Request')->get('sort_col') ?: 'ct.route.tax.standalone_tax_database.id';
        $sort_dir = ee('Request')->get('sort_dir') ?: 'desc';
        $this->per_page = ee('Request')->get('perpage') ?: $this->per_page;

        $query = [
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
        ];
        $base_url = ee('CP/URL')->make($this->base_url . '/tax-db', $query);

        $table = ee('CP/Table', [
            'lang_cols' => true,
            'sort_col' => $sort_col,
            'sort_dir' => $sort_dir,
            'class' => 'tax-db',
            'limit' => $this->per_page,
        ]);

        $vars['cp_page_title'] = lang('ct.route.tax.standalone_tax_database');
        $table->setColumns([
            'ct.route.tax.standalone_tax_database.id',
            'ct.route.tax.standalone_tax_database.tax_name',
            'ct.route.tax.standalone_tax_database.percent',
            'ct.route.tax.standalone_tax_database.shipping_taxable',
            'manage' => [
                'type' => Table::COL_TOOLBAR,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('ct.route.tax.standalone_tax_database.tax_entries')));

        $taxes = ee('Model')
            ->get('cartthrob:Tax');

        $page = ((int)ee('Request')->get('page')) ?: 1;
        $offset = ($page - 1) * $this->per_page; // Offset is 0 indexed

        // Handle Pagination
        $totalTaxes = $taxes->count();

        $taxes->limit($this->per_page)
            ->offset($offset);

        $data = [];

        $sort_map = [
            'ct.route.tax.standalone_tax_database.id' => 'id',
            'ct.route.tax.standalone_tax_database.tax_name' => 'tax_name',
            'ct.route.tax.standalone_tax_database.percent' => 'percent',
            'ct.route.tax.standalone_tax_database.shipping_taxable' => 'shipping_is_taxable',
        ];

        $taxes->order($sort_map[$sort_col], $sort_dir);
        foreach ($taxes->all() as $tax) {
            $url = ee('CP/URL')->make($this->base_url . '/tax-db/edit/' . $tax->getId());

            $data[] = [
                [
                    'content' => $tax->getId(),
                    'href' => $url,
                ],
                $tax->tax_name,
                $tax->percent,
                $tax->shipping_is_taxable == 1 ? lang('yes') : lang('no'),
                ['toolbar_items' => [
                    'edit' => [
                        'href' => $url,
                        'title' => lang('edit'),
                    ],
                    'remove' => [
                        'href' => ee('CP/URL')->make($this->base_url . '/tax-db/remove/' . $tax->getId()),
                        'title' => lang('settings'),
                    ],
                ]],
            ];
        }

        $table->setData($data);
        $vars['table'] = $table->viewData($base_url);
        $vars['pagination'] = ee('CP/Pagination', $totalTaxes)
            ->perPage($this->per_page)
            ->currentPage($page)
            ->render($base_url);

        $vars['base_url'] = $base_url;

        $this->setHeading($this->getCpPageTitle());
        $this->setBody('routes/tax_db', $vars);

        $this->addBreadcrumb($this->url('settings/general', true), 'Settings');
        $this->addBreadcrumb($this->url('settings/taxes', true), 'ct.route.header.taxes_options');
        $this->addBreadcrumb($this->url($this->getRoutePath(), false), $this->getCpPageTitle());

        return $this;
    }
}
