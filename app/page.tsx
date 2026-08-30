'use client';

import type { IScannerControls } from '@zxing/browser';
import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from 'react';

type Teacher = { id: string; name: string; email: string; school_name?: string };
type SchoolClass = { id: string; name: string; section?: string; subject?: string; schedule?: string; student_count?: number };
type AttendanceRecord = { id: string; lrn: string; student_name: string; class_name: string; recorded_at: string; status: string; class_attendance_count?: number };
type ScanResult = { kind: 'success' | 'duplicate' | 'error'; title: string; message: string; record?: AttendanceRecord };
type InstallPromptEvent = Event & { prompt: () => Promise<void>; userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }> };

const API_ROOT = (process.env.NEXT_PUBLIC_SMS_API_URL || '').replace(/\/$/, '');

function apiUrl(path: string) {
  return `${API_ROOT}${path}`;
}

async function apiRequest<T>(path: string, options: RequestInit = {}, token?: string): Promise<T> {
  const response = await fetch(apiUrl(path), {
    ...options,
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers,
    },
  });
  const body = await response.json().catch(() => ({}));
  if (!response.ok) {
    const error = new Error(body.message || 'The SMS API could not complete this request.') as Error & { status?: number; body?: Record<string, unknown> };
    error.status = response.status;
    error.body = body;
    throw error;
  }
  return body as T;
}

function extractLrn(raw: string): string | null {
  const clean = raw.trim();
  let value = clean;
  try {
    const parsed = JSON.parse(clean);
    value = String(parsed.lrn || parsed.LRN || '');
  } catch { /* A student QR can also contain a URL or plain LRN. */ }
  if (value === clean) {
    try { value = new URL(clean).searchParams.get('lrn') || ''; } catch { /* Plain LRN. */ }
  }
  const digits = value.replace(/\D/g, '');
  return /^\d{12}$/.test(digits) ? digits : null;
}

function initials(name: string) {
  return name.split(/\s+/).filter(Boolean).map(part => part[0]).slice(0, 2).join('').toUpperCase();
}

