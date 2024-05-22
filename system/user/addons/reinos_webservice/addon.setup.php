<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		https://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     https://addons.reinos.nl/commercial-license
 *
 * Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Rein de Vries and
 * Reinos.nl Internet Media) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

require_once(PATH_THIRD.'reinos_webservice/config.php');

use Reinos\Webservice\Service\Curl;
use Reinos\Webservice\Service\Installer;
use Reinos\Webservice\Service\License;
use Reinos\Webservice\Service\Log;
use Reinos\Webservice\Service\Settings;
use Reinos\Webservice\Service\SettingsHelper;
use Reinos\Webservice\Service\Version;
use Reinos\Webservice\Service\Helper;
use Reinos\Webservice\Service\Permissions;
use Reinos\Webservice\Service\Format;
use Reinos\Webservice\Service\ApiHelper;
use Reinos\Webservice\Service\TestingTool;
use Reinos\Webservice\Service\UploadDir;
use Reinos\Webservice\Service\ApiLog;

return [
    'author'      => REINOS_WEBSERVICE_AUTHOR,
    'author_url'  => REINOS_WEBSERVICE_AUTHOR_URL,
    'name'        => REINOS_WEBSERVICE_TITLE,
    'description' => REINOS_WEBSERVICE_DESCRIPTION,
    'version'     => REINOS_WEBSERVICE_VERSION,
    'docs_url'  => REINOS_WEBSERVICE_DOCS,
    'settings_exist' => TRUE,
    'namespace'   => 'Reinos\Webservice',
    'services.singletons' => [
        'Installer' => function($ee) {
            return new Installer;
        },
        'Settings' => function($ee) {
            return new Settings;
        },
        'Curl' => function($ee) {
            return new Curl;
        },
    ],
    'services' => [
        'UploadDir' => function($ee) {
            return new UploadDir;
        },
        'TestingTool' => function($ee) {
            return new TestingTool;
        },
        'Version' => function($ee) {
            return new Version;
        },
        'SettingsHelper' => function($ee) {
            return new SettingsHelper;
        },
        'License' => function($ee) {
            return new License;
        },
        'Helper' => function($ee) {
            return new Helper;
        },
        'Log' => function($ee) {
            return new Log;
        },
        'Permissions' => function($ee) {
            return new Permissions;
        },
        'Format' => function($ee) {
            return new Format;
        },
        'ApiHelper' => function($ee) {
            return new ApiHelper;
        },
        'ApiLog' => function($ee) {
            return new ApiLog;
        },
    ],
    'models' => [
        'Member' => 'Model\Member',
        'Shortkey' => 'Model\Shortkey',
        'ApiLog' => 'Model\ApiLog',
        'Key' => 'Model\Key',
        'Log' => 'Model\Log',
        'Settings' => 'Model\Settings'
    ],
    'models.dependencies' => [
        'Member'   => [
            'ee:Member',
            'ee:Role'
        ]
    ],
];
