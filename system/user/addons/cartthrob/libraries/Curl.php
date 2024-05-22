<?php defined('BASEPATH') OR exit('No direct script access allowed');

// Copyright 2009 Philip Sturgeon. Used with permission.

/*
 DON'T BE A **** PUBLIC LICENSE

                    Version 1, December 2009

 Copyright (C) 2009 Philip Sturgeon <email@philsturgeon.co.uk>
 
 Everyone is permitted to copy and distribute verbatim or modified
 copies of this license document, and changing it is allowed as long
 as the name is changed.

                  DON'T BE A **** PUBLIC LICENSE
    TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

  1. Do whatever you like with the original work, just don't be a ****.

     Being a **** includes - but is not limited to - the following instances:

 1a. Outright copyright infringement - Don't just copy this and change the name.
 1b. Selling the unmodified original with no work done what-so-ever, that's REALLY being a ****.
 1c. Modifying the original work to contain hidden harmful content. That would make you a PROPER ****.

  2. If you become rich through modifications, related works/services, or supporting the original work,
 share the love. Only a **** would make loads off this work and not buy the original works 
 creator(s) a pint.
 
  3. Code is provided with no warranty. Using somebody else's code and bitching when it goes wrong makes 
 you a ******* ****. Fix the problem yourself. A non-**** would submit the fix back.
*/

/**
 * CodeIgniter Curl Class
 *
 * Work with remote servers via cURL much easier than using the native PHP bindings.
 *
 * @package            CodeIgniter
 * @subpackage        Libraries
 * @category        Libraries
 * @author            Philip Sturgeon
 * @license         http://philsturgeon.co.uk/code/dbad-license
 * @link            http://philsturgeon.co.uk/code/codeigniter-curl
 */
class Curl
{

    public $error_code;    // CodeIgniter instance
    public $error_string;  // Contains the cURL response for debug
    public $info;          // Contains the cURL handler for a session
    private $response;     // Populates curl_setopt_array
    private $session;      // Populates extra HTTP headers
    private $url;          // Error code returned as an int
    private $options = []; // Error message returned as a string
    private $headers = []; // Returned after request (elapsed time, etc)

    /**
     * Curl constructor.
     * @param string $url
     */
    function __construct($url = '')
    {
        log_message('debug', 'cURL Class Initialized');

        if (!$this->is_enabled()) {
            log_message('error',
                'cURL Class - PHP was not built with cURL enabled. Rebuild PHP with --with-curl to use cURL.');
        }

        $url AND $this->create($url);
    }

    /**
     * @return bool
     */
    public function is_enabled()
    {
        return function_exists('curl_init');
    }

    /* =================================================================================
     * SIMPLE METHODS
     * Using these methods you can make a quick and easy cURL call with one line.
     * ================================================================================= */

    /**
     * Return a get request results
     * @param $url
     * @return $this
     */
    public function create($url)
    {
        // Reset the class
        $this->set_defaults();

        // If no a protocol in URL, assume its a CI link
        if (!preg_match('!^\w+://! i', $url)) {
            ee()->load->helper('url');
            $url = site_url($url);
        }

        $this->url = $url;
        $this->session = curl_init($this->url);

        return $this;
    }

    /**
     *
     */
    private function set_defaults()
    {
        $this->response = '';
        $this->info = [];
        $this->options = [];
        $this->error_code = 0;
        $this->error_string = '';
    }

