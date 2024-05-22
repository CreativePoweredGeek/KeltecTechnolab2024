<?php

namespace BoldMinded\DataGrab\Queue\Drivers;

use BoldMinded\DataGrab\Dependency\Illuminate\Queue\Capsule\Manager as QueueCapsuleManager;
use BoldMinded\DataGrab\Dependency\Illuminate\Queue\QueueManager;
use BoldMinded\DataGrab\Queue\Connectors\SqsFifoConnector;
use ExpressionEngine\Core\Provider;

class SQSDriver implements QueueDriverInterface
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

        $capsuleQueueManager->addConnector('sqs', function () {
            return new SqsFifoConnector();
        });

        $capsuleQueueManager->addConnection([
            'after_commit' => false,
            'driver' => 'sqs',
            'key'    => $this->config['key'] ?? '',
            'secret' => $this->config['secret'] ?? '',
            'region' => $this->config['region'] ?? '',
            'queue'  => 'default',
            'prefix'  => $this->config['prefix'] ?? '',
        ]);

        return $capsuleQueueManager->getQueueManager();
    }
}