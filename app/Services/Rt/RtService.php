<?php

namespace App\Services\Rt;

use App\Services\Rt\Objects\SearchTopic;
use App\Services\Rt\Objects\Topic;
use App\Services\Rt\Repo\ForumPageRepo;
use App\Services\Rt\Repo\SearchPageRepo;
use App\Services\Rt\Enums\SearchCategory;
use App\Services\Rt\Enums\SearchDirection;
use App\Services\Rt\Enums\SearchOrder;
use App\Services\Rt\Enums\SearchPeriod;
use App\Services\Rt\Repo\TopicPageRepo;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

class RtService
{
    public function http(): HttpClient
    {
        return resolve(HttpClient::class);
    }

    /**
     * Search for topics with many filters ✨.
     * @param string $query
     * @param SearchOrder $order
     * @param SearchDirection $direction
     * @param SearchPeriod $period
     * @param SearchCategory|null $category
     * @param int $page
     * @return SearchTopic[]
     * @throws ConnectionException
     */
    public function search(
        string          $query,
        SearchOrder     $order = SearchOrder::Registered,
        SearchDirection $direction = SearchDirection::Descending,
        SearchPeriod    $period = SearchPeriod::All,
        SearchCategory  $category = null,
        int             $page = 1
    ): array
    {
        $body = $this->http()->getBody('forum/tracker.php', [
            'nm'    => $query,
            'o'     => $order->value,
            's'     => $direction->value,
            'tm'    => $period->value,
            'start' => ($page - 1) * 50,
            ...$category ? ['f' => $category->value] : []
        ]);

        return SearchPageRepo::topics($body);
    }

    /**
     * Forum topics.
     */
    public function forumTopics(int $id, int $page = 1): array
    {
        $body = Cache::remember(
            "forum.$id.page.$page", 3600 * 24,
            fn() => $this->http()->getBody('forum/viewforum.php', [
                'f'     => $id,
                'start' => ($page - 1) * 50
            ])
        );

        return ForumPageRepo::topics($body);
    }

    /**
     * @throws Exception
     */
    public function topic(int $id): Topic
    {
        $body = Cache::remember(
            "topic.$id", 3600 * 24,
            fn () => $this->http()->getBody('forum/viewtopic.php', ['t' => $id])
        );

        if (!($topic = TopicPageRepo::topic($body)))
            throw new Exception('Invalid topic');

        return $topic;
    }
}
