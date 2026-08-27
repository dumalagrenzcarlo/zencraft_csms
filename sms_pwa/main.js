console.log("🔥 MAIN.JS LOADED:", __filename);
// main.js
const { app, BrowserWindow, ipcMain } = require("electron");
const { autoUpdater } = require("electron-updater");
const fs = require("fs");
const path = require("path");
const { pathToFileURL } = require("url");
const Database = require("better-sqlite3");
const {
  buildAttendanceSyncRecord,
  getPersonDisplayLabel,
  isDuplicateScan,
  normalizeDirectoryRecord
} = require("./src/attendance-utils");
const { buildUpdateFeedUrl } = require("./src/update-utils");

// ==================== Paths ====================
const dataFolder = app.getPath("userData");
const dbPath = path.join(dataFolder, "attendance.db");
const logoCacheFolder = path.join(dataFolder, "cache");
const logoCacheBasePath = path.join(logoCacheFolder, "school-logo");
const ATTENDANCE_RETENTION_DAYS = 90;
const ATTENDANCE_CLEANUP_BATCH_SIZE = 1000;
const ATTENDANCE_CLEANUP_INTERVAL_MS = 24 * 60 * 60 * 1000;
const UPDATE_CHECK_INTERVAL_MS = 6 * 60 * 60 * 1000;

const db = new Database(dbPath);
db.pragma("journal_mode = WAL");
db.pragma("synchronous = NORMAL");
db.pragma("busy_timeout = 5000");

// ==================== Tables ====================
db.prepare(`
  CREATE TABLE IF NOT EXISTS attendance (
    student_id INTEGER,
    teacher_id INTEGER,
    lrn TEXT,
    type TEXT,
    currentdate TEXT,
    time TEXT,
    synced INTEGER DEFAULT 0,
    timestamp TEXT PRIMARY KEY
  )
`).run();

const attendanceColumns = db.prepare(`PRAGMA table_info(attendance)`).all();
if (!attendanceColumns.some(column => column.name === "teacher_id")) {
  db.prepare(`ALTER TABLE attendance ADD COLUMN teacher_id INTEGER`).run();
}

db.prepare(`
  CREATE INDEX IF NOT EXISTS attendance_student_date_index
  ON attendance (student_id, currentdate)
`).run();

db.prepare(`
  CREATE INDEX IF NOT EXISTS attendance_teacher_date_index
  ON attendance (teacher_id, currentdate)
`).run();

db.prepare(`
  CREATE INDEX IF NOT EXISTS attendance_identifier_date_index
  ON attendance (lrn, currentdate)
`).run();

db.prepare(`
  CREATE INDEX IF NOT EXISTS attendance_unsynced_timestamp_index
  ON attendance (timestamp DESC)
  WHERE synced = 0
`).run();

db.prepare(`
  CREATE INDEX IF NOT EXISTS attendance_synced_date_index
  ON attendance (currentdate)
  WHERE synced = 1
`).run();

db.prepare(`
  CREATE TABLE IF NOT EXISTS students (
    student_id TEXT PRIMARY KEY,
    lrn TEXT UNIQUE,
    profile_photo TEXT
  )
`).run();

db.prepare(`
  CREATE TABLE IF NOT EXISTS people (
    person_type TEXT NOT NULL,
    person_id TEXT NOT NULL,
    identifier TEXT NOT NULL,
    rfid_card_uid TEXT,
    student_id TEXT,
    teacher_id TEXT,
    lrn TEXT,
    student_number TEXT,
    name TEXT,
    profile_photo TEXT,
    PRIMARY KEY (person_type, person_id)
  )
`).run();

const peopleColumns = db.prepare(`PRAGMA table_info(people)`).all();
if (!peopleColumns.some(column => column.name === "rfid_card_uid")) {
  db.prepare(`ALTER TABLE people ADD COLUMN rfid_card_uid TEXT`).run();
}
if (!peopleColumns.some(column => column.name === "lrn")) {
  db.prepare(`ALTER TABLE people ADD COLUMN lrn TEXT`).run();
}
if (!peopleColumns.some(column => column.name === "student_number")) {
  db.prepare(`ALTER TABLE people ADD COLUMN student_number TEXT`).run();
}

db.prepare(`
  CREATE INDEX IF NOT EXISTS people_identifier_index
  ON people (identifier)
`).run();

// Preserve previously downloaded student records for first launch after upgrading.
db.prepare(`
  INSERT OR IGNORE INTO people
    (person_type, person_id, identifier, rfid_card_uid, student_id, teacher_id, lrn, student_number, name, profile_photo)
  SELECT
    'student', student_id, lrn, NULL, student_id, NULL, lrn, NULL, NULL, profile_photo
  FROM students
`).run();

