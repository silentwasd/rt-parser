<?php

namespace App\Http\Controllers;

use App\Services\Rt\RtService;

class TopicController extends Controller
{
    public function show(RtService $rt, int $id)
    {
        return response()->json($rt->topic($id));
    }
}
