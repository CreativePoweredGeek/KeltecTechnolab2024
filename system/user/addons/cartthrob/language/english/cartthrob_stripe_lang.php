<?php

$lang = [
    'stripe_title' => 'Stripe (Card Element)',
    'stripe_overview' => '<p>Stripe relies on JavaScript to submit a payment. This is what
allows it to process a credit card without the number ever having to touch your server, since
the card number is posted directly to Stripe via JavaScript. In order for CartThrob to work
properly, you must not set a custom id for the checkout_form; it must have the default id, which is checkout_form.</p>

<p>The following fields must have blank name attributes, and instead use id attributes for naming:
<code>credit_card_number</code>, <code>CVV2</code>, <code>expiration_month</code>, <code>expiration_year</code>
(ex. <code>&lt;input type="text" name="" id="credit_card_number" /&gt;</code>).
This happens by default when using the {gateway_fields} variable.</p>

<p>Most error messages will happen via Javascript. This means that should a customer enter an incorrect
CC number, they will recieve a JavaScript alert() with an error message. To override this default
behavior, you can write your own JavaScript callback function when an error is encountered.
This callback must be added <i>after</i> your form close, for example:<br><br>
<pre>...

{/exp:cartthrob:checkout_form}

&lt;script type="text/javascript"&gt;
CartthrobTokenizer.setErrorHandler(function(errorMessage){
	$("#checkout_form div.error").html(errorMessage).show();
});
&lt;/script&gt;
</pre></p>

<h2>WARNING</h2>
<p>The stripe payment gateway is not compatible with CartThrob\'s "Validate Credit Card Number" setting. If you enable credit card number validation, payments with Stripe will always fail. Because CartThrob does not capture the Credit Card Number when used with Stripe in any way, validation can not proceed, and will return a validation failure. Turn this setting off if you are going to use Stripe as an available payment gateway.</p>

<h2>Testing</h2>
While in test mode, you must use the <a href="https://stripe.com/docs/testing">linked credit card numbers</a> to test with. 

',
    'stripe_mode_test' => 'Test',
    'stripe_mode_live' => 'Live',
    'stripe_capture' => 'Capture',
    'stripe_auth_and_capture' => 'Authorize and Capture',
    'stripe_auth_only' => 'Authorize Only',
    'stripe_auth_note' => 'If \'Authorize and Capture\' is selected, the credit card will be authorized and charged during checkout.  If \'Authorize Only\' is selected, then the credit card will be authorized, but the payment will need to be approved and captured manually at a later time.',
    'stripe_javascript_required' => 'You must have JavaScript turned on to check out.',
    'stripe_unknown_error' => 'An unknown error has occurred.',
    'stripe_card_declined' => 'The card was declined.',
    'stripe_api_key' => 'Test Mode API Key (publishable)',
    'stripe_private_key' => 'Test Mode API Key (secret)',
    'stripe_live_key' => 'Live Mode API Key (publishable)',
    'stripe_live_key_secret' => 'Live Mode API Key (secret)',
    'stripe_hide_postal_code' => 'Hide Postal Code',
    'stripe_hide_postal_code_description' => 'Hide the postal code field in the Stripe "card" Element. <em>Default is false</em>. If you are already collecting a full billing address or postal code elsewhere, set this to true.',
    'stripe_icon_style' => 'Icon Style',
    'stripe_icon_style_description' => 'Appearance of the credit card icon in the Element. Either solid or default.',
    'stripe_icon_default' => 'Default',
    'stripe_icon_solid' => 'Solid',
    'stripe_hide_icon' => 'Hide Icon',
    'stripe_hide_icon_description' => 'Hides the icon in the Element. <em>Default is false</em>.',
    'stripe_sca_failed' => 'Secure customer authentication failed.',
    'stripe_payment_intent_reference_failed' => 'Unable to access your Stripe Payment Intent reference.',
    'stripe_refund_could_not_be_completed' => 'Refund could not be completed.',
    'stripe_styles' => 'Stripe Style Object',
    'stripe_refund_reason' => 'Reason',
    'stripe_requested_by_customer' => 'Requested by Customer',
    'stripe_fraudulent' => 'Fraudulent',
    'stripe_duplicate' => 'Duplicate',
    'stripe_styles_note' => '<a>Elements are styled using a Style object, which consists of CSS properties nested under objects as defined in the <a href="https://stripe.com/docs/js/appendix/style">Stripe documentation</a>.</p>',
    'stripe_payments_title' => 'Stripe (Payment Element)',
    'stripe_element_theme' => 'Theme',
    'stripe_element_theme_note' => 'Pick a default color scheme for your Payment Element. You can further customize it using the below. ',
    'stripe_element_style_labels' => 'Labels',
    'stripe_element_style_labels_note' => 'Enables switching between labels above form fields and floating labels within the form fields',
    'stripe_element_theme_none' => 'None',
    'stripe_element_theme_stripe' => 'Stripe',
    'stripe_element_theme_night' => 'Night',
    'stripe_element_theme_flat' => 'Flat',
    'stripe_element_label_floating' => 'Floating',
    'stripe_element_style_above' => 'Above',
    'stripe_element_style_variables' => 'Variables',
    'stripe_element_style_variables_note' => 'Set variables to affect the appearance of many components appearing throughout each Element.<br /><br />  The variables option works like CSS variables. You can specify CSS values for each variable and reference other variables with the var(--myVariable) syntax. You can even inspect the resulting DOM using the DOM explorer in your browser. <br /> <br /> Read more on the official <a href="https://stripe.com/docs/stripe-js/appearance-api#variables" target="_blank">Stripe Elements</a> documentation. ',
    'stripe_element_style_rules' => 'Rules',
    'stripe_element_style_rules_note' => 'The rules option is a map of CSS-like selectors to CSS properties, allowing fine-grained customization of individual components. After defining your theme and variables, use rules to seamlessly integrate Elements to match the design of your site. <br /> <br /> Read more on the official <a href="https://stripe.com/docs/stripe-js/appearance-api#rules" target="_blank">Stripe Elements</a> documentation. ',
    'ct.payments.stripe_payments.enable_fields' => 'Enable Checkout Fields',
    'ct.payments.stripe.note' => 'This gateway uses the <a href="https://stripe.com/docs/payments/cards/overview" target="_blank">Stripe API</a> to collect credit card payments securely.',
    'ct.payments.stripe_payments.note' => 'The <a href="https://stripe.com/docs/payments/payment-methods" target="_blank">Stripe Payments</a> gateway allows credit card, Apple Pay, and Google Pay checkout options.',
    'ct.payments.stripe_payments.enable_fields.note' => 'By default, the checkout form includes fields for things like Shipping and Billing details; but with Stripe Payments, there isn\'t a way to validate server-side. This toggle will remove those fields though you should setup stand-alone checkout forms to augment.',
];
