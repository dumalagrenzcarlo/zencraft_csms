import type { MetadataRoute } from 'next';

export const dynamic = 'force-static';

export default function manifest(): MetadataRoute.Manifest {
  return {
    name: 'ZenCraft Attendance Scanner',
    short_name: 'Attendance',
    description: 'Scan student QR codes and record attendance from your phone.',
    start_url: '/',
    display: 'standalone',
    background_color: '#fbfcf8',
    theme_color: '#174f45',
    orientation: 'portrait',
    icons: [
      { src: '/icon-192.png', sizes: '192x192', type: 'image/png', purpose: 'any' },
      { src: '/icon-512.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
    ],
  };
}
