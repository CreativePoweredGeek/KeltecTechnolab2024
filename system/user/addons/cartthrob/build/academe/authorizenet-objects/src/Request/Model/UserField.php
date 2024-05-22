<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Request\Model;

/**
 *
 */
use CartThrob\Dependency\Academe\AuthorizeNet\TransactionRequestInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\AmountInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractModel;
class UserField extends AbstractModel
{
    protected $name;
    protected $value;
    public function __construct($name, $value)
    {
        parent::__construct();
        $this->setName($name);
        $this->setValue($value);
    }
    public function jsonSerialize()
    {
        $data = [];
        $data['name'] = $this->getName();
        $data['value'] = $this->getValue();
        return $data;
    }
    public function hasAny()
    {
        return \true;
    }
    protected function setName($value)
    {
        $this->name = $value;
    }
    protected function setValue($value)
    {
        $this->value = $value;
    }
}