    /* =================================================================================
     * ADVANCED METHODS
     * Use these methods to build up more complex queries
     * ================================================================================= */

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    function __call($method, $arguments)
    {
        if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_delete'))) {
            // Take off the "simple_" and past get/post/put/delete to _simple_call
            $verb = str_replace('simple_', '', $method);
            array_unshift($arguments, $verb);
            return call_user_func_array(array($this, '_simple_call'), $arguments);
        }
    }

    /**
     * @param $method
     * @param $url
     * @param array $params
     * @param array $options
     * @return bool|mixed
     */
    public function _simple_call($method, $url, $params = [], $options = [])
    {
        // If a URL is provided, create new session
        $this->create($url);

        $this->{$method}($params, $options);

        // Add in the specific options provided
        $this->options($options);

        return $this->execute();
    }

    /**
     * @param array $options
     * @return $this
     */
    public function options($options = [])
    {
        // Merge options in with the rest - done as array_merge() does not overwrite numeric keys
        foreach ($options as $option_code => $option_value) {
            $this->option($option_code, $option_value);
        }

        // Set all options provided
        curl_setopt_array($this->session, $this->options);

        return $this;
    }

    /**
     * @param $code
     * @param $value
     * @return $this
     */
    public function option($code, $value)
    {
        if (is_string($code) && !is_numeric($code)) {
            $code = constant('CURLOPT_' . strtoupper($code));
        }

        $this->options[$code] = $value;
        return $this;
    }

    /**
     * @return bool|mixed
     */
    public function execute()
    {
        // Set two default options, and merge any extra ones in
        if (!isset($this->options[CURLOPT_TIMEOUT])) {
            $this->options[CURLOPT_TIMEOUT] = 30;
        }
        if (!isset($this->options[CURLOPT_RETURNTRANSFER])) {
            $this->options[CURLOPT_RETURNTRANSFER] = true;
        }
        if (!isset($this->options[CURLOPT_FAILONERROR])) {
            $this->options[CURLOPT_FAILONERROR] = true;
        }

        // Only set follow location if not running securely
        if (!ini_get('safe_mode') && !ini_get('open_basedir')) {
            // Ok, follow location is not set already so lets set it to true
            if (!isset($this->options[CURLOPT_FOLLOWLOCATION])) {
                $this->options[CURLOPT_FOLLOWLOCATION] = true;
            }
        }

        if (!empty($this->headers)) {
            $this->option(CURLOPT_HTTPHEADER, $this->headers);
        }

        $this->options();

        // Execute the request & and hide all output
        $this->response = curl_exec($this->session);
        $this->info = curl_getinfo($this->session);

        // Request failed
        if ($this->response === false) {
            $this->error_code = curl_errno($this->session);
            $this->error_string = curl_error($this->session);

            curl_close($this->session);
            $this->session = null;
            return false;
        } // Request successful
        else {
            curl_close($this->session);
            $this->session = null;
            return $this->response;
        }
    }

    /**
     * @param $url
     * @param $file_path
     * @param string $username
     * @param string $password
     * @return bool|mixed
     */
    public function simple_ftp_get($url, $file_path, $username = '', $password = '')
    {
        // If there is no ftp:// or any protocol entered, add ftp://
        if (!preg_match('!^(ftp|sftp)://! i', $url)) {
            $url = 'ftp://' . $url;
        }

        // Use an FTP login
        if ($username != '') {
            $auth_string = $username;

            if ($password != '') {
                $auth_string .= ':' . $password;
            }

            // Add the user auth string after the protocol
            $url = str_replace('://', '://' . $auth_string . '@', $url);
        }

        // Add the filepath
        $url .= $file_path;

        $this->option(CURLOPT_BINARYTRANSFER, true);
        $this->option(CURLOPT_VERBOSE, true);

        return $this->execute();
    }

    /**
     * @param array $params
     * @param array $options
     */
    public function post($params = [], $options = [])
    {
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        // Add in the specific options provided
        $this->options($options);

        $this->http_method('post');

        $this->option(CURLOPT_POST, true);
        $this->option(CURLOPT_POSTFIELDS, $params);
    }

    /**
     * @param $method
     * @return $this
     */
    public function http_method($method)
    {
        $this->options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }

    /**
     * @param array $params
     * @param array $options
     */
    public function put($params = [], $options = [])
    {
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        // Add in the specific options provided
        $this->options($options);

        $this->http_method('put');
        $this->option(CURLOPT_POSTFIELDS, $params);

        // Override method, I think this overrides $_POST with PUT data but... we'll see eh?
        $this->option(CURLOPT_HTTPHEADER, array('X-HTTP-Method-Override: PUT'));
    }

    /**
     * @param $params
     * @param array $options
     */
    public function delete($params, $options = [])
    {
        // If its an array (instead of a query string) then format it correctly
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        // Add in the specific options provided
        $this->options($options);

        $this->http_method('delete');

        $this->option(CURLOPT_POSTFIELDS, $params);
    }

    /**
     * @param array $params
     * @return $this
     */
    public function set_cookies($params = [])
    {
        if (is_array($params)) {
            $params = http_build_query($params, null, '&');
        }

        $this->option(CURLOPT_COOKIE, $params);
        return $this;
    }

    /**
     * @param $header
     * @param null $content
     */
    public function http_header($header, $content = null)
    {
        $this->headers[] = $content ? $header . ': ' . $content : $header;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $type
     * @return $this
     */
    public function http_login($username = '', $password = '', $type = 'any')
    {
        $this->option(CURLOPT_HTTPAUTH, constant('CURLAUTH_' . strtoupper($type)));
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * @param string $url
     * @param int $port
     * @return $this
     */
    public function proxy($url = '', $port = 80)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, true);
        $this->option(CURLOPT_PROXY, $url . ':' . $port);
        return $this;
    }

    /**
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function proxy_login($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }

    /**
     * @param bool $verify_peer
     * @param int $verify_host
     * @param null $path_to_cert
     * @return $this
     */
    public function ssl($verify_peer = true, $verify_host = 2, $path_to_cert = null)
    {
        if ($verify_peer) {
            $this->option(CURLOPT_SSL_VERIFYPEER, true);
            $this->option(CURLOPT_SSL_VERIFYHOST, $verify_host);
            $this->option(CURLOPT_CAINFO, $path_to_cert);
        } else {
            $this->option(CURLOPT_SSL_VERIFYPEER, false);
        }
        return $this;
    }

    /**
     *
     */
    public function debug()
    {
        echo "=============================================<br/>\n";
        echo "<h2>CURL Test</h2>\n";
        echo "=============================================<br/>\n";
        echo "<h3>Response</h3>\n";
        echo "<code>" . nl2br(htmlentities($this->response)) . "</code><br/>\n\n";

        if ($this->error_string) {
            echo "=============================================<br/>\n";
            echo "<h3>Errors</h3>";
            echo "<strong>Code:</strong> " . $this->error_code . "<br/>\n";
            echo "<strong>Message:</strong> " . $this->error_string . "<br/>\n";
        }

        echo "=============================================<br/>\n";
        echo "<h3>Info</h3>";
        echo "<pre>";
        print_r($this->info);
        echo "</pre>";
    }

    /**
     * @return array
     */
    public function debug_request()
    {
        return [
            'url' => $this->url
        ];
    }
}
