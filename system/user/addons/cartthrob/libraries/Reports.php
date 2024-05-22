<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Reports
{
    private $default_status;
    private $failed_status;
    private $declined_status;
    private $processing_status;
    private $status_pending;
    private $status_expired;
    private $status_canceled;
    private $status_voided;
    private $status_refunded;
    private $status_reversed;
    private $status_offsite;
    private $ignored_statuses;

    /**
     * Reports constructor.
     */
    public function __construct()
    {
        ee()->load->model('cartthrob_settings_model');
        ee()->load->model('order_model');

        // @TODO whenever we get more statuses set in the config, need to make this list dynamic
        $this->default_status = (ee()->config->item('cartthrob:orders_default_status')) ? ee()->config->item('cartthrob:orders_default_status') : 'open';

        $this->failed_status = (ee()->config->item('cartthrob:orders_failed_status')) ? ee()->config->item('cartthrob:orders_failed_status') : 'closed';
        $this->declined_status = (ee()->config->item('cartthrob:orders_declined_status')) ? ee()->config->item('cartthrob:orders_declined_status') : 'closed';
        $this->processing_status = (ee()->config->item('cartthrob:orders_processing_status')) ? ee()->config->item('cartthrob:orders_processing_status') : 'closed';
        $this->status_pending = (ee()->config->item('cartthrob:orders_status_pending')) ? ee()->config->item('cartthrob:orders_status_pending') : 'closed';
        $this->status_expired = (ee()->config->item('cartthrob:orders_status_expired')) ? ee()->config->item('cartthrob:orders_status_expired') : 'closed';
        $this->status_canceled = (ee()->config->item('cartthrob:orders_status_canceled')) ? ee()->config->item('cartthrob:orders_status_canceled') : 'closed';
        $this->status_voided = (ee()->config->item('cartthrob:orders_status_voided')) ? ee()->config->item('cartthrob:orders_status_voided') : 'closed';
        $this->status_refunded = (ee()->config->item('cartthrob:orders_status_refunded')) ? ee()->config->item('cartthrob:orders_status_refunded') : 'closed';
        $this->status_reversed = (ee()->config->item('cartthrob:orders_status_reversed')) ? ee()->config->item('cartthrob:orders_status_reversed') : 'closed';
        $this->status_offsite = (ee()->config->item('cartthrob:orders_status_offsite')) ? ee()->config->item('cartthrob:orders_status_offsite') : 'closed';

        $this->ignored_statuses = [
            $this->declined_status,
            $this->processing_status,
            $this->failed_status,
            $this->status_reversed,
            $this->status_refunded,
            $this->status_voided,
            $this->status_canceled,
            $this->status_expired,
            $this->status_pending,
            $this->status_offsite,
        ];
    }

    /**
     * @return mixed
     */
    public function get_current_day_total()
    {
        // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab
        // ee()->db->where_not_in('status', $this->ignored_statuses);
        $status = $this->ignored_statuses;

        return ee()->order_model->order_get_totals(
            [
                'year' => date('Y'),
                'month' => date('m'),
                'day' => date('d'),
            ],
            $status, true
        );
    }

    /**
     * @return mixed
     */
    public function get_current_month_total()
    {
        // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab
        // ee()->db->where_not_in('status', $this->ignored_statuses);
        $status = $this->ignored_statuses;

        return ee()->order_model->order_get_totals(
            [
                'year' => date('Y'),
                'month' => date('m'),
            ],
            $status, true
        );
    }

    /**
     * @return mixed
     */
    public function get_current_year_total()
    {
        // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab
        // ee()->db->where_not_in('status', $this->ignored_statuses);
        $status = $this->ignored_statuses;

        return ee()->order_model->order_get_totals(
            [
                'year' => date('Y'),
            ],
            $status, true
        );
    }

    /**
     * @param $year
     * @return array
     */
    public function get_yearly_totals($year)
    {
        $rows = [];

        for ($i = 1; $i <= 12; $i++) {
            $month = ($i < 10) ? '0' . $i : $i;

            // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab
            // ee()->db->where_not_in('status', $this->ignored_statuses);
            $status = $this->ignored_statuses;
            $data = ee()->order_model->order_get_totals([
                'year' => $year,
                'month' => $month,
            ], $status);

            $rows[] = [
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'],
                'shipping' => $data['shipping'],
                'discount' => $data['discount'],
                'total' => $data['total'],
                'date' => $month . $year,
                'name' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
                'href' => 'month=' . $month . '&year=' . $year,
            ];
        }

        return $rows;
    }

    /**
     * @param $month
     * @param $year
     * @return array
     */
    public function get_monthly_totals($month, $year)
    {
        // @TODO make this use any status other than processing, declined, failed CT statuses.

        $rows = [];

        $days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        for ($i = 1; $i <= $days; $i++) {
            $day = ($i < 10) ? '0' . $i : $i;

            // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab
            // ee()->db->where_not_in('status', $this->ignored_statuses);
            $status = $this->ignored_statuses;
            $data = ee()->order_model->order_get_totals([
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ], $status);

            $rows[] = [
                'subtotal' => $data['subtotal'],
                'tax' => $data['tax'],
                'shipping' => $data['shipping'],
                'discount' => $data['discount'],
                'total' => $data['total'],
                'date' => $day,
                'name' => date('D d', mktime(0, 0, 0, $month, $i, $year)),
                'href' => 'month=' . $month . '&year=' . $year . '&day=' . $day,
            ];
        }

        return $rows;
    }

    /**
     * @param $day
     * @param $month
     * @param $year
     * @return array
     */
    public function get_daily_totals($day, $month, $year)
    {
        $rows = [];
        // @TODO fix: if there's no order channel installed... this will cause errors in the reports tab

        $orders = ee()->order_model->get_orders(['year' => $year, 'month' => $month, 'day' => $day]);

        foreach ($orders as $order) {
            $rows[] = [
                'subtotal' => (ee()->config->item('cartthrob:orders_subtotal_field')) ? $order['field_id_' . ee()->config->item('cartthrob:orders_subtotal_field')] : 0,
                'tax' => (ee()->config->item('cartthrob:orders_tax_field')) ? $order['field_id_' . ee()->config->item('cartthrob:orders_tax_field')] : 0,
                'shipping' => (ee()->config->item('cartthrob:orders_shipping_field')) ? $order['field_id_' . ee()->config->item('cartthrob:orders_shipping_field')] : 0,
                'discount' => (ee()->config->item('cartthrob:orders_discount_field')) ? $order['field_id_' . ee()->config->item('cartthrob:orders_discount_field')] : 0,
                'total' => (ee()->config->item('cartthrob:orders_total_field')) ? $order['field_id_' . ee()->config->item('cartthrob:orders_total_field')] : 0,
                'date' => $order['entry_date'],
                'name' => date('g:ia', $order['entry_date']),
                'href' => 'entry_id=' . $order['entry_id'],
            ];
        }

        return $rows;
    }

    /**
     * @param null $start
     * @param null $end
     * @return array
     */
    public function get_all_totals($start = null, $end = null)
    {
        $rows = [];

        if (!$start) {
            ee()->db->where_not_in('status', $this->ignored_statuses);
            $start = ee()->db->select('entry_date')
                ->limit(1)
                ->where('channel_id', ee()->config->item('cartthrob:orders_channel'))
                ->order_by('entry_date', 'asc')
                ->get('channel_titles')
                ->row('entry_date');
        }

        if (!$end) {
            ee()->db->where_not_in('status', $this->ignored_statuses);
            $end = ee()->db->select('entry_date')
                ->limit(1)
                ->where('channel_id', ee()->config->item('cartthrob:orders_channel'))
                ->order_by('entry_date', 'desc')
                ->get('channel_titles')
                ->row('entry_date');
        }

        if ($start && $end) {
            $start = getdate($start);
            $end = getdate($end);
            $status = $this->ignored_statuses;
            $totals = [];

            for ($year = $start['year']; $year <= $end['year']; $year++) {
                for ($month = ($year == $start['year']) ? $start['mon'] : 1; $month <= (($year == $end['year']) ? $end['mon'] : 12); $month++) {
                    // ee()->db->where_not_in('status', $this->ignored_statuses);
                    $totals[$year][$month] = ee()->order_model->order_totals([
                        'entry_start_date' => mktime(0, 0, 0, $month, 1, $year),
                        'entry_end_date' => mktime(23, 59, 59, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year),
                            $year),
                    ], $status);
                }
            }

            foreach ($totals as $year => $months) {
                foreach ($months as $month => $data) {
                    $month = ($month < 10) ? '0' . $month : $month;

                    $rows[] = [
                        'subtotal' => $data['subtotal'],
                        'tax' => $data['tax'],
                        'shipping' => $data['shipping'],
                        'discount' => $data['discount'],
                        'total' => $data['total'],
                        'date' => $month . $year,
                        'name' => date('F Y', mktime(0, 0, 0, $month, 1, $year)),
                        'href' => 'month=' . $month . '&year=' . $year,
                    ];
                }
            }
        }

        return $rows;
    }
}
