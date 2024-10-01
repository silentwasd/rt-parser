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
            "документально-игровая реконструкция",
            "историко-биографический фильм",
            "криминальная трагикомедия",
            "детский музыкальный фильм",
            "историко-приключенческий",
            "антиклерикальный памфлет",
            "триллер ужасы \/ мистика",
            "историко-эпический фильм",
            "художественный кинофильм",
            "драма\\\\документальный",
            "военно-приключенческий",
            "эротическое мракобесие",
            "романтическая комедия",
            "музыкальная мелодрама",
            "фантастический комикс",
            "художественный фильм",
            "боевик\/приключения",
            "музыкальная комедия",
            "комедийные новеллы",
            "ужасы\/фантастика",
            "эротический триллер",
            "мистическая драма",
            "молодежная драмедия",
            "историко-библейский",
            "детективная пародия",
            "гангстерская сага",
            "шпионский триллер",
            "музыкальный фильм",
            "готический хоррор",
            "шпионский боевик",
            "комедия мелодрама",
            "боевые искусства",
            "любовный роман",
            "военная драма",
            "постапокалипсис",
            "документальный",
            "биографический",
            "романтический",
            "триллер,ужасы",
            "драма,триллер",
            "фильм ужасов",
            "зомби-экшн",
            "фильм-нуар",
            "фильм нуар",
            "рок-опера",
            "гей-тема",
            "гей драма",
            "лесби-фильм",
            "мелодрама",
            "киберпанк",
            "боевик",
            "немое кино",
            "комедия",
            "драма",
            "история",
            "пародия",
            "ужасы",
            "триллер",
            "детектив",
            "драмеди",
            "драмедия",
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
            "драмы",
            "романс",
            "эротика",
            "хоррор",
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
            "роман",
            "война",
            "fantasy",
            "giallo",
            "action",
            "biography",
            "history",
            "comedy",
            "drama",
            "war",
            "sci-fi"
        ];

        $genre = $this->extractAll("/\W(" . implode("|", $availableGenres) . ")+\W/iu", $topicModel->name);

        $genres = collect($genre[1] ?? [])
            ->map(fn($genre) => Str::lower($genre))
            ->map(fn($genre) => match ($genre) {
                'comedy', 'комедии'                         => 'комедия',
                'drama', 'драмы'                            => 'драма',
                'action'                                    => 'боевик',
                'biography', 'биографический'               => 'биография',
                'history', 'исторический'                   => 'история',
                'war', 'война'                              => 'военный',
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
                'ужас', 'фильм ужасов'                      => 'ужасы',
                'эротический триллер'                       => ['эротика', 'триллер'],
                'мистическая драма'                         => ['мистика', 'драма'],
                'sci-fi'                                    => 'научная фантастика',
                'зомби-экшн'                                => ['зомби', 'боевик'],
                'художественный кинофильм'                  => 'художественный',
                'историко-эпический фильм'                  => ['история', 'эпический'],
                'триллер ужасы / мистика'                   => ['триллер', 'ужасы', 'мистика'],
                'криминальная трагикомедия'                 => ['криминал', 'трагикомедия'],
                'комедия мелодрама'                         => ['комедия', 'мелодрама'],
                'фантастический комикс'                     => ['фантастика', 'комикс'],
                'ужасы/фантастика'                          => ['ужасы', 'фантастика'],
                'триллер,ужасы'                             => ['триллер', 'ужасы'],
                'драма,триллер'                             => ['триллер', 'драма'],
                'документально-игровая реконструкция'       => ['документальный', 'игровой', 'реконструкция'],
                'драмедия'                                  => 'драмеди',
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
            "эстония",
            "сербия",
            "ирландия",
            "хорватия",
            "бельгия",
            "армения",
            "словения",
            "венесуэла",
            "куба",
            "уругвай",
            "коста-рика",
            "боливия",
            "ливан",
            "колумбия",
            "мальта",
            "чили",
            "саудовская аравия",
            "сингапур",
            "индонезия",
            "китай",
            "кипр",
            "босния и герцеговина",
            "таиланд",
            "парагвай",
            "филиппины",
            "литва",
            "сент-китс и невис",
            "южная корея",
            "индия",
            "латвия",
            "сенегал",
            "монголия",
            "доминиканская республика",
            "сща"
        ];

        $country = $this->extractAll("/(" . implode("|", $availableCountries) . ")+/iu", $topicModel->name);

        $countries = collect($country[1] ?? [])
            ->map(fn($country) => Str::lower($country))
            ->map(fn($country) => match ($country) {
                'западная германия', 'фрг', 'гдр' => 'германия',
                'вел.бр'                          => 'великобритания',
                'сша', 'сща'                      => 'США',
                'ссср'                            => 'СССР',
                'юар'                             => 'ЮАР',
                'исламская республика иран'       => 'иран',
                'новая зеландия'                  => 'Новая Зеландия',
                'коста-рика'                      => 'Коста-Рика',
                'саудовская аравия'               => 'Саудовская Аравия',
                'босния и герцеговина'            => 'Босния и Герцеговина',
                'сент-китс и невис'               => 'Сент-Китс и Невис',
                'южная корея'                     => 'Южная Корея',
                'доминиканская республика'        => 'Доминиканская Республика',
                default                           => $country
            })
            ->flatten()
            ->unique()
            ->map(fn($country) => Country::firstOrCreate(['name' => Str::ucfirst($country)]))
            ->map(fn(Country $country) => $country->id);

        $availableReleases = [
            "hddvdrip",
            "dvdremux",
            "dvdscr",
            "dvdrip-avc",
            "dvdrip",
            "dvd-avc",
            "dvd5",
            "dvd9",
            "dvb",
            "dvd",
            "web-dlrip-avc",
            "web-dlvrip-avc",
            "web-dlrip",
            "web-dl-avc",
            "web-dl",
            "webrip",
            "bdrip-avc, 720p",
            "bdrip-avc hi10p",
            "bdrip-avc",
            "bdrip avc",
            "bdrip 720p",
            "bdrip",
            "bdrp",
            "bdr-avc",
            "brip-avc",
            "vhsrip-avc",
            "vhsrip",
            "hdrip-avc",
            "hdrip - avc",
            "hdrip",
            "hddrip",
            "hdtvrip-avc",
            "hdtvrip",
            "hdtv 1080i",
            "hdtv 1080p",
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
            "d-theater rip-avc",
            "d-theater-avc",
            "d-theaterrip",
            "dtheaterrip",
            "vhs -> dvd",
            "workprint"
        ];

        $release = $this->extract("/(" . implode("|", $availableReleases) . ")+/iu", $topicModel->name);

        $releaseName = match (Str::lower($release[1] ?? '')) {
            'hddvdrip'                                      => 'HDDVD-Rip',
            'dvd-avc'                                       => 'DVD-AVC',
            'dvd9'                                          => 'DVD9',
            'dvd5'                                          => 'DVD5',
            'dvdscr'                                        => 'DVDSCR',
            'dvdrip-avc'                                    => 'DVDRip-AVC',
            'dvdrip'                                        => 'DVDRip',
            'dvd'                                           => 'DVD',
            'bdrip-avc hi10p'                               => 'BDRip-AVC Hi10p',
            'bdrip-avc, 720p'                               => 'BDRip-AVC 720p',
            'bdrip avc', 'bdrip-avc', 'bdr-avc', 'brip-avc' => 'BDRip-AVC',
            'bdrip 720p',                                   => 'BDRip 720p',
            'vhsrip-avc'                                    => 'VHSRip-AVC',
            'vhsrip'                                        => 'VHSRip',
            'hdrip-avc', 'hdrip - avc'                      => 'HDRip-AVC',
            'hdrip', 'hddrip'                               => 'HDRip',
            'hdtv 1080i'                                    => 'HDTV 1080i',
            'hdtv 1080p'                                    => 'HDTV 1080p',
            'hdtvrip-avc'                                   => 'HDTVRip-AVC',
            'hdtvrip'                                       => 'HDTVRip',
            'bdrip', 'bdrp'                                 => 'BDRip',
            'web-dlrip-avc'                                 => 'WEB-DLRip-AVC',
            'web-dlvrip-avc'                                => 'WEB-DLVRip-AVC',
            'web-dlrip'                                     => 'WEB-DLRip',
            'web-dl-avc'                                    => 'WEB-DL-AVC',
            'web-dl'                                        => 'WEB-DL',
            'tvrip-avc'                                     => 'TVRip-AVC',
            'tvrip'                                         => 'TVRip',
            'webrip'                                        => 'WEBRip',
            'satrip'                                        => 'SATRip',
            'dvb'                                           => 'DVB',
            'dvdremux'                                      => 'DVDRemux',
            'ldrip'                                         => 'LDRip',
            'betacamrip'                                    => 'BetacamRip',
            'telecine'                                      => 'Telecine',
            'telesync'                                      => 'Telesync',
            'd-theater-avc'                                 => 'D-Theater-AVC',
            'd-theater rip-avc'                             => 'D-TheaterRip-AVC',
            'dtheaterrip', 'd-theaterrip'                   => 'D-TheaterRip',
            'vcdrip'                                        => 'VCDRip',
            'camrip'                                        => 'CAMRip',
            'ldremux'                                       => 'LDRemux',
            'vhs -> dvd'                                    => 'VHS -> DVD',
            default                                         => Str::ucfirst($release[1] ?? '')
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