export default function AttendanceScanner() {
  const videoRef = useRef<HTMLVideoElement>(null);
  const controlsRef = useRef<IScannerControls | null>(null);
  const scanLockRef = useRef(false);
  const [teacher, setTeacher] = useState<Teacher | null>(null);
  const [token, setToken] = useState('');
  const [classes, setClasses] = useState<SchoolClass[]>([]);
  const [selectedClassId, setSelectedClassId] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loginError, setLoginError] = useState('');
  const [isLoggingIn, setIsLoggingIn] = useState(false);
  const [cameraOn, setCameraOn] = useState(false);
  const [cameraError, setCameraError] = useState('');
  const [isRecording, setIsRecording] = useState(false);
  const [result, setResult] = useState<ScanResult | null>(null);
  const [flash, setFlash] = useState(false);
  const [installPrompt, setInstallPrompt] = useState<InstallPromptEvent | null>(null);
  const [showInstallHelp, setShowInstallHelp] = useState(false);
  const [sessionCount, setSessionCount] = useState(0);

  useEffect(() => {
    const onInstall = (event: Event) => { event.preventDefault(); setInstallPrompt(event as InstallPromptEvent); };
    window.addEventListener('beforeinstallprompt', onInstall);
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    return () => { window.removeEventListener('beforeinstallprompt', onInstall); controlsRef.current?.stop(); };
  }, []);

  const selectedClass = useMemo(() => classes.find(item => item.id === selectedClassId) || null, [classes, selectedClassId]);
  const dateLabel = useMemo(() => new Intl.DateTimeFormat('en-PH', { weekday: 'long', month: 'long', day: 'numeric' }).format(new Date()), []);

  async function login(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setLoginError('');
    setIsLoggingIn(true);
    try {
      const auth = await apiRequest<{ teacher: Teacher; access_token?: string }>('/api/mobile/auth/login', {
        method: 'POST',
        body: JSON.stringify({ email, password, device_name: 'ZenCraft Attendance PWA' }),
      });
      const accessToken = auth.access_token || '';
      const classResponse = await apiRequest<{ classes: SchoolClass[] }>('/api/mobile/teacher/classes', {}, accessToken);
      setTeacher(auth.teacher);
      setToken(accessToken);
      setClasses(classResponse.classes || []);
      setSelectedClassId(classResponse.classes?.length === 1 ? classResponse.classes[0].id : '');
      setPassword('');
    } catch (error) {
      const status = (error as Error & { status?: number }).status;
      setLoginError(status === 401 ? 'Incorrect email or password.' : 'We could not connect to your school. Check your connection and try again.');
    } finally { setIsLoggingIn(false); }
  }

  const recordAttendance = useCallback(async (raw: string) => {
    if (scanLockRef.current || !selectedClassId) return;
    const lrn = extractLrn(raw);
    if (!lrn) {
      scanLockRef.current = true;
      setResult({ kind: 'error', title: 'Invalid student QR', message: 'This code does not contain a valid 12-digit LRN.' });
      if (navigator.vibrate) navigator.vibrate(90);
      window.setTimeout(() => { scanLockRef.current = false; }, 1800);
      return;
    }
    scanLockRef.current = true;
    setIsRecording(true);
    setCameraError('');
    try {
      const response = await apiRequest<{ attendance: AttendanceRecord }>('/api/mobile/attendance', {
        method: 'POST',
        body: JSON.stringify({ lrn, class_id: selectedClassId, scanned_at: new Date().toISOString(), source: 'teacher_pwa' }),
      }, token);
      setResult({ kind: 'success', title: 'Attendance recorded', message: `${response.attendance.student_name} is marked ${response.attendance.status.toLowerCase()}.`, record: response.attendance });
      setSessionCount(count => count + 1);
      if (navigator.vibrate) navigator.vibrate([55, 35, 95]);
    } catch (error) {
      const apiError = error as Error & { status?: number; body?: Record<string, unknown> };
      if (apiError.status === 409) {
        const attendance = apiError.body?.attendance as AttendanceRecord | undefined;
        setResult({ kind: 'duplicate', title: 'Already recorded', message: apiError.message, record: attendance });
      } else if (apiError.status === 404) {
        setResult({ kind: 'error', title: 'Student not found', message: 'This LRN is not enrolled in the selected class.' });
      } else if (apiError.status === 401) {
        stopCamera();
        setTeacher(null);
        setToken('');
        setResult(null);
        setLoginError('Your session expired. Please sign in again.');
      } else {
        setResult({ kind: 'error', title: 'Not recorded', message: 'The SMS could not save attendance. No local copy was created—please scan again.' });
      }
      if (navigator.vibrate) navigator.vibrate(100);
    } finally {
      setIsRecording(false);
      window.setTimeout(() => { scanLockRef.current = false; }, 1800);
    }
  }, [selectedClassId, token]);

  const startCamera = useCallback(async () => {
    if (!videoRef.current || !selectedClassId) return;
    setCameraError('');
    setResult(null);
    try {
      const { BrowserQRCodeReader } = await import('@zxing/browser');
      const reader = new BrowserQRCodeReader(undefined, { delayBetweenScanAttempts: 160 });
      controlsRef.current = await reader.decodeFromConstraints(
        { audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 1280 } } },
        videoRef.current,
        decoded => { if (decoded) void recordAttendance(decoded.getText()); },
      );
      setCameraOn(true);
    } catch {
      setCameraError('Camera access was blocked. Allow camera permission in your browser settings, then try again.');
      setCameraOn(false);
    }
  }, [recordAttendance, selectedClassId]);

  function stopCamera() {
    controlsRef.current?.stop();
    controlsRef.current = null;
    setCameraOn(false);
    setFlash(false);
  }

  async function toggleFlash() {
    const stream = videoRef.current?.srcObject as MediaStream | null;
    const track = stream?.getVideoTracks()[0];
    if (!track) return;
    try {
      const next = !flash;
      await track.applyConstraints({ advanced: [{ torch: next } as MediaTrackConstraintSet] });
      setFlash(next);
    } catch { setCameraError('Flash is not available on this camera.'); }
  }

  async function installApp() {
    if (installPrompt) {
      await installPrompt.prompt();
      await installPrompt.userChoice;
      setInstallPrompt(null);
    } else { setShowInstallHelp(true); }
  }

  async function logout() {
    stopCamera();
    try { await apiRequest('/api/mobile/auth/logout', { method: 'POST' }, token); } catch { /* The local session still ends. */ }
    setTeacher(null);
    setToken('');
    setClasses([]);
    setSelectedClassId('');
    setSessionCount(0);
    setResult(null);
  }

  if (!teacher) {
    return <main className="loginPage">
      <div className="loginBrand"><span className="logoCrop"><img src="/zencraft-logo.png" alt="" /></span><span>ZenCraft <b>Attendance</b></span></div>
      <section className="loginCard">
        <div className="loginArt" aria-hidden="true"><div className="artPhone"><span>STUDENT QR</span><b>▦</b><i /></div><span className="artDot d1" /><span className="artDot d2" /><span className="artRing" /></div>
        <div className="loginCopy">
          <span className="eyebrow"><i /> SECURE TEACHER ACCESS</span>
          <h1>Welcome<br /><em>back.</em></h1>
          <p>Sign in with your school account to scan attendance for your classes.</p>
          <form onSubmit={login}>
            <label>School email<input type="email" value={email} onChange={event => setEmail(event.target.value)} placeholder="teacher@school.edu.ph" autoComplete="username" required /></label>
            <label>Password<input type="password" value={password} onChange={event => setPassword(event.target.value)} placeholder="Enter your password" autoComplete="current-password" required /></label>
            {loginError && <p className="formError" role="alert"><span>!</span>{loginError}</p>}
            <button className="primaryButton loginButton" disabled={isLoggingIn}>{isLoggingIn ? <><i className="spinner" /> Signing in…</> : <>Sign in to scanner <span>→</span></>}</button>
          </form>
          <button className="installLink" onClick={installApp}><span>⇩</span> Install attendance app</button>
        </div>
      </section>
      <footer className="loginFooter"><span>Powered by ZenCraft CSMS</span><span>Secure · API connected · No offline records</span></footer>
      {showInstallHelp && <InstallHelp onClose={() => setShowInstallHelp(false)} />}
    </main>;
  }

  return <main className="attendanceApp">
    <header className="topbar">
      <a className="scanBrand" href="#top" aria-label="ZenCraft Attendance home"><span className="logoCrop"><img src="/zencraft-logo.png" alt="" /></span><span>ZenCraft <b>Attendance</b></span></a>
      <div className="headerActions"><button className="installButton" onClick={installApp}>⇩ <span>Install app</span></button><button className="profileButton" onClick={logout} aria-label="Sign out"><span>{initials(teacher.name)}</span><i><b>{teacher.name}</b><small>Sign out</small></i></button></div>
    </header>

    <section className="scanShell" id="top">
      <div className="scannerHeading">
        <div><span className="eyebrow"><i /> LIVE ATTENDANCE</span><h1>Scan student <em>QR code</em></h1><p>{dateLabel} · {teacher.school_name || 'Teacher attendance'}</p></div>
        {sessionCount > 0 && <div className="sessionCount"><b>{sessionCount}</b><span>recorded<br />this session</span></div>}
      </div>

      <section className="classPanel">
        <div className="classIcon">▤</div>
        <div className="classControl">
          <label htmlFor="class-select">CLASS TO RECORD</label>
          {classes.length > 1 ? <div className="selectWrap"><select id="class-select" value={selectedClassId} onChange={event => { stopCamera(); setResult(null); setSelectedClassId(event.target.value); }}><option value="">Select a class…</option>{classes.map(item => <option value={item.id} key={item.id}>{item.name}{item.section ? ` · ${item.section}` : ''}</option>)}</select><span>⌄</span></div> : selectedClass ? <strong>{selectedClass.name}{selectedClass.section ? ` · ${selectedClass.section}` : ''}</strong> : <strong>No assigned classes</strong>}
          {selectedClass && <small>{[selectedClass.subject, selectedClass.schedule, selectedClass.student_count ? `${selectedClass.student_count} students` : ''].filter(Boolean).join(' · ')}</small>}
        </div>
        {selectedClass && <span className="apiStatus"><i /> API connected</span>}
      </section>

      {!selectedClassId ? <section className="selectEmpty"><span>⌄</span><h2>{classes.length ? 'Select a class to begin' : 'No classes available'}</h2><p>{classes.length ? 'Attendance will be recorded under the class you choose.' : 'Ask your administrator to assign a class to your teacher account.'}</p></section> : <>
        <section className={`scanner ${cameraOn ? 'isLive' : ''}`} aria-label="LRN QR code scanner">
          <video ref={videoRef} muted playsInline aria-label="Camera preview" />
          <div className="cameraPlaceholder"><span className="cameraIcon" aria-hidden="true">⌗</span><h2>Ready to scan</h2><p>Only the student LRN is read. Attendance is sent directly to the SMS.</p></div>
          {cameraOn && <div className="scanGuide" aria-hidden="true"><i /><i /><i /><i /><span /></div>}
          {isRecording && <div className="recordingOverlay"><i className="spinner" /><b>Recording attendance…</b><span>Sending LRN securely to the SMS</span></div>}
          <div className="cameraActions">{!cameraOn ? <button className="primaryButton" onClick={startCamera}><span>▣</span> Open camera</button> : <><button className="roundButton" onClick={toggleFlash} aria-label="Toggle camera flash">{flash ? '☀' : 'ϟ'}</button><button className="stopButton" onClick={stopCamera}>Stop camera</button></>}</div>
        </section>
        {cameraError && <p className="errorMessage" role="alert"><span>!</span>{cameraError}</p>}
        <div className="privacyNote"><span>⌁</span><p><b>API-only attendance.</b> The QR scanner sends the LRN to your school’s SMS. No attendance records are stored on this device.</p></div>
      </>}
    </section>

    <footer className="appFooter"><span><i /> {selectedClass ? 'Ready to record' : 'Waiting for class'}</span><p>Secure connection to {teacher.school_name || 'your tenant SMS'}</p></footer>

    {result && <ResultCard result={result} onClose={() => setResult(null)} />}
    {showInstallHelp && <InstallHelp onClose={() => setShowInstallHelp(false)} />}
  </main>;
}

