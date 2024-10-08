<?php

namespace App\Http\Controllers;

use App\Http\Resources\SearchResource;
use App\Models\Movie;
use App\Services\Rt\Enums\SearchDirection;
use App\Services\Rt\Enums\SearchOrder;
use App\Services\Rt\RtService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function __invoke(Request $request, RtService $rt)
    {
        $data = $request->validate([
            'query'          => 'nullable|string|max:255',
            'sort_column'    => [
                'required',
                Rule::in(['name', 'category', 'size', 'seeds', 'leeches', 'downloads', 'created_at'])
            ],
            'sort_direction' => [
                'required',
                Rule::in(['asc', 'desc'])
            ],
            'page'           => 'nullable|integer|min:1'
        ]);

        $order = match ($data['sort_column']) {
            'name'       => SearchOrder::TopicName,
            'size'       => SearchOrder::Size,
            'seeds'      => SearchOrder::Seeds,
            'leeches'    => SearchOrder::Leeches,
            'downloads'  => SearchOrder::Downloads,
            'created_at' => SearchOrder::Registered
        };

        $direction = match ($data['sort_direction']) {
            'asc'  => SearchDirection::Ascending,
            'desc' => SearchDirection::Descending
        };

        if ($data['query'] ?? false) {
            $data = $rt->search(
                query: $data['query'],
                order: $order,
                direction: $direction,
                page: $data['page'] ?? 1
            );
        } else {
            $data = [];
        }

        return response()->json([
            'data'     => $data['data'] ?? [],
            'meta'     => [
                'per_page' => 50,
                'total'    => $data['total'] ?? 0
            ],
            'counters' => [
                'movies' => Movie::count()
            ]
        ]);
    }
}
