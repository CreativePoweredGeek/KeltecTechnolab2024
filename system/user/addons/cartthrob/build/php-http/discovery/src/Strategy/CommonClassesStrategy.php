<?php

namespace CartThrob\Dependency\Http\Discovery\Strategy;

use CartThrob\Dependency\GuzzleHttp\Client as GuzzleHttp;
use CartThrob\Dependency\GuzzleHttp\Promise\Promise;
use CartThrob\Dependency\GuzzleHttp\Psr7\Request as GuzzleRequest;
use CartThrob\Dependency\Http\Adapter\Artax\Client as Artax;
use CartThrob\Dependency\Http\Adapter\Buzz\Client as Buzz;
use CartThrob\Dependency\Http\Adapter\Cake\Client as Cake;
use CartThrob\Dependency\Http\Adapter\Guzzle5\Client as Guzzle5;
use CartThrob\Dependency\Http\Adapter\Guzzle6\Client as Guzzle6;
use CartThrob\Dependency\Http\Adapter\Guzzle7\Client as Guzzle7;
use CartThrob\Dependency\Http\Adapter\React\Client as React;
use CartThrob\Dependency\Http\Adapter\Zend\Client as Zend;
use CartThrob\Dependency\Http\Client\Curl\Client as Curl;
use CartThrob\Dependency\Http\Client\HttpAsyncClient;
use CartThrob\Dependency\Http\Client\HttpClient;
use CartThrob\Dependency\Http\Client\Socket\Client as Socket;
use CartThrob\Dependency\Http\Discovery\ClassDiscovery;
use CartThrob\Dependency\Http\Discovery\Exception\NotFoundException;
use CartThrob\Dependency\Http\Discovery\MessageFactoryDiscovery;
use CartThrob\Dependency\Http\Discovery\Psr17FactoryDiscovery;
use CartThrob\Dependency\Http\Message\MessageFactory;
use CartThrob\Dependency\Http\Message\MessageFactory\DiactorosMessageFactory;
use CartThrob\Dependency\Http\Message\MessageFactory\GuzzleMessageFactory;
use CartThrob\Dependency\Http\Message\MessageFactory\SlimMessageFactory;
use CartThrob\Dependency\Http\Message\RequestFactory;
use CartThrob\Dependency\Http\Message\StreamFactory;
use CartThrob\Dependency\Http\Message\StreamFactory\DiactorosStreamFactory;
use CartThrob\Dependency\Http\Message\StreamFactory\GuzzleStreamFactory;
use CartThrob\Dependency\Http\Message\StreamFactory\SlimStreamFactory;
use CartThrob\Dependency\Http\Message\UriFactory;
use CartThrob\Dependency\Http\Message\UriFactory\DiactorosUriFactory;
use CartThrob\Dependency\Http\Message\UriFactory\GuzzleUriFactory;
use CartThrob\Dependency\Http\Message\UriFactory\SlimUriFactory;
use CartThrob\Dependency\Laminas\Diactoros\Request as DiactorosRequest;
use CartThrob\Dependency\Nyholm\Psr7\Factory\HttplugFactory as NyholmHttplugFactory;
use CartThrob\Dependency\Psr\Http\Client\ClientInterface as Psr18Client;
use CartThrob\Dependency\Psr\Http\Message\RequestFactoryInterface as Psr17RequestFactory;
use CartThrob\Dependency\Slim\Http\Request as SlimRequest;
use CartThrob\Dependency\Symfony\Component\HttpClient\HttplugClient as SymfonyHttplug;
use CartThrob\Dependency\Symfony\Component\HttpClient\Psr18Client as SymfonyPsr18;
use CartThrob\Dependency\Zend\Diactoros\Request as ZendDiactorosRequest;
/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class CommonClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [MessageFactory::class => [['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]], ['class' => GuzzleMessageFactory::class, 'condition' => [GuzzleRequest::class, GuzzleMessageFactory::class]], ['class' => DiactorosMessageFactory::class, 'condition' => [ZendDiactorosRequest::class, DiactorosMessageFactory::class]], ['class' => DiactorosMessageFactory::class, 'condition' => [DiactorosRequest::class, DiactorosMessageFactory::class]], ['class' => SlimMessageFactory::class, 'condition' => [SlimRequest::class, SlimMessageFactory::class]]], StreamFactory::class => [['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]], ['class' => GuzzleStreamFactory::class, 'condition' => [GuzzleRequest::class, GuzzleStreamFactory::class]], ['class' => DiactorosStreamFactory::class, 'condition' => [ZendDiactorosRequest::class, DiactorosStreamFactory::class]], ['class' => DiactorosStreamFactory::class, 'condition' => [DiactorosRequest::class, DiactorosStreamFactory::class]], ['class' => SlimStreamFactory::class, 'condition' => [SlimRequest::class, SlimStreamFactory::class]]], UriFactory::class => [['class' => NyholmHttplugFactory::class, 'condition' => [NyholmHttplugFactory::class]], ['class' => GuzzleUriFactory::class, 'condition' => [GuzzleRequest::class, GuzzleUriFactory::class]], ['class' => DiactorosUriFactory::class, 'condition' => [ZendDiactorosRequest::class, DiactorosUriFactory::class]], ['class' => DiactorosUriFactory::class, 'condition' => [DiactorosRequest::class, DiactorosUriFactory::class]], ['class' => SlimUriFactory::class, 'condition' => [SlimRequest::class, SlimUriFactory::class]]], HttpAsyncClient::class => [['class' => SymfonyHttplug::class, 'condition' => [SymfonyHttplug::class, Promise::class, RequestFactory::class, [self::class, 'isPsr17FactoryInstalled']]], ['class' => Guzzle7::class, 'condition' => Guzzle7::class], ['class' => Guzzle6::class, 'condition' => Guzzle6::class], ['class' => Curl::class, 'condition' => Curl::class], ['class' => React::class, 'condition' => React::class]], HttpClient::class => [['class' => SymfonyHttplug::class, 'condition' => [SymfonyHttplug::class, RequestFactory::class, [self::class, 'isPsr17FactoryInstalled']]], ['class' => Guzzle7::class, 'condition' => Guzzle7::class], ['class' => Guzzle6::class, 'condition' => Guzzle6::class], ['class' => Guzzle5::class, 'condition' => Guzzle5::class], ['class' => Curl::class, 'condition' => Curl::class], ['class' => Socket::class, 'condition' => Socket::class], ['class' => Buzz::class, 'condition' => Buzz::class], ['class' => React::class, 'condition' => React::class], ['class' => Cake::class, 'condition' => Cake::class], ['class' => Zend::class, 'condition' => Zend::class], ['class' => Artax::class, 'condition' => Artax::class], ['class' => [self::class, 'buzzInstantiate'], 'condition' => [\CartThrob\Dependency\Buzz\Client\FileGetContents::class, \CartThrob\Dependency\Buzz\Message\ResponseBuilder::class]]], Psr18Client::class => [['class' => [self::class, 'symfonyPsr18Instantiate'], 'condition' => [SymfonyPsr18::class, Psr17RequestFactory::class]], ['class' => GuzzleHttp::class, 'condition' => [self::class, 'isGuzzleImplementingPsr18']], ['class' => [self::class, 'buzzInstantiate'], 'condition' => [\CartThrob\Dependency\Buzz\Client\FileGetContents::class, \CartThrob\Dependency\Buzz\Message\ResponseBuilder::class]]]];
    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (Psr18Client::class === $type) {
            return self::getPsr18Candidates();
        }
        return self::$classes[$type] ?? [];
    }
    /**
     * @return array The return value is always an array with zero or more elements. Each
     *               element is an array with two keys ['class' => string, 'condition' => mixed].
     */
    private static function getPsr18Candidates()
    {
        $candidates = self::$classes[Psr18Client::class];
        // HTTPlug 2.0 clients implements PSR18Client too.
        foreach (self::$classes[HttpClient::class] as $c) {
            if (!\is_string($c['class'])) {
                continue;
            }
            try {
                if (ClassDiscovery::safeClassExists($c['class']) && \is_subclass_of($c['class'], Psr18Client::class)) {
                    $candidates[] = $c;
                }
            } catch (\Throwable $e) {
                \trigger_error(\sprintf('Got exception "%s (%s)" while checking if a PSR-18 Client is available', \get_class($e), $e->getMessage()), \E_USER_WARNING);
            }
        }
        return $candidates;
    }
    public static function buzzInstantiate()
    {
        return new \CartThrob\Dependency\Buzz\Client\FileGetContents(MessageFactoryDiscovery::find());
    }
    public static function symfonyPsr18Instantiate()
    {
        return new SymfonyPsr18(null, Psr17FactoryDiscovery::findResponseFactory(), Psr17FactoryDiscovery::findStreamFactory());
    }
    public static function isGuzzleImplementingPsr18()
    {
        return \defined('CartThrob\\Dependency\\GuzzleHttp\\ClientInterface::MAJOR_VERSION');
    }
    /**
     * Can be used as a condition.
     *
     * @return bool
     */
    public static function isPsr17FactoryInstalled()
    {
        try {
            Psr17FactoryDiscovery::findResponseFactory();
        } catch (NotFoundException $e) {
            return \false;
        } catch (\Throwable $e) {
            \trigger_error(\sprintf('Got exception "%s (%s)" while checking if a PSR-17 ResponseFactory is available', \get_class($e), $e->getMessage()), \E_USER_WARNING);
            return \false;
        }
        return \true;
    }
}