db.prepare(`
  UPDATE people
  SET lrn = (
    SELECT students.lrn FROM students
    WHERE students.student_id = people.student_id
  )
  WHERE person_type = 'student'
    AND lrn IS NULL
    AND EXISTS (
      SELECT 1 FROM students
      WHERE students.student_id = people.student_id
    )
`).run();

// ==================== Attendance Helpers ====================
function saveScan(scanData) {
  const timestamp = scanData.timestamp || new Date().toISOString();
  const stmt = db.prepare(`
    INSERT OR REPLACE INTO attendance
    (student_id, teacher_id, lrn, type, currentdate, time, synced, timestamp)
    VALUES (@student_id, @teacher_id, @lrn, @type, @currentdate, @time, @synced, @timestamp)
  `);

  stmt.run({
    student_id: scanData.student_id || null,
    teacher_id: scanData.teacher_id || null,
    lrn: scanData.lrn || null,
    type: scanData.type || null,
    currentdate: scanData.currentdate || null,
    time: scanData.time || null,
    synced: scanData.synced ? 1 : 0,
    timestamp
  });
}

function getRecordedScan(person, currentdate) {
  if (person.teacher_id != null) {
    return db.prepare(`
      SELECT * FROM attendance
      WHERE (teacher_id = ? OR lrn = ?) AND currentdate = ?
      ORDER BY time DESC
      LIMIT 1
    `).get(person.teacher_id, person.identifier, currentdate);
  }

  if (person.student_id != null) {
    return db.prepare(`
      SELECT * FROM attendance
      WHERE (student_id = ? OR lrn = ?) AND currentdate = ?
      ORDER BY time DESC
      LIMIT 1
    `).get(person.student_id, person.identifier, currentdate);
  }

  return db.prepare(`
    SELECT * FROM attendance
    WHERE lrn = ? AND currentdate = ?
    ORDER BY time DESC
    LIMIT 1
  `).get(person.identifier, currentdate);
}

const recordScan = db.transaction((person, scanData) => {
  const existingScan = getRecordedScan(person, scanData.currentdate);
  if (isDuplicateScan(person, existingScan, scanData.time)) {
    return { recorded: false, existingScan };
  }

  saveScan({
    student_id: person.student_id,
    teacher_id: person.teacher_id,
    lrn: person.identifier,
    type: scanData.type,
    currentdate: scanData.currentdate,
    time: scanData.time,
    synced: false,
    timestamp: scanData.timestamp || new Date().toISOString()
  });

  return { recorded: true };
});

function getUnsynced() {
  console.log("Fetching unsynced scans from DB...");
  const rows = db.prepare(`SELECT * FROM attendance WHERE synced = 0 ORDER BY timestamp DESC`).all();
  console.log(`Found ${rows.length} unsynced scans.`);
  return rows;
}

const markScanSyncedStmt = db.prepare(
  `UPDATE attendance SET synced = 1 WHERE timestamp = ?`
);

function markScanSynced(timestamp) {
  markScanSyncedStmt.run(timestamp);
}

let attendanceCleanupInProgress = false;

function cleanupSyncedAttendance() {
  if (attendanceCleanupInProgress) return;
  attendanceCleanupInProgress = true;

  const deleteBatch = db.prepare(`
    DELETE FROM attendance
    WHERE timestamp IN (
      SELECT timestamp
      FROM attendance
      WHERE synced = 1
        AND currentdate < date('now', ?)
      ORDER BY currentdate
      LIMIT ?
    )
  `);
  const retentionModifier = `-${ATTENDANCE_RETENTION_DAYS} days`;
  let deletedTotal = 0;

  const deleteNextBatch = () => {
    try {
      const result = deleteBatch.run(retentionModifier, ATTENDANCE_CLEANUP_BATCH_SIZE);
      deletedTotal += result.changes;

      if (result.changes === ATTENDANCE_CLEANUP_BATCH_SIZE) {
        setImmediate(deleteNextBatch);
        return;
      }

      if (deletedTotal > 0) {
        console.log(
          `Removed ${deletedTotal} synced attendance records older than ${ATTENDANCE_RETENTION_DAYS} days.`
        );
      }
      db.pragma("optimize");
      attendanceCleanupInProgress = false;
    } catch (err) {
      attendanceCleanupInProgress = false;
      console.error("Attendance cleanup error:", err);
    }
  };

  deleteNextBatch();
}

