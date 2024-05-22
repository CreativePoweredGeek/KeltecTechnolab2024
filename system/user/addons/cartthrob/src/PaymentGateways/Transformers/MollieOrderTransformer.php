<?php

namespace CartThrob\PaymentGateways\Transformers;

use CartThrob\Dependency\Illuminate\Support\Arr;
use CartThrob\Dependency\Omnipay\Common\CreditCard;
use CartThrob\Dependency\Omnipay\Mollie\Item;
use Cartthrob_payments;

class MollieOrderTransformer extends AbstractOrderTransformer
{
    /**
     * Transform an order into a structured array
     *
     * @param array $order
     * @return array
     */
    public function transform($order)
    {
        loadCartThrobPath();

        // Allows support for Publisher
        ee()->load->library('locales');

        return [
            'amount' => $order['total'],
            'currency' => $order['currency_code'],
            'orderNumber' => $order['order_id'],
            'lines' => array_merge($this->transformLineItems($order), $this->transformShipping($order)),
            'card' => $this->transformCard($order),
            'metadata' => [
                'entry_id' => $order['entry_id'],
            ],
            'locale' => Arr::get($order, 'locale', 'en_US'),
            'returnUrl' => Cartthrob_payments::responseUrl('Cartthrob_mollie', ['action' => 'order', 'redirect' => $order['authorized_redirect']]),
            'notifyUrl' => Cartthrob_payments::responseUrl('Cartthrob_mollie', ['action' => 'payment']),
            'paymentMethod' => Arr::get($order, 'payment_gateway_method'),
        ];
    }

    /**
     * @param $order
     * @return array
     */
    private function transformLineItems($order)
    {
        $lineItems = [];
        $items = $order['items'] ?? [];

        foreach ($items as $item) {
            $vatRate = ($item['price_plus_tax'] - $item['price']) / $item['price'];

            // Per item
            $price_before_tax_after_discount = $item['price'];
            $tax = $item['price'] * $vatRate;
            $discount_amount = round($item['discount'] * $item['quantity'], 2);
            $order_discounts = [];

            $lineItems[] = new Item([
                // SKIP - type
                // SKIP - sku
                'name' => $item['title'],
                // SKIP - productUrl
                // SKIP - imageUrl
                'quantity' => (int)$item['quantity'],
                'vatRate' => round($vatRate * 100, 2),
                'unitPrice' => round($item['price'] + $tax, 2),
                // Add - $item[discount] to totalAmount
                'totalAmount' => round(($price_before_tax_after_discount + $tax) * $item['quantity'], 2),
                'discountAmount' => number_format(0, 2),
                'vatAmount' => round($tax * $item['quantity'], 2),
            ]);

            if ($discount_amount) {
                $order_discounts[] = $discount_amount;
            }
        }

        if (!empty($order['coupon_codes']) && !empty($order['discount'])) {
            $lineItems[] = new Item([
                'type' => 'discount',
                'name' => $order['coupon_codes'],
                'quantity' => 1,
                'vatRate' => number_format(0, 2),
                'unitPrice' => '-' . $order['discount'],
                // Add - $item[discount] to totalAmount
                'totalAmount' => '-' . $order['discount'],
                'vatAmount' => number_format(0, 2),
            ]);
        }

        if ($order_discounts) {
            foreach ($order_discounts as $discount) {
                $lineItems[] = new Item([
                    'type' => 'discount',
                    'name' => 'Discount',
                    'quantity' => 1,
                    'vatRate' => number_format(0, 2),
                    'unitPrice' => '-' . $discount,
                    // Add - $item[discount] to totalAmount
                    'totalAmount' => '-' . $discount,
                    'vatAmount' => number_format(0, 2),
                ]);
            }
        }

        return $lineItems;
    }

    /**
     * @param $order
     * @return array
     */
    private function transformShipping($order)
    {
        $shipping = [];

        if (isset($order['shipping']) && $order['shipping'] > 0) {
            $vatRate = ($order['shipping_plus_tax'] - $order['shipping']) / $order['shipping'];
            $shipping[] = new Item([
                'type' => 'shipping_fee',
                // SKIP - sku
                'name' => ee()->lang->line('mollie_shipping'),
                // SKIP - productUrl
                // SKIP - imageUrl
                'quantity' => 1,
                'vatRate' => number_format($vatRate * 100, 2),
                'unitPrice' => number_format($order['shipping_plus_tax'], 2),
                'totalAmount' => number_format($order['shipping_plus_tax'], 2),
                'vatAmount' => number_format($order['shipping'] * $vatRate, 2),
            ]);
        }

        return $shipping;
    }

