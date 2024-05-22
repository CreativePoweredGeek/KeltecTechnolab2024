<?php

use CartThrob\Events\Event;
use CartThrob\Math\Number;
use CartThrob\OrderManager\Services\ReportService;
use CartThrob\Plugins\Payment\PaymentPlugin;
use CartThrob\Services\Order\OrderService;
use CartThrob\Traits\OrderStatusesTrait;
use CartThrob\Transactions\TransactionState;
use ExpressionEngine\Library\CP\Table;
use Illuminate\Support\Str;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
@NOTE @TODO

Currently this requires the following additional fields to be set up

order_refund_id
order_shipping_note
order_tracking_number

Email Address
Subject
Order Complete
*/

class Cartthrob_order_manager_mcp
{
    use OrderStatusesTrait;

    private $module_name = 'cartthrob_order_manager';
    private $base_url = 'addons/settings/cartthrob_order_manager';
    private $sidebar;
    private $config;

    private $templates = [];

    public $per_page = 25;
    public $required_settings = [];
    public $template_errors = [];
    public $templates_installed = [];
    public $extension_enabled = 0;
    public $module_enabled = 0;
    public $limit = '100';
    public $version;
    public $nav = [];
    public $no_nav = [];
    public $default_columns = [
        'row_id',
        'row_order',
        'order_id',
        'entry_id',
        'title',
        'quantity',
        'price',
        'price_plus_tax',
        'weight',
        'shipping',
        'no_tax',
        'no_shipping',
        'extra',
    ];
    public $order_fields = [
        'orders_billing_first_name',
        'orders_billing_last_name',
        'orders_billing_company',
        'orders_billing_address',
        'orders_billing_address2',
        'orders_billing_city',
        'orders_billing_state',
        'orders_billing_zip',
        'orders_country_code',
        'orders_shipping_first_name',
        'orders_shipping_last_name',
        'orders_shipping_company',
        'orders_shipping_address',
        'orders_shipping_address2',
        'orders_shipping_city',
        'orders_shipping_state',
        'orders_shipping_zip',
        'orders_shipping_country_code',
        'orders_customer_email',
        'orders_customer_phone',
        'orders_language_field',
        'orders_full_billing_address',
        'orders_full_shipping_address',
    ];
    public $total_fields = [
        'orders_total',
        'orders_tax',
        'orders_subtotal',
        'orders_shipping',
    ];
    public $params;
    public $cartthrob;
    public $store;
    public $cart;
    public $table = 'cartthrob_order_manager_table';
    private $currency_code = null;
    private $prefix = null;
    private $dec_point = null;
    private $remove_keys = ['name', 'submit', 'x', 'y', 'templates', 'XID', 'CSRF_TOKEN'];

