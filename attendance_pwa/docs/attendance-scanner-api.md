# Attendance Scanner API Contract

The PWA uses the tenant site's same-origin `/api/mobile/*` endpoints by default. It sends cookies with every request and supports an `access_token` response for installations that require bearer authentication. It never saves credentials, QR values, or attendance records in browser storage.

## `POST /api/mobile/auth/login`

Request: `{ "email": string, "password": string, "device_name": "ZenCraft Attendance PWA" }`

Success `200`: `{ "teacher": { "id": string, "name": string, "email": string, "school_name": string }, "access_token"?: string }`

Return `401` for invalid credentials. Prefer an HttpOnly, Secure, SameSite session cookie over returning `access_token`.

## `GET /api/mobile/teacher/classes`

Success `200`: `{ "classes": [{ "id": string, "name": string, "section"?: string, "subject"?: string, "schedule"?: string, "student_count"?: number }] }`

Only return classes assigned to the authenticated teacher. The PWA selects the sole class automatically and shows a selector when multiple classes are returned.

## `POST /api/mobile/attendance`

Request: `{ "lrn": string, "class_id": string, "scanned_at": ISO8601 string, "source": "teacher_pwa" }`

Success `201`: `{ "attendance": { "id": string, "lrn": string, "student_name": string, "class_name": string, "recorded_at": ISO8601 string, "status": string, "class_attendance_count"?: number } }`

The endpoint must authenticate the teacher, confirm the class belongs to that teacher, resolve the student by the 12-digit LRN, confirm enrollment in the selected class, and insert the attendance record transactionally. Enforce a unique rule for the tenant's attendance period to prevent duplicate check-ins.

- `401`: unauthenticated or expired session
- `403`: teacher is not assigned to the class
- `404`: LRN is unknown or not enrolled in the class
- `409`: attendance already exists; include the existing `attendance` object and a human-readable `message`
- `422`: malformed LRN, class ID, or timestamp

## `POST /api/mobile/auth/logout`

Invalidate the API session or bearer token and return `204`.

All responses must use JSON except the `204` logout response. Apply tenant scoping, rate limiting, audit logging, TLS, and CSRF protection when cookie authentication is used.
