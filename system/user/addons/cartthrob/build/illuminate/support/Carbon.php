<?php

namespace CartThrob\Dependency\Illuminate\Support;

use CartThrob\Dependency\Carbon\Carbon as BaseCarbon;
use CartThrob\Dependency\Carbon\CarbonImmutable as BaseCarbonImmutable;
use CartThrob\Dependency\Illuminate\Support\Traits\Conditionable;
class Carbon extends BaseCarbon
{
    use Conditionable;
    /**
     * {@inheritdoc}
     */
    public static function setTestNow($testNow = null)
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
}
