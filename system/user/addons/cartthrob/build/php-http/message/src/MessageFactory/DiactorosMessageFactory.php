<?php

namespace CartThrob\Dependency\Http\Message\MessageFactory;

use CartThrob\Dependency\Http\Message\MessageFactory;
use CartThrob\Dependency\Http\Message\StreamFactory\DiactorosStreamFactory;
use CartThrob\Dependency\Laminas\Diactoros\Request as LaminasRequest;
use CartThrob\Dependency\Laminas\Diactoros\Response as LaminasResponse;
use CartThrob\Dependency\Zend\Diactoros\Request as ZendRequest;
use CartThrob\Dependency\Zend\Diactoros\Response as ZendResponse;
/**
 * Creates Diactoros messages.
 *
 * @author GeLo <geloen.eric@gmail.com>
 *
 * @deprecated This will be removed in php-http/message2.0. Consider using the official Diactoros PSR-17 factory
 */
final class DiactorosMessageFactory implements MessageFactory
{
    /**
     * @var DiactorosStreamFactory
     */
    private $streamFactory;
    public function __construct()
    {
        $this->streamFactory = new DiactorosStreamFactory();
    }
    /**
     * {@inheritdoc}
     */
    public function createRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        if (\class_exists(LaminasRequest::class)) {
            return (new LaminasRequest($uri, $method, $this->streamFactory->createStream($body), $headers))->withProtocolVersion($protocolVersion);
        }
        return (new ZendRequest($uri, $method, $this->streamFactory->createStream($body), $headers))->withProtocolVersion($protocolVersion);
    }
    /**
     * {@inheritdoc}
     */
    public function createResponse($statusCode = 200, $reasonPhrase = null, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        if (\class_exists(LaminasResponse::class)) {
            return (new LaminasResponse($this->streamFactory->createStream($body), $statusCode, $headers))->withProtocolVersion($protocolVersion);
        }
        return (new ZendResponse($this->streamFactory->createStream($body), $statusCode, $headers))->withProtocolVersion($protocolVersion);
    }
}
