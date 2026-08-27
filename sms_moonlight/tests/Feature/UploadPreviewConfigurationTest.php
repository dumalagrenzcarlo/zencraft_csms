<?php

namespace Tests\Feature;

use App\MoonShine\Resources\Adviser\AdviserResource;
use App\MoonShine\Resources\Student\Pages\StudentDetailPage;
use App\MoonShine\Resources\Student\StudentResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use MoonShine\UI\Components\Table\TableBuilder;
use MoonShine\UI\Fields\Image;
use ReflectionMethod;
use Tests\TestCase;

class UploadPreviewConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_uploads_use_previewable_image_fields(): void
    {
        $studentProfilePhoto = collect(app(StudentResource::class)->formFields())
            ->first(fn ($field) => $field->getColumn() === 'profile_photo');
        $adviserProfilePhoto = collect(app(AdviserResource::class)->formFields())
            ->first(fn ($field) => $field->getColumn() === 'profile_photo');

        $this->assertInstanceOf(Image::class, $studentProfilePhoto);
        $this->assertInstanceOf(Image::class, $adviserProfilePhoto);
    }

    public function test_global_file_upload_preview_assets_exist(): void
    {
        $script = public_path('js/file-upload-preview.js');
        $stylesheet = public_path('css/file-upload-preview.css');

        $this->assertFileExists($script);
        $this->assertFileExists($stylesheet);
        $this->assertStringContainsString("input.type === 'file'", (string) file_get_contents($script));
        $this->assertStringContainsString('.file-upload-image-preview', (string) file_get_contents($stylesheet));
    }

    public function test_student_detail_table_uses_the_two_column_layout_hook(): void
    {
        $method = new ReflectionMethod(StudentDetailPage::class, 'modifyDetailComponent');
        $table = TableBuilder::make([]);
        $result = $method->invoke(app(StudentDetailPage::class), $table);

        $this->assertStringContainsString(
            'student-detail-two-column',
            (string) $result->getAttribute('class')
        );

        $layoutSource = (string) file_get_contents(
            app_path('MoonShine/Layouts/CustomLayout.php')
        );

        $this->assertStringContainsString(
            '.student-detail-two-column .table-grid > .grid',
            $layoutSource
        );
        $this->assertStringContainsString(
            '.student-detail-two-column td.table-grid',
            $layoutSource
        );
        $this->assertStringContainsString('max-width: none !important', $layoutSource);
        $this->assertStringContainsString('flex-direction: column', $layoutSource);
    }
}
