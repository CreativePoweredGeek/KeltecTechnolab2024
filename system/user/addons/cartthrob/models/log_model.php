<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Log model
 */
class Log_model extends CI_Model
{
    public $cartthrob;
    public $store;
    public $cart;

    /**
     * Log_model constructor.
     */
    public function __construct()
    {
        $this->load->library('logger');
    }

    /**
     * Write an entry to EE's console log
     *
     * @param $message
     * @param bool $method
     */
    public function log($message, $method = false)
    {
        if ($method === 'js') {
            $this->load->library('javascript');

            echo '<script type="text/javascript">var log = ' . json_encode(['message' => $message]) . '; if (window.console){ window.console.log(log.message); }</script>';
        } else {
            if ($method === 'console') {
                if (is_string($message)) {
                    echo $message . PHP_EOL;
                } else {
                    var_dump($message);
                }
            } else {
                if (file_exists(PATH_THIRD . 'omnilog/classes/omnilogger.php')) {
                    require_once PATH_THIRD . 'omnilog/classes/omnilogger.php';

                    if (is_array($message)) {
                        $simple_array = true;

                        foreach ($message as $key => $value) {
                            if (!is_int($key) || ($value && !is_string($value))) {
                                $simple_array = false;
                            }
                        }

                        $message = ($simple_array) ? implode("\r\n", $message) : print_r($message, true);
                    }

                    $omnilog_entry = new Omnilog_entry([
                        'addon_name' => 'CartThrob',
                        'date' => time(),
                        'message' => $message,
                        'notify_admin' => false,
                        'type' => Omnilog_entry::NOTICE,
                    ]);

                    Omnilogger::log($omnilog_entry);

                    unset($omnilog_entry);
                } else {
                    $this->logger->log_action($message);
                }
            }
        }
    }
}
// END CLASS
