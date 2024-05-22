<?php

namespace CartThrob\Services;

class EmailService
{
    /**
     * Dispatches emails to the CartThrob email system
     * @param string $event_name
     * @param array $variables
     * @return void
     */
    public function dispatchEvent(string $event_name, array $variables = []): void
    {
        ee('cartthrob:NotificationsService')->dispatch($event_name, $variables);
    }
}
