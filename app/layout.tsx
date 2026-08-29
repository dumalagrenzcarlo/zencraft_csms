import type { Metadata, Viewport } from 'next';
import { DM_Sans, Manrope } from 'next/font/google';
import './globals.css';

const body = DM_Sans({ variable: '--body', subsets: ['latin'] });
const display = Manrope({ variable: '--display', subsets: ['latin'] });

export const viewport: Viewport = { themeColor: '#174f45', width: 'device-width', initialScale: 1, viewportFit: 'cover' };

export const metadata: Metadata = {
  metadataBase: new URL('https://sms-moonlight.dumalagrenzcarlo.chatgpt.site'),
  title: 'ZenCraft Attendance Scanner',
  description: 'Scan student QR codes and record attendance instantly from any phone.',
  applicationName: 'ZenCraft Attendance',
  manifest: '/manifest.webmanifest',
  appleWebApp: { capable: true, statusBarStyle: 'black-translucent', title: 'Attendance' },
  icons: { icon: '/icon-192.png', apple: '/icon-192.png' },
  openGraph: { title: 'ZenCraft Attendance Scanner', description: 'Fast, camera-first student attendance scanning.', type: 'website', images: ['/og.png'] },
  twitter: { card: 'summary_large_image', title: 'ZenCraft Attendance Scanner', description: 'Fast, camera-first student attendance scanning.', images: ['/og.png'] },
};

export default function RootLayout({ children }: Readonly<{children: React.ReactNode}>) {
  return <html lang="en"><body className={`${body.variable} ${display.variable}`}>{children}</body></html>;
}
