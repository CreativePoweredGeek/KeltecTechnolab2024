<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Request\Model;

/**
 *
 */
use CartThrob\Dependency\Academe\AuthorizeNet\TransactionRequestInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\AmountInterface;
use CartThrob\Dependency\Academe\AuthorizeNet\AbstractModel;
class Setting extends AbstractModel
{
    protected $settingName;
    protected $settingValue;
    public function __construct($settingName, $settingValue)
    {
        parent::__construct();
        $this->setSettingName($settingName);
        $this->setSettingValue($settingValue);
    }
    public function jsonSerialize()
    {
        $data = [];
        $data['settingName'] = $this->getSettingName();
        $data['settingValue'] = $this->getSettingValue();
        return $data;
    }
    public function hasAny()
    {
        return \true;
    }
    protected function setSettingName($value)
    {
        $this->settingName = $value;
    }
    protected function setSettingValue($value)
    {
        $this->settingValue = $value;
    }
}
