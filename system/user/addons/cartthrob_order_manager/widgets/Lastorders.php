<?php

namespace CartThrob\OrderManager\Widgets;

use CartThrob\Traits\OrderStatusesTrait;
use ExpressionEngine\Addons\Pro\Service\Dashboard;
use ExpressionEngine\Library\CP\Table;

class Lastorders extends Dashboard\AbstractDashboardWidget implements Dashboard\DashboardWidgetInterface
{
    use OrderStatusesTrait;

    public $width = 'full';

    private $base_url = 'addons/settings/cartthrob_order_manager';

    public function getTitle()
    {
        return lang('ct.om.order_totals');
    }

    public function getContent()
    {
        if (!$this->init()) {
            return 'Please install CartThrob';
        }

        return $this->orders();
    }

    public function getRightHead()
    {
        return '<a href="' . ee('CP/URL', 'addons/settings/cartthrob_order_manager') . '" class="button button--default button--small">' . lang('CartThrob Order Manager') . '</a>';
    }

    /**
     * @return bool
     */
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
     * Generate orders table
     * @param null $order_id
     * @return array|mixed
     */
    private function orders()
    {
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob/');
        ee()->load->add_package_path(PATH_THIRD . 'cartthrob_order_manager/');

        ee()->load->library([
            'table',
            'number',
        ]);

        $vars['orders'] = [];
        $vars['statuses'] = $this->getOrderStatuses();

        $table = ee('CP/Table', [
            'autosort' => false,
            'autosearch' => false,
            'lang_cols' => false,
            'class' => 'orders mainTable padTable',
            'limit' => 25,
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
        ]);

        $table->setNoResultsText(sprintf(lang('no_found'), lang('ct.om.no_orders')));

        $orders = ee('Model')->get('ChannelEntry')
            ->filter('channel_id', ee()->cartthrob->store->config('orders_channel'))
            ->order('entry_date', 'desc')
            ->limit(25)
            ->all();

        $orders->alias('cartthrob:CartthrobStatus', 'OrderStatus');

        $data = [];

        foreach ($orders as $order) {
            $gateway = $order->{'field_id_' . ee()->cartthrob->store->config('orders_payment_gateway')};

            $gateway = '<small>' . ucwords(str_replace('_', ' ', $gateway) . '</small>');

            $customer = $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')} . ' ' . $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')};

            if (ee()->cartthrob->store->config('orders_customer_email')) {
                if ($email = $order->{'field_id_' . ee()->cartthrob->store->config('orders_customer_email')} ?? false) {
                    $params = ee('cartthrob:EncryptionService')->encode(serialize(['customer_email' => $email]));

                    $customer = [
                        'content' => $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_first_name')} . ' ' . $order->{'field_id_' . ee()->cartthrob->store->config('orders_billing_last_name')},
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
                $order->status,
            ];
        }

        $table->setData($data);

        $data = ee('View')->make('ee:_shared/table')->render($table->viewData());

        return $data;
    }
}
