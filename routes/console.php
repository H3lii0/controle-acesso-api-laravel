<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('notifications:prune', function (): void {
    $deleted = DB::table('notifications')
        ->whereNotNull('read_at')
        ->where('created_at', '<', now()->subDays(7))
        ->delete();

    $this->info("{$deleted} notificacao(oes) removida(s).");
})->purpose('Remove notificacoes lidas com mais de sete dias');
