<?php

namespace App\Services\Rt\Repo;

use App\Services\Html\Parser\Filter;
use App\Services\Rt\Objects\SearchTopic;

class SearchPageRepo extends Repository
{
    /**
     * Parse topics.
     * @return SearchTopic[]
     */
    public static function topics(string $body): array
    {
        $document = static::parser()->parse($body);

        $rows = $document->find(new Filter(class: 'forumline tablesorter'))
                         ->find(new Filter(name: 'tbody'))
                         ->findAll(new Filter(name: 'tr'));

        $result = [];

        foreach ($rows as $row) {
            $name = $row->find(new Filter(class: 'row4 med tLeft t-title-col tt'))
                        ?->find(new Filter(name: 'a'))->text;

            $name = html_entity_decode($name);

            if (!$name)
                continue;

            $author = $row->find(new Filter(class: 'row1 u-name-col'))
                          ->find(new Filter(name: 'a'))->text;

            $size       = $row->find(new Filter(class: 'row4 small nowrap tor-size'))->attributes['data-ts_text'];
            $seeds      = $row->find(new Filter(class: 'row4 nowrap'))->attributes['data-ts_text'];
            $leeches    = $row->find(new Filter(class: 'row4 leechmed bold'))->text;
            $downloads  = $row->find(new Filter(class: 'row4 small number-format'))->text;
            $created_at = $row->find(new Filter(class: 'row4 small nowrap'))->attributes['data-ts_text'];
            $category   = $row->find(new Filter(class: 'row1 f-name-col'))
                              ->find(new Filter(name: 'a'))->text;

            $topic             = new SearchTopic();
            $topic->id         = (int)$row->attributes['data-topic_id'];
            $topic->name       = $name;
            $topic->author     = $author ? html_entity_decode($author) : null;
            $topic->size       = (int)$size;
            $topic->seeds      = (int)$seeds;
            $topic->leeches    = (int)$leeches;
            $topic->downloads  = (int)$downloads;
            $topic->created_at = (int)$created_at;
            $topic->category   = $category ? html_entity_decode($category) : null;

            $result[] = $topic;
        }

        return $result;
    }
}
