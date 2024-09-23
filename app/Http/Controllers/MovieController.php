<?php

namespace App\Http\Controllers;

use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate([
            'query' => 'nullable|string|max:255',
            'page'  => 'nullable|integer|min:1'
        ]);

        $movies = Movie::query();

        if ($data['query'] ?? false) {
            $movies->where('title', 'LIKE', "%{$data['query']}%")
                   ->orWhere('second_title', 'LIKE', "%{$data['query']}%");
        }

        return MovieResource::collection(
            $movies->paginate(perPage: 50)
        )->additional([
            'counters' => [
                'movies' => Movie::count()
            ]
        ]);
    }
}
