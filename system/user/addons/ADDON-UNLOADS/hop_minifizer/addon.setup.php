<?php

require_once PATH_THIRD . 'hop_minifizer/config.php';

return [
    'author'        => 'Hop Studios',
    'author_url'    => 'https://hopstudios.com',
    'name'          => HOP_MINIFIZER_NAME,
    'version'       => HOP_MINIFIZER_VERSION,
    'description'   => 'Minimize, combine and cache your CSS, JS and HTML',
    'docs_url'      => 'https://hopstudios.com/software/hop_minifizer/docs',
    'namespace'     => 'HopStudios\HopMinifizer',
    'models'        => [
        'Config' => 'Model\Config'
    ],
    'settings_exist' => true
];