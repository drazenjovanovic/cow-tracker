<?php

declare(strict_types=1);

namespace App\Domain;

/** What happened. Values are stored in events.action and shown in the stats filter. */
enum EventAction: string
{
    case Login = 'login';
    case Logout = 'logout';
    case Registration = 'registration';
    case ViewPage = 'view-page';
    case ButtonClick = 'button-click';
}
