<?php

/**
 * Thanks to https://github.com/flaushi for his suggestion:
 * https://github.com/doctrine/dbal/issues/2873#issuecomment-534956358
 */
namespace BoldMinded\DataGrab\Dependency\Carbon\Doctrine;

use BoldMinded\DataGrab\Dependency\Doctrine\DBAL\Platforms\AbstractPlatform;
class CarbonType extends DateTimeType implements CarbonDoctrineType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'carbon';
    }
    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return \true;
    }
}