function getScansForLastNDays(days) {
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - days + 1);
  const startStr = startDate.toISOString().slice(0, 10);

  return db.prepare(`
    SELECT * FROM attendance WHERE currentdate >= ? ORDER BY timestamp DESC
  `).all(startStr);
}

function getPersonByIdentifier(identifier) {
  const stmt = db.prepare(`
    SELECT * FROM people
    WHERE identifier = ?
       OR rfid_card_uid = ?
       OR lrn = ?
       OR student_number = ?
  `);
  const value = String(identifier);
  const results = stmt.all(value, value, value, value);

  if (results.length > 1) {
    return { error: "Identifier matches more than one person. Please contact an administrator." };
  }

  return results[0];
}

function serializePerson(person) {
  return {
    person_type: person.person_type,
    person_id: person.person_id,
    student_id: person.student_id,
    teacher_id: person.teacher_id,
    identifier: person.identifier,
    lrn: person.lrn,
    student_number: person.student_number,
    name: person.name,
    display_label: getPersonDisplayLabel(person),
    profile_photo: person.profile_photo
  };
}

function getLogoExtension(contentType, logoUrl) {
  const byContentType = {
    "image/jpeg": ".jpg",
    "image/jpg": ".jpg",
    "image/png": ".png",
    "image/gif": ".gif",
    "image/webp": ".webp",
    "image/svg+xml": ".svg",
    "image/x-icon": ".ico"
  };

  if (contentType) {
    const extension = byContentType[contentType.split(";")[0].trim().toLowerCase()];
    if (extension) return extension;
  }

  try {
    const extension = path.extname(new URL(logoUrl).pathname);
    if (extension) return extension;
  } catch {
    // Fall back below.
  }

  return ".png";
}

function removeCachedSchoolLogo() {
  if (!fs.existsSync(logoCacheFolder)) return;

  for (const fileName of fs.readdirSync(logoCacheFolder)) {
    if (fileName.startsWith("school-logo.")) {
      fs.rmSync(path.join(logoCacheFolder, fileName), { force: true });
    }
  }
}

function getCachedSchoolLogoUrl() {
  if (!fs.existsSync(logoCacheFolder)) return null;

  const cachedLogo = fs
    .readdirSync(logoCacheFolder)
    .find(fileName => fileName.startsWith("school-logo."));

  return cachedLogo ? pathToFileURL(path.join(logoCacheFolder, cachedLogo)).href : null;
}

async function syncAttendanceRecords(scansToSync, settings) {
  let count = 0;
  const serverUrl = settings?.server?.url;
  const authCode = settings?.server?.authCode;

  if (!serverUrl || !authCode || scansToSync.length === 0) {
    return 0;
  }

  console.log("Scans to sync:", scansToSync);

  // Laravel expects array of records (same as before)
  const payload = scansToSync.map(buildAttendanceSyncRecord);

  console.log("Payload:", payload);

  try {
    const res = await fetch(`${serverUrl}/api/attendance/sync`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",

        // âœ… NEW: use header auth instead of query string
        "X-API-AUTHCODE": authCode
      },
      body: JSON.stringify(payload)
    });

    if (res.ok) {
      const data = await res.json();

      console.log("Sync response:", data);

      const inserted = Number(data?.inserted);
      const skipped = Number(data?.skipped);
      const hasAggregateAcknowledgement =
        Number.isFinite(inserted)
        && Number.isFinite(skipped)
        && inserted + skipped === scansToSync.length;

      if (hasAggregateAcknowledgement) {
        const markBatchSynced = db.transaction((scans) => {
          for (const scan of scans) {
            markScanSynced(scan.timestamp);
          }
        });
        markBatchSynced(scansToSync);
        count = scansToSync.length;
      } else {
        console.error(
          "Sync response did not acknowledge the complete batch; local records remain queued.",
          {
            submitted: scansToSync.length,
            inserted: data?.inserted,
            skipped: data?.skipped
          }
        );
      }

      console.log(`Inserted: ${data?.inserted}, Skipped: ${data?.skipped}`);
    } else {
      const errText = await res.text();
      console.log("Server returned error:", res.status, errText);
    }
  } catch (err) {
    console.log("Offline â€” queued for sync", err);
  }

  return count;
}
// ==================== IPC Handlers ====================
ipcMain.handle("save-scan", (event, scanData) => {
  try {

    const person = getPersonByIdentifier(scanData.identifier ?? scanData.lrn);

    if (person?.error) {
      return { success: false, message: person.error };
    }

    if (person == undefined) {
      console.log("Invalid identifier. Student, teacher, or staff member not found.");
      return { success: false, message: "Invalid identifier. Student, teacher, or staff member not found." };
    }

    console.log("Person found:", person);

    const scanResult = recordScan(person, scanData);

    if (!scanResult.recorded) {
      console.log(`Already Recorded: ${person.identifier} on ${scanData.currentdate}`);
      return {
        success: false,
        duplicate: true,
        message: "Already Recorded",
        existingScan: scanResult.existingScan,
        person: serializePerson(person)
      };
    }

    return {
      success: true,
      person: serializePerson(person)
    };

  } catch (err) {
    console.error("Save scan error:", err);
    return { success: false, message: "System error." };
  }
});

