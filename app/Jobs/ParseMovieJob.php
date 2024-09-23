<?php

namespace App\Jobs;

use App\Models\Author;
use App\Models\Topic;
use App\Services\Rt\Objects\ForumTopic;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseMovieJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ForumTopic $topic
    )
    {
    }

    public function handle(): void
    {
        Topic::updateOrCreate(
            ['original_id' => $this->topic->id],
            [
                'name'      => $this->topic->name,
                'size'      => $this->topic->size,
                'seeds'     => $this->topic->seeds,
                'leeches'   => $this->topic->leeches,
                'downloads' => $this->topic->downloads,
                'author_id' => $this->topic->author ? Author::firstOrCreate(['name' => $this->topic->author])->id : null
            ]
        );
    }
}
