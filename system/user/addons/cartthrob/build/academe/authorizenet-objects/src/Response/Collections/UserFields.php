<?php

namespace CartThrob\Dependency\Academe\AuthorizeNet\Response\Collections;

/**
 * Collection of response UserFields.
 */
use CartThrob\Dependency\Academe\AuthorizeNet\Response\HasDataTrait;
use CartThrob\Dependency\Academe\AuthorizeNet\Request\Collections\UserFields as RequestUserFields;
use CartThrob\Dependency\Academe\AuthorizeNet\Response\Model\UserField;
class UserFields extends RequestUserFields
{
    use HasDataTrait;
    public function __construct(array $data = [])
    {
        $this->setData($data);
        // An array of userField records.
        foreach ($data as $userField_data) {
            $this->push(new UserField($userField_data));
        }
    }
    protected function hasExpectedStrictType($item)
    {
        // Make sure the item is the correct type, and is not empty.
        return $item instanceof UserField && $item->hasAny();
    }
}
