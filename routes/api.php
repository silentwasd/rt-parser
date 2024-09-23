<?php

use App\Http\Controllers\MovieController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('test', function (App\Services\Rt\HttpClient $http) {
    return $http->getBody('/');
});

Route::get('search', SearchController::class);
Route::get('topics/{id}', [TopicController::class, 'show']);
Route::get('movies', MovieController::class);
