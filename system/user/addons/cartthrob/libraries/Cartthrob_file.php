<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property CI_Controller $EE
 */
class Cartthrob_file
{
    public $errors;

    /**
     * @return mixed
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * upload
     *
     * @param string $directory the id or server location of upload directory
     * @return bool(false)|array
     */
    public function upload($directory = null)
    {
        $allowed_types = 'gif|jpg|png';
        $max_size = '0';
        $max_width = '0';
        $max_height = '0';

        ee()->load->model('file_upload_preferences_model');
        $file_dirs = ee()->file_upload_preferences_model->get_file_upload_preferences();
        if (empty($file_dirs)) {
            $this->add_error(ee()->lang->line('upload_urls_not_set'));

            return false;
        }
        // if no directory was specified, we'll use the original default directory if it exists
        if (!$directory || is_numeric($directory)) {
            $directory_id = $directory;

            foreach ($file_dirs as $dir) {
                // using the first directory ID we find since none was passed in.
                if (!$directory_id) {
                    $directory_id = $dir['id'];
                }
                if (!empty($dir['id']) && $dir['id'] == $directory_id) {
                    $directory = $dir['server_path'];
                    $allowed_types = $dir['allowed_types'];
                    $max_size = $dir['max_size'];
                    $max_width = $dir['max_width'];
                    $max_height = $dir['max_height'];
                }
            }
        }

        if ($allowed_types == 'all') {
            $allowed_types = '*';
        }
        if (!$directory) {
            $this->add_error(ee()->lang->line('upload_url_not_specified'));

            return false;
        }
        $config = [
            'upload_path' => $directory,
            'allowed_types' => $allowed_types,
            'max_size' => $max_size,
            'max_width' => $max_width,
            'max_height' => $max_height,
            'overwrite' => false, // default
            'remove_spaces' => true, // default
        ];
        ee()->load->library('upload', $config);

        if (!ee()->upload->do_upload()) {
            $this->add_error(ee()->upload->display_errors());

            return false;
        } else {
            return ee()->upload->data();
        }
    }

    /**
     * @param $error
     * @return $this
     */
    public function add_error($error)
    {
        if (is_array($error)) {
            foreach ($error as $e) {
                $this->add_error($e);
            }
        } else {
            $this->errors[] = $error;
        }

        return $this;
    }

    /**
     * @param null $post_url
     * @return string
     */
    public function fileDebug($post_url = null)
    {
        ee()->load->library('paths');

        if (!$post_url) {
            debug([ee()->lang->line('download_filename') => ee()->lang->line('download_url_not_specified')]);
        }

        $url = ee()->paths->get_server_path($post_url);

        $file_info[ee()->lang->line('download_filename')] = $filename = $this->get_filename($url);
        $file_info[ee()->lang->line('download_extension')] = $extension = $this->get_extension($filename);
        $file_info[ee()->lang->line('download_mime')] = $this->getMime($extension);

        $download_info = $this->force_download($url, $debug = true);

        @ob_start();
        debug(array_merge($download_info, $file_info));
        $buffer = @ob_get_contents();
        @ob_end_clean();

        return $buffer;
    }

    /**
     * @param $url
     * @return string|string[]|null
     */
    public function get_filename($url)
    {
        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }
        $filename = basename($url);

