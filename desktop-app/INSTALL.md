# Installation Guide

## Quick Start

### Step 1: Install Node.js

Download and install Node.js from [nodejs.org](https://nodejs.org/) (v16 or higher recommended).

### Step 2: Install Dependencies

Open a terminal in the `desktop-app` directory and run:

```bash
npm install
```

This will install:
- Electron (desktop app framework)
- better-sqlite3 (local database)
- axios (HTTP client)
- electron-builder (for building installers)

### Step 3: Run the Application

```bash
npm start
```

The application will launch. On first run, you'll see the login screen.

### Step 4: Login

1. Enter your username and password (same as web application)
2. Enter the API URL (default: `http://localhost/tyazubwenge_v2`)
3. Click "Login"

## Building for Distribution

### Windows

```bash
npm run build:win
```

This creates an installer in the `dist` folder.

### macOS

```bash
npm run build:mac
```

This creates a `.dmg` file in the `dist` folder.

### Linux

```bash
npm run build:linux
```

This creates an `AppImage` in the `dist` folder.

## Troubleshooting

### "better-sqlite3" Installation Fails

This is common on Windows. Try:

```bash
npm install --build-from-source better-sqlite3
```

Or install build tools:
- Windows: Install Visual Studio Build Tools
- macOS: Install Xcode Command Line Tools
- Linux: Install `build-essential`

### Application Won't Start

1. Check Node.js version: `node --version` (should be v16+)
2. Delete `node_modules` and reinstall: `rm -rf node_modules && npm install`
3. Check for error messages in the terminal

### Database Errors

The database is created automatically on first run. If you encounter database errors:
1. Close the application
2. Delete the database file (see README.md for location)
3. Restart the application

## Development

To run in development mode with DevTools:

```bash
npm start
```

DevTools will open automatically.

## Next Steps

1. Test the application with your server
2. Customize the UI if needed
3. Build installers for distribution
4. Deploy to users

