<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Queue;

use InvalidArgumentException;
class InvalidPayloadException extends InvalidArgumentException
{
    /**
     * Create a new exception instance.
     *
     * @param  string|null  $message
     * @return void
     */
    public function __construct($message = null)
    {
        parent::__construct($message ?: \json_last_error());
    }
}
