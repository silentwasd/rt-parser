<?php

use App\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/avatars/{path}', AvatarController::class)->where('path', '.*');
