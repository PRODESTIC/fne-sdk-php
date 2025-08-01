<?php

namespace DgiCi\FneSdk\Http;

class Response
{
    private int $statusCode;
    private array $headers;
    private string $body;
    private ?array $json = null;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function json(): ?array
    {
        if ($this->json === null) {
            $decoded = json_decode($this->body, true);
            $this->json = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        return $this->json;
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }
}