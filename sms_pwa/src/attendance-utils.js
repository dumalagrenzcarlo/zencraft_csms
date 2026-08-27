const PERSONNEL_TYPES = new Set(["teacher", "staff"]);
const DUPLICATE_SCAN_WINDOW_SECONDS = 10 * 60;

function isPersonnelType(personType) {
  return PERSONNEL_TYPES.has(String(personType || "").toLowerCase());
}

function getPersonDisplayLabel(person, fallback = "") {
  if (!person) return fallback;

  if (isPersonnelType(person.person_type)) {
    return person.name || person.identifier || fallback;
  }

  return person.lrn
    || person.student_number
    || person.student_no
    || person.student_id
    || person.identifier
    || fallback;
}

function timeToSeconds(value) {
  const match = /^(\d{2}):(\d{2}):(\d{2})$/.exec(String(value || ""));
  if (!match) return null;

  const hours = Number(match[1]);
  const minutes = Number(match[2]);
  const seconds = Number(match[3]);
  if (hours > 23 || minutes > 59 || seconds > 59) return null;

  return (hours * 60 * 60) + (minutes * 60) + seconds;
}

function isDuplicateScan(person, existingScan, newTime) {
  if (!existingScan) return false;

  const existingSeconds = timeToSeconds(existingScan.time);
  const newSeconds = timeToSeconds(newTime);

  if (existingSeconds == null || newSeconds == null) {
    return true;
  }

  return Math.abs(newSeconds - existingSeconds) < DUPLICATE_SCAN_WINDOW_SECONDS;
}

function normalizeDirectoryRecord(record) {
  const personType = String(
    record.person_type || (record.teacher_id ? "teacher" : "student")
  ).toLowerCase();
  const personnel = isPersonnelType(personType);
  const personId = record.person_id ?? record.teacher_id ?? record.adviser_id ?? record.student_id;
  const rfidCardUid = String(record.rfid_card_uid ?? "").trim();
  const composedName = [record.first_name, record.middle_name, record.last_name]
    .filter(Boolean)
    .join(" ");
  const personName = record.name
    ?? record.full_name
    ?? record.teacher_name
    ?? composedName
    ?? null;
  const fallbackIdentifier = record.identifier
    ?? (personnel
      ? (record.teacher_id ?? record.adviser_id ?? record.person_id)
      : (record.lrn ?? record.student_number ?? record.student_no ?? record.student_id));
  const identifier = rfidCardUid || fallbackIdentifier;

  if (personId == null || identifier == null) {
    throw new Error("People directory contains a record without an ID or identifier");
  }

  return {
    person_type: personType,
    person_id: String(personId),
    identifier: String(identifier),
    rfid_card_uid: rfidCardUid || null,
    student_id: record.student_id == null ? null : String(record.student_id),
    teacher_id: (record.teacher_id ?? record.adviser_id) == null
      ? (personnel ? String(personId) : null)
      : String(record.teacher_id ?? record.adviser_id),
    lrn: record.lrn == null ? null : String(record.lrn),
    student_number: record.student_number == null
      ? (record.student_no == null ? null : String(record.student_no))
      : String(record.student_number),
    name: personName || null,
    profile_photo: record.profile_photo ?? null
  };
}

function buildAttendanceIdentity(scan) {
  return scan.teacher_id
    ? { teacher_id: scan.teacher_id }
    : { student_id: scan.student_id };
}

function buildAttendanceSyncRecord(scan) {
  if (!scan?.timestamp || !scan?.currentdate || !scan?.time) {
    throw new Error("Attendance record is missing its event ID, date, or time");
  }

  return {
    ...buildAttendanceIdentity(scan),
    currentdate: scan.currentdate,
    time: scan.time,
    event_id: scan.timestamp
  };
}

module.exports = {
  buildAttendanceIdentity,
  buildAttendanceSyncRecord,
  getPersonDisplayLabel,
  isDuplicateScan,
  isPersonnelType,
  normalizeDirectoryRecord
};
