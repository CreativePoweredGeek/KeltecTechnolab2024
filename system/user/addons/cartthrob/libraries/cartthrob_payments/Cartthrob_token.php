<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Cartthrob_token
{
    protected $token;
    protected $customerId;
    protected $errorMsg = '';
    protected $offsite = false;

    public function __construct($params = [])
    {
        foreach (array_keys(get_object_vars($this)) as $key) {
            if (isset($params[$key])) {
                $this->$key = $params[$key];
            }
        }
    }

    public function __toString()
    {
        return $this->token();
    }

    public function token()
    {
        return $this->token;
    }

    public function customer_id()
    {
        return $this->customerId;
    }

    public function error_message()
    {
        return $this->errorMsg;
    }

    public function offsite()
    {
        return $this->offsite;
    }

    public function set_token($token)
    {
        $this->token = $token;

        return $this;
    }

    public function set_customer_id($customerId)
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function set_error_message($msg)
    {
        $this->errorMsg = $msg;

        return $this;
    }

    public function set_offsite($offsite = true)
    {
        $this->offsite = $offsite;

        return $this;
    }
}
