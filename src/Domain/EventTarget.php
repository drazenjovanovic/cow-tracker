<?php

declare(strict_types=1);

namespace App\Domain;

/** What the action was applied to (page or button). Stored in events.target. */
enum EventTarget: string
{
    case PageA = 'page_a';
    case PageB = 'page_b';
    case BuyCow = 'buy_cow';
    case Download = 'download';

    public function label(): string
    {
        return match ($this) {
            self::PageA => 'Page A',
            self::PageB => 'Page B',
            self::BuyCow => 'Button "Buy a cow"',
            self::Download => 'Button "Download"',
        };
    }
}
