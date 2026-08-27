<?php

namespace Database\Seeders;

use App\Models\Adviser;
use App\Models\Student;
use App\Models\StudentAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MoonShine\Laravel\Models\MoonshineUser;
use MoonShine\Laravel\Models\MoonshineUserRole;

class TenantDemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();
        $nowString = $now->toDateTimeString();
        $timestamp = $now->timestamp;

        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => MoonshineUserRole::DEFAULT_ROLE_ID],
            ['name' => 'Admin', 'created_at' => $nowString, 'updated_at' => $nowString]
        );
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 2],
            ['name' => 'Teacher', 'created_at' => $nowString, 'updated_at' => $nowString]
        );
        DB::table('moonshine_user_roles')->updateOrInsert(
            ['id' => 3],
            ['name' => 'Student', 'created_at' => $nowString, 'updated_at' => $nowString]
        );

        $admin = MoonshineUser::updateOrCreate(
            ['username' => 'admin'],
            [
                'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
                'name' => 'Admin',
                'username' => 'admin',
                'email' => $this->emailForUsername('admin'),
                'password' => Hash::make('admin123'),
            ]
        );

        DB::table('users')->updateOrInsert(
            ['email' => 'demo.user@mail.com'],
            [
                'name' => 'Demo Web User',
                'email_verified_at' => $nowString,
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'created_at' => $nowString,
                'updated_at' => $nowString,
            ]
        );

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => 'demo.user@mail.com'],
            [
                'token' => Str::random(64),
                'created_at' => $nowString,
            ]
        );

        DB::table('sessions')->updateOrInsert(
            ['id' => 'demo-session'],
            [
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DatabaseSeeder',
                'payload' => base64_encode('demo session payload'),
                'last_activity' => $timestamp,
            ]
        );

        $teacherUsers = [
            1 => ['email' => 'teacher@mail.com', 'name' => 'Teacher One', 'username' => 'teacher1'],
            2 => ['email' => 'teacher2@mail.com', 'name' => 'Teacher Two', 'username' => 'teacher2'],
        ];

        $teacherUserIds = [];
        foreach ($teacherUsers as $index => $teacherData) {
            $teacherUser = MoonshineUser::updateOrCreate(
                ['username' => $teacherData['username']],
                [
                    'moonshine_user_role_id' => 2,
                    'name' => $teacherData['name'],
                    'username' => $teacherData['username'],
                    'email' => $this->emailForUsername($teacherData['username']),
                    'password' => Hash::make('teacher123'),
                    'must_change_password' => true,
                ]
            );

            $teacherUserIds[$index] = $teacherUser->id;
        }

        $advisers = [
            1 => [
                'user_id' => $teacherUserIds[1],
                'name' => 'Teacher One',
                'rank' => 'Teacher I',
                'major' => 'General Education',
                // 'profile_photo' => 'teacher-one.png',
            ],
            2 => [
                'user_id' => $teacherUserIds[2],
                'name' => 'Teacher Two',
                'rank' => 'Teacher II',
                'major' => 'Mathematics',
                // 'profile_photo' => 'teacher-two.png',
            ],
        ];

        $adviserIds = [];
        foreach ($advisers as $index => $adviserData) {
            $adviser = Adviser::updateOrCreate(
                [
                    'name' => $adviserData['name'],
                    'staff_type' => Adviser::TYPE_TEACHER,
                ],
                $adviserData
            );

            $adviserIds[$index] = $adviser->id;
        }

        DB::table('school_year')->updateOrInsert(
            ['id' => 1],
            ['school_year' => '2025-2026', 'active' => 1]
        );

        DB::table('school_year')->updateOrInsert(
            ['id' => 2],
            ['school_year' => '2024-2025', 'active' => 0]
        );

        foreach (['Cash', 'Credit'] as $paymentType) {
            DB::table('payment_types')->updateOrInsert(
                ['name' => $paymentType],
                ['updated_at' => $nowString, 'created_at' => $nowString]
            );
        }

        foreach ([
            1 => ['grade' => 'Grade 7', 'status' => 'active'],
            2 => ['grade' => 'Grade 8', 'status' => 'active'],
            3 => ['grade' => 'Grade 9', 'status' => 'active'],
            4 => ['grade' => 'Grade 10', 'status' => 'active'],
        ] as $id => $grade) {
            DB::table('grade')->updateOrInsert(['id' => $id], $grade);
        }

        foreach ([
            1 => ['subject' => 'Mathematics', 'include_in_average' => 1, 'record_order' => 1, 'record_orders' => 1],
            2 => ['subject' => 'Science', 'include_in_average' => 1, 'record_order' => 2, 'record_orders' => 2],
            3 => ['subject' => 'English', 'include_in_average' => 1, 'record_order' => 3, 'record_orders' => 3],
            4 => ['subject' => 'MAPEH', 'include_in_average' => 1, 'record_order' => 4, 'record_orders' => 4],
        ] as $id => $subject) {
            DB::table('subjects')->updateOrInsert(['id' => $id], $subject);
        }

        $studentFixtures = [
            1 => ['email' => 'student1@mail.com', 'lrn' => '108226160086', 'lastname' => 'Cater', 'firstname' => 'Aira', 'middlename' => 'Viloria', 'gender' => 'Female', 'dob' => '2010-07-12', 'class_id' => 1],
            2 => ['email' => 'student2@mail.com', 'lrn' => '108223150506', 'lastname' => 'Borromeo', 'firstname' => 'Alliah Grace', 'middlename' => 'Alatiit', 'gender' => 'Female', 'dob' => '2011-03-23', 'class_id' => 1],
            3 => ['email' => 'student3@mail.com', 'lrn' => '108225150467', 'lastname' => 'Aureada', 'firstname' => 'Angelie', 'middlename' => 'Flores', 'gender' => 'Female', 'dob' => '2010-08-14', 'class_id' => 1],
            4 => ['email' => 'student4@mail.com', 'lrn' => '108216150006', 'lastname' => 'Karikitan', 'firstname' => 'Argel', 'middlename' => 'Delima', 'gender' => 'Male', 'dob' => '2010-04-14', 'class_id' => 1],
            5 => ['email' => 'student5@mail.com', 'lrn' => '108225140343', 'lastname' => 'Bongon', 'firstname' => 'Arriane Rose', 'middlename' => 'Espinosa', 'gender' => 'Female', 'dob' => '2008-02-21', 'class_id' => 2],
            6 => ['email' => 'student6@mail.com', 'lrn' => '108223150053', 'lastname' => 'Llanera', 'firstname' => 'Avril Juvern', 'middlename' => 'Guardacasa', 'gender' => 'Female', 'dob' => '2009-11-15', 'class_id' => 2],
            7 => ['email' => 'student7@mail.com', 'lrn' => '112929150042', 'lastname' => 'Athea', 'firstname' => 'Baraquia', 'middlename' => 'Macadaeg', 'gender' => 'Female', 'dob' => '2009-08-24', 'class_id' => 3],
            8 => ['email' => 'student8@mail.com', 'lrn' => '108434150149', 'lastname' => 'Kcristan', 'firstname' => 'Camacho', 'middlename' => 'Ropiles', 'gender' => 'Male', 'dob' => '2010-03-29', 'class_id' => 4],
        ];

        $studentIds = [];
        foreach ($studentFixtures as $id => $studentData) {
            $studentUser = MoonshineUser::updateOrCreate(
                ['username' => $studentData['lrn']],
                [
                    'moonshine_user_role_id' => 3,
                    'name' => "{$studentData['firstname']} {$studentData['lastname']}",
                    'username' => $studentData['lrn'],
                    'email' => $this->emailForUsername($studentData['lrn']),
                    'password' => Hash::make('student123'),
                    'must_change_password' => true,
                ]
            );

            $student = Student::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'lrn' => $studentData['lrn'],
                    'lastname' => $studentData['lastname'],
                    'firstname' => $studentData['firstname'],
                    'middlename' => $studentData['middlename'],
                    'gender' => $studentData['gender'],
                    'dob' => $studentData['dob'],
                    'address' => 'Sample City',
                    'birthplace' => 'Sample City',
                    // 'profile_photo' => "student-{$id}.png",
                    'parent_guardian' => "Guardian {$id}",
                    'parent_guardian_address' => 'Sample City',
                    'parent_guardian_relationship' => $id % 2 === 0 ? 'Father' : 'Mother',
                    'is_4ps_member' => $id % 3 === 0,
                    'weight' => (string) (38 + $id),
                    'height' => (string) (140 + $id),
                    'elementary_school_name' => 'Sample Elementary',
                    'elementary_school_id' => 'ES-'.str_pad((string) $id, 3, '0', STR_PAD_LEFT),
                    'elementary_school_address' => 'Sample City',
                    'elementary_school_grade' => '6',
                    'elementary_school_citation' => $id % 2 === 0 ? 'None' : 'Honor',
                    'deworming_grade_7' => $id <= 4,
                    'deworming_grade_8' => $id > 4 && $id <= 6,
                    'deworming_grade_9' => $id === 7,
                    'deworming_grade_10' => $id === 8,
                    'archived' => 0,
                    'archive_date' => null,
                ]
            );

            $studentIds[$id] = $student->id;

            StudentAccess::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'user_id' => $studentUser->id,
                    'active' => 1,
                ]
            );
        }

        foreach ([
            1 => ['adviser_id' => $adviserIds[1], 'grade_id' => 1, 'section' => 'A', 'school_year_id' => 1, 'status' => 'active', 'active' => 1],
            2 => ['adviser_id' => $adviserIds[1], 'grade_id' => 2, 'section' => 'B', 'school_year_id' => 1, 'status' => 'active', 'active' => 1],
            3 => ['adviser_id' => $adviserIds[2], 'grade_id' => 3, 'section' => 'C', 'school_year_id' => 1, 'status' => 'active', 'active' => 1],
            4 => ['adviser_id' => $adviserIds[2], 'grade_id' => 4, 'section' => 'D', 'school_year_id' => 1, 'status' => 'active', 'active' => 1],
            5 => ['adviser_id' => $adviserIds[1], 'grade_id' => 1, 'section' => 'Old A', 'school_year_id' => 2, 'status' => 'inactive', 'active' => 0],
        ] as $id => $class) {
            DB::table('classes')->updateOrInsert(['id' => $id], $class);
        }

        foreach ($studentFixtures as $id => $studentData) {
            DB::table('class_students')->updateOrInsert(
                ['class_id' => $studentData['class_id'], 'student_id' => $studentIds[$id], 'school_year_id' => 1],
                ['hidden_grade' => $id === 8 ? 1 : 0]
            );

            DB::table('student_class')->updateOrInsert(
                ['student_id' => $studentIds[$id], 'grade_id' => $this->gradeIdForClass($studentData['class_id']), 'school_year' => '2025-2026'],
                [
                    'section' => $this->sectionForClass($studentData['class_id']),
                    'status' => 'active',
                ]
            );
        }

        foreach ([1, 2, 3, 4] as $classId) {
            foreach ([1, 2, 3] as $subjectId) {
                DB::table('class_subjects')->updateOrInsert(
                    ['class_id' => $classId, 'subject_id' => $subjectId],
                    ['class_id' => $classId, 'subject_id' => $subjectId]
                );
            }
        }

        foreach ([
            ['adviser_id' => $adviserIds[1], 'day' => 'Monday', 'section' => 'A', 'time_frame' => '07:30 AM - 11:30 AM'],
            ['adviser_id' => $adviserIds[1], 'day' => 'Wednesday', 'section' => 'A', 'time_frame' => '01:00 PM - 04:00 PM'],
            ['adviser_id' => $adviserIds[1], 'day' => 'Tuesday', 'section' => 'B', 'time_frame' => '08:00 AM - 12:00 PM'],
            ['adviser_id' => $adviserIds[2], 'day' => 'Thursday', 'section' => 'C', 'time_frame' => '09:00 AM - 12:00 PM'],
            ['adviser_id' => $adviserIds[2], 'day' => 'Friday', 'section' => 'D', 'time_frame' => '01:00 PM - 04:00 PM'],
        ] as $schedule) {
            DB::table('class_adviser_schedules')->updateOrInsert(
                ['adviser_id' => $schedule['adviser_id'], 'day' => $schedule['day'], 'section' => $schedule['section']],
                ['time_frame' => $schedule['time_frame']]
            );
        }

        foreach ([
            1 => ['title' => 'Welcome Back', 'content' => 'Classes for the new school year are now active.', 'target_audience' => 'both', 'expiry_date' => now()->addMonth()->toDateTimeString()],
            2 => ['title' => 'Quiz Week', 'content' => 'Students should review the posted quiz schedule.', 'target_audience' => 'students', 'expiry_date' => now()->addWeeks(2)->toDateTimeString()],
            3 => ['title' => 'Attendance Reminder', 'content' => 'Daily attendance is checked every morning and afternoon.', 'target_audience' => 'teachers', 'expiry_date' => null],
        ] as $id => $announcement) {
            DB::table('announcements')->updateOrInsert(['id' => $id], $announcement);
        }

        foreach ([
            1 => ['question' => 'What is 8 multiplied by 7?'],
            2 => ['question' => 'Which planet is known as the Red Planet?'],
            3 => ['question' => 'What is the synonym of quick?'],
            4 => ['question' => 'What is the chemical symbol for water?'],
        ] as $id => $quiz) {
            DB::table('quizzes')->updateOrInsert(['id' => $id], $quiz + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            1 => '56',
            2 => '48',
            3 => 'Mars',
            4 => 'Venus',
            5 => 'Fast',
            6 => 'Slow',
            7 => 'H2O',
            8 => 'CO2',
        ] as $id => $answer) {
            DB::table('quiz_answers')->updateOrInsert(['id' => $id], ['answer' => $answer, 'record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            1 => ['quiz_id' => 1, 'answer_id' => 1, 'is_correct_answer' => 1],
            2 => ['quiz_id' => 1, 'answer_id' => 2, 'is_correct_answer' => 0],
            3 => ['quiz_id' => 2, 'answer_id' => 3, 'is_correct_answer' => 1],
            4 => ['quiz_id' => 2, 'answer_id' => 4, 'is_correct_answer' => 0],
            5 => ['quiz_id' => 3, 'answer_id' => 5, 'is_correct_answer' => 1],
            6 => ['quiz_id' => 3, 'answer_id' => 6, 'is_correct_answer' => 0],
            7 => ['quiz_id' => 4, 'answer_id' => 7, 'is_correct_answer' => 1],
            8 => ['quiz_id' => 4, 'answer_id' => 8, 'is_correct_answer' => 0],
        ] as $id => $row) {
            DB::table('quiz_quiz_answers')->updateOrInsert(['id' => $id], $row + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            1 => ['school_year_id' => 1, 'grade_id' => 1, 'week' => 'Week 1'],
            2 => ['school_year_id' => 1, 'grade_id' => 2, 'week' => 'Week 1'],
            3 => ['school_year_id' => 1, 'grade_id' => 3, 'week' => 'Week 2'],
        ] as $id => $group) {
            DB::table('quiz_group')->updateOrInsert(['id' => $id], $group + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            1 => ['title' => 'Math Drill', 'quiz_group_id' => 1, 'day' => 'Monday', 'quiz_duration_seconds' => 900],
            2 => ['title' => 'Science Check', 'quiz_group_id' => 2, 'day' => 'Tuesday', 'quiz_duration_seconds' => 900],
            3 => ['title' => 'English Vocabulary', 'quiz_group_id' => 3, 'day' => 'Wednesday', 'quiz_duration_seconds' => 600],
        ] as $id => $day) {
            DB::table('quiz_group_days')->updateOrInsert(['id' => $id], $day + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            1 => ['quiz_id' => 1, 'quiz_group_days_id' => 1],
            2 => ['quiz_id' => 2, 'quiz_group_days_id' => 2],
            3 => ['quiz_id' => 3, 'quiz_group_days_id' => 3],
        ] as $id => $row) {
            DB::table('quiz_quiz_group_days')->updateOrInsert(['id' => $id], $row + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach (range(1, 8) as $studentIndex) {
            $classId = $studentFixtures[$studentIndex]['class_id'];
            foreach ([1, 2, 3] as $subjectId) {
                DB::table('class_student_grades')->updateOrInsert(
                    ['class_id' => $classId, 'student_id' => $studentIds[$studentIndex], 'subject_id' => $subjectId],
                    [
                        'grade_id' => $this->gradeIdForClass($classId),
                        'q1' => 82 + (($studentIndex + $subjectId) % 10),
                        'q2' => 83 + (($studentIndex + $subjectId) % 9),
                        'q3' => 84 + (($studentIndex + $subjectId) % 8),
                        'q4' => 85 + (($studentIndex + $subjectId) % 7),
                    ]
                );
            }
        }

        $attendanceDates = ['2026-01-08', '2026-01-09', '2026-02-02', '2026-02-03', '2026-03-04'];
        foreach ($studentIds as $studentIndex => $studentId) {
            foreach ($attendanceDates as $dateIndex => $date) {
                if (($studentIndex + $dateIndex) % 5 === 0) {
                    continue;
                }

                DB::table('attendance_record')->updateOrInsert(
                    ['student_id' => $studentId, 'currentdate' => $date],
                    [
                        'amlogin' => '07:30:00',
                        'amlogout' => '11:30:00',
                        'pmlogin' => '13:00:00',
                        'pmlogout' => '16:00:00',
                        'logged_time' => '08:00:00',
                    ]
                );
            }
        }

        foreach ([
            1 => ['quiz_group_days_id' => 1, 'quiz_id' => 1, 'answer_id' => 1, 'student_id' => $studentIds[1]],
            2 => ['quiz_group_days_id' => 1, 'quiz_id' => 1, 'answer_id' => 2, 'student_id' => $studentIds[2]],
            3 => ['quiz_group_days_id' => 2, 'quiz_id' => 2, 'answer_id' => 3, 'student_id' => $studentIds[5]],
            4 => ['quiz_group_days_id' => 3, 'quiz_id' => 3, 'answer_id' => 5, 'student_id' => $studentIds[7]],
        ] as $id => $row) {
            DB::table('student_quiz_answers')->updateOrInsert(['id' => $id], $row + ['record_created' => $nowString, 'record_updated' => $nowString, 'record_deleted' => null]);
        }

        foreach ([
            ['settingName' => 'school_name', 'settingValue' => 'My School', 'settingType' => 'text'],
            ['settingName' => 'school_shortname', 'settingValue' => 'MS', 'settingType' => 'text'],
            ['settingName' => 'school_logo', 'settingValue' => '', 'settingType' => 'file'],
            ['settingName' => 'school_id', 'settingValue' => '123456789', 'settingType' => 'text'],
            ['settingName' => 'school_region', 'settingValue' => 'Region', 'settingType' => 'text'],
            ['settingName' => 'school_district', 'settingValue' => 'District', 'settingType' => 'text'],
            ['settingName' => 'school_division', 'settingValue' => 'Division', 'settingType' => 'text'],
            ['settingName' => 'default_config_student_password', 'settingValue' => 'student123', 'settingType' => 'text'],
            ['settingName' => 'default_config_teacher_password', 'settingValue' => 'teacher123', 'settingType' => 'text'],
            ['settingName' => 'default_config_form10_template_location', 'settingValue' => '', 'settingType' => 'file'],
            ['settingName' => 'api_authcode', 'settingValue' => '1234567890abcdef', 'settingType' => 'text'],
            ['settingName' => 'use_jhs_fields', 'settingValue' => '0', 'settingType' => 'boolean'],
            ['settingName' => 'elementary_fields_enabled', 'settingValue' => '1', 'settingType' => 'boolean'],
            ['settingName' => 'tshirt_size_enabled', 'settingValue' => '1', 'settingType' => 'boolean'],
            ['settingName' => 'teacher_student_detail_editing_enabled', 'settingValue' => '0', 'settingType' => 'boolean'],

        ] as $setting) {
            DB::table('settings')->updateOrInsert(['settingName' => $setting['settingName']], ['settingValue' => $setting['settingValue'], 'settingType' => $setting['settingType']]);
        }

        // DB::table('notifications')->updateOrInsert(
        //     ['id' => (string) Str::uuid()],
        //     [
        //         'type' => 'DatabaseSeeder\\DemoNotification',
        //         'notifiable_type' => MoonshineUser::class,
        //         'notifiable_id' => $admin->id,
        //         'data' => json_encode(['message' => 'Demo notification from database seeder.']),
        //         'read_at' => null,
        //         'created_at' => $nowString,
        //         'updated_at' => $nowString,
        //     ]
        // );

        DB::table('cache')->updateOrInsert(
            ['key' => 'demo-cache-key'],
            ['value' => serialize('demo cache value'), 'expiration' => $timestamp + 3600]
        );
        DB::table('cache_locks')->updateOrInsert(
            ['key' => 'demo-cache-lock'],
            ['owner' => 'database-seeder', 'expiration' => $timestamp + 60]
        );

        DB::table('job_batches')->updateOrInsert(
            ['id' => 'demo-batch'],
            [
                'name' => 'Demo Batch',
                'total_jobs' => 0,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => json_encode([]),
                'options' => json_encode(['seeded' => true]),
                'cancelled_at' => null,
                'created_at' => $timestamp,
                'finished_at' => $timestamp,
            ]
        );

        DB::table('failed_jobs')->updateOrInsert(
            ['uuid' => 'demo-failed-job'],
            [
                'connection' => 'sync',
                'queue' => 'default',
                'payload' => json_encode(['displayName' => 'DemoFailedJob']),
                'exception' => 'Demo failed job row for development data only.',
                'failed_at' => $nowString,
            ]
        );
    }

    private function gradeIdForClass(int $classId): int
    {
        return match ($classId) {
            1, 5 => 1,
            2 => 2,
            3 => 3,
            default => 4,
        };
    }

    private function sectionForClass(int $classId): string
    {
        return match ($classId) {
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            default => 'Old A',
        };
    }

    private function emailForUsername(string $username): string
    {
        return $username.'@'.config('app.domain', 'localhost');
    }
}
