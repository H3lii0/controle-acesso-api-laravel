<?php

namespace App\Enums;

enum AccountStatus: string
{
    case PendingActivation = 'pending_activation';
    case Active = 'active';
    case Disabled = 'disabled';
}
