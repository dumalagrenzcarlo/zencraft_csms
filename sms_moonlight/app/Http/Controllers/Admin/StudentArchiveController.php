<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\ClassesModel;
use App\Models\Student;
use App\Services\StudentArchiver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class StudentArchiveController extends Controller
{
    public function archive(Student $student, StudentArchiver $archiver): RedirectResponse
    {
        $changed = $archiver->archive($student);

        return back()->with('status', $changed
            ? 'Student archived successfully.'
            : 'Student is already archived.');
    }

    public function archiveClass(ClassesModel $class, StudentArchiver $archiver): RedirectResponse
    {
        $count = $archiver->archiveClass($class);

        return back()->with('status', $count === 1
            ? '1 student archived from the class.'
            : "{$count} students archived from the class.");
    }

    public function archiveSelectedClass(Request $request, StudentArchiver $archiver): RedirectResponse
    {
        $data = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
        ]);

        return $this->archiveClass(
            ClassesModel::query()->findOrFail($data['class_id']),
            $archiver,
        );
    }

    public function restore(Student $student, StudentArchiver $archiver): RedirectResponse
    {
        $changed = $archiver->restore($student);

        return back()->with('status', $changed
            ? 'Student restored successfully. Portal access has been re-enabled.'
            : 'Student is already active.');
    }
}
