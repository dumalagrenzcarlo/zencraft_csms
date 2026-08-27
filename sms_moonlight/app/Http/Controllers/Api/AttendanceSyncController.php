<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\Adviser;
use App\Models\Setting;
use App\Support\RfidCardUid;
use App\Support\TeacherStaffAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AttendanceSyncController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $qrEnabled = Setting::enabled('qr_code_enabled', true);
        $rfidEnabled = Setting::enabled('rfid_enabled', true);

        if (! $qrEnabled && ! $rfidEnabled) {
            return response()->json(['message' => 'QR code and RFID features are disabled.'], 404);
        }

        $token = (string) (
            $request->input('token')
            ?? $request->query('token')
            ?? ''
        );

        $expectedToken = (string) DB::table('settings')
            ->where('settingName', 'api_authcode')
            ->value('settingValue');

        if (
            $token === ''
            || $expectedToken === ''
            || ! hash_equals($expectedToken, $token)
        ) {
            return response()->json([
                'message' => 'Access Denied',
            ], 401);
        }

        $students = DB::table('students')
            ->when(
                ! $qrEnabled,
                fn ($query) => $query
                    ->whereNotNull('rfid_card_uid')
                    ->whereRaw("TRIM(rfid_card_uid) <> ''"),
            )
            ->select([
                DB::raw("'student' as person_type"),
                'id as person_id',
                'id as student_id',
                DB::raw('NULL as teacher_id'),
                $rfidEnabled
                    ? DB::raw("COALESCE(NULLIF(TRIM(rfid_card_uid), ''), lrn) as identifier")
                    : 'lrn as identifier',
                'lrn',
                DB::raw('NULL as name'),
                $rfidEnabled ? 'rfid_card_uid' : DB::raw('NULL as rfid_card_uid'),
                'profile_photo',
            ])
            ->get();

        $personnel = $rfidEnabled && TeacherStaffAttendance::enabled()
            ? DB::table('advisers')
                ->whereIn('staff_type', $this->attendancePersonnelTypes())
                ->select([
                    DB::raw("'staff' as person_type"),
                    'id as person_id',
                    DB::raw('NULL as student_id'),
                    'id as teacher_id',
                    DB::raw("COALESCE(NULLIF(TRIM(rfid_card_uid), ''), CAST(id AS CHAR)) as identifier"),
                    DB::raw('NULL as lrn'),
                    'name',
                    'rfid_card_uid',
                    'profile_photo',
                ])
                ->get()
            : collect();

        return response()->json($students->concat($personnel)->values());
    }

    public function store(Request $request): JsonResponse
    {
        $qrEnabled = Setting::enabled('qr_code_enabled', true);
        $rfidEnabled = Setting::enabled('rfid_enabled', true);

        if (! $qrEnabled && ! $rfidEnabled) {
            return response()->json(['message' => 'QR code and RFID features are disabled.'], 404);
        }

        $authcode = (string) (
            $request->header('X-API-AUTHCODE')
            ?? $request->input('api_authcode')
            ?? $request->input('token')
            ?? $request->query('api_authcode')
            ?? $request->query('token')
            ?? ''
        );

        $expectedAuthcode = (string) DB::table('settings')
            ->where('settingName', 'api_authcode')
            ->value('settingValue');

        if ($authcode === '' || $expectedAuthcode === '' || ! hash_equals($expectedAuthcode, $authcode)) {
            return response()->json([
                'message' => 'Access Denied',
            ], 401);
        }

        $records = $this->extractRecords($request);

        // The desktop scanner's legacy queue stores student RFID scans as
        // student_id-only records, which are indistinguishable from QR scans.
        // Keep those compatible whenever either scanner feature is enabled.
        $usesRfid = collect($records)->contains(
            static fn (array $record): bool => filled($record['rfid_card_uid'] ?? null)
                || filled($record['card_uid'] ?? null)
                || filled($record['uid'] ?? null)
                || filled($record['teacher_id'] ?? null)
                || filled($record['adviser_id'] ?? null),
        );

        if ($usesRfid && ! $rfidEnabled) {
            return response()->json(['message' => 'RFID features are disabled.'], 404);
        }

        $validator = Validator::make(
            ['records' => $records],
            [
                'records' => ['required', 'array', 'min:1'],
                'records.*.student_id' => ['nullable', 'integer'],
                'records.*.teacher_id' => ['nullable', 'integer'],
                'records.*.adviser_id' => ['nullable', 'integer'],
                'records.*.rfid_card_uid' => ['nullable', 'string', 'max:100'],
                'records.*.card_uid' => ['nullable', 'string', 'max:100'],
                'records.*.uid' => ['nullable', 'string', 'max:100'],
                'records.*.currentdate' => ['required', 'date_format:Y-m-d'],
                'records.*.time' => ['required', 'date_format:H:i:s'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $resolvedRecords = [];
        $identityErrors = [];

        foreach ($records as $index => $record) {
            $identity = $this->resolveIdentity($record);

            if (isset($identity['error'])) {
                $identityErrors["records.$index.identity"] = [$identity['error']];

                continue;
            }

            $resolvedRecords[] = [...$record, ...$identity];
        }

        if ($identityErrors !== []) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $identityErrors,
            ], 422);
        }

        $inserted = 0;
        $skipped = 0;

        foreach ($resolvedRecords as $record) {
            $created = $this->insertAttendanceLogIfAllowed(
                $record['student_id'],
                $record['adviser_id'],
                (string) $record['currentdate'],
                (string) $record['time'],
                (string) $record['source'],
            );

            if ($created) {
                $inserted++;
            } else {
                $skipped++;
            }
        }

        return response()->json([
            'message' => 'Synchronization Complete',
            'received' => count($records),
            'inserted' => $inserted,
            'skipped' => $skipped,
        ]);
    }

    public function rfidCards(Request $request): JsonResponse
    {
        if (! Setting::enabled('rfid_enabled', true)) {
            return response()->json(['message' => 'RFID features are disabled.'], 404);
        }

        if (! $this->isAuthorized($request)) {
            return response()->json(['message' => 'Access Denied'], 401);
        }

        $students = DB::table('students')
            ->whereNotNull('rfid_card_uid')
            ->select([
                DB::raw("'student' as person_type"),
                'id as person_id',
                'rfid_card_uid',
                'lrn as identifier',
                'firstname',
                'lastname',
                'profile_photo',
            ])
            ->get()
            ->map(fn ($student): array => [
                'person_type' => $student->person_type,
                'person_id' => $student->person_id,
                'student_id' => $student->person_id,
                'teacher_id' => null,
                'rfid_card_uid' => $student->rfid_card_uid,
                'identifier' => $student->identifier,
                'name' => trim($student->firstname.' '.$student->lastname),
                'profile_photo' => $student->profile_photo,
            ]);

        $personnel = TeacherStaffAttendance::enabled()
            ? DB::table('advisers')
                ->whereIn('staff_type', $this->attendancePersonnelTypes())
                ->whereNotNull('rfid_card_uid')
                ->select([
                    'staff_type as person_type',
                    'id as person_id',
                    'rfid_card_uid',
                    'name',
                    'profile_photo',
                ])
                ->get()
                ->map(fn ($person): array => [
                    'person_type' => $person->person_type,
                    'person_id' => $person->person_id,
                    'student_id' => null,
                    'teacher_id' => $person->person_id,
                    'rfid_card_uid' => $person->rfid_card_uid,
                    'identifier' => ($person->person_type === Adviser::TYPE_STAFF ? 'S-' : 'T-').$person->person_id,
                    'name' => $person->name,
                    'profile_photo' => $person->profile_photo,
                ])
            : collect();

        return response()->json($students->concat($personnel)->values());
    }

    public function scanRfid(Request $request): JsonResponse
    {
        if (! Setting::enabled('rfid_enabled', true)) {
            return response()->json(['message' => 'RFID features are disabled.'], 404);
        }

        if (! $this->isAuthorized($request)) {
            return response()->json(['message' => 'Access Denied'], 401);
        }

        $uid = RfidCardUid::normalize(
            $request->input('rfid_card_uid')
            ?? $request->input('card_uid')
            ?? $request->input('uid')
        );

        $validator = Validator::make(
            [
                'rfid_card_uid' => $uid,
                'currentdate' => $request->input('currentdate', now()->toDateString()),
                'time' => $request->input('time', now()->format('H:i:s')),
            ],
            [
                'rfid_card_uid' => ['required', 'string', 'max:100'],
                'currentdate' => ['required', 'date_format:Y-m-d'],
                'time' => ['required', 'date_format:H:i:s'],
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $identity = $this->resolveIdentity(['rfid_card_uid' => $data['rfid_card_uid']]);

        if (isset($identity['error'])) {
            return response()->json([
                'message' => $identity['error'],
            ], 422);
        }

        $created = $this->insertAttendanceLogIfAllowed(
            $identity['student_id'],
            $identity['adviser_id'],
            $data['currentdate'],
            $data['time'],
            'rfid',
        );

        return response()->json([
            'message' => $created ? 'Attendance recorded' : 'Duplicate scan skipped',
            'recorded' => $created,
            'person_type' => $identity['person_type'],
            'person_id' => $identity['adviser_id'] ?? $identity['student_id'],
            'name' => $identity['name'],
            'currentdate' => $data['currentdate'],
            'time' => $data['time'],
        ], $created ? 201 : 200);
    }

    /**
     * @return array<int, array{student_id: int|string, currentdate: string, time: string}>
     */
    private function extractRecords(Request $request): array
    {
        $records = $request->input('records');

        if (is_array($records)) {
            return $records;
        }

        $all = $request->all();

        if (is_array($all) && isset($all[0]) && is_array($all[0])) {
            return $all;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{student_id: int|null, adviser_id: int|null, source: string, name: string}|array{error: string}
     */
    private function resolveIdentity(array $record): array
    {
        $uid = RfidCardUid::normalize(
            $record['rfid_card_uid']
            ?? $record['card_uid']
            ?? $record['uid']
            ?? null
        );

        if ($uid !== null) {
            $student = DB::table('students')->where('rfid_card_uid', $uid)->first();
            $teacher = DB::table('advisers')
                ->whereIn('staff_type', $this->attendancePersonnelTypes())
                ->where('rfid_card_uid', $uid)
                ->first();

            if ($student && $teacher) {
                return ['error' => 'This RFID card is assigned to both a student and a staff member. Assign a unique card to each person.'];
            }

            if ($student) {
                return [
                    'student_id' => (int) $student->id,
                    'adviser_id' => null,
                    'source' => 'rfid',
                    'name' => trim($student->firstname.' '.$student->lastname),
                    'person_type' => 'student',
                ];
            }

            if ($teacher) {
                return [
                    'student_id' => null,
                    'adviser_id' => (int) $teacher->id,
                    'source' => 'rfid',
                    'name' => (string) $teacher->name,
                    'person_type' => (string) $teacher->staff_type,
                ];
            }

            return ['error' => 'RFID card is not assigned to a student, teacher, or staff member.'];
        }

        $studentId = isset($record['student_id']) ? (int) $record['student_id'] : null;
        $adviserId = isset($record['teacher_id'])
            ? (int) $record['teacher_id']
            : (isset($record['adviser_id']) ? (int) $record['adviser_id'] : null);

        if (($studentId && $adviserId) || (! $studentId && ! $adviserId)) {
            return ['error' => 'Provide one student_id, teacher_id, or rfid_card_uid.'];
        }

        if ($studentId) {
            $student = DB::table('students')->find($studentId);

            if (! $student) {
                return ['error' => 'The selected student does not exist.'];
            }

            return [
                'student_id' => $studentId,
                'adviser_id' => null,
                'source' => 'scanner',
                'name' => trim($student->firstname.' '.$student->lastname),
                'person_type' => 'student',
            ];
        }

        $teacher = DB::table('advisers')
            ->where('id', $adviserId)
            ->whereIn('staff_type', $this->attendancePersonnelTypes())
            ->first();

        if (! $teacher) {
            return ['error' => 'The selected teacher or staff member does not exist.'];
        }

        return [
            'student_id' => null,
            'adviser_id' => $adviserId,
            'source' => 'scanner',
            'name' => (string) $teacher->name,
            'person_type' => (string) $teacher->staff_type,
        ];
    }

    /**
     * @return list<string>
     */
    private function attendancePersonnelTypes(): array
    {
        if (! TeacherStaffAttendance::enabled()) {
            return [];
        }

        return filter_var(config('school_portal.features.staff_module', true), FILTER_VALIDATE_BOOLEAN)
            ? [Adviser::TYPE_TEACHER, Adviser::TYPE_STAFF]
            : [Adviser::TYPE_TEACHER];
    }

    private function insertAttendanceLogIfAllowed(
        ?int $studentId,
        ?int $adviserId,
        string $date,
        string $time,
        string $source,
    ): bool {
        $lastRecord = DB::table('attendance_record')
            ->when(
                $studentId,
                fn ($query) => $query->where('student_id', $studentId),
                fn ($query) => $query->where('adviser_id', $adviserId),
            )
            ->where('currentdate', $date)
            ->orderByDesc('logged_time')
            ->first();

        if ($lastRecord !== null) {
            $newTime = Carbon::createFromFormat('H:i:s', $time);
            $lastTime = Carbon::createFromFormat('H:i:s', (string) $lastRecord->logged_time);

            if ($newTime->diffInSeconds($lastTime) < 600) {
                return false;
            }
        }

        DB::table('attendance_record')->insert([
            'student_id' => $studentId,
            'adviser_id' => $adviserId,
            'currentdate' => $date,
            'logged_time' => $time,
            'source' => $source,
            'amlogin' => '00:00:00',
            'amlogout' => '00:00:00',
            'pmlogin' => '00:00:00',
            'pmlogout' => '00:00:00',
        ]);

        return true;
    }

    private function isAuthorized(Request $request): bool
    {
        $authcode = (string) (
            $request->header('X-API-AUTHCODE')
            ?? $request->input('api_authcode')
            ?? $request->input('token')
            ?? $request->query('api_authcode')
            ?? $request->query('token')
            ?? ''
        );

        $expectedAuthcode = (string) DB::table('settings')
            ->where('settingName', 'api_authcode')
            ->value('settingValue');

        return $authcode !== ''
            && $expectedAuthcode !== ''
            && hash_equals($expectedAuthcode, $authcode);
    }
}
