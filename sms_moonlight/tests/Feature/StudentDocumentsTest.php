<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentDocument;
use App\MoonShine\Resources\Student\StudentResource;
use App\MoonShine\Resources\StudentDocument\StudentDocumentResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use MoonShine\Laravel\Fields\Relationships\HasMany;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;
use MoonShine\UI\Fields\File;
use Tests\TestCase;

class StudentDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_documents_schema_replaces_the_form137_path(): void
    {
        $this->assertTrue(Schema::hasColumns('student_documents', [
            'id',
            'student_id',
            'file',
            'notes',
            'created_at',
            'updated_at',
        ]));
        $this->assertFalse(Schema::hasColumn('students', 'form137path'));
    }

    public function test_documents_resource_uses_the_requested_fields(): void
    {
        $resource = app(StudentDocumentResource::class);

        $this->assertSame(
            ['id', 'student_id', 'file', 'notes'],
            collect($resource->formFields())
                ->map(fn ($field): string => $field->getColumn())
                ->all(),
        );
        $this->assertSame('Documents', $resource->getTitle());
    }

    public function test_student_details_include_a_documents_tab_and_no_form137_field(): void
    {
        $resource = app(StudentResource::class);
        $details = collect($resource->detailsFields());
        $documentRelation = $details
            ->filter(fn ($field): bool => $field instanceof HasMany)
            ->first(fn (HasMany $field): bool => $field->getRelationName() === 'documents');

        $this->assertNotNull($documentRelation);
        $this->assertNotContains(
            'form137path',
            $details->map(fn ($field): string => $field->getColumn())->all(),
        );
        $this->assertNotContains(
            'form137path',
            collect($resource->formFields())
                ->map(fn ($field): string => $field->getColumn())
                ->all(),
        );
    }

    public function test_document_uploads_keep_and_display_the_original_file_name(): void
    {
        /** @var File $field */
        $field = collect(app(StudentDocumentResource::class)->formFields())
            ->first(fn ($field): bool => $field->getColumn() === 'file');
        $uploadedFile = UploadedFile::fake()->create('Quarter 1 Report Card.pdf', 100, 'application/pdf');
        $storedName = ($field->getCustomName())($uploadedFile, $field);
        $displayName = ($field->resolveNames())('student-documents/'.$storedName, 0, $field);

        $this->assertStringEndsWith('/Quarter 1 Report Card.pdf', $storedName);
        $this->assertSame('Quarter 1 Report Card.pdf', $displayName);
    }

    public function test_student_documents_are_deleted_with_the_student(): void
    {
        $user = (new MoonshineUser)->forceFill([
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'username' => 'document-student',
            'email' => 'document-student@example.test',
            'name' => 'Juan Dela Cruz',
            'password' => bcrypt('password'),
        ]);
        $user->save();

        $student = Student::withoutEvents(fn (): Student => Student::query()->create([
            'user_id' => $user->id,
            'lrn' => '123456789012',
            'lastname' => 'Dela Cruz',
            'firstname' => 'Juan',
            'middlename' => 'Santos',
            'gender' => 'male',
            'dob' => '2010-01-01',
            'address' => 'Test Address',
            'birthplace' => 'Test City',
            'profile_photo' => 'students/test.jpg',
            'parent_guardian' => 'Maria Dela Cruz',
            'parent_guardian_address' => 'Test Address',
            'parent_guardian_relationship' => 'Mother',
            'is_4ps_member' => false,
        ]));
        $document = $student->documents()->create([
            'file' => 'student-documents/report-card.pdf',
            'notes' => 'Report card',
        ]);

        $this->assertTrue($student->documents->contains($document));

        $student->delete();

        $this->assertDatabaseMissing((new StudentDocument)->getTable(), [
            'id' => $document->id,
        ]);
    }
}
