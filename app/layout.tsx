import type { Metadata } from 'next';
import { DM_Sans, Manrope } from 'next/font/google';
import './globals.css';

const body = DM_Sans({ variable: '--body', subsets: ['latin'] });
const display = Manrope({ variable: '--display', subsets: ['latin'] });

export const metadata: Metadata = {
  metadataBase: new URL('https://zencraft.ph'),
  title: 'ZenCraft CSMS | Campus & Student Management System',
  description: 'ZenCraft CSMS brings student records, faculty, attendance, classes, and campus operations into one secure, easy-to-use platform.',
  icons: { icon: '/zencraft-logo.png' },
  openGraph: { title: 'ZenCraft CSMS | One campus. Clearly managed.', description: 'A modern campus and student management system with guided setup and transparent pricing.', type: 'website' },
  twitter: { card: 'summary', title: 'ZenCraft CSMS | One campus. Clearly managed.', description: 'A modern campus and student management system with guided setup and transparent pricing.' },
};

export default function RootLayout({ children }: Readonly<{children: React.ReactNode}>) {
  return <html lang="en"><body className={`${body.variable} ${display.variable}`}>{children}</body></html>;
}
