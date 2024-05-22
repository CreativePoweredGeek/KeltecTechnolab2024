<?php

namespace CartThrob\OrderManager\Widgets;

use CartThrob\Services\Order\OrderService;
use ExpressionEngine\Addons\Pro\Service\Dashboard;

class Ordersummary extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    public function getTitle()
    {
        return lang('ct.om.summary');
    }

    public function getContent()
    {
        if (!$this->init()) {
            return 'Please install CartThrob';
        }

        return $this->orderTotals();
    }

    public function getRightHead()
    {
        return '<a href="' . ee('CP/URL', 'addons/settings/cartthrob_order_manager') . '" class="button button--default button--small">' . lang('CartThrob Order Manager') . '</a>';
    }

    private function init()
    {
        if (!ee('Addon')->get('cartthrob')->isInstalled()) {
            return false;
        }

        if (!ee()->cartthrob->store->config('save_orders') && !ee()->cartthrob->store->config('orders_channel')) {
            return false;
        }

        return true;
    }

    /**
     * Generate orders summary table
     * @return mixed
     */
    private function orderTotals()
    {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob_order_manager/');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');

        ee()->load->library([
            'cartthrob_loader',
            'localize',
            'number',
            'table',
        ]);

        ee()->load->model('order_management_model');

        ee()->table->clear();
        ee()->table->set_template(['table_open' => '<table border="0" cellpadding="0" cellspacing="0" class="mainTable padTable">']);

        $orderService = new OrderService();

        $order_totals = $orderService->totals();

        $totalSales = array_sum(array_column($order_totals, 'total'));
        $totalOrders = array_sum(array_column($order_totals, 'count'));
        $averageOrderValue = ($totalOrders > 0) ? $totalSales / $totalOrders : 0;

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
}
