# Fixing better-sqlite3 Module Version Error

## Problem
The error occurs because `better-sqlite3` was compiled for a different Node.js version than what you're currently using.

## Quick Fix (Windows)

### Option 1: Run the fix script
```powershell
.\fix-sqlite.ps1
```

Or double-click `fix-sqlite.bat`

### Option 2: Manual steps

1. **Remove the old module:**
   ```powershell
   Remove-Item -Recurse -Force node_modules\better-sqlite3
   ```

2. **Reinstall from source:**
   ```powershell
   npm install better-sqlite3 --build-from-source
   ```

3. **Rebuild:**
   ```powershell
   npm rebuild better-sqlite3
   ```

## Alternative: Reinstall all dependencies

If the above doesn't work, try a complete reinstall:

```powershell
# Remove node_modules and package-lock.json
Remove-Item -Recurse -Force node_modules
Remove-Item -Force package-lock.json

# Reinstall everything
npm install
```

## If you still have issues

### Check Node.js version
```powershell
node --version
```

You need Node.js v16 or higher. If you have a different version:
- Download from [nodejs.org](https://nodejs.org/)
- Install the LTS version
- Then run the fix steps again

### Install build tools (if needed)

**Windows:**
- Install [Visual Studio Build Tools](https://visualstudio.microsoft.com/downloads/#build-tools-for-visual-studio-2022)
- Select "Desktop development with C++" workload

**Or use npm-windows-build-tools:**
```powershell
npm install -g windows-build-tools
```

## After fixing

Run the app:
```powershell
npm start
```

The error should be resolved!

