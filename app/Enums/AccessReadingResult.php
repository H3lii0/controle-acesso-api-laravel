<?php

namespace App\Enums;

enum AccessReadingResult: string
{
    case EntryRegistered = 'entry_registered';
    case DuplicateRead = 'duplicate_read';
    case ExitRegistered = 'exit_registered';
    case DailyAccessCompleted = 'daily_access_completed';
}
