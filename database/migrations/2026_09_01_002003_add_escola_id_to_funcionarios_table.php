<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->foreignId('escola_id')
                ->nullable()
                ->after('id')
                ->constrained('escolas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('escola_id');
        });
    }
};
