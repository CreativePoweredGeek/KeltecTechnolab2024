<?php

namespace CartThrob\Dependency\Omnipay\Dummy\Message;

use CartThrob\Dependency\Omnipay\Common\Message\AbstractRequest;
use CartThrob\Dependency\Omnipay\Common\Message\ResponseInterface;
/**
 * Dummy Complete/Capture/Void/Refund Request
 *
 * This is the request that will be called for any transaction which submits a transactionReference.
 */
class TransactionReferenceRequest extends AbstractRequest
{
    public function getData()
    {
        $this->validate('transactionReference');
        return array('transactionReference' => $this->getTransactionReference());
    }
    public function sendData($data)
    {
        $data['reference'] = $this->getTransactionReference();
        $data['success'] = \strpos($this->getTransactionReference(), 'fail') !== \false ? \false : \true;
        $data['message'] = $data['success'] ? 'Success' : 'Failure';
        return $this->response = new Response($this, $data);
    }
}
