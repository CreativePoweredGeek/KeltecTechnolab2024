<?php

$lang = [
    'authorize_net_title' => 'Authorize.net API',
    'authorize_net_overview' => '<p>Authorize.net relies on JavaScript accept.js solution to submit a payment. This is 
what allows it to process a credit card without the number ever having to touch your server, since
the card number is posted directly to Authorize.net via JavaScript. In order for CartThrob to work
properly, you must not set a custom id for the checkout_form; it must have the default id, which is checkout_form.</p>

<p>The following fields must have blank name attributes, and instead use id attributes for naming:
<code>credit_card_number</code>, <code>CVV2</code>, <code>expiration_month</code>, <code>expiration_year</code>
(ex. <code>&lt;input type="text" name="" id="credit_card_number" /&gt;</code>).
This happens by default when using the {gateway_fields} variable.</p>
</p>

<h2>WARNING</h2>
<p>The Authorize.net payment gateway is not compatible with CartThrob\'s "Validate Credit Card Number" setting. If you enable credit card number validation, payments with Stripe will always fail. Because CartThrob does not capture the Credit Card Number when used with Stripe in any way, validation can not proceed, and will return a validation failure. Turn this setting off if you are going to use Stripe as an available payment gateway.</p>
<p>Currently, the Authorize.net API payment gateway and Stripe payment gateway cannot both be enabled for multiple gateway selection, due to javascript incompatibilities.</p>

<h2>Testing</h2>
<p>While in test mode, you must use the <a href="https://developer.authorize.net/hello_world/testing_guide.html">linked credit card numbers</a> on this page test with.</p> 

<h2>PSD2 Compliance</h2>
<p><a href="https://www.authorize.net/en-gb.html">Authorize.Net</a> is available to businesses physically located in the United States and Canada. At this time, it appears that CyberSource is an alternate vendor that will be supplying PSD2 or 3D secure compliance going forward.</p>  
',
    'authorize_net_mode_test' => 'Test',
    'authorize_net_mode_live' => 'Live',
    'authorize_net_javascript_required' => 'You must have JavaScript turned on to check out.',
    'authorize_net_unknown_error' => 'An unknown error has occurred.',
    'authorize_net_card_declined' => 'The card was declined.',
    'authorize_net_api_login_id_live' => 'API Login ID',
    'authorize_net_api_login_id_test' => 'Test Mode API Login ID',
    'authorize_net_transaction_key_live' => 'Transaction Key',
    'authorize_net_transaction_key_test' => 'Test Mode Transaction Key',
    'authorize_net_public_client_key_live' => 'Accept.js Public Client Key',
    'authorize_net_public_client_key_test' => 'Test Mode Accept.js Public Client Key',
    'authorize_net_sca_failed' => 'Secure customer authentication failed.',
    'authorize_net_payment_intent_reference_failed' => 'Unable to access your Stripe Payment Intent reference.',
    'authorize_net_capture' => 'Authorization and/or Capture Payment Option',
    'authorize_net_auth_and_capture' => 'Authorize and Capture',
    'authorize_net_auth_only' => 'Authorize Only',
    'authorize_net_options' => 'If \'Authorize and Capture\' is selected, the credit card will be authorized and charged during checkout.  If \'Authorize Only\' is selected, then the credit card will be authorized, but the payment will need to be approved and captured manually at a later time.',
    'ct.payments.authorize.net.note' => 'Uses the <a href="https://www.authorize.net/" target="_blank">authorize.net</a> payment gateway to take credit card payments on your website.',
];
