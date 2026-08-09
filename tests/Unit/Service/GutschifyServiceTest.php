<?php

namespace Gutschify\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Gutschify\Service\GutschifyService;
use Gutschify\Exception\GutschifyException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Psr\SimpleCache\CacheInterface;

class GutschifyServiceTest extends TestCase
{
    /**
     * @var Client|MockObject
     */
    private $httpClient;

    /**
     * @var CacheInterface|MockObject
     */
    private $cache;

    /**
     * @var GutschifyService
     */
    private $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->httpClient = $this->createMock(Client::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->service = new GutschifyService($this->httpClient, $this->cache);
    }

    public function testFetchEmbeddedHomeSuccess(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $collectionSlug = 'default';
        $expectedContent = '<div>Gutschify Content</div>';

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn($expectedContent);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->stringContains('/embedded-home/'),
                $this->callback(function ($options) {
                    return isset($options['timeout']) && 
                           isset($options['connect_timeout']) &&
                           isset($options['headers']['User-Agent']);
                })
            )
            ->willReturn($response);

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->cache
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains(GutschifyService::CACHE_PREFIX),
                $expectedContent,
                3600
            );

        $result = $this->service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            $collectionSlug,
            true,
            3600
        );

        $this->assertEquals($expectedContent, $result);
    }

    public function testFetchEmbeddedHomeWithCache(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $cachedContent = '<div>Cached Content</div>';

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturn($cachedContent);

        $this->httpClient
            ->expects($this->never())
            ->method('get');

        $result = $this->service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            'default',
            true,
            3600
        );

        $this->assertEquals($cachedContent, $result);
    }

    public function testFetchEmbeddedHomeCacheDisabled(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedContent = '<div>Content</div>';

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn($expectedContent);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->cache
            ->expects($this->never())
            ->method('get');

        $this->cache
            ->expects($this->never())
            ->method('set');

        $result = $this->service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            'default',
            false,
            3600
        );

        $this->assertEquals($expectedContent, $result);
    }

    public function testFetchEmbeddedHomeUrlConstruction(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $collectionSlug = 'test-collection';
        $expectedUrl = $baseUrl . '/embedded-home/?organization_id=' . urlencode($organizationId) . '&collection=' . urlencode($collectionSlug);

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn('<div>Content</div>');

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->stringContains('/embedded-home/'),
                $this->anything()
            )
            ->willReturn($response);

        $this->cache->method('get')->willReturn(null);
        $this->cache->method('set')->willReturn(true);

        $this->service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            $collectionSlug,
            true,
            3600
        );
    }

    public function testFetchEmbeddedHomeHttpError(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';

        $response = $this->createMock(Response::class);
        $response->method('getStatusCode')->willReturn(404);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->cache->method('get')->willReturn(null);

        $this->expectException(GutschifyException::class);
        $this->expectExceptionMessage('HTTP error: 404');

        $this->service->fetchEmbeddedHome($baseUrl, $organizationId);
    }

    public function testFetchEmbeddedHomeRequestException(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';

        $exception = new RequestException(
            'Connection failed',
            $this->createMock(\Psr\Http\Message\RequestInterface::class)
        );

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willThrowException($exception);

        $this->cache->method('get')->willReturn(null);

        $this->expectException(GutschifyException::class);
        $this->expectExceptionMessage('Failed to fetch content');

        $this->service->fetchEmbeddedHome($baseUrl, $organizationId);
    }

    public function testFetchEmbeddedHomeEmptyBaseUrl(): void
    {
        $this->expectException(GutschifyException::class);
        $this->expectExceptionMessage('Base URL is required');

        $this->service->fetchEmbeddedHome('', '123e4567-e89b-12d3-a456-426614174000');
    }

    public function testFetchEmbeddedHomeEmptyOrganizationId(): void
    {
        $this->expectException(GutschifyException::class);
        $this->expectExceptionMessage('Organization ID is required');

        $this->service->fetchEmbeddedHome('https://gutschify.xxiii.tools', '');
    }

    public function testFetchEmbeddedHomeDefaultCollectionSlug(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn('<div>Content</div>');

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->with(
                $this->stringContains('collection=default'),
                $this->anything()
            )
            ->willReturn($response);

        $this->cache->method('get')->willReturn(null);
        $this->cache->method('set')->willReturn(true);

        $this->service->fetchEmbeddedHome($baseUrl, $organizationId);
    }

    public function testFetchEmbeddedHomeCustomCacheTtl(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $customTtl = 7200;
        $expectedContent = '<div>Content</div>';

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn($expectedContent);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $this->cache->method('get')->willReturn(null);

        $this->cache
            ->expects($this->once())
            ->method('set')
            ->with(
                $this->anything(),
                $expectedContent,
                $customTtl
            );

        $this->service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            'default',
            true,
            $customTtl
        );
    }

    public function testFetchEmbeddedHomeWithoutCacheService(): void
    {
        $baseUrl = 'https://gutschify.xxiii.tools';
        $organizationId = '123e4567-e89b-12d3-a456-426614174000';
        $expectedContent = '<div>Content</div>';

        // Create service without cache
        $service = new GutschifyService($this->httpClient, null);

        $response = $this->createMock(Response::class);
        $responseBody = $this->createMock(\Psr\Http\Message\StreamInterface::class);
        
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getBody')->willReturn($responseBody);
        $responseBody->method('getContents')->willReturn($expectedContent);

        $this->httpClient
            ->expects($this->once())
            ->method('get')
            ->willReturn($response);

        $result = $service->fetchEmbeddedHome(
            $baseUrl,
            $organizationId,
            'default',
            true, // Cache enabled but no cache service
            3600
        );

        $this->assertEquals($expectedContent, $result);
    }
}
