<?php

namespace CartThrob\Dependency\Omnipay\Common\Http;

use function CartThrob\Dependency\GuzzleHttp\Psr7\str;
use CartThrob\Dependency\Http\Client\HttpClient;
use CartThrob\Dependency\Http\Discovery\HttpClientDiscovery;
use CartThrob\Dependency\Http\Discovery\MessageFactoryDiscovery;
use CartThrob\Dependency\Http\Message\RequestFactory;
use CartThrob\Dependency\Omnipay\Common\Http\Exception\NetworkException;
use CartThrob\Dependency\Omnipay\Common\Http\Exception\RequestException;
use CartThrob\Dependency\Psr\Http\Message\RequestInterface;
use CartThrob\Dependency\Psr\Http\Message\ResponseInterface;
use CartThrob\Dependency\Psr\Http\Message\StreamInterface;
use CartThrob\Dependency\Psr\Http\Message\UriInterface;
class Client implements ClientInterface
{
    /**
     * The Http Client which implements `public function sendRequest(RequestInterface $request)`
     * Note: Will be changed to PSR-18 when released
     *
     * @var HttpClient
     */
    private $httpClient;
    /**
     * @var RequestFactory
     */
    private $requestFactory;
    public function __construct($httpClient = null, RequestFactory $requestFactory = null)
    {
        $this->httpClient = $httpClient ?: HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?: MessageFactoryDiscovery::find();
    }
    /**
     * @param $method
     * @param $uri
     * @param array $headers
     * @param string|array|resource|StreamInterface|null $body
     * @param string $protocolVersion
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function request($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $request = $this->requestFactory->createRequest($method, $uri, $headers, $body, $protocolVersion);
        return $this->sendRequest($request);
    }
    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    private function sendRequest(RequestInterface $request)
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (\CartThrob\Dependency\Http\Client\Exception\NetworkException $networkException) {
            throw new NetworkException($networkException->getMessage(), $request, $networkException);
        } catch (\Exception $exception) {
            throw new RequestException($exception->getMessage(), $request, $exception);
        }
    }
}
