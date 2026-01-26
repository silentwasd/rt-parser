<?php

namespace App\Http\Controllers;

use App\Services\Http\ProxyService;
use Illuminate\Support\Facades\Cache;

class AvatarController extends Controller
{
    public function __invoke(ProxyService $proxy, string $path)
    {
        $data = Cache::remember("avatars:$path", 3600 * 24, function () use ($proxy, $path) {
            $response = $proxy->through()->get("https://static.rutracker.cc/avatars/$path");

            return [
                'body'    => $response->body(),
                'status'  => $response->status(),
                'headers' => $response->headers()
            ];
        });

        return response($data['body'], $data['status'], $data['headers']);
    }
}
