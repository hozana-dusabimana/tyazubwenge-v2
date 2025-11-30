# Tyazubwenge Desktop Application

Desktop application for Tyazubwenge Training Center with offline support and automatic sync.

## Features

- ✅ **Offline Support**: Work without internet connection
- ✅ **Automatic Sync**: Syncs data when connection is available
- ✅ **POS System**: Process sales offline
- ✅ **Stock Management**: Manage inventory
- ✅ **Customer Management**: Add and manage customers
- ✅ **Sales History**: View all sales transactions
- ✅ **Real-time Sync Status**: Monitor sync progress

## Installation

### Prerequisites

- Node.js (v16 or higher)
- npm or yarn

### Setup

1. Navigate to the desktop-app directory:
```bash
cd desktop-app
```

2. Install dependencies:
```bash
npm install
```

3. Start the application:
```bash
npm start
```

## Building

### Windows
```bash
npm run build:win
```

### macOS
```bash
npm run build:mac
```

### Linux
```bash
npm run build:linux
```

Built applications will be in the `dist` directory.

## Configuration

### API URL

The default API URL is `http://localhost/tyazubwenge_v2`. You can change this:
1. During login (enter custom API URL)
2. Or modify the default in `auth/authManager.js`

## Usage

### First Time Setup

1. Launch the application
2. Enter your credentials:
   - Username
   - Password
   - API URL (default: http://localhost/tyazubwenge_v2)
3. Click Login

### Working Offline

- All operations work offline
- Data is stored locally in SQLite database
- Changes are queued for sync
- Automatic sync runs every 60 seconds when online

### Manual Sync

1. Go to the "Sync" page
2. Click "Sync Now" to manually trigger sync

## Database

The application uses SQLite for local storage. The database is stored in:
- **Windows**: `%APPDATA%/tyazubwenge-desktop/tyazubwenge.db`
- **macOS**: `~/Library/Application Support/tyazubwenge-desktop/tyazubwenge.db`
- **Linux**: `~/.config/tyazubwenge-desktop/tyazubwenge.db`

## Project Structure

```
desktop-app/
├── main.js              # Main Electron process
├── preload.js           # Preload script for security
├── package.json         # Dependencies and build config
├── index.html           # Main UI
├── database/
│   └── db.js           # Database initialization
├── auth/
│   └── authManager.js  # Authentication management
├── sync/
│   └── syncManager.js  # Sync logic
├── js/
│   ├── app.js          # Main app controller
│   └── pages/          # Page modules
├── styles/
│   └── main.css       # Styles
└── assets/            # Icons and images
```

## Development

### Running in Development Mode

```bash
npm start
```

This will open DevTools automatically.

### Making Changes

- UI changes: Edit files in `js/` and `styles/`
- Backend changes: Edit `main.js`, `database/`, `auth/`, `sync/`
- After changes, restart the app

## Troubleshooting

### Login Fails

- Check API URL is correct
- Ensure server is running
- Check network connection

### Sync Not Working

- Verify you're online (check sync status indicator)
- Check API URL is accessible
- Verify token is valid (try logging out and back in)

### Database Errors

- Close the app
- Delete the database file (see Database section for location)
- Restart the app (database will be recreated)

## Support

For issues or questions, contact Lanari Tech Ltd.

