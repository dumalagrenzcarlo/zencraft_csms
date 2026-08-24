import type { Metadata } from 'next';
import { DM_Sans, Manrope } from 'next/font/google';
import './globals.css';

const body = DM_Sans({ variable: '--body', subsets: ['latin'] });
const display = Manrope({ variable: '--display', subsets: ['latin'] });

export const metadata: Metadata = {
  metadataBase: new URL('https://smsmoonlight.com'),
  title: 'SMS Moonlight | School Management, Beautifully Simplified',
  description: 'SMS Moonlight by ZenCraft Web Services—modern school management with guided setup and fair, transparent pricing.',
  icons: { icon: '/zencraft-logo.png' },
  openGraph: { title: 'SMS Moonlight by ZenCraft | More time for what matters', description: 'Modern school management with simple setup and transparent pricing.', type: 'website', images: [{ url: '/og.png', width: 1672, height: 909, alt: 'SMS Moonlight by ZenCraft — More time for what matters.' }] },
  twitter: { card: 'summary_large_image', title: 'SMS Moonlight by ZenCraft | More time for what matters', description: 'Modern school management with simple setup and transparent pricing.', images: ['/og.png'] },
};

export default function RootLayout({ children }: Readonly<{children: React.ReactNode}>) {
  return <html lang="en"><body className={`${body.variable} ${display.variable}`}>{children}</body></html>;
}
