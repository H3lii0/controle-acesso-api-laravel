<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'contact_email', 'phone', 'address', 'timezone'])]
class SchoolSetting extends Model
{
    public static function current(): self
    {
        return static::query()->findOrFail(1);
    }
}
