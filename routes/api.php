<?php

use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('test', function (App\Services\Rt\HttpClient $http) {
    return $http->getBody('/');
});

Route::get('search', SearchController::class);
