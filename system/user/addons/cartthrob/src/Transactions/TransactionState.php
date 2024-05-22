<?php

namespace CartThrob\Transactions;

class TransactionState
{
    private $state;

    /**
     * @param array $state
     */
    public function __construct($state = [])
    {
        $this->fresh($state);
    }

    /**
     * @param array $state
     * @return TransactionState
     */
    private function fresh($state = [])
    {
        $this->state = array_merge(
            [
                'processing' => false,
                'authorized' => false,
                'declined' => false,
                'failed' => true,
                'refunded' => false,
                'expired' => false,
                'canceled' => false,
                'voided' => false,
                'pending' => false,
                'error_message' => '',
                'transaction_id' => null,
            ],
            $state
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->state['error_message'];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->state['transaction_id'];
    }

    /**
     * @param $transactionId
     * @return TransactionState
     */
    public function setTransactionId($transactionId)
    {
        $this->state['transaction_id'] = $transactionId;

        return $this;
    }

    /**
     * @param null $msg
     * @return TransactionState
     */
    public function setAuthorized($msg = null)
    {
        return $this->fresh([
            'authorized' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param null $msg
     * @return TransactionState
     */
    public function setRefunded($msg = null)
    {
        return $this->fresh([
            'refunded' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param null $msg
     * @return TransactionState
     */
    public function setPending($msg = null)
    {
        return $this->fresh([
            'processing' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param $msg
     * @return TransactionState
     */
    public function setFailed($msg = null)
    {
        return $this->fresh([
            'failed' => true,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param $msg
     * @return TransactionState
     */
    public function setProcessing($msg = null)
    {
        return $this->fresh([
            'processing' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param $msg
     * @return TransactionState
     */
    public function setCanceled($msg = null)
    {
        return $this->fresh([
            'canceled' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param $msg
     * @return TransactionState
     */
    public function setExpired($msg = null)
    {
        return $this->fresh([
            'expired' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param $msg
     * @return TransactionState
     */
    public function setDeclined($msg = null)
    {
        return $this->fresh([
            'declined' => true,
            'failed' => false,
            'error_message' => $msg,
        ]);
    }

    /**
     * @param array $auth
     * @return TransactionState
     * @TODO This creates a nested auth object on the order. Why?
     */
    public function setAuth($auth)
    {
        $this->state['auth'] = $auth;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized()
    {
        return (bool)$this->state['authorized'];
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return (bool)$this->state['failed'];
    }

    /**
     * @return bool
     */
    public function isDeclined()
    {
        return (bool)$this->state['declined'];
    }

    /**
     * @return bool
     */
    public function isProcessing()
    {
        return (bool)$this->state['processing'];
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return (bool)$this->state['expired'];
    }

    /**
     * @return bool
     */
    public function isCanceled()
    {
        return (bool)$this->state['canceled'];
    }

    /**
     * @return bool
     */
    public function isVoided()
    {
        return (bool)$this->state['voided'];
    }

    /**
     * @return bool
     */
    public function isRefunded()
    {
        return (bool)$this->state['refunded'];
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return (bool)$this->state['pending'];
    }
}
