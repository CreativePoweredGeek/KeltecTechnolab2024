<?php

declare (strict_types=1);
namespace CartThrob\Dependency\Http\Adapter\Guzzle7;

use CartThrob\Dependency\GuzzleHttp\Client as GuzzleClient;
use CartThrob\Dependency\GuzzleHttp\ClientInterface;
use CartThrob\Dependency\GuzzleHttp\HandlerStack;
use CartThrob\Dependency\GuzzleHttp\Middleware;
use CartThrob\Dependency\GuzzleHttp\Utils;
use CartThrob\Dependency\Http\Client\HttpAsyncClient;
use CartThrob\Dependency\Http\Client\HttpClient;
use CartThrob\Dependency\Psr\Http\Message\RequestInterface;
use CartThrob\Dependency\Psr\Http\Message\ResponseInterface;
/**
 * HTTP Adapter for Guzzle 7.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Client implements HttpClient, HttpAsyncClient
{
    /**
     * @var ClientInterface
     */
    private $guzzle;
    public function __construct(?ClientInterface $guzzle = null)
    {
        if (!$guzzle) {
            $guzzle = self::buildClient();
        }
        $this->guzzle = $guzzle;
    }
    /**
     * Factory method to create the Guzzle 7 adapter with custom Guzzle configuration.
     */
    public static function createWithConfig(array $config) : Client
    {
        return new self(self::buildClient($config));
    }
    /**
     * {@inheritdoc}
     */
    public function sendRequest(RequestInterface $request) : ResponseInterface
    {
        return $this->sendAsyncRequest($request)->wait();
    }
    /**
     * {@inheritdoc}
     */
    public function sendAsyncRequest(RequestInterface $request)
    {
        $promise = $this->guzzle->sendAsync($request);
        return new Promise($promise, $request);
    }
    /**
     * Build the Guzzle client instance.
     */
    private static function buildClient(array $config = []) : GuzzleClient
    {
        $handlerStack = new HandlerStack(Utils::chooseHandler());
        $handlerStack->push(Middleware::prepareBody(), 'prepare_body');
        $config = \array_merge(['handler' => $handlerStack], $config);
        return new GuzzleClient($config);
    }
}
