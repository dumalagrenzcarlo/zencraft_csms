const test = require("node:test");
const assert = require("node:assert/strict");

const {
  buildAttendanceIdentity,
  buildAttendanceSyncRecord,
  getPersonDisplayLabel,
  isDuplicateScan,
  normalizeDirectoryRecord
} = require("../src/attendance-utils");

test("normalizes a staff directory record for the shared adviser attendance API", () => {
  assert.deepEqual(normalizeDirectoryRecord({
    person_type: "staff",
    person_id: 42,
    teacher_id: 42,
    identifier: "STAFF-CARD",
    rfid_card_uid: "STAFF-CARD",
    name: "Juan Dela Cruz",
    profile_photo: "staff/juan.jpg"
  }), {
    person_type: "staff",
    person_id: "42",
    identifier: "STAFF-CARD",
    rfid_card_uid: "STAFF-CARD",
    student_id: null,
    teacher_id: "42",
    lrn: null,
    student_number: null,
    name: "Juan Dela Cruz",
    profile_photo: "staff/juan.jpg"
  });
});

test("supports adviser_id and a personnel ID fallback for staff records", () => {
  const person = normalizeDirectoryRecord({
    person_type: "staff",
    adviser_id: 7,
    name: "Office Staff"
  });

  assert.equal(person.person_id, "7");
  assert.equal(person.teacher_id, "7");
  assert.equal(person.identifier, "7");
});

test("staff display labels use names and sync with teacher_id", () => {
  const staff = {
    person_type: "staff",
    name: "Juan Dela Cruz",
    identifier: "STAFF-CARD"
  };

  assert.equal(getPersonDisplayLabel(staff), "Juan Dela Cruz");
  assert.deepEqual(buildAttendanceIdentity({ teacher_id: "42" }), { teacher_id: "42" });
});

test("sync records retain the stable local event ID for replay protection", () => {
  assert.deepEqual(buildAttendanceSyncRecord({
    student_id: "12",
    currentdate: "2026-08-27",
    time: "07:45:00",
    timestamp: "2026-08-27T07:45:00.123Z"
  }), {
    student_id: "12",
    currentdate: "2026-08-27",
    time: "07:45:00",
    event_id: "2026-08-27T07:45:00.123Z"
  });
});

test("personnel can record another attendance scan after ten minutes", () => {
  const staff = { person_type: "staff" };
  const existing = { time: "08:00:00" };

  assert.equal(isDuplicateScan(staff, existing, "08:09:59"), true);
  assert.equal(isDuplicateScan(staff, existing, "08:10:00"), false);
});

test("students can record another attendance scan after ten minutes", () => {
  assert.equal(
    isDuplicateScan({ person_type: "student" }, { time: "08:00:00" }, "17:00:00"),
    false
  );
});
