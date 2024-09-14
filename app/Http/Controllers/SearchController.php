<?php

namespace App\Http\Controllers;

use App\Services\Rt\RtService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function __invoke(Request $request, RtService $rt)
    {
        $data = $request->validate([
            'query' => 'required|string|max:255'
        ]);

        return response()->json(
            $rt->search($data['query'])
        );
    }
}