        if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
        }

        return $filename;
    }

    /**
     * @param $filename
     * @return bool|mixed
     */
    public function get_extension($filename)
    {
        if (false === strpos($filename, '.')) {
            return false;
        }
        $ext = explode('.', $filename);
        $extension = end($ext);

        return $extension;
    }

    /**
     * @param $extension
     * @return string
     */
    public function getMime($extension)
    {
        // get mimes
        if (defined('ENVIRONMENT') and is_file(APPPATH . 'config/' . ENVIRONMENT . '/mimes.php')) {
            include APPPATH . 'config/' . ENVIRONMENT . '/mimes.php';
        } elseif (is_file(APPPATH . 'config/mimes.php')) {
            $mimes = ee()->config->loadFile('mimes');
        }

        if (!isset($mimes[$extension])) {
            $mime = 'application/octet-stream';
        } else {
            $mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
        }

        return $mime;
    }

    /**
     * @param $path
     * @param bool $debug
     * @return mixed
     */
    public function force_download($path, $debug = false)
    {
        $this->clear_errors();

        ee()->load->library('curl');
        ee()->load->library('form_builder');

        // strip trailing slash
        $path = rtrim($path, '/');

        // output the URL if debugging is on.
        if ($debug) {
            $debug_array['URL'] = $path;
        }

        ee()->load->library('paths');

        // this will attempt to turn some urls into server path if possible
        $path = ee()->paths->get_server_path($path);

        $filename = $this->get_filename($path);
        $extension = $this->get_extension($filename);
        $mime = $this->getMime($extension);

        if (ee()->paths->is_url($path, true)) {
            if ($debug) {
                $debug_array['Note'] = ee()->lang->line('download_remote_file');

                if (!is_callable('apache_setenv')) {
                    $debug_array['Note'] .= ' ' . ee()->lang->line('gzip_settings_cant_be_adjusted');
                }
                if (!is_callable('ini_set')) {
                    $debug_array['Note'] .= ' ' . ee()->lang->line('output_compression_settings_cant_be_adjusted');
                }

                // @TODO change to LANG file
                $debug_array['file_size'] = 'unknown';

                return $debug_array;
            }
            // remote file. attempt to get it.
            $data = ee()->curl->simple_get($path);

            if ($data) {
                // alert that this file can be gotten from the remote site
                header('Content-Type: ' . $mime);
                header('Content-Disposition: attachment; filename="' . $filename . '";');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Content-Transfer-Encoding: binary');
                header('Pragma: public');
                header('Content-Length: ' . strlen($data));
                header('Cache-Control: public', false);
                exit($data);
            } else {
                $this->add_error(ee()->lang->line('download_file_read_error'));
            }
        } else {
            // local. download it
            if (file_exists($path)) {
                // alert that this file can be gotten from the local site
                if ($debug) {
                    $debug_array['Note'] = ee()->lang->line('download_local_data_exists');

                    if (!is_callable('apache_setenv')) {
                        $debug_array['Note'] .= ' ' . ee()->lang->line('gzip_settings_cant_be_adjusted');
                    }
                    if (!is_callable('ini_set')) {
                        $debug_array['Note'] .= ' ' . ee()->lang->line('output_compression_settings_cant_be_adjusted');
                    }

                    $debug_array['file_size'] = filesize($path) . ' bytes';

                    return $debug_array;
                }

                // turning off compression if its set up.
                // if these don't work, add this to htaccess SetEnv no-gzip dont-vary
                if (is_callable('apache_setenv')) {
                    @apache_setenv('no-gzip', 1);
                }
                if (is_callable('ini_set')) {
                    @ini_set('zlib.output_compression', 0);
                }

                $file_size = filesize($path);

                header('Accept-Ranges: bytes');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: public', false);
                header('Content-Description: File Transfer');
                header('Content-Disposition: attachment; filename="' . $filename . '";');
                header('Content-Transfer-Encoding: binary');
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . $file_size);
                header('Pragma: public');

                $chunk_size = 1024 * 1024;

                // if the file is large, using chunked fread
                if ($file_size > $chunk_size) {
                    // ending the session so the user can continue to browse the site while the file is downloading.
                    @session_destroy();
                    if (@$this->readfile_chunked($path, $chunk_size) === false) {
                        $this->add_error(ee()->lang->line('download_file_read_error'));
                    }
                } else {
                    @ob_clean();
                    @flush();
                    // ending the session so the user can continue to browse the site while the file is downloading.
                    @session_destroy();
                    // @ added to suppress PHP errors which were outputting file path as part of error message.
                    if (@readfile($path) === false) {
                        $read_success = false;
                        $this->add_error(ee()->lang->line('download_file_read_error'));
                    }
                }
                exit;
            } else {
                if ($debug) {
                    $debug_array['Note'] = ee()->lang->line('download_local_data_does_not_exist');

                    return $debug_array;
                }

                $this->add_error(ee()->lang->line('download_file_read_error'));
            }
        }
        if ($debug) {
            return $debug_array;
        }
    }

    public function clear_errors()
    {
        $this->errors = [];
    }

    /**
     * @param $filename
     * @param int $chunk_size
     * @return bool|int
     */
    public function readfile_chunked($filename, $chunk_size = 1048576)
    {
        $handle = fopen($filename, 'rb');
        if ($handle === false) {
            return false;
        }
        // reading block by block. This gets around problem where $chunk_size is exactly the length of the file and fread is trying to read data that doesn't exist in the loop.
        while (($buffer = fread($handle, $chunk_size)) != '') {
            echo $buffer;
            @ob_flush();
            @flush();
            $bytes += strlen($buffer);
        }
        if (fclose($handle)) {
            return $bytes;
        }
    }

    /**
     * outputStreamingFileHeaders
     *
     * Ooooh I like this function!
     *
     * execute this function before running an echo on content, and you can effectively stream / download
     * the output content. This is great for outputting craploads of content, like from a mysql dump.
     * make sure the output content is printed in manageable chunks.
     *
     **/
    public function outputStreamingFileHeaders($filename = 'file.txt')
    {
        $extension = $this->get_extension($filename);
        $mime = $this->getMime($extension);

        if (is_callable('apache_setenv')) {
            @apache_setenv('no-gzip', 1);
        }
        if (is_callable('ini_set')) {
            @ini_set('zlib.output_compression', 0);
        }
        @session_destroy();

        header('Accept-Ranges: bytes');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public', false);
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        header('Content-Transfer-Encoding: binary');
        header('Content-Type: ' . $mime);
        // we don't know the size of the file, so we can't set it.
        // it doesn't matter much since we're not downloading MP3s or ZIPs.
        // header('Content-Length: ' . $file_size);
        header('Pragma: public');
    }
}
