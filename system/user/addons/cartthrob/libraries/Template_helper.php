<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (class_exists(basename(__FILE__, '.php'))) {
    return;
}

/**
 * Template Helper
 *
 * @property $EE CI_Controller
 */
class Template_helper
{
    public $template_key;

    public $base_url;

    /**
     * Template_helper constructor.
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * @param array $params
     * @return $this
     */
    public function reset($params = [])
    {
        $this->base_url = (isset($params['base_url'])) ? $params['base_url'] : ee()->config->item('site_url');

        $this->template_key = (isset($params['template_key'])) ? $params['template_key'] : 'template';

        return $this;
    }

    /**
     * @param null $template
     * @return mixed
     */
    public function cp_render($template = null)
    {
        if (is_null($template)) {
            $template = ee()->input->get_post($this->template_key);
        }

        ee()->config->config['site_url'] = $this->base_url;

        ee()->config->config['site_index'] = '';

        if (ee()->input->post('ACT')) {
            ee()->db->flush_cache();

            // @TODO this is broke. for now don't do forms in your reports templates

            return ee()->core->generate_action(true);
        }

        ee()->cp->cp_page_title = $template;

        ee()->load->library('template', null, 'TMPL');

        ee()->uri->uri_string = $template;

        ee()->uri->segments = explode('/', $template);

        ee()->uri->rsegments = array_reverse(ee()->uri->segments);

        ee()->uri->_reindex_segments();

        $this->load_snippets();

        ee()->TMPL->run_template_engine(ee()->uri->segment(1), ee()->uri->segment(2));

        loadCartThrobPath();

        $this->return_data = ee()->output->get_output();

        ee()->output->set_output('');

        foreach (ee()->cp->js_files as $type => $files) {
            if (!is_array($files)) {
                ee()->cp->js_files[$type] = explode(',', $files);
            }
        }

        return $this->return_data;
    }

    private function load_snippets()
    {
        // load up any Snippets
        $query = ee()->db->select('snippet_name, snippet_contents')
            ->where('(site_id = ' . ee()->db->escape_str(ee()->config->item('site_id')) . ' OR site_id = 0)')
            ->get('snippets');

        if ($query->num_rows() > 0) {
            foreach ($query->result() as $row) {
                ee()->config->_global_vars[$row->snippet_name] = $row->snippet_contents;
            }
        }

        $query->free_result();
    }

    /**
     * @param $data
     */
    public function apply_search_filters(&$data)
    {
        ee()->load->library('data_filter');

        foreach ((array)ee()->TMPL->tagparams as $key => $value) {
            if (strncmp($key, 'search:', 7) !== 0 || !$value) {
                continue;
            }

            $key = substr($key, 7);
            $exact = false;
            $operator = null;

            if ($value && in_array($value[0], ['=', '>', '>=', '<', '<='])) {
                $exact = $value[0] === '=';
                $operator = $value[0];
                $value = substr($value, 1);
            }

            $not = false;

            if (strncmp('not ', $value, 4) === 0) {
                $not = true;
                $value = substr($value, 4);
            }

            $and = false;
            $array = false;

            if (strstr($value, '&&')) {
                $and = true;
                $array = true;
            } elseif (strstr($value, '|')) {
                $array = true;
            }

            if ($array) {
                if ($exact) {
                    if ($not) {
                        ee()->data_filter->filter($data, $key, $value, 'NOT_IN', $reset_keys = true);
                    } else {
                        ee()->data_filter->filter($data, $key, $value, 'IN', $reset_keys = true);
                    }
                } elseif ($not) {
                    if ($and) {
                        ee()->data_filter->filter($data, $key, explode('&&', $value), 'DOES_NOT_CONTAIN_ALL_OF', $reset_keys = true);
                    } else {
                        ee()->data_filter->filter($data, $key, $value, 'DOES_NOT_CONTAIN_ONE_OF', $reset_keys = true);
                    }
                } elseif ($and) {
                    ee()->data_filter->filter($data, $key, explode('&&', $value), 'CONTAINS_ALL_OF', $reset_keys = true);
                } else {
                    ee()->data_filter->filter($data, $key, $value, 'CONTAINS_ONE_OF', $reset_keys = true);
                }
            } elseif ($operator) {
                if ($exact) {
                    ee()->data_filter->filter($data, $key, $value, '==', $reset_keys = true);
                } else {
                    ee()->data_filter->filter($data, $key, $value, $operator, $reset_keys = true);
                }
            } else {
                ee()->data_filter->filter($data, $key, $value, 'CONTAINS', $reset_keys = true);
            }
        }
    }

