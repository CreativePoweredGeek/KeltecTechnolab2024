<?php

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace BoldMinded\DataGrab\Dependency\Carbon\Exceptions;

use Exception;
class UnitNotConfiguredException extends UnitException
{
    /**
     * Constructor.
     *
     * @param string         $unit
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($unit, $code = 0, Exception $previous = null)
    {
        parent::__construct("Unit {$unit} have no configuration to get total from other units.", $code, $previous);
    }
}