    /**
     * Cartthrob_order_manager_mcp constructor.
     */
    public function __construct()
    {
        $this->config = ee('Addon')->get('cartthrob_order_manager');

        ee()->view->header = [
            'toolbar_items' => [
                'settings' => [
                    'href' => ee('CP/URL')->make($this->base_url . '/settings'),
                    'title' => lang('settings'),
                ],
            ],
        ];

        $this->buildSidebar();

        ee()->load->add_package_path(PATH_THIRD . 'cartthrob_order_manager/');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->library([
            'cartthrob_loader',
            'number',
        ]);

        ee()->load->helper('form');

        if (!ee('Addon')->get('cartthrob')->isInstalled()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.must_be_installed'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons'));
        }

        if (!ee()->cartthrob->store->config('save_orders') && !ee()->cartthrob->store->config('orders_channel')) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.orders_channel_must_be_configured'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make('addons/settings/cartthrob/order_settings'));
        }
    }

    /**
     * Control panel sidebar
     */
    private function buildSidebar()
    {
        $this->sidebar = ee('CP/Sidebar')->make();

        $this->sidebar->addHeader(lang('ct.om.nav'), ee('CP/URL')->make($this->base_url));
        $this->sidebar->addHeader(lang('ct.om.nav.orders'), ee('CP/URL')->make($this->base_url . '/orders'));

        $reportsHeader = $this->sidebar->addHeader(lang('ct.om.nav.reports'));
        $reportsHeaderList = $reportsHeader->addBasicList();

        $reportsHeaderList->addItem(lang('ct.om.nav.order_report'), ee('CP/URL')->make($this->base_url . '/order_report'));
        $reportsHeaderList->addItem(lang('ct.om.nav.customer_report'), ee('CP/URL')->make($this->base_url . '/customer_report'));
        $reportsHeaderList->addItem(lang('ct.om.nav.product_report'), ee('CP/URL')->make($this->base_url . '/product_report'));
        $reportsHeaderList->addItem(lang('ct.om.nav.discount_report'), ee('CP/URL')->make($this->base_url . '/discount_report'));

        $reports = $this->getOrderReports();

        if (!empty($reports)) {
            $customReportsHeader = $this->sidebar->addHeader(lang('ct.om.nav.custom_reports'));

            foreach ($reports as $id => $title) {
                $customReportsHeaderList = $customReportsHeader->addBasicList();

                $params = ee('cartthrob:EncryptionService')->encode(serialize(['id' => $id]));

                $customReportsHeaderList->addItem($title, ee('CP/URL')->make("{$this->base_url}/run_report", ['params' => $params]));
            }
        }

        $utilitiesHeader = $this->sidebar->addHeader(lang('ct.om.nav.utilities'));
        $utilitiesHeaderList = $utilitiesHeader->addBasicList();
        $utilitiesHeaderList->addItem(lang('ct.om.nav.settings'), ee('CP/URL')->make($this->base_url . '/settings'));

        $this->sidebar
            ->addHeader(lang('ct.om.nav.docs'))
            ->withUrl($this->config->get('docs_url'))
            ->urlIsExternal();
    }

    private function initialize()
    {
        $this->params['module_name'] = $this->module_name;
        $this->params['skip_extension'] = true;
        $this->params['nav'] = [
            // // ORDERS
            'view' => ['view' => ee()->lang->line('ct.om.orders_list')],
            'order_report' => ['order_report' => ee()->lang->line('order_report')],
            'edit' => ['edit' => ee()->lang->line('edit')],
            'delete' => ['delete' => ee()->lang->line('delete')],

            // / REPORTS
            'customer_report' => ['customer_report' => ee()->lang->line('customer_report')],
            'product_report' => ['product_report' => ee()->lang->line('product_report')],
            'discount_report' => ['discount_report' => ee()->lang->line('discount_report')],
            'run_report' => ['run_report' => ee()->lang->line('run_report')],

            // / UTILITIES
            'print_invoice' => ['print_invoice' => ee()->lang->line('print_invoice')],
            'print_packing_slip' => ['print_packing_slip' => ee()->lang->line('print_packing_slip')],
        ];

        $this->params['no_form'] = [
            'edit',
            'delete',
            'view',
            'order_report',
            'run_report',
            'customer_report',
            'print_packing_slip',
            'print_invoice',
            'product_report',
        ];

        $this->params['no_nav'] = [
            'edit',
            'delete',
            'run_report',
            'print_invoice',
            'print_packing_slip',
        ];

        ee()->load->library('mbr_addon_builder');
        ee()->mbr_addon_builder->initialize($this->params);
    }

    /**
     * @return mixed
     */
    public function settings()
    {
        if (!empty($_POST)) {
            $this->saveSettings();
        }

        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob-order-manager.css" type="text/css" media="screen" />');

        $settings = ee('cartthrob:SettingsService')->settings($this->module_name, false);

        $vars = [
            'cp_page_title' => lang('ct.om.settings'),
            'base_url' => ee('CP/URL')->make($this->base_url . '/settings'),
            'save_btn_text' => lang('ct.om.save'),
            'save_btn_text_working' => lang('ct.om.saving'),
        ];

        $vars['sections'] = [
            [
                [
                    'title' => 'ct.om.invoice_template',
                    'fields' => [
                        'invoice_template' => [
                            'type' => 'select',
                            'choices' => $this->templates(),
                            'value' => $settings['invoice_template'] ?? null,
                        ],
                    ],
                ],
                [
                    'title' => 'ct.om.packing_slip_template',
                    'fields' => [
                        'packing_slip_template' => [
                            'type' => 'select',
                            'choices' => $this->templates(),
                            'value' => $settings['packing_slip_template'] ?? null,
                        ],
                    ],
                ],
                $this->customTemplateGrid(),
                $this->customReportGrid(),
            ],
        ];

        return [
            'heading' => lang($vars['cp_page_title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:settings')->render($vars),
        ];
    }

    /**
     * @return mixed
     */
    public function product_report()
    {
        $this->initialize();

        ee()->cp->add_js_script(['ui' => 'datepicker']);
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/jquery-ui.min.css" type="text/css" media="screen" />');
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob-order-manager.css" type="text/css" media="screen" />');

        $date_picker_js = "
            if ($('.datepicker').size() > 0)
            {
                $('.datepicker').datepicker({dateFormat: 'yy-mm-dd'});
                $('.datepicker').on('change',function(){
                    var field_name = $(this).attr('name');
                    var value = $(this).val();
                    $(\"input[name='\"+field_name+\"']\").val(value);
                });
            }
        ";
        ee()->javascript->output($date_picker_js);

        ee()->load->model('order_management_model');
        ee()->load->library('table');

        $vars = [];
        $date_range = ee()->input->get_post('where');
        $where = [];

        $vars['date_start'] = null;
        $vars['date_finish'] = null;

        if ($date_range) {
            if ($date_range['date_start']) {
                $start_date = explode('-', $date_range['date_start']);
                $start_time = mktime(0, 0, 0, $start_date[1], $start_date[2], $start_date[0]);
                $where[ee()->db->dbprefix . 'cartthrob_order_items.entry_date >='] = $start_time;
            } else {
                $vars['date_start'] = null;
            }

            if ($date_range['date_finish']) {
                $end_date = explode('-', $date_range['date_finish']);
                $end_time = mktime(23, 59, 59, $end_date[1], $end_date[2], $end_date[0]);
                $where[ee()->db->dbprefix . 'cartthrob_order_items.entry_date <='] = $end_time;
            } else {
                $vars['date_finish'] = null;
            }
        }

        if (ee()->input->get_post('id')) {
            $products = ee()->order_management_model->get_purchased_item(ee()->input->get_post('id'));
        } else {
            $products = ee()->order_management_model->get_purchased_products($where);
        }

        foreach ($products as &$row) {
            $row['options'] = null;

            if ($row['extra'] && $extra = _unserialize($row['extra'], true)) {
                foreach ($extra as $key => $value) {
                    if (!ee()->input->get_post('download')) {
                        $row['options'] .= '<strong>' . $key . '</strong>: ' . $value . '<br>';
                    } else {
                        $row['options'] .= $key . ': ' . $value . '| ';
                    }
                }
            }

            $row['total_sales'] = ee()->number->format($row['total_sales']);
            $row['price'] = ee()->number->format($row['price']);
            $row['price_plus_tax'] = ee()->number->format($row['price_plus_tax']);

            if (!ee()->input->get_post('download')) {
                $href = ee('CP/URL')->make('publish/edit/entry/' . $row['entry_id']);
                $detail_href = ee('CP/URL')->make("{$this->base_url}/run_reload", ['product_id' => $row['entry_id']]);
                $row['entry_id'] = $row['entry_id'] . "<a href='" . $href . "'> (" . lang('ct.om.edit_product') . '&raquo;)</a>';
                $row['title'] = $row['title'] . "<a href='" . $detail_href . "'> (" . lang('ct.om.product_detail_report') . '&raquo;)</a>';
            }

            unset($row['row_id'], $row['row_order'], $row['quantity'], $row['order_id'], $row['weight'], $row['no_tax'], $row['no_shipping'], $row['extra'], $row['entry_date']);
        }

        if (!isset($products[0])) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asIssue()
                ->withTitle(lang('ct.om.no_products_have_been_sold'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/index'));
        }

        $keys = array_keys($products[0]);

        foreach ($keys as &$val) {
            $val = lang('ct.om.' . $val);
        }

        ee()->table->clear();
        ee()->table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">']);
        ee()->table->set_heading($keys);

        if (ee()->input->get_post('download')) {
            $reportService = new ReportService();
            $return_data = false;
            $format = ee()->input->get_post('download');
            $reportService->download($products, $keys, [], $format, ee()->input->post('filename'));
        }

        $vars['products'] = ee()->table->generate($products);
        $vars['hidden_inputs'] = null;

        ee()->load->helper('form');

        if (ee()->input->get_post('id')) {
            foreach ($_POST as $key => $value) {
                $vars['hidden_inputs'] .= form_hidden('id', ee()->input->get_post('id'));
            }
        }

        $dateStart = $_POST['where']['date_start'] ?? null;
        $vars['hidden_inputs'] .= form_hidden('where[date_start]', $dateStart);

        $dateEnd = $_POST['where']['date_finish'] ?? null;
        $vars['hidden_inputs'] .= form_hidden('where[date_finish]', $dateEnd);
        $vars['base_url'] = $this->base_url;
        $vars['export_csv'] = form_open(ee('CP/URL')->make($this->base_url . '/' . __FUNCTION__, ['return' => __FUNCTION__]));

        return [
            'heading' => 'Product Report',
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make($this->base_url . '/orders')->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:product_report')->render($vars),
        ];
    }

    /**
     * @return mixed
     */
    public function index()
    {
        $vars = [];
        $vars['cp_page_title'] = lang('ct.om.dashboard');

        $this->saveReport();
        if ($order_id = ee()->input->get('entry_id')) {
            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        if (ee()->input->get_post('report')) {
            if (is_numeric(ee()->input->get_post('report'))) {
                $report = ee('Model')->get('cartthrob_order_manager:OrderReport')
                    ->fields('id')
                    ->filter('id', (int)ee()->input->get_post('report', true))
                    ->first();

                if ($report) {
                    ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/run_reload", ['id' => $report->id]));
                }
            } else {
                ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
            }
        }

        ee()->load->model('order_model');
        ee()->load->model('order_management_model');
        ee()->load->helper('form', 'array');
        ee()->load->library(['table', 'reports', 'number', 'localize']);

        $orderService = new OrderService();

        if (ee()->input->get('year', true) && ee()->input->get('month', true)) {
            if (ee()->input->get('day')) {
                $name = date('D d', mktime(0, 0, 0, ee()->input->get('month'), ee()->input->get('day'), ee()->input->get('year')));
                $rows = ee()->reports->get_daily_totals(ee()->input->get('day'), ee()->input->get('month'), ee()->input->get('year'));
                $overview = lang('narrow_by_order');
            } else {
                $name = date('F Y', mktime(0, 0, 0, ee()->input->get('month'), 1, ee()->input->get('year')));

                $rows = $orderService->totalsByDay([
                    'year' => ee()->input->get('year'),
                    'month' => ee()->input->get('month'),
                ]);

                foreach ($rows as &$row) {
                    $row['date'] = $row['day'];
                    $row['name'] = date('d F', mktime(0, 0, 0, ee()->input->get('month'), $row['day'], ee()->input->get('year')));
                    $row['href'] = 'day=' . $row['day'] . '&month=' . ee()->input->get('month') . '&year=' . ee()->input->get('year');
                }

                $overview = lang('narrow_by_day');
            }
        } else {
            $where = [];

            if (ee()->input->get('year', true)) {
                $name = ee()->input->get('year');
                $where = ['year' => ee()->input->get('year', true)];
                $overview = lang('narrow_by_month');
            } else {
                $name = (ee()->input->get_post('date_start', true) || ee()->input->get_post('date_finish', true))
                    ? ee()->lang->line('reports_order_totals_in_range')
                    : ee()->lang->line('reports_order_totals_to_date');

                if (ee()->input->get_post('date_start', true)) {
                    $where['entry_start_date'] = strtotime(ee()->input->get_post('date_start', true));
                    $vars['entry_start_date'] = ee()->input->get_post('date_start', true);
                }

                if (ee()->input->get_post('date_finish', true)) {
                    $where['entry_end_date'] = strtotime(ee()->input->get_post('date_finish', true));
                    $vars['entry_end_date'] = ee()->input->get_post('date_finish', true);
                }
            }

            $rows = $orderService->totals($where);

            if (isset($rows['total'])) {
                $rows = [$rows];
            }

            foreach ($rows as &$row) {
                if (!isset($row['month'])) {
                    continue;
                }
                $row['date'] = $row['month'] . $row['year'];
                $row['name'] = date('F Y', mktime(0, 0, 0, $row['month'], 1, $row['year']));
                $row['href'] = 'month=' . $row['month'] . '&year=' . $row['year'];
            }

            $overview = lang('narrow_by_month');
        }

        ee()->cp->add_to_foot('<script src="' . URL_THIRD_THEMES . '/cartthrob/scripts/order-manager.js" type="text/javascript" charset="utf-8"></script>');

        $vars['view'] = ee()->load->view('om_reports_home', [
            'title' => $overview,
            'data' => json_encode($rows),
        ], true);

        $orders = ee()->order_model->get_orders([
            'year' => ee()->localize->format_date('%Y'),
            'month' => ee()->localize->format_date('%m'),
            'day' => ee()->localize->format_date('%d'),
        ]);

        $vars['todays_orders'] = (!empty($orders)) ? $this->todaysOrders($orders) : '';

        $vars['order_totals'] = $this->orderTotals();

        $vars['current_report'] = ee()->input->get_post('report');

        $vars['reports'] = $this->reports();

        $vars['range_options'] = $this->dateRanges();

        ee()->cp->add_js_script(['ui' => 'datepicker']);
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/jquery-ui.min.css" type="text/css" media="screen" />');

        return [
            'heading' => lang($vars['cp_page_title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:index')->render($vars),
        ];
    }

    /**
     * get Order Totals table
     * @return mixed
     */
    public function orderTotalsTable()
    {
        $vars = [];
        $vars['cp_page_title'] = lang('ct.om.dashboard');

        $this->saveReport();
        if ($order_id = ee()->input->get('entry_id')) {
            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        if (ee()->input->get_post('report')) {
            if (is_numeric(ee()->input->get_post('report'))) {
                $report = ee('Model')->get('cartthrob_order_manager:OrderReport')
                    ->fields('id')
                    ->filter('id', (int)ee()->input->get_post('report', true))
                    ->first();

                if ($report) {
                    ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/run_reload", ['id' => $report->id]));
                }
            } else {
                ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
            }
        }

        ee()->load->model('order_model');
        ee()->load->model('order_management_model');
        ee()->load->helper('form', 'array');
        ee()->load->library(['table', 'reports', 'number', 'localize']);

        $vars['order_totals'] = $this->orderTotals();

        return [
            'heading' => lang($vars['cp_page_title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:order_summary')->render($vars),
        ];
    }

    /**
     * saves everything in POST to a like named setting in the DB (if found)
     *
     * @param bool $set_success_message if FALSE, a success message will not be set (in case you want to roll your own.) Otherwise says something like "saved"
     *
     * GET variables:
     * return location to return to.
     * $this->module_name.'_tab' adds a tab to return to (adds: #YOUR_SPECIFIED_TAB to the return value): return=method#some_tab
     */
    public function quick_save($set_success_message = true)
    {
        $data = [];

        foreach (array_keys($_POST) as $key) {
            if (!in_array($key,
                $this->remove_keys) && !preg_match('/^(' . ucwords('cartthrob_order_manager') . '_.*?_settings)_.*/',
                    $key)) {
                $data[$key] = ee()->input->post($key, true);
            }
        }

        foreach ($data as $key => $value) {
            $where = [
                'site_id' => ee()->config->item('site_id'),
                '`key`' => $key,
            ];

            // custom key actions
            switch ($key) {
                case 'cp_menu':
                    $is_installed = (bool)ee()->db->where('class',
                        ucwords('cartthrob_order_manager') . '_ext')->where('hook',
                            'cp_menu_array')->count_all_results('extensions');

                    if ($value && $value = 'yes') {
                        if (!$is_installed) {
                            ee()->db->insert('extensions', [
                                'class' => ucwords('cartthrob_order_manager') . '_ext',
                                'method' => 'cp_menu_array',
                                'hook' => 'cp_menu_array',
                                'settings' => '',
                                'priority' => 10,
                                'version' => $this->version(),
                                'enabled' => 'y',
                            ]);
                        }
                    } else {
                        if ($is_installed) {
                            ee()->db->where('class', ucwords('cartthrob_order_manager') . '_ext')->where('hook',
                                'cp_menu_array')->delete('extensions');
                        }
                    }

                    break;
            }

            $this->saveSetting($key);
        }

        if ($set_success_message) {
            ee()->session->set_flashdata('message_success', sprintf('%s', lang('settings_saved')));
        }

        $return = (ee()->input->get('return')) ? AMP . 'method=' . ee()->input->get('return', true) : '';

        if (ee()->input->post('cartthrob_order_manager' . '_tab')) {
            $return .= '#' . ee()->input->post('cartthrob_order_manager' . '_tab', true);
        }

        ee()->functions->redirect(ee('CP/URL')->make($this->base_url) . $return);
    }

    /**
     * @return mixed
     */
    public function customer_report()
    {
        ee()->load->helper('form');
        ee()->load->model('order_management_model');

        $vars['customer_count'] = ee()->order_management_model->getCustomerCount();

        $this->initialize();

        ee()->load->library('table');

        ee()->table->clear();
        ee()->table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">']);

        $raw_headers = [
            lang('ct.om.customer_name'),
            lang('ct.om.customer_email'),
            lang('ct.om.customer_phone'),
            lang('ct.om.total_order_amount'),
            lang('ct.om.total_order_count'),
            lang('ct.om.total_order_date'),
            '',
        ];

        $defaults = [
            'sort' => ['order_last' => 'desc'],
        ];

        $params['limit'] = $this->limit;
        $defaults['offset'] = ee()->input->get_post('rownum', 0);

        ee()->table->set_heading($raw_headers);

        ee()->load->library('pagination');

        $data_table = $this->customer_report_datasource($defaults, $params, ee()->input->get_post('download'));

        if (ee()->input->get_post('download')) {
            $this->downloadReport($data_table, $raw_headers, [], ee()->input->get_post('download'));
            exit;
        }

        foreach ($data_table['rows'] as $row) {
            ee()->table->add_row(array_values($row));
        }

        ee()->pagination->initialize([
            // Pass the relevant data to the paginate class
            'base_url' => ee('CP/URL')->make("{$this->base_url}/customer_report"),
            'total_rows' => $data_table['pagination']['total_rows'],
            'per_page' => $data_table['pagination']['per_page'],
            'page_query_string' => true,
            'query_string_segment' => 'rownum',
            'full_tag_open' => '<div style="float: none;" class="paginate" title="' . $data_table['pagination']['total_rows'] . ' total entries"><ul>',
            'full_tag_close' => '</ul></div>',
            'first_tag_open' => '<li>',
            'first_tag_close' => '</li>',
            'prev_tag_open' => '<li>',
            'prev_tag_close' => '</li>',
            'cur_tag_open' => '<li><a href="" class="act">',
            'cur_tag_close' => '</a></li>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'next_tag_open' => '<li>',
            'next_tag_close' => '</li>',
            'last_tag_open' => '<li>',
            'last_tag_close' => '</li>',
            'prev_link' => 'Prev',
            'next_link' => 'Next',
            'first_link' => 'First',
            'last_link' => 'Last',
        ]);

        $vars['html'] = ee()->table->generate() . ee()->pagination->create_links();
        $vars['export_csv'] = form_open(ee('CP/URL')->make($this->base_url . '/' . __FUNCTION__, ['return' => __FUNCTION__]));

        return [
            'heading' => 'Customer Report',
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make($this->base_url . '/orders')->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:customer_report')->render($vars),
        ];
    }

    /**
     * @return mixed
     */
    public function discount_report()
    {
        ee()->load->helper('form');
        ee()->load->model('discount_model');

        $vars = [];

        // Download the report
        // @TODO
        if (ee()->input->get_post('download')) {
            list($data, $headers) = $this->discount_export_datasource();

            $service = new ReportService();

            $service->download($data, $headers, [], ee()->input->get_post('download'), $filename);
            exit;
        }

        $this->initialize();

        ee()->load->library('table');

        ee()->table->clear();
        ee()->table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">']);

        $raw_headers = [
            lang('ct.om.discount_id'),
            lang('ct.om.discount_type'),
            lang('ct.om.discount_data'),
            lang('ct.om.discount_used_by'),
            '',
        ];

        $defaults = [
            'sort' => ['entry_date' => 'asc'],
        ];

        $params['limit'] = $this->limit;
        $defaults['offset'] = ee()->input->get_post('rownum', 0);

        ee()->table->set_heading($raw_headers);

        ee()->load->library('pagination');

        $data_table = $this->discount_report_datasource($defaults, $params);

        $vars['discount_count'] = count($data_table['rows']);

        foreach ($data_table['rows'] as $row) {
            ee()->table->add_row(array_values($row));
        }

        ee()->pagination->initialize([
            // Pass the relevant data to the paginate class
            'base_url' => ee('CP/URL')->make("{$this->base_url}/discount_report"),
            'total_rows' => $data_table['pagination']['total_rows'],
            'per_page' => $data_table['pagination']['per_page'],
            'page_query_string' => true,
            'query_string_segment' => 'rownum',
            'full_tag_open' => '<div style="float: none;" class="paginate" title="' . $data_table['pagination']['total_rows'] . ' total entries"><ul>',
            'full_tag_close' => '</ul></div>',
            'first_tag_open' => '<li>',
            'first_tag_close' => '</li>',
            'prev_tag_open' => '<li>',
            'prev_tag_close' => '</li>',
            'cur_tag_open' => '<li><a href="" class="act">',
            'cur_tag_close' => '</a></li>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'next_tag_open' => '<li>',
            'next_tag_close' => '</li>',
            'last_tag_open' => '<li>',
            'last_tag_close' => '</li>',
            'prev_link' => 'Prev',
            'next_link' => 'Next',
            'first_link' => 'First',
            'last_link' => 'Last',
        ]);

        if (count($data_table['rows']) != 0) {
            $vars['html'] = ee()->table->generate() . ee()->pagination->create_links();
        } else {
            $vars['html'] = lang('ct.om.discount_no_results');
        }

        $vars['export_csv'] = form_open(ee('CP/URL')->make($this->base_url . '/' . __FUNCTION__, ['return' => __FUNCTION__]));

        return [
            'heading' => lang('ct.om.discount_report'),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make($this->base_url . '/orders')->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:discount_report')->render($vars),
        ];
    }

    /**
     * @param array $where
     * @param string $order_by
     * @param string $sort
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function customer_export_datasource($where = [], $order_by = 'entry_date', $sort = 'DESC', $limit = null, $offset = null)
    {
        ee()->load->model('cartthrob_field_model');

        $fields = $this->order_fields;
        $default_show = [];
        $headers = [];

        foreach ($fields as $field) {
            if (ee()->cartthrob->store->config($field)) {
                $default_show[] = 'field_id_' . ee()->cartthrob->store->config($field);
                $lang = lang($field);
                $headers['field_id_' . ee()->cartthrob->store->config($field)] = ($lang ? $lang : $field);
            }
        }

        ee()->load->model('order_management_model');

        $rows = ee()->order_management_model->get_customers_reports($where, $order_by, $sort, $limit, $offset);

        $new_rows = [];
        foreach ($rows as $key => &$row) {
            foreach ($row as $k => $r) {
                if (!in_array($k, $default_show)) {
                    unset($row[$k]);
                }
            }

            $new_rows[] = array_merge($headers, $row);
        }

        return [
            'data' => $new_rows,
            'headers' => $headers,
        ];
    }

    public function discount_report_datasource($state, $params = [])
    {
        $offset = $state['offset'];
        $limit = isset($params['limit']) ? $params['limit'] : $this->limit;

        $this->sort = $state['sort'];

        ee()->load->model('discount_model');
        ee()->load->library('localize');

        $sub_params = [];
        foreach ($state['sort'] as $key => $value) {
            $sub_params['order_by'][] = $key;
            $sub_params['sort'][] = $value;
        }

        // let's only show orders that have a status that matches "completed/authorized" orders
        $where = [];

        // $where, $sub_params['order_by'], $sub_params['sort'], $limit, $offset
        $rows = ee()->discount_model->get_all_discounts();

        foreach ($rows as $key => &$row) {
            // Get discount setting name
            $discountClass = (new $row['type']());
            $settingsName = lang($discountClass->settings[0]['name'] ?? $discountClass->title);

            $discountName = $discountClass->toString($row);

            $used_by = (!empty($row['used_by']))
                ? array_count_values(preg_split('#\s*[,|]\s*#', trim($row['used_by'])))
                : [];

            // Set up return data
            $newRow = [
                'id' => $row['entry_id'],
                'discount_type' => $settingsName,
                'data' => $discountName,
                'used_by' => is_array($row['used_by']) ? count($row['used_by']) . ' users' : 'Not used',
                'actions' => '',
            ];

            // link to customer report. can't use post, so we have to use a link
            $row = $newRow;
        }

        return [
            'rows' => $rows,
            'pagination' => [
                'per_page' => $limit,
                'total_rows' => count($rows),
            ],
        ];
    }

    public function discount_export_datasource()
    {
        ee()->lang->loadfile('cartthrob_order_manager');

        $rows = ee()->discount_model->get_all_discounts();

        foreach ($rows as $key => &$row) {
            // Get discount setting name
            $discountClass = (new $row['type']());
            $settingsName = lang($discountClass->settings[0]['name'] ?? $discountClass->title);

            $discountName = $discountClass->toString($row);

            $used_by = (!empty($row['used_by']))
                ? array_count_values(preg_split('#\s*[,|]\s*#', trim($row['used_by'])))
                : [];

            // Set up return data
            $newRow = [
                'id' => $row['entry_id'],
                'discount_type' => $settingsName,
                'data' => $discountName,
                'used_by' => count(explode('|', $row['used_by'])),
            ];

            // link to customer report. can't use post, so we have to use a link
            $row = $newRow;
        }

        $headers = [
            [lang('ct.om.discount_id')],
            [lang('ct.om.discount_type')],
            [lang('ct.om.discount_data')],
            [lang('ct.om.discount_used_by')],
        ];

        return [
            $rows,
            $headers,
        ];
    }

    /**
     * @param $state
     * @param array $params
     * @return array
     */
    public function customer_report_datasource($state, $params = [], $asDownload = false)
    {
        $offset = $state['offset'];
        $limit = isset($params['limit']) ? $params['limit'] : $this->limit;

        $this->sort = $state['sort'];

        ee()->load->model('order_management_model');
        ee()->load->library('localize');

        $sub_params = [];
        foreach ($state['sort'] as $key => $value) {
            $sub_params['order_by'][] = $key;
            $sub_params['sort'][] = $value;
        }

        // let's only show orders that have a status that matches "completed/authorized" orders
        $default_status = ee('cartthrob:SettingsService')->get('cartthrob', 'orders_default_status');
        $where = [];

        $rows = ee()->order_management_model->get_customers($where, $sub_params['order_by'], $sub_params['sort'], $limit, $offset);

        foreach ($rows as $key => &$row) {
            $email_address = null;
            $first_name = null;
            $last_name = null;
            $phone = null;

            if (!empty($row['field_id_' . ee()->cartthrob->store->config('orders_customer_email')])) {
                $email_address = $row['field_id_' . ee()->cartthrob->store->config('orders_customer_email')];
            }
            if (!empty($row['field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')])) {
                $first_name = $row['field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')];
            }
            if (!empty($row['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')])) {
                $last_name = $row['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')];
            }
            if (!empty($row['field_id_' . ee()->cartthrob->store->config('orders_customer_phone')])) {
                $phone = $row['field_id_' . ee()->cartthrob->store->config('orders_customer_phone')];
            }

            // first and last name + link to edit information
            if (ee()->order_management_model->is_member($row['author_id'])) {
                $new_row['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')] = '<a href="' . ee('CP/URL')->make('members/profile/settings', ['id' => $row['author_id']]) . '">' . $first_name . ' ' . $last_name . '(' . $row['author_id'] . ')</a>';
            } else {
                $new_row['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')] = $first_name . ' ' . $last_name;
            }

            // email address + link
            $new_row['field_id_' . ee()->cartthrob->store->config('orders_customer_email')] = $asDownload
                ? $email_address
                : '<a href="mailto:' . $email_address . '">' . $email_address . '</a>';
            // customer phone
            $new_row['field_id_' . ee()->cartthrob->store->config('orders_customer_phone')] = $phone;
            $new_row['order_total'] = ee()->number->format($row['order_total']);
            $new_row['order_count'] = $row['order_count'];

            $new_row['order_last'] = ee()->localize->format_date('%m-%d-%Y %h:%i %a', $row['order_last'], true);

            // link to customer report. can't use post, so we have to use a link
            $new_row['actions'] = '<a href="' . ee('CP/URL')->make("{$this->base_url}/run_reload",
                [
                    'return' => __FUNCTION__,
                    'customer_email' => $email_address,
                ]) . '" class="btn submit">' . lang('ct.om.view_customer_orders') . '</a>';

            $row = $new_row;
        }

        if ($asDownload) {
            return $rows;
        }

        $count = ee()->order_management_model->getCustomerCount();

        return [
            'rows' => $rows,
            'pagination' => [
                'per_page' => $limit,
                'total_rows' => $count,
            ],
        ];
    }

    /**
     * @return mixed
     */
    public function orderReport()
    {
        if ($reportId = ee()->input->get_post('report', true)) {
            $report = ee('Model')->get('cartthrob_order_manager:OrderReport')
                ->filter('id', $reportId)
                ->first();

            if ($report) {
                ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/run_reload", ['id' => $report->id]));
            }
        }

        $order_reports = [];

        $this->initialize();

        ee()->cp->add_js_script(['ui' => 'datepicker']);
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/jquery-ui.min.css" type="text/css" media="screen" />');
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob-order-manager.css" type="text/css" media="screen" />');
        ee()->load->helper('form');

        $data = [];
        $data['date_start'] = null; // ee()->localize->decode_date('%Y-%m-%d',ee()->localize->now - 7*24*60*60);
        $data['date_finish'] = null; // ee()->localize->decode_date('%Y-%m-%d',ee()->localize->now );

        $data['reports'] = $this->getOrderReports();

        // ORDER TOTALS
        $fields = [
            'average_total',
            'discount',
            'orders',
            'shipping',
            'shipping_plus_tax',
            'subtotal',
            'subtotal_plus_tax',
            'tax',
            'total',
        ];

        $default_show = [
            'total',
            'subtotal',
            'tax',
            'shipping',
            'discount',
            'orders',
            'order_items',
        ];

        foreach ($fields as $value) {
            $checked = null;
            if (in_array($value, $default_show)) {
                $checked = ' checked="checked" ';
            }
            $fields_input[] = lang('ct.om.' . $value);
            $fields_input[] = ' <input type="checkbox" value="' . $value . '" ' . $checked . ' name="show_fields[]" />';
        }

        ee()->load->library('table');
        ee()->table->clear();
        ee()->table->set_template([
            'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">',
            'heading_cell_start' => '<th colspan="6">',
        ]);

        $new_list = ee()->table->make_columns($fields_input, 6);
        $data['order_totals'] = ee()->table->generate($new_list);

        // /// SEARCH ORDER FIELDS
        $search_fields = [
            'cartthrob_mcp' => $this,
            'plugin_type' => 'search_fields',
            'plugins' => [
                [
                    'classname' => 'search_fields',
                    'title' => 'search_fields_title',
                    'settings' => [
                        [
                            'name' => 'search_fields',
                            'short_name' => 'search_fields',
                            'type' => 'matrix',
                            'settings' => [
                                [
                                    'name' => 'search_field',
                                    'short_name' => 'search_field',
                                    'type' => 'select',
                                    'attributes' => [
                                        'class' => 'order_channel_fields',
                                    ],
                                ],
                                [
                                    'name' => 'search_content',
                                    'short_name' => 'search_content',
                                    'type' => 'text',
                                ],
                                [
                                    'name' => 'search_not_empty',
                                    'short_name' => 'search_not_empty',
                                    'type' => 'checkbox',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $data['search_fields'] = ee()->mbr_addon_builder->view_plugin_settings($search_fields, true);
        // /// MEMBER INPUT FIELDS

        ee()->table->clear();
        ee()->table->set_template([
            'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">',
            'heading_cell_start' => '<th colspan="4">',
        ]);
        ee()->table->set_heading('');

        $member_field_list = [
            'author_id',
            'email_address',
            'first_name',
            'last_name',
            'city',
            'state',
            'country_code',
        ];
        $member_field_inputs = [];

        foreach ($member_field_list as $value) {
            $member_field_inputs[] = lang('ct.om.' . $value);

            switch ($value) {
                case 'state':
                    $class = 'states_blank';
                    $member_field_inputs[] = form_dropdown('where[' . $value . ']', [], null, "class='" . $class . "'");
                    break;
                case 'country_code':
                    $class = 'countries_blank';
                    $member_field_inputs[] = form_dropdown('where[' . $value . ']', [], null, "class='" . $class . "'");
                    break;
                default:
                    $member_field_inputs[] = form_input('where[' . $value . ']', '');
            }
        }

        $new_list = ee()->table->make_columns($member_field_inputs, 4);
        $data['member_inputs'] = ee()->table->generate($new_list);

        // /// ORDER FIELDS

        ee()->load->model('cartthrob_field_model');

        $channel_id = ee()->cartthrob->store->config('orders_channel');
        $order_channel_fields = ee()->cartthrob_field_model->get_fields_by_channel($channel_id);

        $default_show = [
            'order_total',
            'order_subtotal',
            'order_shipping',
            'order_shipping',
            'order_tax',
            'discount',
            'order_items',
        ];

        $order_fields = [];
        $order_fields['title'] = 'title';
        $order_fields['entry_id'] = 'entry_id';
        $order_fields['status'] = 'status';
        $order_fields['year'] = 'year';
        $order_fields['month'] = 'month';
        $order_fields['day'] = 'day';
        $order_fields['entry_date'] = 'entry_date';
        $order_fields['gateway'] = lang('ct.om.payment_gateway');

        foreach ($order_channel_fields as $value) {
            if (in_array($value['field_name'], $default_show)) {
                $default_show[] = 'field_id_' . $value['field_id'];
            }
            $order_fields['field_id_' . $value['field_id']] = $value['field_label'];
        }

        asort($order_fields);

        foreach ($order_fields as $key => $value) {
            $checked = null;
            if (in_array($key, $default_show)) {
                $checked = ' checked="checked" ';
            }
            $order_fields_input[] = $value;
            $order_fields_input[] = ' <input type="checkbox" value="' . $value . '" ' . $checked . ' name="order_fields[' . $key . ']" />';
        }

        // GENERATE ORDER FIELDS TABLE
        ee()->table->clear();
        ee()->table->set_template([
            'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">',
            'heading_cell_start' => '<th colspan="6">',
        ]);
        ee()->table->set_heading(lang('ct.om.include_order_fields'));

        $new_list = ee()->table->make_columns($order_fields_input, 6);
        $data['order_fields'] = ee()->table->generate($new_list);

        // JAVASCRIPT
        ee()->cp->add_js_script(['ui' => 'datepicker']);
        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/jquery-ui.min.css" type="text/css" media="screen" />');
        ee()->javascript->output("
            if ($('.datepicker').size() > 0) {
                $('.datepicker').datepicker({dateFormat: 'yy-mm-dd'});
            }
        ");

        $modalVars = [
            'name' => 'modal-confirm-remove',
            'form_url' => ee('CP/URL')->make('addons/settings/cartthrob_order_manager/remove_report'),
            'hidden' => [
                'content_id' => '',
            ],
        ];

        $modalHtml = ee('View')->make('ee:_shared/modal_confirm_remove')->render($modalVars);
        ee('CP/Modal')->addModal('remove', $modalHtml);

        $vars = array_merge(
            $data,
            [
                'run_report' => form_open(ee('CP/URL')->make("{$this->base_url}/run_reload", ['return' => __FUNCTION__])),
                'reports_filter' => form_open(ee('CP/URL')->make($this->base_url . '/' . __FUNCTION__), 'id="reports_filter"'),
                'statuses' => $this->getOrderStatuses(),
            ]
        );

        ee()->cp->add_to_foot(<<<PAGE
<script>
    $(document).ready(function () {
        $('button.m-link').click(function (e) {
            var modalIs = $('.' + $(this).attr('rel'));
            console.log('modalIs', modalIs)
            $('.checklist', modalIs)
                .html('') // Reset it
                .append('<li>' + $("select[name='report'] option:selected").first().text() + '</li>');
            $('input[name="content_id"]', modalIs).val($("select[name='report']").val());
            e.preventDefault();
        })
    });
</script>
PAGE
        );

        return [
            'heading' => 'Order Report',
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make($this->base_url . '/orders')->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:order_report')->render($vars),
        ];
    }

    public function run_reload()
    {
        $data = [];

        foreach ($_POST as $key => $value) {
            $data[$key] = ee('Security/XSS')->clean($value);
        }

        foreach ($_GET as $key => $value) {
            $data[$key] = ee('Security/XSS')->clean($value);
        }

        $params = ee('cartthrob:EncryptionService')->encode(serialize($data));

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/run_report", ['params' => $params]));
    }

    public function remove_report()
    {
        $reportService = new ReportService();
        $id = ee()->input->post('content_id', true) ?? ee()->input->post('id', true);
        $reportService->delete($id);
        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order_report"));
    }

    /**
     * @return mixed
     */
    public function run_report()
    {
        ee()->load->helper('array');

        $p_data = [];

        if (isset($_GET['params'])) {
            $p_data = unserialize(ee('cartthrob:EncryptionService')->decode(ee()->input->get('params', true)));
            $p_data = $p_data ?: [];
        } elseif (ee()->input->post('download')) {
            $p_data = array_merge($_GET, $_POST);
        }

        if (isset($p_data['where']) && is_array($p_data['where']) && strtolower(element('status', $p_data['where'])) == 'any') {
            unset($p_data['where']['status']);
            $any_status = 'any';
        }

        ee()->load->model('cartthrob_field_model');
        ee()->load->model('order_management_model');
        ee()->load->model('order_model');

        // 1. ========= AFFECTS PAGE LOAD AND CONTENTS

        // /////////// LOAD EXISTING REPORTS
        if (element('id', $p_data)) {
            $report = ee('Model')->get('cartthrob_order_manager:OrderReport')
                ->fields('settings')
                ->filter('id', (int)element('id', $p_data))
                ->first();

            if ($report) {
                $settings = $report->settings;

                if (isset($settings['save_report'])) {
                    unset($settings['save_report']);
                }

                $p_data = array_merge($p_data, $settings);
            }
        }

        // //////// SAVE REPORTS
        if (element('save_report', $p_data)) {
            $settings = [];
            foreach ($p_data as $key => $value) {
                if (!in_array($key, $this->remove_keys) && !empty($value)) {
                    $settings[$key] = $value;
                }
            }

            ee()->load->library('localize');

            $key = $this->save_report($settings, element('report_title', $p_data, 'Untitled ' . ee()->localize->format_date('%m-%d-%Y %h:%i %a')));

            if ($key) {
                ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                    ->asSuccess()
                    ->withTitle(lang('ct.om.report_saved_successfully'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order_report"));
            } else {
                ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                    ->asIssue()
                    ->withTitle(lang('ct.om.report_not_saved'))
                    ->defer();

                ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order_report"));
            }
        }

        // 2. ========= SET UP PARAMETERS FOR DB SEARCH
        $where_array = (array)element('where', $p_data);
        $where = [];

        $where_array = array_filter($where_array);

        // ////// FILTER BY CUSTOMER
        if (element('customer_email', $p_data)) {
            $where_array['field_id_' . ee()->cartthrob->store->config('orders_customer_email')] = element('customer_email',
                $p_data);
        }

        foreach ($where_array as $key => $value) {
            switch ($key) {
                case 'date_start':
                    $where['entry_start_date'] = strtotime($value);
                    break;
                case 'date_finish':
                    $where['entry_end_date'] = strtotime($value);
                    break;
                case 'total_maximum':
                    if ($field_id = ee()->cartthrob->store->config('orders_total_field')) {
                        $where['field_id_' . $field_id . ' <'] = Number::sanitize($value, true);
                    }
                    break;
                case 'total_minimum':
                    if ($field_id = ee()->cartthrob->store->config('orders_total_field')) {
                        $where['field_id_' . $field_id . ' >'] = Number::sanitize($value, true);
                    }
                    break;
                default:
                    $where[$key] = $value;
            }
        }

        // Filter checks
        if (isset($where['entry_start_date']) && !isset($where['entry_end_date'])) {
            $where['entry_end_date'] = strtotime('now');
        }

        if (isset($where['entry_end_date']) && !isset($where['entry_start_date'])) {
            $where['entry_start_date'] = strtotime('2010-01-01');
        }

        // //// FILTER BY CONTENT IN SPECIFIC FIELDS
        $search_fields = element('search_fields_settings', $p_data);
        $product_order_ids = [];

        if (!empty($search_fields)) {
            $order_items_field = null;
            $order_items_field_id = ee()->cartthrob->store->config('orders_items_field');

            if ($order_items_field_id) {
                $order_items_field = 'field_id_' . $order_items_field_id;
            }

            foreach ($search_fields['search_fields'] as $key => $value) {
                if (!empty($value['search_content']) && !empty($value['search_field'])) {
                    // this could get to be a really slow search on large DBs
                    if ($order_items_field && $value['search_field'] == $order_items_field) {
                        $order_items_where = [];

                        if (is_numeric($value['search_content'])) {
                            // i happen to know that we'll have to specify the DB prefix here
                            $order_items_where[ee()->db->dbprefix . 'cartthrob_order_items.entry_id'] = $value['search_content'];
                        } else {
                            // i happen to know that we'll have to specify the DB prefix here
                            // this will only get EXACT results. no... "like" search.
                            $order_items_where[ee()->db->dbprefix . 'cartthrob_order_items.title'] = $value['search_content'];
                        }

                        $purchased_products_status = null;

                        if (isset($p_data['where']['status'])) {
                            $purchased_products_status = $p_data['where']['status'];
                        } elseif (isset($any_status)) {
                            $purchased_products_status = 'any';
                        }

                        $product_entries = ee()->order_management_model->get_purchased_products([], $order_by = 'title', $sort = 'ASC', null, null, $order_items_where, $purchased_products_status);

                        if ($product_entries) {
                            foreach ($product_entries as $e) {
                                $ordz = ee()->order_management_model->get_related_orders_by_item(element('entry_id', $e));
                                if ($ordz) {
                                    foreach ($ordz as $k => $v) {
                                        $product_order_ids[] = element('order_id', $v);
                                    }
                                }
                            }
                        }
                    } else {
                        $where[$value['search_field']] = $value['search_content'];
                    }
                } elseif (!empty($value['search_field']) && !empty($value['search_not_empty'])) {
                    $where[$value['search_field']] = 'IS NOT NULL';
                }
            }
        }

        if ($product_order_ids) {
            $product_order_ids = array_unique($product_order_ids);
            $where[ee()->db->dbprefix . 'channel_titles.entry_id'] = $product_order_ids;
        }

        // remove empties
        $where = array_filter($where);

        // 3. ========= SET UP THE FIELDS THAT WILL BE SHOWN
        $show_fields = element('show_fields', $p_data);

        if (!$show_fields) {
            $show_fields = [
                'average_total',
                'discount',
                'orders',
                'shipping',
                'shipping_plus_tax',
                'subtotal',
                'subtotal_plus_tax',
                'tax',
                'total',
            ];
        }

        $order_fields = element('order_fields', $p_data);

        if (!$order_fields) {
            $order_fields = [
                'title' => 'title',
                'status' => 'status',
                'entry_date' => 'entry_date',
                'field_id_' . ee()->cartthrob->store->config('orders_total_field') => lang('ct.om.total'),
                'field_id_' . ee()->cartthrob->store->config('orders_subtotal_field') => lang('ct.om.subtotal'),
                'field_id_' . ee()->cartthrob->store->config('orders_tax_field') => lang('ct.om.tax'),
                'field_id_' . ee()->cartthrob->store->config('orders_shipping_field') => lang('ct.om.shipping'),
                'field_id_' . ee()->cartthrob->store->config('orders_discount_field') => lang('ct.om.discount'),
                'field_id_' . ee()->cartthrob->store->config('orders_items_field') => lang('ct.om.items'),
            ];
        }

        // 4. ========= OUTPUT THE CONTENT
        $header_data = $this->order_data($where, $order_by = 'title', $sort = 'DESC', 1, 0, $order_fields, 'CSV');

        if (empty($header_data['order_data'])) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.report_no_results'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order_report"));
        }

        // / based on the single line item we grabbed above, we'll generate the CSV and standard headers
        // headers = standard, raw_headers = csv headers.
        $headers = $raw_headers = [];
        $params = [];

        foreach ($header_data['order_headers'] as $field_id => $type) {
            $label = ee()->cartthrob_field_model->get_field_label(str_replace('field_id_', '', $field_id));

            if ($label) {
                $headers[$field_id] = ['header' => $label];
                $params['fields'][$field_id] = $label;
                $raw_headers[] = $label;
            } else {
                if ($field_id == 'title') {
                    $headers[$field_id] = ['header' => lang('order_id')];
                    $params['fields'][$field_id] = lang('order_id');
                    $raw_headers[] = lang('order_id');
                } else {
                    $headers[$field_id] = ['header' => lang($field_id)];
                    $params['fields'][$field_id] = lang($field_id);
                    $raw_headers[] = lang($field_id);
                }
            }
        }

        // //// SEARCH BY PRODUCT ID
        $order_ids = null;

        if (element('product_id', $p_data)) {
            ee()->load->model('order_management_model');

            $order_ids = ee()->order_management_model->get_related_orders_by_item(element('product_id', $p_data));

            if ($order_ids) {
                foreach ($order_ids as $key => &$value) {
                    $value = $value['order_id'];
                }
            }
        }

        $this->initialize();
        $defaults = [
            'sort' => ['title' => 'desc'],
        ];

        if ($order_ids) {
            $where['entry_id'] = $order_ids;
            $params['where'] = $where;
        } elseif (element('customer_email', $p_data) || element('product_id', $p_data)) {
            $params['where'] = $where;
        } elseif ($where) {
            $params['where'] = $where;
        }

        $params['limit'] = $this->limit;

        $defaults['offset'] = ee()->input->get_post('rownum', 0);

        // /// EXPORT TO CSV
        if (element('download', $p_data)) {
            $rows = $this->order_datasource($defaults, $params, true);
            ee()->load->model('order_model');

            $totals = ee()->order_model->order_totals($where);
            $this->downloadReport($rows, $headers, $totals, $p_data['download'] ?? 'csv');
        }

        ee()->load->library('table');

        ee()->table->clear();

        ee()->table->set_heading($raw_headers);

        ee()->load->library('pagination');

        $data_table = $this->order_datasource($defaults, $params);

        foreach ($data_table['rows'] as $row) {
            ee()->table->add_row(array_values($row));
        }

        ee()->pagination->initialize([
            // Pass the relevant data to the paginate class
            'base_url' => ee('CP/URL')->make("{$this->base_url}/order_report"),
            'total_rows' => $data_table['pagination']['total_rows'],
            'per_page' => $data_table['pagination']['per_page'],
            'page_query_string' => true,
            'query_string_segment' => 'rownum',
            'full_tag_open' => '<div style="float: none;" class="paginate" title="' . $data_table['pagination']['total_rows'] . ' total entries"><ul>',
            'full_tag_close' => '</ul></div>',
            'first_tag_open' => '<li>',
            'first_tag_close' => '</li>',
            'prev_tag_open' => '<li>',
            'prev_tag_close' => '</li>',
            'cur_tag_open' => '<li><a href="" class="act">',
            'cur_tag_close' => '</a></li>',
            'num_tag_open' => '<li>',
            'num_tag_close' => '</li>',
            'next_tag_open' => '<li>',
            'next_tag_close' => '</li>',
            'last_tag_open' => '<li>',
            'last_tag_close' => '</li>',
            'prev_link' => 'Prev',
            'next_link' => 'Next',
            'first_link' => 'First',
            'last_link' => 'Last',
        ]);

        $content = ee()->table->generate() . ee()->pagination->create_links();
        $data['order_table'] = $content;

        // adding the order totals that appear at the top of the screen
        $data = array_merge($data, $this->get_totals($where, $show_fields));

        // adding in any of the get / post variables back into the page in case we want to save the report or generate a CSV using the same parameters
        $data['hidden_inputs'] = null;
        $data['report_title'] = 'Report';

        if (element('report_title', $p_data)) {
            $data['report_title'] = element('report_title', $p_data);
        }

        foreach ($p_data as $key => $value) {
            if (!in_array($key, $this->remove_keys) && !empty($value)) {
                if ($key == 'search_fields_settings') {
                    foreach ($value as $k => $v) {
                        foreach ($v as $kk => $vv) {
                            foreach ($vv as $kkl => $vvv) {
                                $data['hidden_inputs'] .= form_hidden($key . '[' . $k . '][' . $kk . '][' . $kkl . ']', $vvv);
                            }
                        }
                    }
                } else {
                    $data['hidden_inputs'] .= form_hidden($key, $value);
                }
            }
        }

        foreach ($_GET as $key => $value) {
            if (!in_array($key, $this->remove_keys) && !empty($value)) {
                $data['hidden_inputs'] .= form_hidden($key, $value);
            }
        }

        // CUSTOMER SPECIFIC. If this is customer report, we'll add a custom header.
        if (element('customer_email', $p_data)) {
            $customer = ee()->order_management_model->get_customers([
                'field_id_' . ee()->cartthrob->store->config('orders_customer_email') => element('customer_email', $p_data),
            ], $order_by = 'author_id', $sort = 'DESC', $limit = 1, $offset = null);

            $first_name = null;
            $last_name = null;

            if (!empty($customer[0]['field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')])) {
                $first_name = $customer[0]['field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')];
            }

            if (!empty($customer[0]['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')])) {
                $last_name = $customer[0]['field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')];
            }

            $data['report_title'] = lang('ct.om.customer_report_title') . ' ' . $first_name . ' ' . $last_name;
        } elseif (element('product_id', $p_data)) {
            $data['report_title'] = lang('ct.om.order_by_product_report_title') . ': (' . element('product_id', $p_data) . ')';
        }

        // adding the filename dynamically based on the report title
        $data['hidden_inputs'] .= form_hidden('filename', $data['report_title']);
        $data['export_csv'] = form_open(ee('CP/URL')->make($this->base_url . '/' . __FUNCTION__, ['return' => __FUNCTION__]));

        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob-order-manager.css" type="text/css" media="screen" />');

        return [
            'heading' => lang(ee()->lang->line('nav_head_run_report')),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:customer')->render($data),
        ];
    }

    /**
     * @param $settings
     * @param $title
     * @return int
     */
    public function save_report($settings, $title)
    {
        $reportService = new ReportService();

        $report = $reportService->create($title, $settings, 'order');

        return $report->id;
    }

    private function downloadReport($rows, $headers, $totals, $type = 'csv')
    {
        $reportService = new ReportService();
        $reportService->download($rows, $headers, $totals, $type);
        exit;
    }

    private function saveReport()
    {
        // @TODO Rework
        if (ee()->input->get('save')) {
            $reports_settings = ee()->input->post('reports_settings');

            if (is_array($reports_settings) && isset($reports_settings['reports'])) {
                $_POST = ['reports' => $reports_settings['reports']];
            } else {
                $_POST = ['reports' => []];
            }

            $_GET['return'] = '';

            return $this->quick_save();
        }
    }

    /**
     * This function gets order data from the order model and formats it to our requirements for a standard output, or csv.
     *
     * @param $where
     * @param string $orderBy
     * @param string $sort
     * @param null $limit
     * @param int $offset
     * @param array $fields
     * @param string $format
     * @return array
     */
    public function order_data($where, $orderBy = 'title', $sort = 'DESC', $limit = null, $offset = 0, $fields = [], $format = 'REPORT')
    {
        ee()->load->model('order_management_model');
        ee()->load->model('cartthrob_field_model');

        $data = [];

        $extraFieldsToProcess = [
            'gateway' => [
                'field_id' => ee()->cartthrob->store->config('orders_payment_gateway'),
                'callback' => function ($c) {
                    return ucwords(str_replace('_', ' ', $c));
                },
            ],
        ];

        $extraFields = [];

        foreach ($fields as $field => $title) {
            if (in_array($field, array_keys($extraFieldsToProcess))) {
                $extraFields[] = $field;
            }
        }

        foreach ($where as $key => $value) {
            if (!is_array($value) && $value == 'IS NOT NULL') {
                ee()->db->where($key . " <> ''", null, false);
                ee()->db->where($key . ' IS NOT NULL', null, false);
            }
        }

        $orders = ee()->order_management_model->getOrders($where, $orderBy, $sort, $limit, $offset);
        if (!$orders) {
            $data['order_data'] = [];
            $data['order_headers'] = [];

            return $data;
        }

        // /// GET THE FIELD TYPES FOR EACH FIELD. SOME OF THEM WE'RE GOING TO HAVE TO PARSE
        $field_types = [];

        foreach ($fields as $field_id => $label) {
            if ($field_id == 'field_id_') {
                unset($fields[$field_id]);
                continue;
            }

            $field = ee()->cartthrob_field_model->get_field_by_id(str_replace('field_id_', '', $field_id));

            if ($field) {
                $field_types[$field_id] = $field['field_type'];
            } else {
                $field_types[$field_id] = 'text';
            }
        }

        // Default columns if none are supplied

        $default_item_columns = array_fill_keys([
            'row_id',
            'row_order',
            'order_id',
            'entry_id',
            'title',
            'quantity',
            'price',
            'price_plus_tax',
            'weight',
            'shipping',
            'no_tax',
            'no_shipping',
            'extra',
        ], '');

        // Cleaning the orders
        $country_code_field = ee()->cartthrob_field_model->get_field_by_name('order_country_code');
        $state_field = ee()->cartthrob_field_model->get_field_by_name('order_billing_state');
        $ip_address_field = ee()->cartthrob_field_model->get_field_by_name('order_ip_address');

        ee()->load->helper('array');
        ee()->load->library('localize');
        ee()->load->model('order_model');

        foreach ($orders as $key => &$ord) {
            if (!is_array($ord)) {
                continue;
            }

            $order_id = $ord['entry_id'];
            $title = $ord['title'];
            $ord1 = $ord;
            $ord = array_intersect_key($ord, $field_types);

            foreach ($field_types as $key => $type) {
                if (!array_key_exists($key, $ord)) {
                    continue;
                }

                $href = ee('CP/URL')->make("{$this->base_url}/order/{$order_id}");

                if ($format == 'REPORT') {
                    // create a link to the entry
                    if ($key == 'entry_id') {
                        $ord['entry_id'] = "<a href='" . $href . "'>" . $order_id . ' &raquo;</a>';
                    }

                    // create a link to the entry
                    if ($key == 'title') {
                        $ord['title'] = "<a href='" . $href . "'>" . $title . ' &raquo;</a>';
                    }
                }

                // format the entry date
                if ($key == 'entry_date') {
                    $ord['entry_date'] = ee()->localize->format_date('%m-%d-%Y %h:%i %a', $ord['entry_date']);
                }

                if (isset($country_code_field['field_id']) && isset($state_field['field_id']) && isset($ip_address_field['field_id'])) {
                    $this->multi_location(
                        element('field_id_' . $country_code_field['field_id'], $ord1),
                        element('field_id_' . $state_field['field_id'], $ord1),
                        element('field_id_' . $ip_address_field['field_id'], $ord1)
                    );
                }

                if (!is_null($this->currency_code)) {
                    ee()->number->set_prefix($this->currency_code);
                }

                if (!is_null($this->prefix)) {
                    ee()->number->set_prefix($this->prefix);
                }

                if (!is_null($this->dec_point)) {
                    ee()->number->set_dec_point($this->dec_point);
                }

                switch ($type) {
                    case 'cartthrob_order_items':
                        $order_items = ee()->order_model->getOrderItems([$order_id]);
                        $items = null;

                        if (is_array($order_items)) {
                            foreach ($order_items as $item) {
                                if ($format == 'REPORT') {
                                    // create a link to the item
                                    $items .= "<a href='" . ee('CP/URL')->make('publish/edit/entry/' . $item['entry_id']) . "' >";
                                    $items .= $item['title'];
                                    $items .= '(' . $item['entry_id'] . ')';
                                    $items .= '</a>';
                                    $items .= '<br>';
                                    $items .= $item['quantity'] . ' x ' . ee()->number->format($item['price']) . '(' . ee()->number->format($item['price_plus_tax']) . ')';
                                    $items .= '<br>';
                                    $item_options = array_diff_key($item, $default_item_columns);

                                    if ($item_options) {
                                        foreach ($item_options as $option_key => $option) {
                                            if (is_array($option)) {
                                                foreach ($option as $opt) {
                                                    if ($opt['title']) {
                                                        $items .= $option_key . ': ' . $opt['title'] . '<br>';
                                                    }
                                                }
                                            } else {
                                                $items .= $option_key . ': ' . $option . '<br>';
                                            }
                                        }
                                    }
                                    $items .= '<br><br>';
                                } else {
                                    $items .= $item['entry_id'] . ':' . $item['title'] . ':' . $item['quantity'] . ':' . $item['price'] . '|';
                                }
                            }
                        }
                        $ord[$key] = $items;
                        break;
                    case 'cartthrob_price_simple':
                        if ($format == 'REPORT') {
                            $ord[$key] = ee()->number->format($ord[$key]);
                        }
                        break;
                }
            }

            foreach ($extraFields as $field) {
                if (!isset($extraFieldsToProcess[$field])) {
                    continue;
                }
                $extraField = $extraFieldsToProcess[$field];
                $fieldName = 'field_id_' . $extraField['field_id'];
                $datum = element($fieldName, $ord1);

                $ord[$field] = isset($extraField['callback'])
                    ? $extraField['callback']($datum)
                    : $datum;
            }
        }

        if (!isset($orders[0]) || !is_array($orders[0])) {
            return;
        }

        $single_order = array_keys($orders[0]);

        $entry_keys = [
            'entry_id' => 'entry_id',
            'title' => 'title',
            'status' => 'status',
            'year' => 'year',
            'month' => 'month',
            'day' => 'day',
            'entry_date' => 'entry_date',
        ];

        // because we're getting back all of this stuff in a specific format... we can't just do normal sorting.
        $available_keys = array_intersect(array_values($field_types), $entry_keys);

        $headers = array_merge(array_flip($single_order), $available_keys);
        $headers = array_merge($headers, $fields);

        $data['order_data'] = $orders;
        $data['order_headers'] = $headers;

        return $data;
    }

    /**
     * @param $country_code
     * @param null $state
     * @param null $ip_address
     * @return bool|null
     */
    public function multi_location($country_code, $state = null, $ip_address = null)
    {
        $settings = ee('cartthrob:SettingsService')->settings('cartthrob_multi_location');

        if (!$settings) {
            return false;
        }

        $european_union_array = [
            'AUT',
            'BEL',
            'BGR',
            'CYP',
            'CZE',
            'DNK',
            'EST',
            'FIN',
            'FRA',
            'DEU',
            'GRC',
            'HUN',
            'IRL',
            'ITA',
            'LVA',
            'LTU',
            'LUX',
            'MLT',
            'NLD',
            'POL',
            'PRT',
            'ROU',
            'ROM',
            'SVK',
            'SVN',
            'ESP',
            'SWE',
            'GBR',
        ];

        $europe_array = array_merge([
            'HRV',
            'MKD',
            'ISL',
            'MNE',
            'SRB',
            'TUR',
            'ALB',
            'AND',
            'ARM',
            'AZE',
            'BLR',
            'BIH',
            'GEO',
            'LIE',
            'MDA',
            'MCO',
            'NOR',
            'RUS',
            'SMR',
            'CHE',
            'UKR',
            'VAT',
        ], $european_union_array);

        $us_offshore = ['HI', 'AK'];

        ee()->load->library('locales');
        ee()->load->library('number');

        $country_code = ee()->locales->alpha3_country_code($country_code);

        if ($ip_address && ee()->db->table_exists('ip2nation')) {
            ee()->load->add_package_path(APPPATH . 'modules/ip_to_nation/');
            ee()->load->model('ip_to_nation_data', 'ip_data');

            $country_code = ee()->ip_data->find($ip_address);

            if ($country_code !== false) {
                if (!isset(ee()->session->cache['ip_to_nation']['countries'])) {
                    if (include APPPATH . 'config/countries.php') {
                        /*
                         * The countries.php file above contains the countries variable.
                         *
                         * @var array $countries
                         */
                        ee()->session->cache['ip_to_nation']['countries'] = $countries;
                    }
                }

                $country_code = strtoupper($country_code);

                if ($country_code == 'UK') {
                    $country_code = 'GB';
                }
            }

            $country_code = ee()->locales->alpha3_country_code($country_code);
        }

        if (isset($settings['other'])) {
            foreach ($settings['other'] as $other) {
                if ($country_code == $other['country']
                    || $other['country'] == 'global'
                    || ($other['country'] == 'non-continental_us' && in_array($state, $us_offshore))
                    || ($other['country'] == 'europe' && in_array($country_code, $europe_array))
                    || ($other['country'] == 'european_union' && in_array($country_code, $european_union_array))) {
                    if (!empty($other['currency_code'])
                    ) {
                        // going to set the local var, and the config as well. must do this for order totals and other things that use the number lib.
                        // each time number lib gets used, seems the set_ methods don't last till the next use
                        $this->currency_code = $other['currency_code'];
                        ee()->cartthrob->cart->set_config('number_format_defaults_currency_code',
                            $other['currency_code']);
                    }

                    if (!empty($other['prefix'])) {
                        // going to set the local var, and the config as well. must do this for order totals and other things that use the number lib.
                        // each time number lib gets used, seems the set_ methods don't last till the next use
                        $this->prefix = $other['prefix'];
                        ee()->cartthrob->cart->set_config('number_format_defaults_prefix', $other['prefix']);
                    }

                    if (!empty($other['dec_point'])) {
                        switch ($other['dec_point']) {
                            case 'comma':
                                $dec_point = ',';
                                break;
                            case 'period':
                                $dec_point = '.';
                                break;
                            default:
                                $dec_point = '.';
                        }

                        // going to set the local var, and the config as well. must do this for order totals and other things that use the number lib.
                        // each time number lib gets used, seems the set_ methods don't last till the next use
                        $this->dec_point = $dec_point;
                        ee()->cartthrob->cart->set_config('number_format_defaults_dec_point', $dec_point);
                    }
                    break;
                }
            }
        }

        return $settings;
    }

    /**
     * @param $state
     * @param array $params
     * @return array
     */
    public function order_datasource($state, $params = [], $rowsOnly = false)
    {
        $offset = $state['offset'];
        $limit = isset($params['limit']) ? $params['limit'] : $this->limit;
        $sub_params = ['order_by' => [], 'sort' => []];

        if (!empty($state['sort'])) {
            foreach ($state['sort'] as $key => $value) {
                $sub_params['order_by'][] = $key;
                $sub_params['sort'][] = $value;
            }
        }

        $where = [];
        $fields = [];

        if (isset($params['where'])) {
            $where = $params['where'];
        }

        if (isset($params['fields'])) {
            $fields = $params['fields'];
        }

        $rows = $this->order_data($where, $sub_params['order_by'], $sub_params['sort'], $limit, $offset, $fields, $rowsOnly ? 'DOWNLOAD' : 'REPORT');

        if ($rowsOnly) {
            return $rows;
        }

        ee()->load->model('order_model');

        $order_totals = ee()->order_model->order_totals($where);

        return [
            'rows' => $rows['order_data'],
            'pagination' => [
                'per_page' => $limit,
                'total_rows' => $order_totals['orders'],
            ],
        ];
    }

    /**
     * @param $where
     * @param $totals_fields
     * @return array
     */
    public function get_totals($where, $totals_fields)
    {
        ee()->load->model('order_model');
        ee()->load->library('table');

        $data = [];
        $show_fields = [];

        // Start creating the table to output this stuff.
        ee()->table->clear();
        ee()->table->set_template([
            'table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">',
            'heading_cell_start' => '<th colspan="2">',
        ]);
        ee()->table->set_heading(lang('ct.om.totals_header'));

        $order_totals = ee()->order_model->order_totals($where);

        // only going to display the selected fields for this report.
        foreach ($totals_fields as $value) {
            if (isset($order_totals[$value])) {
                if ($value != 'orders') {
                    if (is_numeric($order_totals[$value])) {
                        $show_fields[] = [
                            lang('ct.om.' . $value),
                            ee()->number->format($order_totals[$value]),
                        ];
                    } else {
                        $show_fields[] = [lang('ct.om.' . $value), $order_totals[$value]];
                    }
                }
            } else {
                $show_fields[] = [
                    lang('ct.om.' . $value),
                    lang('ct.om.not_available'),
                ];
            }
        }

        $data['total_data'] = $order_totals;
        $data['total_table'] = ee()->table->generate($show_fields);

        return $data;
    }

    /**
     * @param null $order_id
     * @return array|mixed
     */
    public function orders($order_id = null)
    {
        if ($order_id) {
            return $this->order($order_id);
        }

        ee()->cp->add_to_head('<link rel="stylesheet" href="' . URL_THIRD_THEMES . '/cartthrob/css/cartthrob-order-manager.css" type="text/css" media="screen" />');

        $vars['cp_page_title'] = lang('ct.om.orders');
        $vars['orders'] = [];
        $vars['statuses'] = $statuses = $this->getOrderStatuses();

        $base_url = ee('CP/URL')->make($this->base_url . '/orders');
        $update_url = ee('CP/URL')->make($this->base_url . '/bulk_update_orders');

        $table = ee('CP/Table', [
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => false,
            'class' => 'orders mainTable padTable',
            'limit' => $this->per_page,
        ]);

        $table->setColumns([
            'order_id' => [
                'label' => lang('ct.om.order_id'),
                'sort' => false,
            ],
            'date' => [
                'label' => lang('ct.om.date'),
                'sort' => false,
            ],
            'name' => [
                'label' => lang('ct.om.customer_name'),
                'sort' => false,
            ],
            'total' => [
                'label' => lang('ct.om.total'),
                'sort' => false,
            ],
            'gateway' => [
                'label' => lang('ct.om.gateway'),
                'encode' => false,
                'sort' => false,
            ],
            'status' => [
                'label' => lang('ct.om.status'),
                'encode' => false,
                'sort' => false,
            ],
            'manage' => [
                'label' => lang('ct.om.manage'),
                'type' => Table::COL_TOOLBAR,
            ],
            [
                'type' => Table::COL_CHECKBOX,
            ],
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('ct.om.no_orders')));

        $orders = ee('Model')->get('ChannelEntry')
            ->filter('channel_id', ee()->cartthrob->store->config('orders_channel'))
            ->order('entry_date', 'desc');

        $totalOrders = $orders->count();

        if ($totalOrders < 1) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asAttention()
                ->withTitle(lang('ct.om.none'))
                ->canClose()
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
        }

        $page = ((int)ee()->input->get('page')) ?: 1;

        $orders = $orders->offset($this->per_page * ($page - 1))->limit($this->per_page)->all();
        $orders->alias('cartthrob:CartthrobStatus', 'OrderStatus');

        $vars['pagination'] = ee('CP/Pagination', $totalOrders)
            ->perPage($this->per_page)
            ->currentPage($page)
            ->render($base_url);

        $data = [];

        foreach ($orders as $order) {
            $gateway = $order->{'field_id_' . ee()->cartthrob->store->config('orders_payment_gateway')};
            $gateway = '<small>' . ucwords(str_replace('_', ' ', $gateway) . '</small>');

            $toolbar = [
                'view' => [
                    'href' => ee('CP/URL')->make("{$this->base_url}/orders/{$order->entry_id}"),
                    'title' => lang('ct.om.manage'),
                ],
                'remove' => [
                    'href' => ee('CP/URL')->make("{$this->base_url}/delete/{$order->entry_id}"),
                    'title' => lang('ct.om.delete'),
                ],
            ];

            $customer = $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')} . ' ' . $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')};

            if (ee()->cartthrob->store->config('orders_customer_email')) {
                if ($email = $order->{'field_id_' . ee()->cartthrob->store->config('orders_customer_email')} ?? false) {
                    $params = ee('cartthrob:EncryptionService')->encode(serialize(['customer_email' => $email]));

                    $customer = [
                        'content' => $customer,
                        'href' => ee('CP/URL')->make("{$this->base_url}/run_report", ['params' => $params]),
                    ];
                }
            }

            $data[] = [
                [
                    'content' => $order->title . ' (' . $order->entry_id . ')',
                    'href' => ee('CP/URL')->make("{$this->base_url}/orders/{$order->entry_id}"),
                ],
                date('Y-m-d h:i a', $order->entry_date),
                $customer,
                ee()->number->format($order->{'field_id_' . ee()->cartthrob->store->config('orders_total_field')}),
                $gateway,
                form_dropdown('status[' . $order->entry_id . ']', $statuses, $order->status),
                [
                    'toolbar_items' => $toolbar,
                ],
                [
                    'name' => 'selection[]',
                    'value' => $order->entry_id,
                    'data' => [
                        'title' => $order->title,
                        'confirm' => lang('entry') . ': <b>' . $order->title . '</b>',
                    ],
                ],
            ];
        }

        $table->setData($data);

        $vars['table'] = $table->viewData($base_url);
        $vars['base_url'] = $base_url;
        $vars['update_url'] = $update_url;
        $vars['current_page'] = $page;

        return [
            'heading' => lang($vars['cp_page_title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:orders')->render($vars),
        ];
    }

    /**
     * Bulk order update method updates the status of multiple
     * @return mixed
     */
    public function bulk_update_orders()
    {
        ee()->load->model('order_model');

        $statuses = ee()->input->post('status', false);

        $params['page'] = ((int)ee()->input->post('page', true)) ?: 1;

        $default_status = ee()->input->post('toggle_status', true) ?: false;

        if ($order_ids = ee()->input->post('selection', true)) {
            foreach ($order_ids as $order_id) {
                $order_update = [];

                $order_update['status'] = $default_status ?: $statuses[$order_id];

                ee()->load->model('cartthrob_entries_model');

                $current_entry = ee()->cartthrob_entries_model->entry($order_id);

                if (isset($order_update['status']) && $current_entry['status'] === $order_update['status']) {
                    continue;
                }

                $current_entry['previous_status'] = $current_entry['status'];
                $current_entry['status'] = $order_update['status'];

                $this->sendNotifications($event = 'status_change', $current_entry);

                ee()->order_model->updateOrder($current_entry['entry_id'], $order_update);
            }

            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.orders_updated'))
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/orders", $params));
    }

    public function update_order()
    {
        ee()->load->model('order_model');

        $order_ids = ee()->input->post('id');
        $redirect_id = null;
        $method = ee()->input->get('return', true);

        if (is_array($order_ids)) {
            foreach ($order_ids as $key => $id) {
                $order_update = [];
                foreach ($_POST as $post_key => $post_value) {
                    $post_value = ee()->input->post($post_key);
                    // warning: this will not find any "name" key because that's one of the ignored keys
                    if (!in_array($post_key, $this->remove_keys)) {
                        if (is_array($post_value)) {
                            if (array_key_exists($id, $post_value)) {
                                $order_update[$post_key] = $post_value[$id];
                            }
                        } else {
                            $order_update[$post_key] = $post_value;
                        }
                    }

                    if ($post_key == 'toggle_status' && !empty($post_value)) {
                        $order_update['status'] = (string)$post_value;
                    }

                    if (array_key_exists('status', $order_update) && empty($order_update['status'])) {
                        unset($order_update['status']);
                    }
                }

                // let's get the old status to see if we need to send status update emails.
                ee()->load->model('cartthrob_entries_model');

                $current_entry = ee()->cartthrob_entries_model->entry($id);
                $current_status = $current_entry['status'];

                if (isset($order_update['status']) && ($order_update['status'] !== $current_status)) {
                    // the status has changed. Let's send the email event
                    $current_entry['previous_status'] = $current_status;
                    $current_entry['status'] = $order_update['status'];
                    $this->sendNotifications($event = 'status_change', $current_entry);
                }

                ee()->order_model->updateOrder($id, $order_update);
            }

            ee()->session->set_flashdata('cartthrob_order_manager_system_message', lang('ct.om.orders_updated'));
        } elseif ($order_ids) {
            $order_update = [];

            foreach ($_POST as $post_key => $post_value) {
                // warning: this will not find any "name" key because that's one of the ignored keys
                if (!in_array($post_key, $this->remove_keys)) {
                    $order_update[$post_key] = $post_value;
                }
            }

            $redirect_id = '&id=' . $order_ids;

            ee()->order_model->updateOrder($id, $order_update);
            ee()->session->set_flashdata('cartthrob_order_manager_system_message', lang('ct.om.order_updated'));
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/{$method}") . $redirect_id);
    }

    /**
     * Send a notification email
     *
     * @param string $event
     * @param array $variables
     */
    private function sendNotifications($event, $variables = [])
    {
        ee()->load->library('cartthrob_emails');

        if ($event == Event::TYPE_STATUS_CHANGE) {
            $emails = ee()->cartthrob_emails->get_email_for_event($event, $variables['previous_status'], $variables['status']);
        } else {
            $emails = ee()->cartthrob_emails->get_email_for_event($event);
        }

        if (!empty($emails)) {
            ee()->load->helper('array');

            foreach ($emails as $emailDetails) {
                $emailDetails['variables'] = $variables;

                if ($event == 'completed' && ee()->input->post('email_address')) {
                    // we don't want to send any template not directed to {customer_email}
                    // otherwise we might send emails to customers that should be only for vendors or fulfillment.
                    if (!Str::contains(element('to', $emailDetails), '{customer_email}')) {
                        continue;
                    }

                    $emailDetails['to'] = ee()->input->post('email_address');
                } elseif (ee()->input->post('email_address')) {
                    $emailDetails['to'] = ee()->input->post('email_address');
                } elseif (Str::contains(element('to', $emailDetails), '{customer_email}')) {
                    $emailDetails['to'] = element('order_customer_email', $variables);
                }

                if (Str::contains(element('from_name', $emailDetails), '{customer_name}')) {
                    $emailDetails['from_name'] = element('order_customer_full_name', $variables);
                }

                if (Str::contains(element('fromReplyTo', $emailDetails), '{customer_email}')) {
                    $emailDetails['fromReplyTo'] = element('order_customer_email', $variables);
                }

                if (Str::contains(element('fromReplyToName', $emailDetails), '{customer_name}')) {
                    $emailDetails['fromReplyToName'] = element('order_customer_full_name', $variables);
                }

                if (ee()->input->post('email_subject')) {
                    $emailDetails['subject'] = ee()->input->post('email_subject');
                }

                ee()->cartthrob_emails->sendEmail($emailDetails);
            }
        }
    }

    /**
     * @param null $order_id
     */
    public function print_invoice($order_id)
    {
        ee()->load->library('template_helper');

        $vars['entry_id'] = $order_id;
        $template = ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'invoice_template');
        exit(ee()->template_helper->fetch_and_parse($template, $vars));
    }

    public function printPackingSlip($order_id)
    {
        ee()->load->library('template_helper');

        $vars['entry_id'] = $order_id;
        $template = ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'packing_slip_template');

        exit(ee()->template_helper->fetch_and_parse($template, $vars));
    }

    public function printCustomTemplate()
    {
        if (ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'custom_templates')) {
            foreach (ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'custom_templates') as $key => $template) {
                if ($key == ee()->input->get_post('custom_template_id')) {
                    ee()->load->library('template_helper');
                    $vars['entry_id'] = ee()->input->get_post('id');
                    $template = $template['custom_template'];
                    echo ee()->template_helper->fetch_and_parse($template, $vars);
                }
            }
        }

        // have to exit otherwise EE will do all of its auto-outputting business
        exit;
    }

    public function emailCustomTemplate()
    {
        $redirect_id = '';
        $method = ee()->input->get_post('return', true);

        if (ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'custom_templates')) {
            foreach (ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'custom_templates') as $key => $template) {
                if ($key == ee()->input->get_post('custom_template_id')) {
                    ee()->load->library('cartthrob_emails');

                    // using email address and such from completed email!!!!!!!!!
                    $emailDetails = ee()->cartthrob_emails->get_email_for_event(Event::TYPE_ORDER_COMPLETED);

                    ee()->load->library('template_helper');
                    $vars['entry_id'] = ee()->input->get_post('id');
                    $order_id = ee()->input->post('id');
                    $redirect_id = null;

                    $variables = $this->loadCartWithOrderById($order_id);

                    $emailDetails['variables'] = $variables;
                    $emailDetails['messageTemplate'] = $template['custom_template'];

                    if (ee()->input->post('email_address')) {
                        $emailDetails['to'] = ee()->input->post('email_address');
                    }
                    if (ee()->input->post('email_subject')) {
                        $emailDetails['subject'] = ee()->input->post('email_subject');
                    }

                    ee()->cartthrob_emails->sendEmail($emailDetails);

                    $redirect_id = '&id=' . $order_id;
                }
            }
        }

        ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
            ->asSuccess()
            ->withTitle(lang('ct.om.custom_email_sent'))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/{$method}/{$redirect_id}"));
    }

    /**
     * @param $orderId
     * @return array
     */
    private function loadCartWithOrderById($orderId)
    {
        ee()->load->helper('array');
        ee()->load->library([
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_tax_plugins',
            'cartthrob_payments',
            'form_builder',
            'encrypt',
        ]);
        ee()->load->model('order_model');

        ee()->cartthrob->cart->set_calculation_caching(false);

        $gateway = ee()->input->post('gateway') ?
            ee('cartthrob:EncryptionService')->decode(ee()->input->post('gateway', true)) :
            ee()->cartthrob->store->config('payment_gateway');

        $order = ee()->order_model->getOrder($orderId);
        $order['items'] = ee()->order_model->getOrderItems($orderId);

        $order = array_merge($order, [
            'entry_id' => element('entry_id', $order),
            'order_id' => element('entry_id', $order),
            'shipping' => element('entry_id', $order),
            'shipping_plus_tax' => element('order_shipping_plus_tax', $order),
            'tax' => element('order_tax', $order),
            'subtotal' => element('order_subtotal', $order),
            'subtotal_plus_tax' => element('order_subtotal_plus_tax', $order),
            'discount' => element('order_discount', $order),
            'total' => element('order_total', $order),
            'authorized' => true,
            'transaction_id' => element('order_transaction_id', $order),
            'credit_card_number' => null,
            'create_user' => false,
            'group_id' => null,
            'create_user_data' => null,
            'payment_gateway' => $gateway,
            'auth' => [
                'authorized' => true,
                'transaction_id' => element('order_transaction_id', $order),
            ],
        ]);

        ee()->cartthrob->cart->set_order($order);
        ee()->cartthrob->cart->save();

        return $order;
    }

    public function add_tracking_to_order()
    {
        $entry_id = (int)ee()->input->post('id', true) ?? null;

        if (!$entry_id) {
            ee('CP/Alert')->makeInline('error')
                ->asIssue()
                ->withTitle(lang('ct.om.entry_id_not_passed'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
        }

        $tracking_number = ee()->input->post('order_tracking_number', true);
        $note = ee()->input->post('order_shipping_note', true);
        $status = ee()->input->post('status', true);

        $data = array_merge(
            (array)$this->loadCartWithOrderById($entry_id),
            [
                'entry_id' => $entry_id,
                'order_tracking_number' => $tracking_number,
                'order_shipping_note' => $note,
                'status' => $status,
            ]
        );

        $this->save_tracking($entry_id, $data);

        // when notifications are registered by third party, need to add module name (case sensitive)
        $this->sendNotifications(ucwords('cartthrob_order_manager') . '_tracking_added_to_order', $data);

        ee('CP/Alert')->makeInline('tracking-notification-sent')
            ->asSuccess()
            ->withTitle(lang('ct.om.tracking_added'))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$entry_id}"));
    }

    /**
     * @param $order_id
     * @param array $constants
     * @return bool
     */
    public function save_tracking($order_id, $constants = [])
    {
        ee()->load->model('order_model');

        $order_update = [
            'status' => $constants['status'],
            'order_tracking_number' => $constants['order_tracking_number'],
            'order_shipping_note' => $constants['order_shipping_note'],
        ];

        ee()->order_model->updateOrder($order_id, $order_update);

        return true;
    }

    /**
     * Resend completed order notification email
     */
    public function resendEmail()
    {
        $orderId = ee()->input->get_post('id', true);
        $method = ee()->input->get_post('return', true);

        $this->sendNotifications($event = 'completed', $this->loadCartWithOrderById($orderId));

        ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
            ->asSuccess()
            ->withTitle(lang('ct.om.email_resent'))
            ->defer();

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/{$method}/{$orderId}"));
    }

    /**
     * Refund an order
     */
    public function refund()
    {
        $order_id = ee()->input->post('id');

        if (!$order_id) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.refund_failed'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/orders"));
        }

        ee()->load->library([
            'form_validation',
            'encrypt',
            'form_builder',
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_tax_plugins',
            'cartthrob_payments',
        ]);

        ee()->load->model('purchased_items_model');
        ee()->load->model('order_model');

        $original_order = ee()->order_model->get_order_from_entry($order_id);
        if (empty($original_order['transaction_id'])) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.problem_empty_trans_id') . ' ' . lang('ct.om.refund_failed'))
                ->defer();

            ee()->functions->redirect('CP/URL')->make("{$this->base_url}/order/{$order_id}");
        }

        $gateway = isset($original_order['payment_gateway']) ? $original_order['payment_gateway'] : ee()->cartthrob->store->config('payment_gateway');

        // Load the payment processing plugin that's stored in the extension's settings.
        if (!ee()->cartthrob_payments->setGateway($gateway)->gateway()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.problem_loading_gateway') . ' ' . lang('ct.om.refund_failed'))
                ->defer();

            ee()->functions->redirect('CP/URL')->make("{$this->base_url}/order/{$order_id}");
        }

        if (!ee()->cartthrob_payments->isValidGatewayMethod('refund')) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.gateway_could_not_process_refund') . ' ' . lang('ct.om.refund_failed'))
                ->defer();

            ee()->functions->redirect('CP/URL')->make("{$this->base_url}/order/{$order_id}");
        }

        $partial_refund = ee()->input->post('type') === 'partial';

        if ($partial_refund === true) {
            $refund = Number::sanitize(ee()->input->post('subtotal')) + Number::sanitize(ee()->input->post('tax')) + Number::sanitize(ee()->input->post('shipping'));
        } else {
            $refund = Number::sanitize(ee()->input->post('total'));
        }

        /** @var TransactionState $state */
        $state = ee()->cartthrob_payments->refund($original_order['transaction_id'], $refund, $original_order['last_four_digits'], $_POST);

        if ($state->isAuthorized()) {
            if ($partial_refund) {
                $order_update = [];

                if (ee()->cartthrob->store->config('orders_total_field')) {
                    $order_update['total'] = $original_order['total'] - $refund;
                }

                if (ee()->cartthrob->store->config('orders_subtotal_field')) {
                    $order_update['subtotal'] = $original_order['subtotal'] - Number::sanitize(ee()->input->post('subtotal'));
                }

                if (ee()->cartthrob->store->config('orders_subtotal_plus_tax_field')) {
                    $order_update['subtotal_plus_tax'] = $original_order['subtotal_plus_tax'] - Number::sanitize(ee()->input->post('subtotal')) - Number::sanitize(ee()->input->post('tax'));
                }

                if (ee()->cartthrob->store->config('orders_tax_field')) {
                    $order_update['tax'] = $original_order['tax'] - Number::sanitize(ee()->input->post('tax'));
                }

                if (ee()->cartthrob->store->config('orders_shipping_field')) {
                    $order_update['shipping'] = $original_order['shipping'] - Number::sanitize(ee()->input->post('shipping'));
                }

                if ($order_update) {
                    ee()->order_model->updateOrder($order_id, $order_update);
                }
            } else {
                foreach (ee()->order_model->getOrderItems($order_id) as $item) {
                    $item_options = array_diff_key($item, $this->default_columns);
                    ee()->load->model('product_model');
                    ee()->product_model->increaseInventory($item['entry_id'], $item['quantity'], $item_options);
                }

                ee()->cartthrob_payments->setStatus(Cartthrob_payments::STATUS_REFUNDED, $state, $order_id, $send_email = true);

                $order_update = [
                    'order_refund_id' => $state->getTransactionId(),
                ];

                ee()->order_model->updateOrder($order_id, $order_update);
            }

            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.refund_succeeded'))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.refund_failed') . ' - ' . $state->getMessage())
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
    }

    public function authorize_and_charge()
    {
        $order_id = ee()->input->post('id');

        if (!$order_id) {
            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/orders"));
        }

        $this->loadCartWithOrderById($order_id);

        ee()->load->library([
            'form_validation',
            'encrypt',
            'form_builder',
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_tax_plugins',
            'cartthrob_payments',
        ]);

        ee()->load->model('purchased_items_model');
        ee()->load->model('order_model');

        $original_order = ee()->order_model->getOrder($order_id);
        $gateway = isset($original_order['order_payment_gateway']) ? $original_order['order_payment_gateway'] : ee()->cartthrob->store->config('payment_gateway');

        // Load the payment processing plugin that's stored in the extension's settings.
        if (!ee()->api_cartthrob_payment_gateways->set_gateway($gateway)->gateway()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.gateway_could_not_process'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        if (!ee()->cartthrob_payments->isValidGatewayMethod('auth_and_charge')) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.gateway_could_not_process'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        // @TODO create this method. need to make gateways that support auth + auth and charge.
        /** @var TransactionState $state */
        $state = ee()->cartthrob_payments->setGateway($gateway)->auth_and_charge();

        if ($state->isAuthorized()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.auth_and_charge_succeeded'))
                ->defer();
        } else {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.auth_and_charge_failed') . ' - ' . $state->getMessage())
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
    }

    public function capture()
    {
        $order_id = ee()->input->post('id');

        if (!$order_id) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.capture_failed'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/orders"));
        }

        ee()->load->library([
            'form_validation',
            'encrypt',
            'form_builder',
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_tax_plugins',
            'cartthrob_payments',
        ]);
        ee()->load->model('purchased_items_model');
        ee()->load->model('order_model');
        ee()->load->model('cartthrob_field_model');

        $original_order = ee()->order_model->getOrder($order_id);

        $gateway = isset($original_order['order_payment_gateway']) ? $original_order['order_payment_gateway'] : ee()->cartthrob->store->config('payment_gateway');

        // Load the payment processing plugin that's stored in the extension's settings.
        if (!ee()->cartthrob_payments->setGateway($gateway)->gateway()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.problem_loading_gateway'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/edit", ['id' => $order_id]));
        }

        if (!ee()->cartthrob_payments->isValidGatewayMethod('capture')) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.gateway_can_not_capture'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        $transaction_id = $original_order['field_id_' . ee()->cartthrob->store->config('orders_transaction_id')];

        $amount = 0;

        if (ee()->input->post('total')) {
            $amount = Number::sanitize(ee()->input->post('total'));
        }

        // need to unset the post variables before sending to the capture method
        unset($_POST);

        /** @var TransactionState $state */
        $state = ee()->cartthrob_payments->gateway()->capture($transaction_id, $amount, $order_id);

        if ($state->isAuthorized()) {
            ee()->cartthrob_payments->relaunchCartSnapshot($order_id);
            ee()->cartthrob_payments->checkoutCompleteOffsite($state, $order_id, Cartthrob_payments::COMPLETION_TYPE_STOP);

            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.capture_succeeded'))
                ->defer();

            $order_items = ee()->order_model->getOrderItems($order_id);

            foreach ($order_items as $item) {
                $item_options = array_diff_key($item, $this->default_columns);
                ee()->load->model('product_model');
                ee()->product_model->reduce_inventory($item['entry_id'], $item['quantity'], $item_options);
            }
        } else {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.capture_failed') . ' - ' . $state->getMessage())
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
    }

    public function void()
    {
        $order_id = ee()->input->post('id');

        $this->loadCartWithOrderById($order_id);

        ee()->load->library([
            'form_validation',
            'encrypt',
            'form_builder',
            'api/api_cartthrob_payment_gateways',
            'api/api_cartthrob_tax_plugins',
            'cartthrob_payments',
        ]);
        ee()->load->model('purchased_items_model');
        ee()->load->model('order_model');
        ee()->load->model('cartthrob_field_model');

        $original_order = ee()->order_model->getOrder($order_id);

        $gateway = isset($original_order['order_payment_gateway']) ? $original_order['order_payment_gateway'] : ee()->cartthrob->store->config('payment_gateway');

        // Load the payment processing plugin that's stored in the extension's settings.
        if (!ee()->cartthrob_payments->setGateway($gateway)->gateway()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.problem_loading_gateway'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        if (!ee()->cartthrob_payments->isValidGatewayMethod('void')) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.gateway_could_not_process'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
        }

        $transaction_id = $original_order['field_id_' . ee()->cartthrob->store->config('orders_transaction_id')];

        /** @var TransactionState $state */
        $state = ee()->cartthrob_payments->gateway()->void($transaction_id);

        if ($state->isAuthorized()) {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.void_succeeded'))
                ->defer();

            ee()->cartthrob_payments->setStatus(Cartthrob_payments::STATUS_VOIDED, $state, $order_id);
        } else {
            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_error')
                ->asIssue()
                ->withTitle(lang('ct.om.void_failed'))
                ->addToBody($state->getMessage())
                ->defer();
        }

        ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/order/{$order_id}"));
    }

    /**
     * @param $order_id
     * @return html
     */
    public function order($order_id)
    {
        if (!$order_id) {
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/orders'));
        }

        ee()->load->helper('array');
        ee()->load->library(['locales', 'cartthrob_payments']);
        ee()->load->model(['order_model', 'cartthrob_field_model']);

        $vars = (array)ee()->order_model->getOrder($order_id);

        $settings = ee('cartthrob:SettingsService')->settings($this->module_name);

        // based on (at least) the country code we're going to try to get the correct currency symbol for this order. only works if multi currency is installed
        if (isset($vars['order_country_code'])) {
            $this->multi_location($vars['order_country_code'],
                element('order_billing_state', $vars),
                element('order_ip_address', $vars)
            );
        }

        if (!array_key_exists('order_tracking_number', $vars)) {
            $vars['order_tracking_number'] = null;
        }

        if (!array_key_exists('order_shipping_note', $vars)) {
            $vars['order_shipping_note'] = null;
        }

        // Set the order_transaction_id based on the configured transaction id field
        if (!array_key_exists('order_transaction_id', $vars) && array_key_exists('field_id_' . ee()->cartthrob->store->config('orders_transaction_id'), $vars)) {
            $vars['order_transaction_id'] = $vars['field_id_' . ee()->cartthrob->store->config('orders_transaction_id')];
        }

        $vars['order_items'] = ee()->order_model->getOrderItems($order_id);

        $keys = array_merge($this->order_fields, $this->total_fields);

        foreach ($keys as $k) {
            if (!is_null($this->currency_code)) {
                ee()->number->set_prefix($this->currency_code);
            }

            if (!is_null($this->prefix)) {
                ee()->number->set_prefix($this->prefix);
            }

            if (!is_null($this->dec_point)) {
                ee()->number->set_dec_point($this->dec_point);
            }

            $vars[$k] = null;
            if (ee()->cartthrob->store->config($k . '_field') && array_key_exists('field_id_' . ee()->cartthrob->store->config($k . '_field'), $vars)) {
                $vars[$k] = $vars['field_id_' . ee()->cartthrob->store->config($k . '_field')];

                if (in_array($k, $this->total_fields)) {
                    $vars[$k] = ee()->number->format($vars[$k]);
                }
            } elseif (ee()->cartthrob->store->config($k) && array_key_exists('field_id_' . ee()->cartthrob->store->config($k), $vars)) {
                $vars[$k] = $vars['field_id_' . ee()->cartthrob->store->config($k)];

                if (in_array($k, $this->total_fields)) {
                    $vars[$k] = ee()->number->format($vars[$k]);
                }
            }
        }

        ee()->load->model('order_management_model');

        if (!ee()->order_management_model->is_member($vars['author_id'])) {
            $vars['author_id'] = null;
        }

        // added because one customer's site could't handle $vars['view'] = $vars;
        $view = $vars;
        $vars['base_url'] = $this->base_url;
        $vars['statuses'] = $this->getOrderStatuses();
        $vars['order_payment_gateway'] = $view['order_payment_gateway'] = isset($vars['order_payment_gateway']) ? $vars['order_payment_gateway'] : ee()->cartthrob->store->config('payment_gateway');
        $vars['view'] = $view;
        $vars['form_edit'] = form_open(ee('CP/URL')->make("{$this->base_url}/form_update",
            ['return' => 'edit']));
        $vars['form_delete'] = form_open(ee('CP/URL')->make("{$this->base_url}/delete_order",
            ['return' => 'view']));

        $vars['href_member'] = ee('CP/URL')->make('members/profile/settings', ['id' => '']);
        $vars['custom_templates'] = [];

        $vars['print_invoice'] = (isset($settings['invoice_template']) && !empty($settings['invoice_template'])) ? ee('CP/URL')->make($this->base_url . '/print_invoice/' . $order_id) : null;
        $vars['print_packing_slip'] = (isset($settings['packing_slip_template']) && !empty($settings['packing_slip_template'])) ? ee('CP/URL')->make($this->base_url . '/print_packing_slip/' . $order_id) : null;

        if (isset($settings['custom_templates']) && !empty($settings['custom_templates'])) {
            foreach ($settings['custom_templates'] as $key => $template) {
                if (!empty($template['custom_template'])) {
                    $vars['custom_templates'][$key] = [
                        'link' => ee('CP/URL')->make("{$this->base_url}/print_custom_template",
                            ['id' => $order_id, 'custom_template_id' => $key]),
                        'name' => $template['custom_template_name'],
                        'form' => form_open(ee('CP/URL')->make("{$this->base_url}/email_custom_template")),
                    ];
                }
            }
        }

        // adding in the capture functionality
        $vars['captured'] = null;
        $vars['voided'] = null;
        $vars['form_capture'] = null;
        $vars['form_void'] = null;
        $vars['refunded'] = null;
        $vars['refund_form'] = null;
        $vars['refund_form_extras'] = [];

        $gateway = ee()->cartthrob_payments->setGateway($vars['order_payment_gateway'])->gateway();
        if (!empty($vars['order_payment_gateway'])) {
            if ($gateway instanceof PaymentPlugin) {
                if (ee()->cartthrob_payments->getOrderStatus($order_id) != 'refunded' && ee()->cartthrob_payments->isValidGatewayMethod('refund')) {
                    if (ee()->cartthrob_payments->isValidGatewayMethod('refundForm')) {
                        $vars['refund_form_extras'] = ee()->cartthrob_payments->setGateway($vars['order_payment_gateway'])->gateway()->refundForm();
                    }
                    $vars['refund_form'] = ee('View')->make('cartthrob_order_manager:order/refund')->render($vars);
                } elseif (ee()->cartthrob_payments->getOrderStatus($order_id) == 'refunded') {
                    $vars['refunded'] = lang('refunded');
                }

                // adding in the capture button
                if (ee()->cartthrob_payments->getOrderStatus($order_id) == 'processing') {
                    if (ee()->cartthrob_payments->isValidGatewayMethod('capture')) {
                        $vars['form_capture'] = form_open(ee('CP/URL')->make("{$this->base_url}/capture",
                            ['return' => 'edit']));
                    }
                } else {
                    $vars['captured'] = lang('captured');
                }

                // adding in the void button
                if (ee()->cartthrob_payments->getOrderStatus($order_id) == 'processing') {
                    if (ee()->cartthrob_payments->isValidGatewayMethod('void')) {
                        $vars['form_void'] = form_open(ee('CP/URL')->make("{$this->base_url}/void",
                            ['return' => 'edit']));
                    }
                } else {
                    $vars['captured'] = lang('captured');
                }
            }
        }

        return [
            'heading' => lang($vars['title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make($this->base_url . '/orders')->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:order')->render($vars),
        ];
    }

    /**
     * @return mixed
     */
    public function delete($order_id)
    {
        if (!$order_id) {
            ee()->functions->redirect(ee('CP/URL')->make($this->base_url . '/orders'));
        }

        $orderService = new OrderService();
        $order = $orderService->get($order_id);

        if (!empty($_POST) && ee()->input->post('confirm') == 'y') {
            $order->delete();

            ee('CP/Alert')->makeInline('cartthrob_order_manager_system_message')
                ->asSuccess()
                ->withTitle(lang('ct.om.deleted'))
                ->defer();

            ee()->functions->redirect(ee('CP/URL')->make("{$this->base_url}/orders"));
        }

        $vars = [
            'cp_page_title' => lang('ct.om.delete_order'),
            'base_url' => ee('CP/URL')->make($this->base_url . '/delete/' . $order->entry_id),
            'save_btn_text' => lang('ct.om.delete_order'),
            'save_btn_text_working' => lang('ct.om.deleting'),
            'sections' => [],
        ];

        $vars['sections'][] = [
            [
                'title' => 'ct.om.confirm_delete',
                'desc' => 'ct.om.delete_order_note',
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

        return [
            'heading' => lang($vars['cp_page_title']),
            'breadcrumb' => [
                ee('CP/URL')->make($this->base_url)->compile() => lang('ct.om.title'),
                ee('CP/URL')->make("{$this->base_url}/orders")->compile() => lang('ct.om.orders'),
            ],
            'body' => ee('View')->make('cartthrob_order_manager:delete')->render($vars),
        ];
    }

    /**
     * @TODO Resolve in CartThrob core's navigation builder and remove
     */
    public function om_sales_dashboard()
    {
        ee()->functions->redirect(ee('CP/URL')->make($this->base_url));
    }

    /**
     * @return array
     */
    private function getOrderReports(): array
    {
        $reports = [];
        $reportsModel = ee('Model')->get('cartthrob_order_manager:OrderReport')->all();

        if ($reportsModel->count() > 0) {
            foreach ($reportsModel as $report) {
                $reports[$report->id] = $report->report_title;
            }
        }

        return $reports;
    }

    /**
     * Save module settings
     */
    private function saveSettings()
    {
        $this->saveSetting('invoice_template');
        $this->saveSetting('packing_slip_template');
        $this->saveSetting('custom_templates');
        $this->saveSetting('reports');
    }

    /**
     * Save an individual module setting
     * @param $key
     */
    private function saveSetting($key)
    {
        $value = $this->prepareValue(ee()->input->post($key, true));

        $settingModel = ee('Model')->get('cartthrob_order_manager:Setting')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('key', $key)
            ->first();

        if (empty($settingModel)) {
            $settingModel = ee('Model')->make('cartthrob_order_manager:Setting');
        }

        $settingModel->key = $key;
        $settingModel->site_id = ee()->config->item('site_id');
        $settingModel->value = $value;

        $result = $settingModel->validate();

        if ($result->isValid()) {
            $settingModel->save();
        }
    }

    /**
     * Prepare post values for saving.
     * @param $value
     * @return array
     */
    private function prepareValue($value)
    {
        if (is_array($value) && isset($value['rows'])) {
            $data = [];

            foreach ($value['rows'] as $k => $v) {
                $data[] = $v;
            }

            $value = $data;
        }

        return $value;
    }

    /**
     * Retrieve array of templates
     * @return array
     */
    private function templates(): array
    {
        if (!empty($this->templates)) {
            return $this->templates;
        }

        $templates = [
            '' => '---',
        ];

        $all_templates = ee('Model')->get('Template')
            ->filter('site_id', ee()->config->item('site_id'))
            ->with('TemplateGroup')
            ->order('TemplateGroup.group_name')
            ->order('template_name')
            ->all();

        foreach ($all_templates as $template) {
            $templates[$template->TemplateGroup->group_name . '/' . $template->template_name] = $template->TemplateGroup->group_name . ': ' . $template->template_name;
        }

        return $templates;
    }

    /**
     * @param $orders
     * @return mixed
     */
    private function todaysOrders($orders)
    {
        if (empty($orders) || !is_array($orders)) {
            return [];
        }

        usort($orders, function ($a, $b) {
            return $b['entry_date'] <=> $a['entry_date'];
        });

        $todays_order_list = [];

        foreach ($orders as $order) {
            $first_name = null;
            $last_name = null;

            if (ee()->cartthrob->store->config('orders_billing_first_name')) {
                $first_name = element('field_id_' . ee()->cartthrob->store->config('orders_billing_first_name'), $order);
            }

            if (ee()->cartthrob->store->config('orders_billing_last_name')) {
                $last_name = element('field_id_' . ee()->cartthrob->store->config('orders_billing_last_name'), $order);
            }

            $member = ee('CP/URL')->make('members/profile/settings', ['id' => '']);

            ee()->load->model('order_management_model');

            if (ee()->order_management_model->is_member($order['author_id'])) {
                $member_info = "<a href='" . $member . element('author_id', $order) . "'>(" . element('author_id', $order) . ') ' . $first_name . ' ' . $last_name . ' &raquo;</a><br> ';
            } else {
                $member_info = $first_name . ' ' . $last_name;
            }

            ee()->load->library('localize');

            $date = ee()->localize->format_date('%h:%i %a', element('entry_date', $order), true);

            $todays_order_list[] = [
                "<a href='" . ee('CP/URL')->make("{$this->base_url}/order/" . element('entry_id', $order)) . "'>" . element('title', $order) . '</a>',
                ee()->number->format(element('order_total', $order)),
                element('status', $order),
                $member_info,
                $date,
                '',
            ];
        }

        $todays_orders_header = [
            lang('ct.om.todays_orders'),
            lang('ct.om.total'),
            lang('ct.om.status'),
            lang('ct.om.customer'),
            lang('ct.om.time'),
            '',
        ];

        array_unshift($todays_order_list, $todays_orders_header);

        return ee()->table->generate($todays_order_list);
    }

    /**
     * @return mixed
     */
    private function orderTotals()
    {
        ee()->load->library('table');

        ee()->table->clear();
        ee()->table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">']);

        $orderService = new OrderService();

        $order_totals = $orderService->totals();

        $totalSales = array_sum(array_column($order_totals, 'total'));
        $totalOrders = array_sum(array_column($order_totals, 'count'));
        $averageOrderValue = ($totalOrders > 0) ? $totalSales / $totalOrders : 0;

        ee()->load->library('localize');

        $years_orders = $orderService->totals([
            'year' => ee()->localize->format_date('%Y'),
        ]);

        $months_orders = $orderService->totals([
            'year' => ee()->localize->format_date('%Y'),
            'month' => ee()->localize->format_date('%m'),
        ]);

        $todays_orders = $orderService->totals([
            'year' => ee()->localize->format_date('%Y'),
            'month' => ee()->localize->format_date('%m'),
            'day' => ee()->localize->format_date('%d'),
        ]);

        return ee()->table->generate(
            [
                [
                    lang('ct.om.order_totals'),
                    lang('ct.om.amount'),
                ],
                [
                    lang('today_sales'),
                    ee()->number->format($todays_orders['total'] ?? 0),
                ],
                [
                    lang('month_sales'),
                    ee()->number->format($months_orders['total'] ?? 0),
                ],
                [
                    lang('year_sales'),
                    ee()->number->format(array_sum(array_column($years_orders, 'total')) ?? 0),
                ],
                [
                    lang('ct.om.total_sales'),
                    ee()->number->format($totalSales),
                ],
                [
                    lang('ct.om.average_sale'),
                    ee()->number->format($averageOrderValue),
                ],
                [
                    lang('ct.om.todays_orders'),
                    $todays_orders['count'] ?? 0,
                ],
                [
                    lang('ct.om.months_orders'),
                    $months_orders['count'] ?? 0,
                ],
                [
                    lang('ct.om.years_orders'),
                    array_sum(array_column($years_orders, 'count')) ?? 0,
                ],
                [
                    lang('ct.om.total_orders'),
                    $totalOrders,
                ],
                [
                    lang('ct.om.total_customers'),
                    ee()->order_management_model->getCustomerCount(),
                ],
            ]
        );
    }

    /**
     * Retrieve report list
     * @return array
     */
    private function reports()
    {
        $data = [
            'Custom Reports' => [],
            'Order Reports' => [],
        ];

        $reports = ee('cartthrob:SettingsService')->get('cartthrob_order_manager', 'reports');

        // the put any already created in CT in there
        if (!$reports) {
            $reports = ee()->cartthrob->store->config('reports');
        }

        foreach ($reports as $report) {
            $data['Custom Reports'][$report['template']] = $report['name'];
        }

        if (!empty($data)) {
            asort($data);

            // shoving the default on the front
            array_unshift($data, lang('order_totals'));
        }

        $reports = ee('Model')->get('cartthrob_order_manager:OrderReport')
            ->all();

        if ($reports) {
            foreach ($reports as $report) {
                $data['Order Reports'][$report->id] = $report->report_title;
            }
        }

        // Fixes empty array notice issue
        foreach ($data as $key => &$value) {
            if (is_array($value) && empty($value)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    private function customTemplateGrid()
    {
        $grid = ee('CP/GridInput', [
            'field_name' => 'custom_templates',
            'reorder' => true,
        ]);

        $grid->setColumns(
            [
                'ct.om.custom_template_name' => [
                    'desc' => '',
                ],
                'ct.om.custom_template' => [
                    'desc' => '',
                ],
            ]
        );

        $data = [];

        $customTemplates = ee('Model')->get('cartthrob_order_manager:Setting')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('key', 'custom_templates')
            ->first();

        if ($customTemplates && is_array($customTemplates->value)) {
            foreach ($customTemplates->value as $i => $customTemplate) {
                $data[] = [
                    'attrs' => [
                        'row_id' => $i,
                    ],
                    'columns' => [
                        form_input('name', $customTemplate['name']),
                        form_dropdown('template', $this->templates(), $customTemplate['template']),
                    ],
                ];
            }
        }

        $grid->setData($data);

        $grid->setNoResultsText('ct.om.no_custom_templates', 'ct.om.add_custom_templates');

        $grid->setBlankRow([
            form_input('name'),
            form_dropdown('template', $this->templates()),
        ]);

        $grid->loadAssets();

        return [
            'title' => 'ct.om.custom_templates',
            'desc' => '',
            'wide' => true,
            'grid' => true,
            'fields' => [
                'custom_templates' => [
                    'type' => 'html',
                    'content' => ee('View')->make('ee:_shared/table')->render($grid->viewData()),
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    private function customReportGrid()
    {
        $grid = ee('CP/GridInput', [
            'field_name' => 'reports',
            'reorder' => true,
        ]);

        $grid->setColumns(
            [
                'ct.om.report_name' => [
                    'desc' => '',
                ],
                'ct.om.report_template' => [
                    'desc' => '',
                ],
            ]
        );

        $data = [];

        $reports = ee('Model')->get('cartthrob_order_manager:Setting')
            ->filter('site_id', ee()->config->item('site_id'))
            ->filter('key', 'reports')
            ->first();

        if ($reports && is_array($reports->value)) {
            foreach ($reports->value as $i => $report) {
                $data[] = [
                    'attrs' => [
                        'row_id' => $i,
                    ],
                    'columns' => [
                        form_input('name', $report['name']),
                        form_dropdown('template', $this->templates(), $report['template']),
                    ],
                ];
            }
        }

        $grid->setData($data);

        $grid->setNoResultsText('ct.om.no_custom_templates', 'ct.om.add_custom_report_templates');

        $grid->setBlankRow([
            form_input('name'),
            form_dropdown('template', $this->templates()),
        ]);

        $grid->loadAssets();

        return [
            'title' => 'ct.om.custom_report_templates',
            'desc' => 'ct.om.custom_report_templates_description',
            'wide' => true,
            'grid' => true,
            'fields' => [
                'custom_templates' => [
                    'type' => 'html',
                    'content' => ee('View')->make('ee:_shared/table')->render($grid->viewData()),
                ],
            ],
        ];
    }

    /**
     * @return array
     * @throws Exception
     */
    private function dateRanges()
    {
        $options = [];
        $today = DateTime::createFromFormat('Y-m-d', ee()->localize->format_date('%Y-%m-%d'));
        $ThirtyDayInterval = new DateInterval('P30D');
        $ThirtyDayDate = (clone $today)->sub($ThirtyDayInterval);
        $ThreeMonthInverval = new DateInterval('P3M');
        $ThreeMonthDate = (clone $today)->sub($ThreeMonthInverval);
        $SixMonthInverval = new DateInterval('P6M');
        $SixMonthDate = (clone $today)->sub($SixMonthInverval);
        $YearInverval = new DateInterval('P1Y');
        $YearDate = (clone $today)->sub($YearInverval);

        $options["{$ThirtyDayDate->format('Y-m-d')}---{$today->format('Y-m-d')}"] = lang('ct.om.last_30_days');
        $options["{$ThreeMonthDate->format('Y-m-d')}---{$today->format('Y-m-d')}"] = lang('ct.om.last_3_months');
        $options["{$SixMonthDate->format('Y-m-d')}---{$today->format('Y-m-d')}"] = lang('ct.om.last_6_months');
        $options["{$YearDate->format('Y-m-d')}---{$today->format('y-m-d')}"] = lang('ct.om.last_year');

        return $options;
    }
}
