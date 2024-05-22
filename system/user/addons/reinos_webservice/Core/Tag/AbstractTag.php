<?php

namespace Reinos\Webservice\Core\Tag;

/**
 * @author		Rein de Vries <support@reinos.nl>
 * @link		http://addons.reinos.nl
 * @copyright 	Copyright (c) 2011 - 2021 Reinos.nl Internet Media
 * @license     http://addons.reinos.nl/commercial-license
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
abstract class AbstractTag
{
    protected $tagdata;
    protected $params;
    protected $member_id;
    protected $site_id;
    protected $prefix;
    protected $return_data;

    public function __construct($tagdata, $params)
    {
        $this->tagdata = $tagdata;
        $this->params = $params ?: array();
        $this->member_id = ee()->session->userdata('member_id');
        $this->site_id = ee()->config->item('site_id');
        $this->prefix = REINOS_WEBSERVICE_MAP.':';

        if (isset($this->params['prefix']) === true) {
            if ($this->params['prefix'] === '') {
                $this->prefix = '';
            } else {
                $this->prefix = $this->params['prefix'] . ':';
            }
        }
    }

    // ------------------------------------------------------------------------------

    abstract public function parse();

    // ------------------------------------------------------------------------------

    public function param($key, $default = false)
    {
        if (isset($this->params[$key])) {
            // Consistent yes/no parameters
            switch ($this->params[$key]) {
                case 'yes':
                case 'y':
                case 'on':
                    return 'yes';
                break;

                case 'no':
                case 'n':
                case 'off':
                    return 'no';
                break;
            }

            return $this->params[$key];
        }

        // Do we have a default value?
        if ($default) {
            return $default;
        }

        // Not set
        return false;
    }

    // ------------------------------------------------------------------------------

    /**
     * Custom No_Result conditional
     *
     * Same as {if no_result} but with your own conditional.
     *
     * @param string $cond_name
     * @param string $source
     * @param string $return_source
     * @return unknown
     */
    public function noResultsConditional($cond_name, $source = false, $return_source = false)
    {
        $cond_name = $this->prefix . $cond_name;
        if ($source === false) {
            $source = $this->tagdata;
        }

        if (strpos($source, LD."if {$cond_name}".RD) !== false) {
            if (preg_match('/'.LD."if {$cond_name}".RD.'(.*?)'.LD.'\/'.'if'.RD.'/s', $source, $cond)) {
                return $cond[1];
            }
        }

        if ($return_source !== false) {
            return $source;
        }
    }

    // ------------------------------------------------------------------------------

    /**
     * Fetch data between var pairs
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairData($varname = '', $source = '')
    {
        if (! preg_match('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $match)) {
            return;
        }

        return $match['1'];
    }

    // ------------------------------------------------------------------------------

    /**
     * Fetch data between var pairs
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairDataAll($varname = '', $source = '')
    {
        if (! preg_match_all('/'.LD.($varname).RD.'(.*?)'.LD.'\/'.$varname.RD.'/s', $source, $matches)) {
            return;
        }

        return $matches;
    }

    // ------------------------------------------------------------------------------

    /**
     * Fetch data between var pairs (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $source - Source
     * @return string
     */
    public function fetchVarPairDataWithParams($open = '', $close = '', $source = '')
    {
        if (! preg_match('/'.LD.preg_quote($open).'.*?'.RD.'(.*?)'.LD.'\/'.$close.RD.'/s', $source, $match)) {
            return;
        }

        return $match['1'];
    }

    // ------------------------------------------------------------------------------

    /**
     * Replace var_pair with final value
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swapVarPair($varname = '', $replacement = '\\1', $source = '')
    {
        $replacement = str_replace('$', '\\$', $replacement);
        return preg_replace("/".LD.$varname.RD."(.*?)".LD.'\/'.$varname.RD."/s", $replacement, $source);
    }

    // ------------------------------------------------------------------------------

    /**
     * Replace var_pair with final value (including optional parameters)
     *
     * @param string $open - Open var (with optional parameters)
     * @param string $close - Closing var
     * @param string $replacement - Replacement
     * @param string $source - Source
     * @return string
     */
    public function swapVarPairWithParams($open = '', $close = '', $replacement = '\\1', $source = '')
    {
        $replacement = str_replace('$', '\\$', $replacement);
        return preg_replace("/".LD.preg_quote($open).RD."(.*?)".LD.'\/'.$close.RD."/s", $replacement, $source);
    }

    // ------------------------------------------------------------------------------

    protected function parseCaptcha($reg_form)
    {
        //parse the captcha
        if (preg_match("/{if captcha}(.+?){\/if}/s", $reg_form, $match)) {
            if (ee()->config->item('use_membership_captcha') == 'y') {
                $reg_form = preg_replace("/{if captcha}.+?{\/if}/s", $match['1'], $reg_form);

                // Bug fix.  Deprecate this later..
                $reg_form = str_replace('{captcha_word}', '', $reg_form);

                if (!class_exists('Template')) {
                    $reg_form = preg_replace("/{captcha}/", ee()->functions->create_captcha(), $reg_form);
                }
            } else {
                $reg_form = preg_replace("/{if captcha}.+?{\/if}/s", "", $reg_form);
            }
        }

        return $reg_form;
    }

}