ipcMain.handle("get-unsynced", () => getUnsynced());

ipcMain.handle("mark-synced", (event, timestamp) => {
  markScanSynced(timestamp);
  return true;
});

ipcMain.handle("get-cached-school-logo", () => getCachedSchoolLogoUrl());

ipcMain.handle("clear-cached-school-logo", () => {
  removeCachedSchoolLogo();
  return true;
});

ipcMain.handle("cache-school-logo", async (event, serverUrl, logoPath) => {
  try {
    if (!serverUrl || !logoPath) {
      removeCachedSchoolLogo();
      return { success: false, url: null };
    }

    const normalizedLogoPath = String(logoPath).replace(/\\/g, "/").replace(/^\/+/, "");
    const isAbsoluteLogoUrl = /^https?:\/\//i.test(String(logoPath));
    const logoCandidates = isAbsoluteLogoUrl
      ? [String(logoPath)]
      : normalizedLogoPath.startsWith("uploads/")
        || normalizedLogoPath.startsWith("storage/")
        || normalizedLogoPath.startsWith("branding/")
        ? [new URL(`/${normalizedLogoPath}`, serverUrl).href]
        : [
            new URL(`/uploads/${normalizedLogoPath}`, serverUrl).href,
            new URL(`/${normalizedLogoPath}`, serverUrl).href,
            new URL(`/storage/${normalizedLogoPath}`, serverUrl).href
          ];

    let response = null;
    let logoUrl = null;
    for (const candidate of logoCandidates) {
      const candidateResponse = await fetch(candidate, {
        headers: {
          "Cache-Control": "no-cache",
          "Pragma": "no-cache"
        }
      });

      if (candidateResponse.ok) {
        response = candidateResponse;
        logoUrl = candidate;
        break;
      }
    }

    if (!response || !logoUrl) {
      throw new Error(`Failed to fetch logo from: ${logoCandidates.join(", ")}`);
    }

    const contentType = response.headers.get("content-type") || "";
    if (!contentType.toLowerCase().startsWith("image/")) {
      throw new Error(`Logo response is not an image (${contentType || "unknown content type"})`);
    }
    const extension = getLogoExtension(contentType, logoUrl);
    const logoPathOnDisk = `${logoCacheBasePath}${extension}`;
    const buffer = Buffer.from(await response.arrayBuffer());

    fs.mkdirSync(logoCacheFolder, { recursive: true });
    removeCachedSchoolLogo();
    fs.writeFileSync(logoPathOnDisk, buffer);

    return {
      success: true,
      url: `${pathToFileURL(logoPathOnDisk).href}?updated=${Date.now()}`
    };
  } catch (err) {
    console.error("Error caching school logo:", err);
    return {
      success: false,
      url: getCachedSchoolLogoUrl(),
      message: err.message
    };
  }
});

async function updatePeople(settings) {
  try {
    const serverUrl = settings?.server?.url;
    const authCode = settings?.server?.authCode;
    if (!serverUrl || !authCode) return 0;

    const response = await fetch(`${serverUrl}/api/autosync`, {
      headers: { "X-API-AUTHCODE": authCode }
    });
    if (!response.ok) throw new Error("Failed to fetch student, teacher, and staff data");

    const records = await response.json();
    if (!Array.isArray(records)) throw new Error("Invalid people directory response");

    const insertStmt = db.prepare(`
            INSERT OR REPLACE INTO people
            (person_type, person_id, identifier, rfid_card_uid, student_id, teacher_id, lrn, student_number, name, profile_photo)
            VALUES
            (@person_type, @person_id, @identifier, @rfid_card_uid, @student_id, @teacher_id, @lrn, @student_number, @name, @profile_photo)
        `);

    const replacePeople = db.transaction((people) => {
      db.prepare(`DELETE FROM people`).run();

      for (const record of people) {
        insertStmt.run(normalizeDirectoryRecord(record));
      }
    });

    replacePeople(records);

    return records.length;
  } catch (err) {
    console.error("Error updating people from API:", err);
    return 0;
  }
}

