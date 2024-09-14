<?php

namespace App\Services\Rt\Enums;

use Illuminate\Support\Str;

enum SearchCategory: int
{
    case Adventure     = 53;
    case Arcade        = 127;
    case Chess         = 278;
    case Fighting      = 2203;
    case ForKids       = 128;
    case Fps           = 647;
    case Horror        = 50;
    case Logic         = 2204;
    case Multiplayer   = 2118;
    case PointAndClick = 1008;
    case RolePlay      = 52;
    case Rts           = 51;
    case Simulator     = 54;
    case Tbs           = 2226;
    case Tps           = 646;
    case VisualNovel   = 900;

    public function text(): string
    {
        return match ($this) {
            self::Adventure     => 'Приключения',
            self::Arcade        => 'Казуальные игры',
            self::Chess         => 'Шахматы',
            self::Fighting      => 'Файтинг',
            self::ForKids       => 'Для детей',
            self::Fps           => 'FPS',
            self::Horror        => 'Хоррор',
            self::Logic         => 'Головоломка',
            self::Multiplayer   => 'Многопользовательские игры',
            self::PointAndClick => 'Поиск предметов',
            self::RolePlay      => 'RPG',
            self::Rts           => 'RTS',
            self::Simulator     => 'Симулятор',
            self::Tbs           => 'Пошаговая',
            self::Tps           => 'TPS',
            self::VisualNovel   => 'Визуальная новелла'
        };
    }

    public static function fromLowerName(string $lowerName): ?self
    {
        return collect(self::cases())->first(fn(SearchCategory $category) => Str::lower($category->name) == $lowerName);
    }
}
