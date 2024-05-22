<?php

$lang = [
    'ct.payments.paypal_standard.title' => 'PayPal Standard',
    'ct.payments.paypal_standard.overview' => 'Adds the <a href="https://developer.paypal.com/docs/checkout/standard/" target="_blank">PayPal Standard</a> payment implementation to your CartThrob site. Note that you\'re going to need a the details from your <a href="https://developer.paypal.com/developer/applications">PayPal developer console</a> to get started.  ',
    'ct.payments.paypal_standard.api.client_id' => 'Client ID',
    'ct.payments.paypal_standard.api.client_id.note' => 'This is just the internal ID PayPal assigns for your App. Note this should be the "Live" Client ID.',
    'ct.payments.paypal_standard.api.secret' => 'Secret',
    'ct.payments.paypal_standard.api.secret.note' => 'Used for creating the request signatures. Note this should be the "Live" Secret.',
    'ct.payments.paypal_standard.api.sandbox.client_id.note' => 'This is just the internal ID PayPal assigns for your App. Note this should be the "Sandbox" Client ID.',
    'ct.payments.paypal_standard.api.sandbox.client_id' => 'Sandbox Client ID',
    'ct.payments.paypal_standard.api.sandbox.secret' => 'Sandbox Secret',
    'ct.payments.paypal_standard.api.sandbox.secret.note' => 'Used for creating the request signatures. Note this should be the "Sandbox" Secret.',
    'ct.payments.paypal_standard.api.mode' => 'Mode',
    'ct.payments.paypal_standard.api.mode.note' => 'When ran in Sandbox mode, no funds will be taken or delivered. Use Sandbox mode for testing and development.',
    'ct.payments.paypal_standard.enable_fields' => 'Enable Checkout Fields',
    'ct.payments.paypal_standard.enable_fields.note' => 'By default, the checkout form includes fields for things like Shipping and Billing details; but with PayPal, there isn\'t a way to validate server-side. This toggle will remove those fields.',
    'ct.payments.paypal_standard.enable_venmo' => 'Enable Venmo',
    'ct.payments.paypal_standard.enable_venmo.note' => 'Add <a href="https://developer.paypal.com/docs/checkout/pay-with-venmo/" target="_blank">Venmo</a> as a payment button',
    'ct.payments.paypal_standard.enable_mybank' => 'Enable Mybank',
    'ct.payments.paypal_standard.enable_mybank.note' => '<a href="https://developer.paypal.com/docs/checkout/apm/mybank/" target="_blank">MyBank</a> is a payment method in Europe.',
    'ct.payments.paypal_standard.enable_bancontact' => 'Enable Bancontact',
    'ct.payments.paypal_standard.enable_bancontact.note' => '<a href="https://developer.paypal.com/docs/checkout/apm/bancontact/" target="_blank">Bancontact</a> is the most widely used, accepted and trusted electronic payment method in Belgium, with over 15 million Bancontact cards issued, and 150,000 online transactions processed a day.',
    'ct.payments.paypal_standard.enable_eps' => 'Enable EPS',
    'ct.payments.paypal_standard.enable_eps.note' => '<a href="https://developer.paypal.com/docs/checkout/apm/eps/" target="_blank">eps</a> is a payment method in Austria.',
    'ct.payments.paypal_standard.enable_card' => 'Enable Credit Card',
    'ct.payments.paypal_standard.enable_card.note' => 'By default, the PayPal Standard checkout adds a Credit Card button; you can remove it here.',
    'ct.payments.paypal_standard.enable_credit' => 'Enable PayPal Credit',
    'ct.payments.paypal_standard.enable_credit.note' => 'PayPal offers their own Buy Now / Pay Later system',
    'ct.payments.paypal_standard.errors.missing_payment_details' => 'PayPal Payment Details are missing from POST payload :(',
    'ct.payments.paypal_standard.errors.paypal_unresponsive' => 'No response from PayPal on transactionReference verification',
    'ct.payments.paypal_standard.errors.reference_not_found' => 'Can\'t find Payment Reference',
    'ct.payments.paypal_standard.errors.payment_failed' => 'Payment wasn\'t successful',
    'ct.payments.paypal_standard.errors.refund_on_not_completed_order' => 'The Order cannot be refunded due to a failed status check.',
    'ct.payments.paypal_standard.errors.refund_failed' => 'The refund failed for an unknown reason; check the PayPal logs for details',
    'ct.payments.gateway.paypal_standard.note' => 'Your run of the mill <a href="https://developer.paypal.com/docs/checkout/standard/" target="_blank">PayPal</a> buttons for checkout. Allows for Venmo, credit card, and most PayPal Payment Sources.',
];