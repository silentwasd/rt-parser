<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\Element;
use App\Services\Html\Parser\Filter;
use App\Services\Rt\Objects\Topic;
use Illuminate\Support\Str;

class TopicPageRepo extends Repository
{
    public static function topic(string $body): ?Topic
    {
        $document = static::parser()->parse($body);

        $table = $document->find(new Filter(id: 'topic_main'));

        $topic = new Topic();

        if (!($title = $document->find(new Filter(class: 'topic-title'))?->niceText()))
            return null;

        $topic->title = $title;
        $topic->magnet      = $document->find(new Filter(class: 'med magnet-link'))?->attributes['href'];
        $topic->description = $table->find(new Filter(class: 'post_body'))?->toArray();
        $topic->size        = $document->find(new Filter(class: 'tor-size-humn'))?->attributes['title'];
        $topic->seeds       = (int)$document->find(new Filter(class: 'seed'))?->find(new Filter(name: 'b'))?->text ?? 0;
        $topic->leeches     = (int)$document->find(new Filter(class: 'leech'))?->find(new Filter(name: 'b'))?->text ?? 0;

        $topic->comments = collect($table->findAll(new Filter(name: 'tbody')))
            ->filter(fn(Element $element) => $element->find(new Filter(class: 'message td2')) != null)
            ->skip(1)
            ->map(fn(Element $element) => [
                'nickname' => html_entity_decode(
                    Str::replace("\n", '', $element->find(new Filter(classes: ['nick']))?->find(new Filter(name: 'a'))?->deepText() ?? '')
                ),

                'avatar' => $element->find(new Filter(classes: ['poster_info']))
                                    ->find(new Filter(class: 'avatar'))
                                    ?->find(new Filter(name: 'img'))?->attributes['src'] ?? null,

                'content' => $element->find(new Filter(class: 'post_body'))->toArray()
            ])
            ->values()
            ->all();

        $topic->images = collect(
            $table->find(new Filter(class: 'post_body'))
                  ->findAll(new Filter(name: 'var'))
        )->sortBy(fn(Element $element) => !$element->classes()->has('postImgAligned'))
         ->map(fn(Element $element) => $element->attributes['title'])
         ->all();

        return $topic;
    }
}
