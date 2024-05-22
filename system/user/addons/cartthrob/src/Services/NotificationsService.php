<?php

namespace CartThrob\Services;

use CartThrob\Plugins\Notification\NotificationPlugin;

class NotificationsService
{
    /**
     * @var array
     */
    protected array $plugins = [];

    /**
     * @var array
     */
    protected array $data = [];

    /**
     * @param string $event
     * @param string|null $statusStart
     * @param string|null $statusEnd
     * @return array
     */
    public function getNotificationsForEvent(string $event, ?string $statusStart = null, ?string $statusEnd = null): array
    {
        $return = [];
        if ($this->hasNotifications()) {
            foreach (ee()->config->item('cartthrob:notifications') as $key => $notification) {
                $_event = $notification['event'] ?? null;
                $notification['id'] = $key;
                if ($_event === $event && $event != 'status_change') {
                    $return[] = $this->buildNotification($notification);
                } elseif ($this->statusChangeNeedsNotification($statusStart, $statusEnd, $notification) &&
                    $event == 'status_change') {
                    $return[] = $this->buildNotification($notification);
                }
            }
        }

        return $return;
    }

    /**
     * @param array $notification
     * @return NotificationPlugin|null
     */
    protected function buildNotification(array $notification): ?NotificationPlugin
    {
        $plugins = $this->plugins();
        foreach ($plugins as $plugin) {
            $type = $notification['type'] ?? null;
            if ($type === $plugin->getType()) {
                $plugin->setData($notification);

                return clone $plugin;
            }
        }

        return null;
    }

    /**
     * @param string $event
     * @param array $data
     * @return bool
     */
    public function dispatch(string $event, array $data = []): bool
    {
        $notifications = $this->getNotificationsForEvent($event);
        $count = 0;
        foreach ($notifications as $notification) {
            if ($notification instanceof NotificationPlugin) {
                if ($notification->send($data)) {
                    $count++;
                }
            }
        }

        return $count >= 1;
    }

    /**
     * @return bool
     */
    public function hasNotifications(): bool
    {
        return ee()->config->item('cartthrob:notifications') !== [];
    }

    /**
     * @return array
     */
    public function getAllPlugins(): array
    {
        return $this->plugins();
    }

    /**
     * @return array
     */
    private function plugins(): array
    {
        if (!$this->plugins) {
            $this->plugins = [];
            $path = CARTTHROB_NOTIFICATION_PLUGIN_PATH;
            if (!is_dir($path)) {
                return [];
            }

            foreach (get_filenames($path, true) as $file) {
                $class = basename($file, '.php');
                if (!class_exists($class)) {
                    include_once $file;
                }

                ee('cartthrob:PluginService')->register($class);
            }

            $plugins = ee('cartthrob:PluginService')->getByType(PluginService::TYPE_NOTIFICATION);
            if ($plugins) {
                $this->plugins = $plugins->toArray();
            }
        }

        return $this->plugins;
    }

    /**
     * @param string $type
     * @return NotificationPlugin|null
     */
    public function getPluginByType(string $type): ?NotificationPlugin
    {
        foreach ($this->plugins() as $key => $plugin) {
            if ($plugin->getType() == $type) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * Check for a order status change that has requested a notification
     *
     * @param string $statusStart
     * @param string $statusEnd
     * @param array $notification
     * @return bool
     */
    private function statusChangeNeedsNotification($statusStart, $statusEnd, $notification): bool
    {
        return $notification['event'] == 'status_change'
            && $statusStart && $statusEnd
            && (isset($notification['starting_status']) && in_array($notification['starting_status'], ['ANY', $statusStart])
                || isset($notification['ending_status']) && in_array($notification['ending_status'], ['ANY', $statusEnd]))
            && $statusStart !== $statusEnd;
    }
}