    /**
     * @param $template
     * @param array $vars
     * @return mixed
     */
    public function fetch_and_parse($template, $vars = [])
    {
        $template_info = $this->fetch_template($template, true);

        return $this->parse_template($template_info['template_data'], $vars, $template_info['parse_php'],
            $template_info['php_parse_location'], $template_info['template_type']);
    }

    /**
     * fetch a template from the database/file structure
     *
     * @param string $template "template_group/template" format
     * @param bool $get_template_info see return below
     *
     * @return string|array either a string of the template_data or an array containing info about the template
     */
    public function fetch_template($template, $get_template_info = false)
    {
        $template = $this->parse_template_path($template);

        $template = explode('/', $template);

        $template_group = $template[0];

        $template_name = (isset($template[1])) ? $template[1] : 'index';

        $query = ee()->db->select('template_data, template_type, allow_php, php_parse_location, template_id')
            ->join('template_groups', 'templates.group_id = template_groups.group_id')
            ->where('group_name', $template_group)
            ->where('template_name', $template_name)
            ->where('templates.site_id', ee()->config->item('site_id'))
            ->get('templates');

        $data = [
            'template_data' => '',
            'parse_php' => false,
            'php_parse_location' => 'output',
            'template_type' => 'webpage',
            'template_id' => null,
        ];

        if ($query->num_rows() !== 0) {
            $data['parse_php'] = $query->row('allow_php') === 'y';

            $data['php_parse_location'] = ($query->row('php_parse_location') === 'i') ? 'input' : 'output';

            $data['template_type'] = $query->row('template_type');

            $data['template_data'] = $query->row('template_data');

            $data['template_id'] = $query->row('template_id');

            if (PATH_TMPL && ee()->config->item('save_tmpl_files') === 'y') {
                ee()->load->library('api');

                ee()->legacy_api->instantiate('template_structure');

                $file = PATH_TMPL . ee()->config->item('site_short_name') . '/'
                    . $template_group . '.group/' . $template_name
                    . ee()->api_template_structure->file_extensions($data['template_type']);

                if (file_exists($file)) {
                    $data['template_data'] = file_get_contents($file);
                }
            }

            $data['template_data'] = str_replace(["\r\n", "\r"], "\n", $data['template_data']);

            $query->free_result();
        }

        // set global template vars
        if (isset(ee()->TMPL)) {
            ee()->TMPL->group_name = $template_group;
            ee()->TMPL->template_name = $template_name;
            ee()->TMPL->template_id = $data['template_id'];
            ee()->TMPL->template_type = $data['template_type'];
        }

        if (ee()->extensions->active_hook('template_fetch_template')) {
            ee()->extensions->call('template_fetch_template', $data);
        }

        return ($get_template_info) ? $data : $data['template_data'];
    }

