<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('advisers', function (Blueprint $table): void {
            $table->string('staff_type', 20)
                ->default('teacher')
                ->after('major')
                ->index();
        });

        DB::table('college_course_offerings')
            ->select('instructor_id')
            ->whereNotNull('instructor_id')
            ->distinct()
            ->pluck('instructor_id')
            ->each(function (int $adviserId): void {
                $usedByHighSchool = DB::table('classes')
                    ->where('adviser_id', $adviserId)
                    ->exists();

                if (! $usedByHighSchool) {
                    DB::table('advisers')
                        ->where('id', $adviserId)
                        ->update(['staff_type' => 'instructor']);

                    return;
                }

                $adviser = DB::table('advisers')->where('id', $adviserId)->first();

                if (! $adviser) {
                    return;
                }

                $instructorId = DB::table('advisers')->insertGetId([
                    'user_id' => $adviser->user_id,
                    'name' => $adviser->name,
                    'rank' => $adviser->rank,
                    'major' => $adviser->major,
                    'staff_type' => 'instructor',
                    'profile_photo' => $adviser->profile_photo,
                    'created_at' => $adviser->created_at,
                    'updated_at' => $adviser->updated_at,
                ]);

                DB::table('college_course_offerings')
                    ->where('instructor_id', $adviserId)
                    ->update(['instructor_id' => $instructorId]);
            });
    }

    public function down(): void
    {
        DB::table('advisers')
            ->where('staff_type', 'instructor')
            ->orderBy('id')
            ->get()
            ->each(function (object $instructor): void {
                $teacherId = DB::table('advisers')
                    ->where('staff_type', 'teacher')
                    ->where('user_id', $instructor->user_id)
                    ->where('name', $instructor->name)
                    ->value('id');

                if (! $teacherId) {
                    return;
                }

                DB::table('college_course_offerings')
                    ->where('instructor_id', $instructor->id)
                    ->update(['instructor_id' => $teacherId]);

                DB::table('advisers')
                    ->where('id', $instructor->id)
                    ->whereNotExists(function ($query): void {
                        $query->selectRaw('1')
                            ->from('college_course_offerings')
                            ->whereColumn('college_course_offerings.instructor_id', 'advisers.id');
                    })
                    ->delete();
            });

        Schema::table('advisers', function (Blueprint $table): void {
            $table->dropIndex(['staff_type']);
            $table->dropColumn('staff_type');
        });
    }
};
