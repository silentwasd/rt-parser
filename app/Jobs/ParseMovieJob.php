<?php

namespace App\Jobs;

use App\Models\Author;
use App\Models\File;
use App\Models\Movie;
use App\Models\Topic;
use App\Services\Rt\Objects\ForumTopic;
use App\Services\Rt\RtService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ParseMovieJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(
        public ForumTopic $topic
    )
    {
    }

    public function handle(RtService $rt): void
    {
        $topicModel = Topic::updateOrCreate(
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

        preg_match('/^([^\[(]+)/', $topicModel->name, $titleMatch);

        $parts = explode(" / ", $titleMatch[1]);

        $movie = Movie::firstOrCreate(
            ['topic_id' => $topicModel->id],
            [
                'title'        => $parts[0],
                'second_title' => $parts[1] ?? null
            ]
        );

        if ($movie->wasRecentlyCreated) {
            $rtTopic = $rt->topic($topicModel->original_id);

            $firstValidImage = null;

            foreach ($rtTopic->images as $image) {
                if (Str::startsWith($image, [
                    'https://static.rutracker.cc',
                    'http://st.kinopoisk.ru',
                    'http://www.kinopoisk.ru',
                    'http://imageban.ru'
                ]))
                    continue;

                try {
                    $response = Http::get($image);

                    if (!$response->ok() || count(array_intersect(['image/png', 'image/jpeg'], $response->getHeader('Content-Type'))) == 0)
                        continue;

                    if ($response->transferStats
                            ->getRequest()
                            ->getUri()
                            ->getPath() == '/thumb_clickview.png')
                        continue;

                    $ext = '';

                    if ($response->getHeader('Content-Type')[0] == 'image/png')
                        $ext = 'png';

                    if ($response->getHeader('Content-Type')[0] == 'image/jpeg')
                        $ext = 'jpg';

                    $firstValidImage = 'covers/' . Str::uuid() . '.' . $ext;
                    Storage::disk('public')->put($firstValidImage, $response->body());

                    break;
                } catch (\Exception $e) {

                }
            }

            $movie->cover_id = $firstValidImage ? File::create(['path' => $firstValidImage])->id : null;
            $movie->save();
        }
    }
}
