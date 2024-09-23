<?php

namespace App\Console\Commands;

use App\Jobs\ParseMovieJob;
use App\Services\Rt\RtService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class ParseForumsCommand extends Command
{
    protected $signature = 'parse:forums';

    protected $description = 'Command description';

    /**
     * @throws ConnectionException
     */
    public function handle(RtService $rt): void
    {
        $forums = config('services.rt.forums');

        foreach ($forums as $forumId => $forumData) {
            $firstPage = $rt->forumTopics((int)$forumId);

            $lastPage = $firstPage['lastPage'];

            foreach ($firstPage['items'] as $item) {
                if ($forumData['type'] == 'movies')
                    ParseMovieJob::dispatch($item);
            }
        }
    }
}
