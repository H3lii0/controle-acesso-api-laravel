<?php

namespace App\Enums;

enum AccessDenialReason: string
{
    case StudentNotFound = 'student_not_found';
    case StudentInactive = 'student_inactive';
    case ExitTooSoon = 'exit_too_soon';
    case DailyAccessCompleted = 'daily_access_completed';
}
