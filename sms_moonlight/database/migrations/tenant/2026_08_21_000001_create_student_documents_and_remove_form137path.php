<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('file', 2048);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('students', 'form137path')) {
            DB::table('students')
                ->whereNotNull('form137path')
                ->where('form137path', '<>', '')
                ->orderBy('id')
                ->chunkById(100, function ($students): void {
                    $now = now();
                    $documents = $students->map(fn ($student): array => [
                        'student_id' => $student->id,
                        'file' => $student->form137path,
                        'notes' => 'Migrated from Form137',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();

                    DB::table('student_documents')->insert($documents);
                });

            Schema::table('students', function (Blueprint $table): void {
                $table->dropColumn('form137path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('students', 'form137path')) {
            Schema::table('students', function (Blueprint $table): void {
                $table->string('form137path', 200)->nullable();
            });

            if (Schema::hasTable('student_documents')) {
                $documents = DB::table('student_documents')
                    ->orderBy('id')
                    ->get()
                    ->unique('student_id');

                foreach ($documents as $document) {
                    DB::table('students')
                        ->where('id', $document->student_id)
                        ->update(['form137path' => $document->file]);
                }
            }
        }

        Schema::dropIfExists('student_documents');
    }
};
