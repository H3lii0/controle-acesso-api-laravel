<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissoesSistemaSeeder::class,
            PerfisSistemaSeeder::class,
        ]);

        if ($this->command?->getLaravel()->environment(['local', 'testing'])) {
            $this->call(DesenvolvimentoEscolaSeeder::class);
        }
    }
}
