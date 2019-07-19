<?php
declare(strict_types=1);

namespace Atymic\Bitly\Api;

use Atymic\Bitly\Client\Client;
use Atymic\Bitly\Exception\InvalidTimeUnitException;
use DateTimeImmutable;

class Bitlinks
{
    const ENDPOINT_EXPAND = 'expand';
    const ENDPOINT_CREATE = 'bitlinks';
    const ENDPOINT_SHORTEN = 'shorten';
    const ENDPOINT_RETRIEVE = 'bitlinks/%s';
    const ENDPOINT_UPDATE = 'bitlinks/%s';
    const ENDPOINT_METRICS = 'bitlinks/%s/%s';
    const ENDPOINT_CLICKS = '/bitlinks/%s/clicks';
    const ENDPOINT_CLICKS_SUMMARY = '/bitlinks/%s/clicks/summary';

    const TIME_UNITS = [
        'minute',
        'hour',
        'day',
        'week',
        'month',
    ];

    const DEFAULT_TIME_UNIT = 'day';

    /** @var Client */
    private $client;

    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }


    public function expand(string $bitlinkId): array
    {
        return $this->client->postRequest(self::ENDPOINT_EXPAND, [
            'bitlink_id' => $bitlinkId,
        ]);
    }

    public function create(
        string $longUrl,
        ?string $domain = null,
        ?string $title = null,
        array $tags = [],
        array $deepLinks = [],
        ?string $groupGuid = null
    ): array {
        $params = [
            'long_url' => $longUrl,
            'domain' => $domain,
            'title' => $title,
            'tags' => $tags,
            'deeplinks' => $deepLinks,
            'group_guid' => $groupGuid,
        ];

        return $this->client->postRequest(self::ENDPOINT_CREATE, array_filter($params));
    }

    public function shorten(string $longUrl, ?string $domain = null, ?string $groupGuid = null): array
    {
        $params = [
            'long_url' => $longUrl,
            'domain' => $domain,
            'group_guid' => $groupGuid,
        ];

        return $this->client->postRequest(self::ENDPOINT_SHORTEN, array_filter($params));
    }

    public function get(string $bitlink): array
    {
        return $this->client->getRequest(sprintf(self::ENDPOINT_RETRIEVE, $bitlink));
    }

    public function update(string $bitlink, array $bitlinkData): array
    {
        return $this->client->postRequest(sprintf(self::ENDPOINT_UPDATE, $bitlink), $bitlinkData);
    }

    public function clicks(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_CLICKS, $bitlink);

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    public function clicksSummary(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_CLICKS_SUMMARY, $bitlink);

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    public function metricsByReferrers(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_METRICS, $bitlink, 'referrers');

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    public function metricsByReferringDomains(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_METRICS, $bitlink, 'referring_domains');

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    public function metricsByCountries(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_METRICS, $bitlink, 'countries');

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    public function metricsReferrersByDomain(
        string $bitlink,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $endpoint = sprintf(self::ENDPOINT_METRICS, $bitlink, 'referrers_by_domains');

        return $this->metricsRequest($endpoint, $unit, $units, $size, $until);
    }

    private function metricsRequest(
        string $endpoint,
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $this->validateUnit($unit);

        return $this->client->getRequest(
            $endpoint,
            $this->buildMetricParams($unit, $units, $size, $until)
        );
    }

    private function buildMetricParams(
        string $unit = self::DEFAULT_TIME_UNIT,
        int $units = -1,
        int $size = 50,
        ?DateTimeImmutable $until = null
    ): array {
        $params = [
            'unit' => $unit,
            'units' => $units,
            'size' => $size,
        ];

        if ($until) {
            $params['unit_reference'] = $until->format(DATE_ISO8601);
        }

        return $params;
    }

    private function validateUnit(string $unit): void
    {
        if (in_array($unit, self::TIME_UNITS, true)) {
            return;
        }

        throw new InvalidTimeUnitException(sprintf(
            'Unit %s is not one of: %s',
            $unit,
            implode(', ', self::TIME_UNITS)
        ));
    }
}
