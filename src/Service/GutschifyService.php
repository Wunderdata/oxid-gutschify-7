<?php

namespace Gutschify\Service;

use Gutschify\Exception\GutschifyException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;

class GutschifyService
{
    public const CACHE_PREFIX = 'gutschify_content_';

    private Client $httpClient;
    private ?CacheInterface $cache;

    public function __construct(Client $httpClient, ?CacheInterface $cache = null)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
    }

    /**
     * @throws GutschifyException on missing input or a failed request.
     */
    public function fetchEmbeddedHome(
        string $baseUrl,
        string $organizationId,
        string $collectionSlug = 'default',
        bool $cacheEnabled = true,
        int $cacheTtl = 3600
    ): string {
        if ($baseUrl === '') {
            throw new GutschifyException('Base URL is required');
        }
        if ($organizationId === '') {
            throw new GutschifyException('Organization ID is required');
        }

        $cacheKey = self::CACHE_PREFIX . md5($baseUrl . '|' . $organizationId . '|' . $collectionSlug);

        if ($cacheEnabled && $this->cache) {
            $cached = $this->cache->get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        $url = rtrim($baseUrl, '/') . '/embedded-home/'
            . '?organization_id=' . urlencode($organizationId)
            . '&collection=' . urlencode($collectionSlug);

        try {
            $response = $this->httpClient->get($url, [
                'timeout' => 10,
                'connect_timeout' => 5,
                'headers' => ['User-Agent' => 'OXID-Gutschify-Module/1.0'],
            ]);
        } catch (GuzzleException $e) {
            throw new GutschifyException('Failed to fetch content: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new GutschifyException('HTTP error: ' . $response->getStatusCode());
        }

        $content = $response->getBody()->getContents();

        if ($cacheEnabled && $this->cache) {
            $this->cache->set($cacheKey, $content, $cacheTtl);
        }

        return $content;
    }
}