    /**
     * creates a template_group/template string from various possibilties:
     *    http://site.com/template_group/template/
     *    /template_group/template
     *    template_group/template
     *    template_group/template/
     *    {path=template_group/template}
     *    {site_url}template_group/template
     *
     * @param string $path a template path
     *
     * @return string
     */
    public function parse_template_path($path)
    {
        $remove = [
            '/',
            ee()->functions->fetch_site_index(true, true),
            ee()->functions->fetch_site_index(),
            ee()->functions->fetch_site_index(true, false),
            ee()->functions->fetch_site_index(false, false),
            '{site_url}',
        ];

        foreach ($remove as $starts_with) {
            $length = strlen($starts_with);

            if (strncmp($path, $starts_with, $length) === 0) {
                $path = substr($path, $length);

                break;
            }
        }

        if (strstr($path, '{path=') && preg_match('/{path=([\042\047]?)(.*?)\\1}/', $path, $match)) {
            $path = $match[2];
        }

        $path = rtrim($path, '/');

        return $path;
    }

    /**
     * @param $template
     * @param array $vars
     * @param bool $parse_php
     * @param string $php_parse_location
     * @param string $template_type
     * @return mixed
     */
    public function parse_template(
        $template,
        $vars = [],
        $parse_php = false,
        $php_parse_location = 'output',
        $template_type = 'webpage'
    ) {
        if (!isset(ee()->TMPL)) {
            ee()->load->library('template', null, 'TMPL');
        }

        if (ee()->extensions->active_hook('template_fetch_template')) {
            ee()->extensions->call('template_fetch_template', ['template_data' => $template]);
        }

        ee()->TMPL->parse_php = $parse_php;

        ee()->TMPL->php_parse_location = $php_parse_location;

        ee()->TMPL->template_type = ee()->functions->template_type = $template_type;

        if ($vars) {
            foreach ($vars as $i => $row) {
                if (is_array($row) && count($row) > 0 && !is_array(current($row))) {
                    if ($i === 'custom_data') {
                        foreach ($row as $key => $value) {
                            $vars['custom_data:' . $key] = $value;
                        }
                    }

                    unset($vars[$i]);
                }
            }

            $template = ee()->TMPL->parse_variables($template, [$vars]);
        }

        ee()->TMPL->parse($template);

        loadCartThrobPath();

        return ee()->TMPL->parse_globals(ee()->TMPL->final_template);
    }

    /**
     * @param bool $location
     */
    public function tag_redirect($location = false)
    {
        ee()->load->library('paths');

        if ($location) {
            ee()->load->library('javascript');

            ee()->functions->redirect(ee()->paths->parse_url_path($location));
        }
    }

    /**
     * @param $row
     * @return mixed
     */
    public function parse_variables_row($row)
    {
        if (!isset(ee()->TMPL)) {
            ee()->load->library('template', null, 'TMPL');
        }

        if ($prefix = ee()->TMPL->fetch_param('variable_prefix')) {
            $row = array_merge($row, array_key_prefix($row, $prefix));
        }

        return ee()->TMPL->parse_variables_row(ee()->TMPL->tagdata, $row);
    }

    /**
     * @param array $variables
     * @return mixed
     */
    public function parse_variables($variables = [])
    {
        if (!isset(ee()->TMPL)) {
            ee()->load->library('template', null, 'TMPL');
        }

        if ($prefix = ee()->TMPL->fetch_param('variable_prefix')) {
            foreach ($variables as &$row) {
                $row = array_merge($row, array_key_prefix($row, $prefix));
            }
        }

        reset($variables);

        if (!$variables || (count($variables) === 1 && !current($variables))) {
            if ($prefix && preg_match('#{if\s+' . preg_quote($prefix) . 'no_results}(.*?){/if}#s', ee()->TMPL->tagdata,
                $match)) {
                ee()->TMPL->tagdata = str_replace($match[0], '', ee()->TMPL->tagdata);

                ee()->TMPL->no_results = $match[1];
            }

            return ee()->TMPL->no_results();
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables);
    }

    /**
     * @param string $data
     * @return mixed (parsed string data)
     */
    public function parse_files($data)
    {
        // Check to see if we need to parse {filedir_n}
        if (strpos($data, '{filedir_') !== false) {
            ee()->load->library('file_field');

            return ee()->file_field->parse_string($data);
        }

        return $data;
    }
}
