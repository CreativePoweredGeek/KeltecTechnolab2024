<?php

namespace CartThrob\Traits;

trait OrderStatusesTrait
{
    /**
     * Return array of order channel statuses
     * @return array
     */
    public function getOrderStatuses(): array
    {
        $statuses = [];
        foreach ((array)ee()->cartthrob->store->config('orders_channel') as $channel) {
            foreach (ee()->cartthrob_settings_model->getStatusChannels($channel) as $status) {
                $statuses[$status['status']] = $status['status'];
            }
        }

        ksort($statuses);

        return $statuses;
    }
}
