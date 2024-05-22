<?php

use CartThrob\Dependency\Omnipay\Common\CreditCard;

if (!function_exists('sanitize_credit_card_number')) {
    /**
     * Strips all non-numeric formatting from a string
     *
     * @param mixed $creditCardNumber
     * @return string
     */
    function sanitize_credit_card_number($creditCardNumber = null): string
    {
        return (string)($creditCardNumber ? preg_replace('/[^0-9]/', '', $creditCardNumber) : '');
    }
}

if (!function_exists('validate_credit_card')) {
    /**
     * validate_credit_card
     *
     * @param string $number
     * @param mixed $statedCardType . Matches the stated card type against the actual card type requirements
     * @return array 'valid' (bool), 'card_type' (string), error_code (int)
     *               error_code 1: card type not found.
     *               error_code 2: card type mismatch
     *               error_code 3: invalid card number
     *               error_code 4: incorrect number length for card type
     */
    function validate_credit_card($number, $statedCardType = null): array
    {
        $response = [
            'valid' => false,
            'card_type' => null,
            'error_code' => null,
        ];

        try {
            $creditCard = new CreditCard([
                'number' => sanitize_credit_card_number($number),
                'expiryMonth' => date('m'),
                'expiryYear' => date('Y') + 1,
            ]);
            $creditCard->validate();
        } catch (Exception $e) {
            $response['error_code'] = 3;
        }

        $creditCards = [
            CreditCard::BRAND_VISA => [
                'length' => [13, 16],
                'prefix' => [4],
            ],
            CreditCard::BRAND_AMEX => [
                'length' => [15],
                'prefix' => [34, 37],
            ],
            CreditCard::BRAND_DISCOVER => [
                'length' => [16],
                'prefix' => [6011, 622, 64, 65],
            ],
            'mc' => [
                'length' => [16],
                'prefix' => [51, 52, 53, 54, 55],
            ],
            'diners' => [
                'length' => [14, 16],
                'prefix' => [305, 36, 38, 54, 55],
            ],
            CreditCard::BRAND_JCB => [
                'length' => [16],
                'prefix' => [35],
            ],
            CreditCard::BRAND_LASER => [
                'length' => [16, 17, 18, 19],
                'prefix' => [6304, 6706, 6771, 6709],
            ],
            CreditCard::BRAND_MAESTRO => [
                'length' => [12, 13, 14, 15, 16, 18, 19],
                'prefix' => [5018, 5020, 5038, 6304, 6759, 6761],
            ],
            CreditCard::BRAND_SOLO => [
                'length' => [16, 18, 19],
                'prefix' => [6334, 6767],
            ],
            CreditCard::BRAND_SWITCH => [
                'length' => [16, 18, 19],
                'prefix' => [4903, 4905, 4911, 4936, 564182, 633110, 6333, 6759],
            ],
            'carteblanche' => [
                'length' => [14],
                'prefix' => [300, 301, 302, 303, 304, 305],
            ],
            'electron' => [
                'length' => [16],
                'prefix' => [417500, 4917, 4913, 4508, 4844],
            ],
            'enroute' => [
                'length' => [15],
                'prefix' => [2014, 2149],
            ],
        ];

        // finding the type of card by its ccnumber
        foreach ($creditCards as $key => $cardData) {
            foreach ($cardData['prefix'] as $prefix) {
                if (strpos($number, $prefix) === 0) {
                    $response['card_type'] = $key;
                    break 2;
                }
            }
        }

        // checking to see if the type's set now that we've examined the card number
        if (!$response['card_type']) {
            // ERROR card not found
            $response['error_code'] = 1;
        }

        // checking to see if we expect the credit card to be of a certain type
        if ($statedCardType != null && $statedCardType != $response['card_type']) {
            $response['error_code'] = 2;
        }

        // checking card length
        $cardLength = strlen($number);
        $match = false;

        foreach ($creditCards[$response['card_type']]['length'] as $expectedLength) {
            if ($cardLength != $expectedLength) {
                continue;
            }

            $match = true;
        }

        if (!$match) {
            $response['error_code'] = 4;
        }

        if (empty($response['error_code']) && $response['card_type']) {
            $response['valid'] = true;
        }

        return $response;
    }
}
