'use client';

import type { IScannerControls } from '@zxing/browser';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

type Student = { id: string; name: string; section: string };
type Scan = Student & { time: string; scannedAt: number };

const demoStudents: Record<string, Omit<Student, 'id'>> = {
  '2026-1042': { name: 'Mikaela Reyes', section: 'Grade 10 · Rizal' },
  '2026-1088': { name: 'Gabriel Santos', section: 'Grade 9 · Mabini' },
  '2026-1124': { name: 'Sofia Mendoza', section: 'Grade 11 · STEM A' },
};

function parseStudent(raw: string): Student {
  const clean = raw.trim();
  try {
    const parsed = JSON.parse(clean);
    if (parsed && (parsed.id || parsed.studentId)) {
      const id = String(parsed.id || parsed.studentId);
      return { id, name: String(parsed.name || demoStudents[id]?.name || 'Registered student'), section: String(parsed.section || parsed.grade || demoStudents[id]?.section || 'Student') };
    }
  } catch { /* QR may be a plain ID or URL. */ }

  try {
    const url = new URL(clean);
    const id = url.searchParams.get('id') || url.pathname.split('/').filter(Boolean).at(-1) || clean;
    return { id, name: url.searchParams.get('name') || demoStudents[id]?.name || 'Registered student', section: url.searchParams.get('section') || demoStudents[id]?.section || 'Student' };
  } catch { /* Plain student ID. */ }

  return { id: clean, name: demoStudents[clean]?.name || 'Registered student', section: demoStudents[clean]?.section || 'Student' };
}

function storageKey() {
  return `zencraft-attendance-${new Date().toISOString().slice(0, 10)}`;
}