    /**
     * @param array $order
     * @return CreditCard
     */
    private function transformCard(array $order)
    {
        $card = new CreditCard();
        $card->setEmail($order['customer_email']);

        $this->setBillingData($order, $card);

        if (!empty($order['shipping_first_name'])) {
            $this->setShippingData($order, $card);
        }

        return $card;
    }

    /**
     * @param array $order
     * @param CreditCard $card
     */
    private function setBillingData($order, CreditCard &$card)
    {
        if (!empty($order['billing_company'])) {
            $card->setCompany($order['billing_company']);
        }

        $data = $this->transformAddress($order, 'billing_');

        if (isset($data['familyName'])) {
            $card->setBillingFirstName($data['givenName']);
            $card->setBillingLastName($data['familyName']);
        } else {
            $card->setBillingName($data['givenName']);
        }

        if (isset($data['streetAndNumber'])) {
            $card->setBillingAddress1($data['streetAndNumber']);
            $card->setBillingPostcode($data['postalCode']);
            $card->setBillingCity($data['city']);
            $card->setBillingCountry($data['country']);
        }

        if (isset($data['streetAdditional'])) {
            $card->setBillingAddress2($data['streetAdditional']);
        }

        if (isset($data['region'])) {
            $card->setBillingState($data['region']);
        }

        if (isset($order['billing_phone'])) {
            $card->setBillingPhone($order['billing_phone']);
        }
    }

    /**
     * @param array $order
     * @param CreditCard $card
     */
    private function setShippingData($order, CreditCard &$card)
    {
        $data = $this->transformAddress($order, 'shipping_');

        if (!empty($data['familyName'])) {
            $card->setShippingFirstName($data['givenName']);
            $card->setShippingLastName($data['familyName']);
        } else {
            $card->setShippingName($data['givenName']);
        }

        if (isset($data['streetAndNumber'])) {
            $card->setShippingAddress1($data['streetAndNumber']);
            $card->setShippingPostcode($data['postalCode']);
            $card->setShippingCity($data['city']);
            $card->setShippingCountry($data['country']);
        }

        if (isset($data['streetAdditional'])) {
            $card->setShippingAddress2($data['streetAdditional']);
        }

        if (isset($data['region'])) {
            $card->setShippingState($data['region']);
        }

        if (isset($order['shipping_phone'])) {
            $card->setShippingPhone($order['shipping_phone']);
        }
    }

    /**
     * @param $order
     * @param $prefix
     * @return array
     */
    private function transformAddress($order, $prefix)
    {
        return $this->filterEmptyValues([
            'givenName' => $order[$prefix . 'first_name'],
            'familyName' => $order[$prefix . 'last_name'],
            'streetAndNumber' => Arr::get($order, $prefix . 'address'),
            'streetAdditional' => Arr::get($order, $prefix . 'address2'),
            'postalCode' => Arr::get($order, $prefix . 'zip'),
            'city' => Arr::get($order, $prefix . 'city'),
            'region' => Arr::get($order, $prefix . 'state'),
            'country' => alpha2_country_code(Arr::get($order, $prefix . 'country_code')),
        ]);
    }

    /**
     * @param array $data
     * @return array
     */
    private function filterEmptyValues($data)
    {
        $filteredArray = [];

        foreach ($data as $key => $value) {
            if ($value != '' && !is_null($value)) {
                $filteredArray[$key] = $value;
            }
        }

        // From Mollie Docs:
        // If any of the fields is provided, all fields have to be provided with exception of the region field.
//        $addressKeys = ['streetAndNumber', 'postalCode', 'city', 'country'];
//        $allExist = true;
//        foreach ($addressKeys as $key) {
//            $allExist = $allExist && isset($filteredArray[$key]);
//        }
//
//        if (!$allExist) {
//            foreach ($addressKeys as $key) {
//                unset($filteredArray[$key]);
//            }
//
//            // These are optional, but must be removed if all others aren't set
//            unset($filteredArray['streetAdditional'], $filteredArray['region']);
//        }

        return $filteredArray;
    }
}
