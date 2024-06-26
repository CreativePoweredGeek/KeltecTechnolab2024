<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Console\Events;

use BoldMinded\DataGrab\Dependency\Illuminate\Console\Scheduling\Event;
class ScheduledTaskSkipped
{
    /**
     * The scheduled event being run.
     *
     * @var \Illuminate\Console\Scheduling\Event
     */
    public $task;
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Console\Scheduling\Event  $task
     * @return void
     */
    public function __construct(Event $task)
    {
        $this->task = $task;
    }
}
