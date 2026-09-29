<?php

namespace App\Enums;

enum AccessReadingResult: string
{
    case EntryRegistered = 'entry_registered';
    case ExitTooSoon = 'exit_too_soon';
    case ExitRegistered = 'exit_registered';
    case DailyAccessCompleted = 'daily_access_completed';
}
