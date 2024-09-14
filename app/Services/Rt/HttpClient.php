<?php

namespace App\Services\Rt;

use App\Services\Http\ProxyService;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class HttpClient
{
    private PendingRequest $client;

    public function __construct(ProxyService $proxy)
    {
        $this->client = $proxy->through()
                              ->withCookies([
                                  'bb_session' => config('services.rt.bb_session')
                              ], 'rutracker.org')
                              ->baseUrl('https://rutracker.org/');
    }

    /**
     * Get body.
     * @param string $url
     * @param array|null|string $query
     * @return string
     * @throws ConnectionException
     */
    public function getBody(string $url, mixed $query = null): string
    {
        return mb_convert_encoding($this->client->get($url, $query)->body(), 'UTF-8', 'Windows-1251');
    }
}
