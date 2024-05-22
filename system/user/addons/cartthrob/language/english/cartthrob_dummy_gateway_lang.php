<?php

$lang = [
    'dummy_title' => 'Dummy Gateway',
    'dummy_overview' => '<p>This is a dummy gateway driver intended for testing purposes. If you provide a card number ending in an even number, the driver will return a success response. If it ends in an odd number, the driver will return a generic failure response. For example:
<br /><br />
4929000000006 - Success<br />
4444333322221111 - Failure</p>',
    'ct.payments.gateway.dummy.note' => 'Allows for testing of the checkout process without a gateway service',
];
