<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('unidade_escolar_id')->constrained('unidades_escolares')->cascadeOnDelete();
            $table->foreignId('periodo_letivo_id')->constrained('periodos_letivos')->cascadeOnDelete();
            $table->foreignId('turno_escolar_id')->constrained('turnos_escolares')->restrictOnDelete();
            $table->string('nome');
            $table->string('serie');
            $table->string('status')->default('ativo')->index();
            $table->timestamps();

            $table->unique(['escola_id', 'periodo_letivo_id', 'nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
