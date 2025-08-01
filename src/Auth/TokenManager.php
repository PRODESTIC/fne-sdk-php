<?php

namespace DgiCi\FneSdk\Auth;

use DgiCi\FneSdk\Exceptions\AuthenticationException;

class TokenManager
{
    private ?string $apiKey = null;
    private ?string $cachedToken = null;
    private ?int $tokenExpiry = null;
    private array $cache = [];

    public function __construct(?string $apiKey = null)
    {
        $this->apiKey = $apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
        $this->invalidateCache();
    }

    public function getApiKey(): string
    {
        if (!$this->apiKey) {
            throw AuthenticationException::invalidApiKey();
        }
        return $this->apiKey;
    }

    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    public function getBearerToken(): string
    {
        return 'Bearer ' . $this->getApiKey();
    }

    public function validateApiKey(): void
    {
        if (!$this->hasApiKey()) {
            throw AuthenticationException::invalidApiKey();
        }

        // Validation basique du format de la clé API
        if (strlen($this->apiKey) < 20) {
            throw AuthenticationException::invalidApiKey();
        }
    }

    public function cacheResponse(string $key, array $data, int $ttl = 3600): void
    {
        $this->cache[$key] = [
            'data' => $data,
            'expires_at' => time() + $ttl
        ];
    }

    public function getCachedResponse(string $key): ?array
    {
        if (!isset($this->cache[$key])) {
            return null;
        }

        $cached = $this->cache[$key];
        if (time() > $cached['expires_at']) {
            unset($this->cache[$key]);
            return null;
        }

        return $cached['data'];
    }

    public function invalidateCache(): void
    {
        $this->cache = [];
        $this->cachedToken = null;
        $this->tokenExpiry = null;
    }

    public function clearCache(string $key = null): void
    {
        if ($key) {
            unset($this->cache[$key]);
        } else {
            $this->invalidateCache();
        }
    }
}