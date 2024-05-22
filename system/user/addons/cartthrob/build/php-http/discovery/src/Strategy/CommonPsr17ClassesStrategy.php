<?php

namespace CartThrob\Dependency\Http\Discovery\Strategy;

use CartThrob\Dependency\Psr\Http\Message\RequestFactoryInterface;
use CartThrob\Dependency\Psr\Http\Message\ResponseFactoryInterface;
use CartThrob\Dependency\Psr\Http\Message\ServerRequestFactoryInterface;
use CartThrob\Dependency\Psr\Http\Message\StreamFactoryInterface;
use CartThrob\Dependency\Psr\Http\Message\UploadedFileFactoryInterface;
use CartThrob\Dependency\Psr\Http\Message\UriFactoryInterface;
/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [RequestFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\RequestFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\RequestFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\RequestFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\RequestFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\RequestFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\RequestFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\RequestFactory'], ResponseFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\ResponseFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\ResponseFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\ResponseFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\ResponseFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\ResponseFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\ResponseFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\ResponseFactory'], ServerRequestFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\ServerRequestFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\ServerRequestFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\ServerRequestFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\ServerRequestFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\ServerRequestFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\ServerRequestFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\ServerRequestFactory'], StreamFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\StreamFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\StreamFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\StreamFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\StreamFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\StreamFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\StreamFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\StreamFactory'], UploadedFileFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\UploadedFileFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\UploadedFileFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\UploadedFileFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\UploadedFileFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\UploadedFileFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\UploadedFileFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\UploadedFileFactory'], UriFactoryInterface::class => ['CartThrob\\Dependency\\Phalcon\\Http\\Message\\UriFactory', 'CartThrob\\Dependency\\Nyholm\\Psr7\\Factory\\Psr17Factory', 'CartThrob\\Dependency\\Zend\\Diactoros\\UriFactory', 'CartThrob\\Dependency\\GuzzleHttp\\Psr7\\HttpFactory', 'CartThrob\\Dependency\\Http\\Factory\\Diactoros\\UriFactory', 'CartThrob\\Dependency\\Http\\Factory\\Guzzle\\UriFactory', 'CartThrob\\Dependency\\Http\\Factory\\Slim\\UriFactory', 'CartThrob\\Dependency\\Laminas\\Diactoros\\UriFactory', 'CartThrob\\Dependency\\Slim\\Psr7\\Factory\\UriFactory']];
    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }
        return $candidates;
    }
}
