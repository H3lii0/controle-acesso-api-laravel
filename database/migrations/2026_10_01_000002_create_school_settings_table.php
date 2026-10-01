<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('timezone', 64)->default('America/Recife');
            $table->timestamps();
        });

        DB::table('school_settings')->insert([
            'id' => 1,
            'name' => config('school.name', 'Escola Modelo'),
            'timezone' => config('school.timezone', 'America/Recife'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};
