<?php

namespace CartThrob\Dependency;

// Don't redefine the functions if included multiple times.
if (!\function_exists('CartThrob\\Dependency\\GuzzleHttp\\Promise\\promise_for')) {
    require __DIR__ . '/functions.php';
}
