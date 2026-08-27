<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_enrollment_courses', function (Blueprint $table): void {
            $table->timestamp('grades_submitted_at')->nullable()->after('remarks');
            $table->unsignedBigInteger('grades_submitted_by')->nullable()->after('grades_submitted_at');
        });
    }

    public function down(): void
    {
        Schema::table('college_enrollment_courses', function (Blueprint $table): void {
            $table->dropColumn(['grades_submitted_at', 'grades_submitted_by']);
        });
    }
};
