<?php

namespace CartThrob\Traits;

trait OrderTrait
{
    /**
     * Generates and stores the next sequential order number for a given site.
     *
     * @param null $site_id
     * @return int
     */
    public function generateOrderNumber($site_id = null)
    {
        if (ee()->cartthrob->store->config('orders_sequential_order_numbers') === false || ee()->cartthrob->store->config('orders_channel') === false) {
            return 0;
        }

        $site_id = $site_id ?? $this->getSiteID();

        $order_number = $this->generateNextOrderNumber($site_id);

        $this->saveOrderNumber($order_number, $site_id);

        return $order_number;
    }

    /**
     * Generates a order title based on configured settings.
     *
     * @param null $orderNumber
     * @return mixed
     */
    public function generateOrderTitle($order_number = null)
    {
        if (is_null($order_number)) {
            $data['title'] = ee()->functions->random('alpha', 20);
            $data['url_title'] = $data['title'];
        } else {
            $data['title'] = ee()->cartthrob->store->config('orders_title_prefix') . $order_number . ee()->cartthrob->store->config('orders_title_suffix');
            $data['url_title'] = ee()->cartthrob->store->config('orders_url_title_prefix') . $order_number . ee()->cartthrob->store->config('orders_url_title_suffix');
        }

        return $data;
    }

    /**
     * Return site_id based on configured orders channel
     *
     * @return int
     */
    protected function getSiteID()
    {
        return ee()->db->select('site_id')->where('channel_id', ee()->cartthrob->store->config('orders_channel'))->get('channels')->row('site_id');
    }

    /**
     * Retrieves last order
     *
     * @param $site_id
     * @return int
     */
    private function generateNextOrderNumber($site_id)
    {
        $query = ee()->db->select('*')
            ->from('cartthrob_settings')
            ->where([
                '`key`' => 'last_order_number',
                'site_id' => $site_id,
            ])->get();

        return ($query->num_rows()) ? $query->row('value') + 1 : 1;
    }

    /**
     * Save Current Order Number in cartthrob_settings
     *
     * @param $order_number
     */
    private function saveOrderNumber($order_number, $site_id)
    {
        ee()->db->where([
            '`key`' => 'last_order_number',
            'site_id' => $site_id,
        ]);

        if (ee()->db->count_all_results('cartthrob_settings') === 0) {
            ee()->db->insert('cartthrob_settings', [
                '`key`' => 'last_order_number',
                'value' => $order_number,
                'site_id' => $site_id,
            ]);
        } else {
            ee()->db->update('cartthrob_settings', [
                'value' => $order_number,
            ], [
                '`key`' => 'last_order_number',
                'site_id' => $site_id,
            ]);
        }
    }
}
