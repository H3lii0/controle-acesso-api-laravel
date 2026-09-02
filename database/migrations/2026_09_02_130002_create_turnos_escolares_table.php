<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnos_escolares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->string('nome');
            $table->string('codigo');
            $table->time('inicio');
            $table->time('fim');
            $table->string('status')->default('ativo')->index();
            $table->timestamps();

            $table->unique(['escola_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_escolares');
    }
};
