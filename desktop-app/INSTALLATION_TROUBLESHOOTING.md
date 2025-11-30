# Installation Troubleshooting Guide

## Error: "This app can't run on your PC"

This error can occur for several reasons. Try these solutions:

### Solution 1: Check Windows Architecture
- The installer is built for **64-bit Windows only**
- Check your system: Right-click "This PC" → Properties → Check "System type"
- If you have 32-bit Windows, you'll need a 32-bit build (contact developer)

### Solution 2: Run as Administrator
1. Right-click the installer file
2. Select "Run as administrator"
3. Click "Yes" when prompted

### Solution 3: Unblock the File
1. Right-click the installer file
2. Select "Properties"
3. If you see an "Unblock" button at the bottom, click it
4. Click "OK"
5. Try running the installer again

### Solution 4: Disable Windows Defender Temporarily
1. Open Windows Security
2. Go to "Virus & threat protection"
3. Click "Manage settings"
4. Temporarily disable "Real-time protection"
5. Try installing again
6. Re-enable protection after installation

### Solution 5: Use Portable Version
If the installer doesn't work, use the portable version:
1. Extract `win-unpacked` folder from the dist folder
2. Run `Tyazubwenge Desktop.exe` directly
3. No installation needed!

### Solution 6: Check Windows Version
- Minimum: Windows 10 (64-bit)
- Recommended: Windows 10/11 (64-bit)

### Solution 7: Install Visual C++ Redistributable
Download and install:
- [Visual C++ Redistributable x64](https://aka.ms/vs/17/release/vc_redist.x64.exe)

### Still Having Issues?

1. Check Windows Event Viewer for detailed error messages:
   - Press `Win + X` → Event Viewer
   - Look for errors related to the installer

2. Try installing in Safe Mode:
   - Restart Windows in Safe Mode
   - Try installing again

3. Contact Support:
   - Email: lanari.rw@gmail.com
   - Include your Windows version and error details

## Alternative: Run from Source

If the installer continues to fail, you can run the app directly from source:

1. Install Node.js (v16 or higher) from [nodejs.org](https://nodejs.org/)
2. Open terminal in `desktop-app` folder
3. Run: `npm install`
4. Run: `npm start`

This will launch the app without installation.

