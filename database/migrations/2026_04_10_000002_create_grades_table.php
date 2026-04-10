<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 8, 2);
            $table->string('letter_grade', 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('graded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['enrollment_id', 'assessment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
