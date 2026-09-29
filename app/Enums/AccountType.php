<?php

namespace App\Enums;

enum AccountType: string
{
    case CentralAdministrator = 'central_administrator';
    case Employee = 'employee';
    case Guardian = 'guardian';
}
