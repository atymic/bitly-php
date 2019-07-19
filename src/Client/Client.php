<?php
declare(strict_types=1);

namespace Atymic\Bitly\Client;

use Atymic\Bitly\Api\Bitlinks;
use Atymic\Bitly\Client\Credentials\CredentialsInterface;
use Atymic\Bitly\Exception\AuthenticationException;
use Atymic\Bitly\Exception\BadRequestException;
use Atymic\Bitly\Exception\InvalidResponseException;
use Atymic\Bitly\Exception\NotFoundException;
use Atymic\Bitly\Exception\RequestException;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

class Client
{
    const API_BASE_URL = 'https://api-ssl.bitly.com/v4';

    /** @var HttpClient */
    protected $httpClient;

    /** @var CredentialsInterface */
    protected $credentials;

    /** @var string */
    protected $accessToken;

    /** @var array */
    protected $options;

    /** @var string */
    protected $apiBaseUrl;

    /**
     * @param HttpClient           $httpClient
     * @param CredentialsInterface $credentials
     * @param array                $options
     */
    public function __construct(
        HttpClient $httpClient,
        CredentialsInterface $credentials,
        array $options = []
    ) {
        $this->httpClient = $httpClient;
        $this->credentials = $credentials;
        $this->options = $options;

        $this->accessToken = $credentials->getToken();

        $this->apiBaseUrl = $options['api_base_url'] ?? static::API_BASE_URL;
    }

    public static function create(CredentialsInterface $credentials, array $options = [])
    {
        return new self(
            new HttpClient(),
            $credentials,
            $options
        );
    }

    public function getRequest(string $endpoint, array $queryParams = []): array
    {
        try {
            $response = $this->httpClient->get($this->buildUrl($endpoint), [
                RequestOptions::QUERY => $queryParams,
            ]);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->parseResponse($response);
    }

    public function postRequest(string $endpoint, array $postData): array
    {
        try {
            $response = $this->httpClient->post($this->buildUrl($endpoint), [
                RequestOptions::JSON => $postData,
            ]);
        } catch (ClientException $e) {
            $this->handleClientException($e);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode(), $e);
        }

        return $this->parseResponse($response);
    }

    protected function handleClientException(ClientException $e)
    {
        if ($e->getResponse() === null) {
            throw new RequestException($e->getMessage(), $e->getCode());
        }

        $requestUrl = $e->getRequest()->getUri();
        $responseBody = json_decode((string) $e->getResponse()->getBody(), true);

        switch ($e->getResponse()->getStatusCode()) {
            case 400:
                throw new BadRequestException(sprintf('Bad Request: %s', $responseBody['message'] ?? ''), 400);
            case 403:
                throw new AuthenticationException(sprintf('Forbidden: %s', $responseBody['message'] ?? ''), 403);
            case 404:
                throw new NotFoundException(sprintf('Not Found: %s', $requestUrl), 404);
            default:
                throw new RequestException($e->getMessage(), $e->getCode());
        }
    }

    protected function parseResponse(ResponseInterface $response): ?array
    {
        $responseBody = (string) $response->getBody();

        if (empty($responseBody)) {
            return null;
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidResponseException(sprintf('Json Decode Failed: %s', json_last_error_msg()));
        }

        return $responseData;
    }

    protected function buildUrl(string $endpoint): string
    {
        return sprintf('%s/%s', $this->apiBaseUrl, $endpoint);
    }

    public function bitlinks(): Bitlinks
    {
        return new Bitlinks($this);
    }
}
