<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\ParserService;
use App\Services\Rt\HttpClient;

class Repository
{
    public static function http(): HttpClient
    {
        return resolve(HttpClient::class);
    }

    public static function parser(): ParserService
    {
        return resolve(ParserService::class);
    }
}
