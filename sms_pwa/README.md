# SMS Attendance Scanner

SMS Attendance Scanner is an Electron desktop application that records student and staff attendance and synchronizes it with the SMS Moonlight web application.

## Features

- Student and staff attendance scanning
- Authorization-code validation against the web application API
- Local attendance handling with server synchronization
- Windows installer and packaged desktop builds
- Automated tests for attendance utility logic

## Requirements

- Node.js and npm
- A running SMS Moonlight web application for API validation and synchronization

## Setup

Run the following commands from this folder:

```bash
npm install
npm start
```

Configure the server URL and authorization code in the application interface when connecting the scanner.

## Tests

```bash
npm test
```

## Build

Create an unpacked Windows application:

```bash
npm run pack
```

Create the configured installer:

```bash
npm run dist
```

Generated dependencies, build output, and `.tgz` packages are intentionally excluded from Git.

## Desktop updates

Packaged scanners configure their update feed from the Server URL saved in the
application. To release an update:

1. Increment `version` in `package.json`.
2. Run `npm run dist`.
3. Copy `latest.yml`, the generated installer, and its `.blockmap` file from
   `dist` to `sms_moonlight/storage/app/application-updates` on the server.

The update endpoint uses the scanner's authorization code. Installed scanners
check the configured server at startup and every six hours, download a newer
version automatically, and install it when the application exits.
