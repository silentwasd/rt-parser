<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('test', function (App\Services\Rt\HttpClient $http) {
    return $http->getBody('/');
});

Route::get('search', function (Request $request, App\Services\Rt\RtService $rt) {
    $data = $request->validate([
        'query' => 'required|string|max:255'
    ]);

    return response()->json(
        $rt->search($data['query'])
    );
});
