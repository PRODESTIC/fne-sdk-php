<?php

namespace DgiCi\FneSdk\Http;

use DgiCi\FneSdk\Exceptions\NetworkException;
use DgiCi\FneSdk\Exceptions\ApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

class HttpClient
{
    private Client $client;
    private string $baseUrl;
    private ?string $apiKey = null;
    private int $timeout;
    private int $retryAttempts;

    public function __construct(
        string $baseUrl,
        ?string $apiKey = null,
        int $timeout = 30,
        int $retryAttempts = 3
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;

        $this->client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => 10,
            'verify' => true,
        ]);
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function post(string $endpoint, array $data = []): Response
    {
        return $this->makeRequest('POST', $endpoint, $data);
    }

    public function get(string $endpoint, array $params = []): Response
    {
        return $this->makeRequest('GET', $endpoint, [], $params);
    }

    private function makeRequest(string $method, string $endpoint, array $data = [], array $params = []): Response
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $options = $this->buildRequestOptions($data, $params);

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                $response = $this->client->request($method, $url, $options);
                return $this->createResponse($response);
            } catch (ConnectException $e) {
                $lastException = NetworkException::connectionFailed($e);
                if ($attempt === $this->retryAttempts) {
                    throw $lastException;
                }
                // Attendre avant de réessayer (backoff exponentiel)
                sleep(pow(2, $attempt - 1));
            } catch (RequestException $e) {
                if ($e->hasResponse()) {
                    $response = $this->createResponse($e->getResponse());
                    $this->handleErrorResponse($response);
                }
                throw NetworkException::connectionFailed($e);
            } catch (GuzzleException $e) {
                throw NetworkException::connectionFailed($e);
            }
        }

        throw $lastException;
    }

    private function buildRequestOptions(array $data = [], array $params = []): array
    {
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'User-Agent' => 'FNE-SDK-PHP/1.0',
            ]
        ];

        if ($this->apiKey) {
            $options['headers']['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        if (!empty($data)) {
            $options['json'] = $data;
        }

        if (!empty($params)) {
            $options['query'] = $params;
        }

        return $options;
    }

    private function createResponse(ResponseInterface $guzzleResponse): Response
    {
        return new Response(
            $guzzleResponse->getStatusCode(),
            $this->parseHeaders($guzzleResponse->getHeaders()),
            $guzzleResponse->getBody()->getContents()
        );
    }

    private function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $name => $values) {
            $parsed[$name] = implode(', ', $values);
        }
        return $parsed;
    }

    private function handleErrorResponse(Response $response): void
    {
        $statusCode = $response->getStatusCode();
        $json = $response->json();

        if ($json && isset($json['message'])) {
            switch ($statusCode) {
                case 400:
                    throw ApiException::badRequest($json['message'], $json);
                case 401:
                    throw ApiException::fromResponse($json, $statusCode);
                case 500:
                    throw ApiException::internalServerError($json['message']);
                default:
                    throw ApiException::fromResponse($json, $statusCode);
            }
        }

        // Fallback pour les réponses sans JSON
        switch ($statusCode) {
            case 400:
                throw ApiException::badRequest('Requête invalide');
            case 401:
                throw ApiException::fromResponse(['message' => 'Non autorisé'], $statusCode);
            case 500:
                throw ApiException::internalServerError();
            default:
                throw ApiException::fromResponse(['message' => 'Erreur API'], $statusCode);
        }
    }
}