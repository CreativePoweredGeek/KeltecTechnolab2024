<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Response\Model;

/**
 * Single Response message.
 * This is the top level of the response, not a message you would find
 * within a transacton response.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Response\HasDataTrait;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractModel;
class PrePaidCard extends AbstractModel
{
    use HasDataTrait;
    protected $requestedAmount;
    protected $approvedAmount;
    protected $balanceOnCard;
    public function __construct($data)
    {
        $this->setData($data);
        $this->setRequestedAmount($this->getDataValue('requestedAmount'));
        $this->setApprovedAmount($this->getDataValue('approvedAmount'));
        $this->setBalanceOnCard($this->getDataValue('balanceOnCard'));
    }
    public function jsonSerialize()
    {
        $data = ['requestedAmount' => $this->getRequestedAmount(), 'approvedAmount' => $this->getApprovedAmount(), 'balanceOnCard' => $this->getBalanceOnCard()];
        return $data;
    }
    protected function setRequestedAmount($value)
    {
        $this->requestedAmount = $value;
    }
    protected function setApprovedAmount($value)
    {
        $this->approvedAmount = $value;
    }
    protected function setBalanceOnCard($value)
    {
        $this->balanceOnCard = $value;
    }
}
