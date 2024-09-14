<?php

use App\Services\Http\ProxyService;
use Illuminate\Support\Facades\Route;

Route::get('test', function (ProxyService $proxy) {
    return mb_convert_encoding(
        $proxy->through()
              ->withCookies([
                  'bb_session' => config('services.rt.bb_session')
              ], 'rutracker.org')
              ->baseUrl('https://rutracker.org/')
              ->get('/')
              ->body(),
        'UTF-8',
        'Windows-1251'
    );
});