function ResultCard({ result, onClose }: { result: ScanResult; onClose: () => void }) {
  return <div className="resultBackdrop" role="dialog" aria-modal="true" aria-label="Scan result" onClick={onClose}><div className={`resultCard ${result.kind}`} onClick={event => event.stopPropagation()}><button className="closeResult" onClick={onClose} aria-label="Close">×</button><span className="resultCheck">{result.kind === 'success' ? '✓' : result.kind === 'duplicate' ? '↻' : '!'}</span><span className="resultLabel">{result.kind === 'success' ? 'SAVED TO SMS' : result.kind === 'duplicate' ? 'NO NEW RECORD' : 'ATTENDANCE NOT SAVED'}</span><h2>{result.title}</h2><p>{result.message}</p>{result.record && <div className="recordDetails"><span><small>LRN</small><b>{result.record.lrn}</b></span><span><small>TIME</small><b>{new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: '2-digit' }).format(new Date(result.record.recorded_at))}</b></span></div>}<button className="primaryButton" onClick={onClose}>{result.kind === 'error' ? 'Try another scan' : 'Scan next student'}</button></div></div>;
}

function InstallHelp({ onClose }: { onClose: () => void }) {
  return <div className="resultBackdrop" role="dialog" aria-modal="true" aria-label="Install attendance app" onClick={onClose}><div className="installCard" onClick={event => event.stopPropagation()}><button className="closeResult" onClick={onClose} aria-label="Close">×</button><span className="installIcon">⇩</span><span className="resultLabel">INSTALL ON THIS PHONE</span><h2>Keep the scanner one tap away.</h2><ol><li><b>iPhone or iPad</b><span>Tap Share in Safari, then “Add to Home Screen”.</span></li><li><b>Android</b><span>Open the browser menu, then choose “Install app”.</span></li></ol><button className="primaryButton" onClick={onClose}>Got it</button></div></div>;
}
