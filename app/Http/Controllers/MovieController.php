<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;

class MovieController extends Controller
{
    public function __invoke()
    {
        return MovieResource::collection(
            Movie::paginate(perPage: 50)
        );
    }
}
