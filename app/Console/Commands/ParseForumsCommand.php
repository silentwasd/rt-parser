<?php

namespace App\Console\Commands;

use App\Jobs\ParseMovieJob;
use App\Services\Rt\RtService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;

class ParseForumsCommand extends Command
{
    protected $signature = 'parse:forums {id?}';

    protected $description = 'Command description';

    /**
     * @throws ConnectionException
     */
    public function handle(RtService $rt): void
    {
        $forums = config('services.rt.forums');

        foreach ($forums as $forumId => $forumData) {
            if (($id = $this->argument('id')) && $forumId != $id)
                continue;

            $firstPage = $rt->forumTopics((int)$forumId, 1);

            $lastPage = $firstPage['lastPage'];

            for ($i = 1; $i <= $lastPage; $i++) {
                $firstPage = $rt->forumTopics((int)$forumId, $i);

                $this->info('Page ' . $i);

                foreach ($firstPage['items'] as $item) {
                    if ($forumData['type'] == 'movies')
                        ParseMovieJob::dispatch($item, (int)$forumId);
                }
            }
        }
    }
}
