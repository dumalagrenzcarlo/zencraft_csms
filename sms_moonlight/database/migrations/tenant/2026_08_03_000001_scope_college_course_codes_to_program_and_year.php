<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureProgramForeignKeyIndex();

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->dropUnique('college_program_course_unique');
            $table->unique(
                ['program_id', 'year_level', 'semester', 'course_code'],
                'college_program_course_unique'
            );
        });
    }

    public function down(): void
    {
        $this->ensureProgramForeignKeyIndex();

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->dropUnique('college_program_course_unique');
            $table->unique(
                ['program_id', 'course_code', 'year_level', 'semester'],
                'college_program_course_unique'
            );
        });

        if (DB::connection()->getDriverName() === 'mysql'
            && Schema::hasIndex('college_curriculum_subjects', 'ccs_program_id_fk_idx')) {
            Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
                $table->dropIndex('ccs_program_id_fk_idx');
            });
        }
    }

    private function ensureProgramForeignKeyIndex(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql'
            || Schema::hasIndex('college_curriculum_subjects', 'ccs_program_id_fk_idx')) {
            return;
        }

        Schema::table('college_curriculum_subjects', function (Blueprint $table): void {
            $table->index('program_id', 'ccs_program_id_fk_idx');
        });
    }
};
