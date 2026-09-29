<?php

use App\Enums\SchoolShift;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('shift', 20)->default(SchoolShift::Morning->value)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        DB::statement(
            'CREATE UNIQUE INDEX school_classes_name_shift_unique ON school_classes (LOWER(name), shift)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('school_classes');
    }
};