export default function AttendanceScanner() {
  const videoRef = useRef<HTMLVideoElement>(null);
  const fileRef = useRef<HTMLInputElement>(null);
  const controlsRef = useRef<IScannerControls | null>(null);
  const cooldownRef = useRef('');
  const [cameraOn, setCameraOn] = useState(false);
  const [cameraError, setCameraError] = useState('');
  const [scans, setScans] = useState<Scan[]>([]);
  const [result, setResult] = useState<{ scan: Scan; duplicate: boolean } | null>(null);
  const [flash, setFlash] = useState(false);

  useEffect(() => {
    queueMicrotask(() => {
      try { setScans(JSON.parse(localStorage.getItem(storageKey()) || '[]')); } catch { setScans([]); }
    });
    if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => undefined);
    return () => controlsRef.current?.stop();
  }, []);

  const recordScan = useCallback((raw: string) => {
    if (!raw || cooldownRef.current === raw) return;
    cooldownRef.current = raw;
    window.setTimeout(() => { cooldownRef.current = ''; }, 2500);
    const student = parseStudent(raw);
    const previous: Scan[] = (() => {
      try { return JSON.parse(localStorage.getItem(storageKey()) || '[]'); } catch { return []; }
    })();
    const existing = previous.find(item => item.id === student.id);
    const scan: Scan = existing || { ...student, time: new Intl.DateTimeFormat('en-PH', { hour: 'numeric', minute: '2-digit' }).format(new Date()), scannedAt: Date.now() };
    const next = existing ? previous : [scan, ...previous];
    if (!existing) {
      localStorage.setItem(storageKey(), JSON.stringify(next));
      setScans(next);
    }
    setResult({ scan, duplicate: Boolean(existing) });
    if (navigator.vibrate) navigator.vibrate(existing ? 80 : [60, 40, 100]);
  }, []);

  const stopCamera = useCallback(() => {
    controlsRef.current?.stop();
    controlsRef.current = null;
    setCameraOn(false);
  }, []);

  const startCamera = useCallback(async () => {
    if (!videoRef.current) return;
    setCameraError('');
    setResult(null);
    try {
      const { BrowserQRCodeReader } = await import('@zxing/browser');
      const reader = new BrowserQRCodeReader(undefined, { delayBetweenScanAttempts: 160 });
      const controls = await reader.decodeFromConstraints(
        { audio: false, video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 1280 } } },
        videoRef.current,
        decoded => { if (decoded) recordScan(decoded.getText()); },
      );
      controlsRef.current = controls;
      setCameraOn(true);
    } catch {
      setCameraError('Camera access was blocked. Allow camera permission, or scan from a saved photo.');
      setCameraOn(false);
    }
  }, [recordScan]);

  async function scanPhoto(file?: File) {
    if (!file) return;
    setCameraError('');
    const url = URL.createObjectURL(file);
    try {
      const { BrowserQRCodeReader } = await import('@zxing/browser');
      const decoded = await new BrowserQRCodeReader().decodeFromImageUrl(url);
      recordScan(decoded.getText());
    } catch {
      setCameraError('No QR code was found in that photo. Try a sharper, closer image.');
    } finally {
      URL.revokeObjectURL(url);
      if (fileRef.current) fileRef.current.value = '';
    }
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

  const dateLabel = useMemo(() => new Intl.DateTimeFormat('en-PH', { weekday: 'long', month: 'long', day: 'numeric' }).format(new Date()), []);

  return (
    <main className="attendanceApp">
      <header className="topbar">
        <a className="scanBrand" href="#top" aria-label="ZenCraft Attendance home"><span className="logoCrop"><img src="/zencraft-logo.png" alt="" /></span><span>ZenCraft <b>Attendance</b></span></a>
        <button className="profile" aria-label="Open profile">MR</button>
      </header>

      <section className="scanShell" id="top">
        <div className="intro">
          <span className="eyebrow"><i /> LIVE ATTENDANCE</span>
          <h1>Scan student<br /><em>QR code</em></h1>
          <p>{dateLabel} · Morning entry</p>
        </div>

        <section className={`scanner ${cameraOn ? 'isLive' : ''}`} aria-label="QR code scanner">
          <video ref={videoRef} muted playsInline aria-label="Camera preview" />
          <div className="cameraPlaceholder">
            <span className="cameraIcon" aria-hidden="true">⌗</span>
            <h2>Ready to scan</h2>
            <p>Point your phone camera at the student’s QR code.</p>
          </div>
          {cameraOn && <div className="scanGuide" aria-hidden="true"><i /><i /><i /><i /><span /></div>}
          <div className="cameraActions">
            {!cameraOn ? <button className="primaryButton" onClick={startCamera}><span>▣</span> Open camera</button> : <><button className="roundButton" onClick={toggleFlash} aria-label="Toggle camera flash">{flash ? '☀' : 'ϟ'}</button><button className="stopButton" onClick={stopCamera}>Stop camera</button></>}
            <input ref={fileRef} className="fileInput" type="file" accept="image/*" capture="environment" onChange={event => scanPhoto(event.target.files?.[0])} />
            {!cameraOn && <button className="photoButton" onClick={() => fileRef.current?.click()}>Scan from photo</button>}
          </div>
        </section>

        {cameraError && <p className="errorMessage" role="alert"><span>!</span>{cameraError}</p>}

        <section className="todayPanel">
          <div className="todayHead"><div><span>TODAY</span><h2>{scans.length} student{scans.length === 1 ? '' : 's'} scanned</h2></div><div className="statusPill"><i /> Saved locally</div></div>
          <div className="scanList">
            {scans.length === 0 ? <div className="emptyState"><span>✓</span><p>Attendance records will appear here after the first scan.</p></div> : scans.slice(0, 4).map(scan => <article key={scan.id}><span className="avatar">{scan.name.split(' ').map(n => n[0]).slice(0, 2).join('')}</span><div><b>{scan.name}</b><small>{scan.section} · {scan.id}</small></div><time>{scan.time}</time><i>✓</i></article>)}
          </div>
        </section>
      </section>

      <footer className="appFooter"><span><i /> Camera ready</span><p>Student information stays on this device.</p></footer>

      {result && <div className="resultBackdrop" role="dialog" aria-modal="true" aria-label="Scan result" onClick={() => setResult(null)}><div className={`resultCard ${result.duplicate ? 'duplicate' : ''}`} onClick={event => event.stopPropagation()}><button className="closeResult" onClick={() => setResult(null)} aria-label="Close">×</button><span className="resultCheck">{result.duplicate ? '↻' : '✓'}</span><span className="resultLabel">{result.duplicate ? 'ALREADY SCANNED' : 'ATTENDANCE RECORDED'}</span><h2>{result.scan.name}</h2><p>{result.scan.section}<br />Student ID {result.scan.id}</p><div><span><small>TIME IN</small><b>{result.scan.time}</b></span><span><small>STATUS</small><b>{result.duplicate ? 'Present' : 'On time'}</b></span></div><button className="primaryButton" onClick={() => setResult(null)}>Scan next student</button></div></div>}
    </main>
  );
}