ipcMain.handle("update-people", (event, settings) => updatePeople(settings));
// Keep the old IPC channel available for older renderer builds.
ipcMain.handle("update-students", (event, settings) => updatePeople(settings));

// resync scans using settings passed from renderer
ipcMain.handle("resync-data", async (event, days, settings) => {
  console.log(`Resyncing data for last ${days} days with settings:`, settings);

  const scansToSync = getScansForLastNDays(days);
  return syncAttendanceRecords(scansToSync, settings);
});

ipcMain.handle("sync-unsynced", async (event, settings) => {
  console.log("Syncing queued unsynced scans...");
  return syncAttendanceRecords(getUnsynced(), settings);
});

// ==================== Create Window ====================
function createWindow() {
  const win = new BrowserWindow({
    width: 1400,
    height: 1000,
    icon: path.join(__dirname, 'assets/icons/icon.ico'),
    webPreferences: {
      preload: path.join(__dirname, "src", "preload.js"),
      contextIsolation: true,
      nodeIntegration: false
    }
  });
  win.setMenuBarVisibility(false); // hides menu
  win.maximize();
  win.loadFile(path.join(__dirname, "src", "scanner.html"));
}

// ==================== Automatic Updates ====================
let automaticUpdatesStarted = false;
let updateFeedConfigured = false;
let updateCheckInProgress = false;

async function checkForUpdates() {
  if (!automaticUpdatesStarted || !updateFeedConfigured || updateCheckInProgress) {
    return;
  }

  updateCheckInProgress = true;
  try {
    await autoUpdater.checkForUpdates();
  } catch (error) {
    console.error("Update check failed:", error);
  } finally {
    updateCheckInProgress = false;
  }
}

async function configureAutomaticUpdates(settings) {
  if (!app.isPackaged) {
    return { enabled: false, reason: "Automatic updates only run in packaged builds." };
  }

  const feedUrl = buildUpdateFeedUrl(settings?.server?.url);
  const authCode = settings?.server?.authCode;

  autoUpdater.requestHeaders = authCode
    ? { "X-API-AUTHCODE": String(authCode) }
    : {};
  autoUpdater.setFeedURL({
    provider: "generic",
    url: feedUrl
  });
  updateFeedConfigured = true;

  console.log(`Automatic update feed configured from ${feedUrl}`);
  await checkForUpdates();

  return { enabled: true, feedUrl };
}

ipcMain.handle("configure-automatic-updates", (event, settings) =>
  configureAutomaticUpdates(settings)
);

function startAutomaticUpdates() {
  if (!app.isPackaged) {
    console.log("Automatic updates disabled for development builds.");
    return;
  }

  autoUpdater.autoDownload = true;
  autoUpdater.autoInstallOnAppQuit = true;
  automaticUpdatesStarted = true;

  autoUpdater.on("checking-for-update", () => {
    console.log("Checking for an SMS Attendance Scanner update...");
  });

  autoUpdater.on("update-available", info => {
    console.log(`Downloading SMS Attendance Scanner ${info.version}...`);
  });

  autoUpdater.on("update-not-available", () => {
    console.log("SMS Attendance Scanner is up to date.");
  });

  autoUpdater.on("update-downloaded", info => {
    console.log(
      `SMS Attendance Scanner ${info.version} downloaded; it will install when the app exits.`
    );
  });

  autoUpdater.on("error", error => {
    console.error("Automatic update failed:", error);
  });

  const initialUpdateTimer = setTimeout(checkForUpdates, 15_000);
  initialUpdateTimer.unref();

  const recurringUpdateTimer = setInterval(
    checkForUpdates,
    UPDATE_CHECK_INTERVAL_MS
  );
  recurringUpdateTimer.unref();
}

app.whenReady().then(() => {
  createWindow();
  startAutomaticUpdates();
  setImmediate(cleanupSyncedAttendance);
  const attendanceCleanupTimer = setInterval(
    cleanupSyncedAttendance,
    ATTENDANCE_CLEANUP_INTERVAL_MS
  );
  attendanceCleanupTimer.unref();

  app.on("activate", () => {
    if (BrowserWindow.getAllWindows().length === 0) createWindow();
  });
});

app.on("window-all-closed", () => {
  if (process.platform !== "darwin") app.quit();
});
