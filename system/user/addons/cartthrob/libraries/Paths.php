<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Paths
{
    protected ?array $upload_dirs = null;

    protected ?array $upload_server_paths = null;

    /**
     * @param $class
     * @param $action
     * @param array $params
     * @param bool $insert_action_ids
     * @return string
     */
    public function build_action_url($class, $action, $params = [], $insert_action_ids = true)
    {
        $url = ee()->functions->fetch_site_index(0, 0) . QUERY_MARKER
            . 'ACT=' . ee()->functions->fetch_action_id($class, $action);

        if ($params) {
            $url .= '&' . http_build_query($params);
        }

        if ($insert_action_ids) {
            $url = ee()->functions->insert_action_ids($url);
        }

        return $url;
    }

    /**
     * parses {filedir_X} variables in a string to server paths
     *
     * @param string $string the string containing the {filedir_X} variables
     *
     * @return string
     */
    public function parse_file_server_paths($string)
    {
        if (preg_match_all('/{filedir_(\d+)}/', $string, $matches)) {
            foreach ($matches[1] as $i => $upload_dir) {
                if (!isset($this->upload_dirs[$upload_dir])) {
                    ee()->load->model('file_upload_preferences_model');

                    $upload_preferences = ee()->file_upload_preferences_model->get_file_upload_preferences(1, $upload_dir);
                    $this->upload_dirs[$upload_dir] = ($upload_preferences) ? $upload_preferences['server_path'] : '';
                }

                $string = str_replace($matches[0][$i], $this->upload_dirs[$upload_dir], $string);
            }
        }

        return $string;
    }

    /**
     * @param $path
     * @return bool
     */
    public function is_windows_path($path)
    {
        return (bool)preg_match('#^\w+:' . DIRECTORY_SEPARATOR . '#', $path);
    }

    /**
     * @param $path
     * @return string
     */
    public function parse_path($path)
    {
        return $this->parse_url_path($path);
    }

    /**
     * takes a presumed url path and parses out any unparsed url tags ({site_url} and {path=})
     * if it's not a url and is a template path (ie. site/index), it will convert to a url
     *
     * @param string $path
     *
     * @return string a full url
     */
    public function parse_url_path($path)
    {
        if (!$path) {
            return '';
        }

        // has a {site_url} variable
        if (strpos($path, '{site_url}') !== false) {
            $path = str_replace('{site_url}', ee()->functions->fetch_site_index(1), $path);
        }

        // is a {path=site/index} variable
        if (strpos($path, '{path=') !== false) {
            $path = preg_replace_callback('/{path=[\042\047]?(.*?)[\042\047]?}/', [ee()->functions, 'create_url'],
                $path);
        }

        // is not a web or http url (ie. http://site.com/site/index) OR is a relative path, starting with a slash (ie. /site/index)
        if (!$this->is_url($path)) {
            $path = ee()->functions->create_url($path);
        }

        return $path;
    }

    /**
     * Checks is a path is a url
     *
     * @param string $path
     * @param bool $check_is_server_path if TRUE, this will return FALSE if the path is a path to an existing file on the server
     *                                   this is so a path like /site/index will be seen as a url
     *                                   while a path like /var/www/html/myfile.zip will not
     *
     * @return bool RUE if the path starts with http://, ftp:// or /
     */
    public function is_url($path, $check_is_server_path = false)
    {
        if (preg_match('#^(https?://|ftp://)#i', $path)) {
            return true;
        }

        if (strncmp($path, '/', 1) === 0 && (!$check_is_server_path || !file_exists($path))) {
            return true;
        }

        return false;
    }

    /**
     * @param $path
     * @return mixed|string
     */
    public function get_server_path($path)
    {
        if (!$path) {
            return $path;
        }

        if (file_exists($path)) {
            return $path;
        }

        // get rid of {path=} and {site_url} variables
        $path = $this->parse_url_file_path($path);

        if (is_null($this->upload_server_paths)) {
            ee()->load->model('file_upload_preferences_model');

            $this->upload_server_paths = ee()->file_upload_preferences_model->get_file_upload_preferences(1);
        }

        /*
         * Loop through existing upload dirs and check to see if the given $path
         * starts with the upload dir's url. if so, swap the url with the server
         * path and return
         */
        foreach ($this->upload_server_paths as $row) {
            if (strncmp($path, $row['url'], strlen($row['url'])) === 0) {
                return str_replace($row['url'], $row['server_path'], $path);
            }
        }

        $site_url = ee()->config->item('site_url');

        // strip the index.php part of site_url if it's there
        if (pathinfo($site_url, PATHINFO_EXTENSION) === 'php') {
            $site_url = dirname($site_url) . '/';
        }

        // the path starts with the site_url
        if (strncmp($path, $site_url, strlen($site_url)) === 0) {
            // replace the url with a server path and check if it exists
            $guessed_path = str_replace($site_url, $_SERVER['DOCUMENT_ROOT'] . '/', $path);

            if (file_exists($guessed_path)) {
                return $guessed_path;
            }
        } // path starts with /
        else {
            if (strncmp($path, '/', 1) === 0) {
                $guessed_path = $_SERVER['DOCUMENT_ROOT'] . $path;

                if (file_exists($guessed_path)) {
                    return $guessed_path;
                }
            }
        }

        return $path;
    }

    /**
     * @param $path
     * @return mixed|string
     */
    public function parse_url_file_path($path)
    {
        if (!$path) {
            return '';
        }

        $site_url = ee()->functions->fetch_site_index(1);

        // strip the index.php part of site_url if it's there
        if (pathinfo($site_url, PATHINFO_EXTENSION) === 'php') {
            $site_url = dirname($site_url) . '/';
        }

        // has a {site_url} variable
        if (strpos($path, '{site_url}') !== false) {
            $path = str_replace('{site_url}', $site_url, $path);
        }

        // is a {path=site/index} variable
        if (strpos($path, '{path=') !== false && preg_match('/{path=[\042\047]?(.*?)[\042\047]?}/', $path, $match)) {
            $path = str_replace($match[0], $site_url . $match[1], $path);
        }

        return $path;
    }
}
