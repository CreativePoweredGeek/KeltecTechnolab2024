<?php

/**
 * PHP-Scoper configuration file.
 *
 * @copyright 2022 PacketTide LLC
 * @license
 */

use Isolated\Symfony\Component\Finder\Finder;

return [
    'prefix' => 'CartThrob\\Dependency',                       // string|null
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'bin',
                'bamarni',
                'doc',
                'docs',
                'test',
                'Test',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
    ],                        // Finder[]
    'patchers' => [],                       // callable[]
    'output-dir' => 'D:\Projects\foster-made\develop\CartThrob\system\user\addons\cartthrob\vendor-build',
    // 'files-whitelist' => [],                // string[]
    // 'whitelist' => [],                      // string[]
    // 'expose-global-constants' => true,   // bool
    // 'expose-global-classes' => true,     // bool
    // 'expose-global-functions' => true,   // bool
    // 'exclude-constants' => [],             // string[]
    // 'exclude-classes' => [],               // string[]
    // 'exclude-functions' => [],             // string[]
];
