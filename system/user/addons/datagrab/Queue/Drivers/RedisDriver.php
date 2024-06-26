<?php

namespace BoldMinded\DataGrab\Queue\Drivers;

use BoldMinded\DataGrab\Dependency\Illuminate\Queue\Capsule\Manager as QueueCapsuleManager;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\Connectors\RedisConnector;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\QueueManager;
use BoldMinded\DataGrab\Dependency\Illuminate\Redis\RedisManager;
use BoldMinded\DataGrab\Dependency\Illuminate\Support\Facades\App;
use ExpressionEngine\Core\Provider;

class RedisDriver implements QueueDriverInterface
{
    /**
     * @var array
     */
    private $config = [];

    /**
     * @var Provider
     */
    private $provider;

    /**
     * @param Provider $provider
     */
    public function __construct(Provider $provider, array $config = [])
    {
        $this->config = $config;
        $this->provider = $provider;
    }

    /**
     * @return QueueManager
     */
    public function getQueueManager(): QueueManager
    {
        $capsuleQueueManager = new QueueCapsuleManager;

        $capsuleQueueManager->addConnector('redis', function () {
            // Could be predis too, but might need additional dependencies
            $redisManager = new RedisManager(new App(), 'phpredis', $this->config);
            return new RedisConnector($redisManager);
        });

        $capsuleQueueManager->addConnection([
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 60 * 5,
            'block_for' => 5,
        ]);

        return $capsuleQueueManager->getQueueManager();
    }
}
