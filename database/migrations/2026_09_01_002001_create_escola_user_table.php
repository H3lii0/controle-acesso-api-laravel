<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escola_user', function (Blueprint $table) {
            $table->foreignId('escola_id')->constrained('escolas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('proprietario')->default(false);
            $table->timestamps();

            $table->primary(['escola_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('escola_user');
    }
};
