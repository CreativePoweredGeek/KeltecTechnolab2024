<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Request\Transaction;

/**
 * Transaction used to capture a previously authorized transaction.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\TransactionRequestInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Model\Order;
use CartThrob\Dependency\Academe\AuthorizeNet\AmountInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractModel;
class PriorAuthCapture extends AbstractModel implements TransactionRequestInterface
{
    protected $objectName = 'transactionRequest';
    protected $transactionType = 'priorAuthCaptureTransaction';
    protected $amount;
    protected $terminalNumber;
    protected $order;
    protected $refTransId;
    /**
     *
     */
    public function __construct(AmountInterface $amount, $refTransId)
    {
        parent::__construct();
        $this->setAmount($amount);
        $this->setRefTransId($refTransId);
    }
    public function jsonSerialize()
    {
        $data = [];
        $data['transactionType'] = $this->getTransactionType();
        // This value object will be formatted according to its currency.
        $data['amount'] = $this->getAmount();
        if ($terminalNumber = $this->getTerminalNumber()) {
            $data['terminalNumber'] = $terminalNumber;
        }
        $data['refTransId'] = $this->getRefTransId();
        if ($this->hasOrder()) {
            $order = $this->getOrder();
            // The order needs at least one of the two optional fields.
            if ($order->hasAny()) {
                // If the order becames more complex, we may need to pick out the
                // individual fields we need.
                $data[$order->getObjectName()] = $order;
            }
        }
        return $data;
    }
    protected function setAmount(AmountInterface $value)
    {
        $this->amount = $value;
    }
    protected function setOrder(Order $value)
    {
        $this->order = $value;
    }
    /**
     * refTransId string
     */
    protected function setRefTransId($value)
    {
        $this->refTransId = $value;
    }
    protected function setTerminalNumber($value)
    {
        $this->terminalNumber = $value;
    }
}
