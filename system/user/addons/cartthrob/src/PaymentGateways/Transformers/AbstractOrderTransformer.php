<?php

namespace CartThrob\PaymentGateways\Transformers;

abstract class AbstractOrderTransformer
{
    /**
     * Transform an order into a structured array
     *
     * @param array $order
     * @return array
     */
    abstract public function transform($order);

    /**
     * Transform a currency amount to structured array
     *
     * @param $value
     * @param $currency
     * @return array
     */
    protected function transformCurrency($value, $currency)
    {
        return [
            'currency' => $currency,
            'value' => number_format($value, 2, '.', ''),
        ];
    }
}
