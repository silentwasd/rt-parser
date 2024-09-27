<?php

namespace App\Jobs;

use App\Enums\GenreType;
use App\Enums\ReleaseType;
use App\Models\Author;
use App\Models\Country;
use App\Models\File;
use App\Models\Genre;
use App\Models\Movie;
use App\Models\Release;
use App\Models\Topic;
use App\Services\Rt\Objects\ForumTopic;
use App\Services\Rt\RtService;
use Exception;
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
        public ForumTopic $topic,
        public int        $forumId
    )
    {
    }

    private function extract(string $regex, string $text): ?array
    {
        if (!preg_match($regex, $text, $matches))
            return null;

        return $matches;
    }

    public function extractAll(string $regex, string $text): ?array
    {
        if (!preg_match_all($regex, $text, $matches))
            return null;

        return $matches;
    }

    /**
     * @throws Exception
     */
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

        if (!preg_match('/^([^\[(]+)/', $topicModel->name, $titleMatch))
            throw new Exception("Can't parse title");

        $parts = explode(" / ", $titleMatch[1]);

        $year = $this->extract("/\[(?<from>\d{4})(-(?<to>\d{4}))?/", $topicModel->name);

        $availableGenres = [
            "драма\\\\документальный",
            "боевик\/приключения",
            "романтическая комедия",
            "музыкальная комедия",
            "музыкальная мелодрама",
            "историко-приключенческий",
            "детский музыкальный фильм",
            "военно-приключенческий",
            "эротическое мракобесие",
            "историко-биографический фильм",
            "детективная пародия",
            "шпионский триллер",
            "постапокалипсис",
            "музыкальный фильм",
            "готический хоррор",
            "шпионский боевик",
            "комедийные новеллы",
            "боевые искусства",
            "романтический",
            "военная драма",
            "эротический триллер",
            "мистическая драма",
            "историко-библейский",
            "фильм-нуар",
            "фильм нуар",
            "рок-опера",
            "гей-тема",
            "лесби-фильм",
            "мелодрама",
            "киберпанк",
            "боевик",
            "немое кино",
            "комедия",
            "драма",
            "история",
            "ужасы",
            "триллер",
            "детектив",
            "приключения",
            "экранизация",
            "вестерн",
            "исторический",
            "военный",
            "фантастика",
            "антиутопия",
            "криминал",
            "сказка",
            "мюзикл",
            "фэнтези",
            "семейный",
            "притча",
            "биография",
            "мистика",
            "афёра",
            "сатира",
            "трагикомедия",
            "комедии",
            "романс",
            "эротика",
            "шпионский",
            "катастрофа",
            "нуар",
            "трагедия",
            "приключенческий",
            "спорт",
            "роуд-муви",
            "музыка",
            "ужас",
            "еврокрайм",
            "трэш",
            "детский",
            "остросюжетный",
            "музыкальный",
            "приключение",
            "fantasy",
            "giallo",
            "action",
            "biography",
            "history",
            "comedy",
            "drama",
            "war",
        ];

        $genre = $this->extractAll("/\W(" . implode("|", $availableGenres) . ")+\W/iu", $topicModel->name);

        $genres = collect($genre[1] ?? [])
            ->map(fn($genre) => Str::lower($genre))
            ->map(fn($genre) => match ($genre) {
                'comedy', 'комедии'                         => 'комедия',
                'drama'                                     => 'драма',
                'action'                                    => 'боевик',
                'biography'                                 => 'биография',
                'history', 'исторический'                   => 'история',
                'war'                                       => 'военный',
                'фильм-нуар', 'фильм нуар'                  => 'нуар',
                'романтическая комедия'                     => ['романтика', 'комедия'],
                'музыкальная комедия'                       => ['мюзикл', 'комедия'],
                'музыкальная мелодрама'                     => ['мюзикл', 'мелодрама'],
                'историко-приключенческий'                  => ['история', 'приключения'],
                'детективная пародия'                       => ['детектив', 'пародия'],
                'музыкальный фильм', 'музыкальный'          => 'мюзикл',
                'шпионский триллер'                         => ['шпионский', 'триллер'],
                'детский музыкальный фильм'                 => ['детский', 'мюзикл'],
                'романс', 'романтический', 'любовный роман' => 'романтика',
                'военно-приключенческий'                    => ['военный', 'приключения'],
                'драма\документальный'                      => ['драма', 'документальный'],
                'боевик/приключения'                        => ['боевик', 'приключения'],
                'историко-биографический фильм'             => ['история', 'биография'],
                'giallo'                                    => ['детектив', 'триллер'],
                'комедийные новеллы'                        => ['комедия'],
                'шпионский боевик'                          => ['шпионский', 'боевик'],
                'военная драма'                             => ['военный', 'драма'],
                'приключение', 'приключенческий'            => 'приключения',
                'fantasy'                                   => 'фэнтези',
                'ужас'                                      => 'ужасы',
                'эротический триллер'                       => ['эротика', 'триллер'],
                'мистическая драма'                         => ['мистика', 'драма'],
                default                                     => $genre
            })
            ->flatten()
            ->unique()
            ->map(fn($genre) => Genre::firstOrCreate(['name' => $genre, 'genre_type' => GenreType::Movie]))
            ->map(fn(Genre $genre) => $genre->id);

        $availableCountries = [
            "западная германия",
            "исламская республика иран",
            "новая зеландия",
            "вел.бр",
            "австрия",
            "фрг",
            "гдр",
            "сша",
            "франция",
            "италия",
            "германия",
            "великобритания",
            "бразилия",
            "канада",
            "испания",
            "югославия",
            "польша",
            "мексика",
            "чехословакия",
            "ссср",
            "израиль",
            "швейцария",
            "австралия",
            "швеция",
            "япония",
            "нидерланды",
            "дания",
            "венгрия",
            "болгария",
            "финляндия",
            "аргентина",
            "алжир",
            "греция",
            "чехия",
            "египет",
            "румыния",
            "норвегия",
            "ботсвана",
            "юар",
            "турция",
            "пакистан",
            "иран",
            "гонконг",
            "португалия",
            "албания",
            "словакия",
            "исландия",
            "эстония"
        ];

        $country = $this->extractAll("/(" . implode("|", $availableCountries) . ")+/iu", $topicModel->name);

        $countries = collect($country[1] ?? [])
            ->map(fn($country) => Str::lower($country))
            ->map(fn($country) => match ($country) {
                'западная германия', 'фрг', 'гдр' => 'германия',
                'вел.бр'                          => 'великобритания',
                'сша'                             => 'США',
                'ссср'                            => 'СССР',
                'юар'                             => 'ЮАР',
                'исламская республика иран'       => 'иран',
                'новая зеландия'                  => 'Новая Зеландия',
                default                           => $country
            })
            ->flatten()
            ->unique()
            ->map(fn($country) => Country::firstOrCreate(['name' => Str::ucfirst($country)]))
            ->map(fn(Country $country) => $country->id);

        $availableReleases = [
            "hddvdrip",
            "dvdremux",
            "dvdrip-avc",
            "dvdrip",
            "dvd-avc",
            "dvd5",
            "dvd9",
            "dvb",
            "web-dlrip-avc",
            "web-dlvrip-avc",
            "web-dlrip",
            "web-dl-avc",
            "web-dl",
            "webrip",
            "bdrip-avc",
            "bdrip avc",
            "bdrip 720p",
            "bdrip",
            "vhsrip-avc",
            "vhsrip",
            "hdrip-avc",
            "hdrip - avc",
            "hdrip",
            "hdtvrip-avc",
            "hdtvrip",
            "hdtv 1080i",
            "tvrip-avc",
            "tvrip",
            "satrip",
            "ldremux",
            "ldrip",
            "vcdrip",
            "camrip",
            "screener",
            "telesync",
            "telecine",
            "betacamrip",
            "d-theater-avc",
            "d-theaterrip",
            "dtheaterrip",
            "vhs -> dvd",
        ];

        $release = $this->extract("/(" . implode("|", $availableReleases) . ")+/iu", $topicModel->name);

        $releaseName = match (Str::lower($release[1] ?? '')) {
            'hddvdrip'                    => 'HDDVD-Rip',
            'dvd-avc'                     => 'DVD-AVC',
            'dvd9'                        => 'DVD9',
            'dvd5'                        => 'DVD5',
            'dvdrip-avc'                  => 'DVDRip-AVC',
            'dvdrip'                      => 'DVDRip',
            'bdrip avc', 'bdrip-avc'      => 'BDRip-AVC',
            'bdrip 720p'                  => 'BDRip 720p',
            'vhsrip-avc'                  => 'VHSRip-AVC',
            'vhsrip'                      => 'VHSRip',
            'hdrip-avc', 'hdrip - avc'    => 'HDRip-AVC',
            'hdrip'                       => 'HDRip',
            'hdtv 1080i'                  => 'HDTV 1080i',
            'hdtvrip-avc'                 => 'HDTVRip-AVC',
            'hdtvrip'                     => 'HDTVRip',
            'bdrip'                       => 'BDRip',
            'web-dlrip-avc'               => 'WEB-DLRip-AVC',
            'web-dlvrip-avc'              => 'WEB-DLVRip-AVC',
            'web-dlrip'                   => 'WEB-DLRip',
            'web-dl-avc'                  => 'WEB-DL-AVC',
            'web-dl'                      => 'WEB-DL',
            'tvrip-avc'                   => 'TVRip-AVC',
            'tvrip'                       => 'TVRip',
            'webrip'                      => 'WEBRip',
            'satrip'                      => 'SATRip',
            'dvb'                         => 'DVB',
            'dvdremux'                    => 'DVDRemux',
            'ldrip'                       => 'LDRip',
            'betacamrip'                  => 'BetacamRip',
            'telecine'                    => 'Telecine',
            'telesync'                    => 'Telesync',
            'd-theater-avc'               => 'D-Theater-AVC',
            'dtheaterrip', 'd-theaterrip' => 'D-TheaterRip',
            'vcdrip'                      => 'VCDRip',
            'camrip'                      => 'CAMRip',
            'ldremux'                     => 'LDRemux',
            'VHS -> DVD'                  => 'VHS -> DVD',
            default                       => Str::ucfirst($release[1] ?? '')
        };

        $releaseModel = $releaseName
            ? Release::firstOrCreate(['name' => $releaseName, 'release_type' => ReleaseType::Movie])
            : null;

        $movie = Movie::updateOrCreate(
            ['topic_id' => $topicModel->id],
            [
                'title'        => $parts[0],
                'second_title' => $parts[1] ?? null,
                'year_from'    => $year['from'] ?? null,
                'year_to'      => $year['to'] ?? null,
                'release_id'   => $releaseModel?->id,
                'forum_id'     => $this->forumId
            ]
        );

        $movie->genres()->sync($genres);
        $movie->countries()->sync($countries);

        if ($movie->wasRecentlyCreated) {
            try {

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
                    } catch (Exception $e) {
                    }
                }

                $movie->cover_id = $firstValidImage ? File::create(['path' => $firstValidImage])->id : null;
                $movie->save();
            } catch (Exception $e) {
                $this->fail($e);
            }
        }
    }
}
