<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horarios_turma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->time('entrada_inicio');
            $table->time('entrada_fim');
            $table->time('saida_inicio');
            $table->time('saida_fim');
            $table->string('status')->default('ativo')->index();
            $table->timestamps();

            $table->unique(['turma_id', 'dia_semana']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horarios_turma');
    }
};
