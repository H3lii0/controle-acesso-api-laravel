<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_access_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->date('access_date')->index();
            $table->timestampTz('entered_at');
            $table->timestampTz('exited_at')->nullable();
            $table->timestampsTz();

            $table->unique(['student_id', 'access_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_access_records');
    }
};
