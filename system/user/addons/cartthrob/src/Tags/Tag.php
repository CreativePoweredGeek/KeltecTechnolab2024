<?php

namespace CartThrob\Tags;

use CartThrob\HasVariables;
use EE_Session;

abstract class Tag
{
    use HasVariables;

    /** @var EE_Session */
    private $session;

    public function __construct(EE_Session $session)
    {
        $this->session = $session;
    }

    /**
     * Coordinate data and parse template tag
     *
     * @return string
     */
    abstract public function process();

    /**
     * Test if a parameter exists
     *
     * @param $key
     * @return bool
     */
    public function hasParam($key)
    {
        return isset(ee()->TMPL->tagparams[$key]);
    }

    /**
     * Parse template with provided data
     *
     * @param array $tagVars
     * @param string $tagData
     * @return string
     */
    public function parseVariables(array $tagVars, $tagData = '')
    {
        if ($prefix = $this->param('variable_prefix')) {
            foreach ($tagVars as &$row) {
                $row = array_merge($row, array_key_prefix($row, $prefix));
            }
        }

        reset($tagVars);

        if (!$tagVars || (count($tagVars) === 1 && !current($tagVars))) {
            return $this->noResults(preg_quote($prefix) . 'no_results');
        }

        $tagData = empty($tagData) ? $this->tagdata() : $tagData;

        return ee()->TMPL->parse_variables($tagData, $tagVars);
    }

    /**
     * Get all tag data
     *
     * @return string
     */
    public function tagdata()
    {
        return ee()->TMPL->tagdata;
    }

    /**
     * Set tag data
     *
     * @param string $tagdata
     * @return $this
     */
    public function setTagdata(string $tagdata)
    {
        ee()->TMPL->tagdata = $tagdata;

        return $this;
    }

    /**
     * @return array
     */
    public function getVarSingle()
    {
        return ee()->TMPL->var_single;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setVarSingle($data)
    {
        ee()->TMPL->var_single = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getVarPair()
    {
        return ee()->TMPL->var_pair;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setVarPair($data)
    {
        ee()->TMPL->var_pair = $data;

        return $this;
    }

    /**
     * Get all tag parameters
     *
     * @return array
     */
    public function params()
    {
        return ee()->TMPL->tagparams;
    }

    /**
     * Get a tag parameter
     *
     * @param $key
     * @param bool $default
     * @param bool $castBooleans
     * @return bool|mixed|string
     */
    public function param($key, $default = false, $castBooleans = true)
    {
        if (!isset(ee()->TMPL->tagparams[$key]) || ee()->TMPL->tagparams[$key] == '') {
            return $default;
        }

        if (is_bool(ee()->TMPL->tagparams[$key])) {
            return ee()->TMPL->tagparams[$key];
        }

        switch (strtolower(ee()->TMPL->tagparams[$key])) {
            case 'true':
            case 't':
            case 'yes':
            case 'y':
            case 'on':
                return $castBooleans ? true : 'yes';

            case 'false':
            case 'f':
            case 'no':
            case 'n':
            case 'off':
                return $castBooleans ? false : 'no';

            default:
                // Remove leading and trailing whitespace
                return trim(str_replace(['&nbsp;', '&#32;'], [' ', ' '], ee()->TMPL->tagparams[$key]));
        }
    }

    /**
     * Get a tag parameter and explode into an array on $delimiter
     *
     * @param $key
     * @param bool $default
     * @param string $delimiter
     * @return array|mixed|bool
     */
    public function explodeParam($key, $default = false, $delimiter = '|')
    {
        if (!$this->hasParam($key) || $this->param($key) == '') {
            return $default;
        }

        return explode($delimiter, $this->param($key, $default));
    }

    /**
     * Set a tag parameter
     *
     * @param $key
     * @param $value
     * @return Tag
     */
    public function setParam($key, $value)
    {
        ee()->TMPL->tagparams[$key] = $value;

        return $this;
    }

    /**
     * Clear a tag parameter
     *
     * @param $key
     */
    public function clearParam($key)
    {
        unset(ee()->TMPL->tagparams[$key]);
    }

    /**
     * Returns the portion of tagdata found between the specified {if no_results} tags,
     * or returns false if no tag exists
     *
     * @param $tagName
     * @return bool|mixed
     */
    public function noResults($tagName)
    {
        if (!empty($tagName) && strpos(ee()->TMPL->tagdata, 'if ' . $tagName) !== false &&
            preg_match('/' . LD . 'if ' . $tagName . RD . '(.*?)' . LD . '\/if' . RD . '/s', ee()->TMPL->tagdata, $match)
        ) {
            // currently this won't handle nested conditional statements.. lame
            return $match[1];
        }

        return ee()->TMPL->no_results();
    }

    /**
     * Parse template rows with provided data
     *
     * @param array $row
     * @return string
     */
    public function parseVariablesRow(array $row)
    {
        if ($prefix = $this->param('variable_prefix')) {
            $row = array_merge($row, array_key_prefix($row, $prefix));
        }

        return ee()->TMPL->parse_variables_row($this->tagdata(), $row);
    }

    /**
     * @param array $data
     * @return $this
     */
    public function setFlashdata(array $data)
    {
        $this->session->set_flashdata($data);

        return $this;
    }

    /**
     * @return string|false
     */
    public function getMemberId()
    {
        return $this->session->userdata('member_id');
    }

    /**
     * @return string|false
     */
    public function getGroupId()
    {
        // return $this->session->userdata('group_id');
        return $this->session->userdata('role_id');
    }

    /**
     * @return array|false
     */
    public function getRoles()
    {
        return $this->session->getMember()->getAllRoles()->pluck('role_id');
    }

    /**
     * @return bool
     */
    public function memberLoggedIn()
    {
        return $this->getMemberId() !== 0;
    }

    /**
     * Require logged out users to redirect if `logged_out_redirect` param is provided
     */
    protected function guardLoggedOutRedirect()
    {
        if (!$this->memberLoggedIn() && $this->hasParam('logged_out_redirect')) {
            ee()->template_helper->tag_redirect($this->param('logged_out_redirect'));
        }
    }
}
