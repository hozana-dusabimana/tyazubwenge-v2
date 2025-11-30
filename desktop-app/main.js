const { app, BrowserWindow, ipcMain, dialog } = require('electron');
const path = require('path');
const { initDatabase, getDatabase } = require('./database/db');
const SyncManager = require('./sync/syncManager');
const AuthManager = require('./auth/authManager');
const { clearAllData, clearSyncData, clearSalesData, clearStockData, clearCustomersData } = require('./database/clearDatabase');

// Global error handlers
process.on('uncaughtException', (error) => {
  console.error('Uncaught Exception:', error);
  console.error('Stack:', error.stack);
  // Don't exit, log and continue
});

process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Rejection at:', promise);
  console.error('Reason:', reason);
  // Don't exit, log and continue
});

// Handle renderer process crashes
app.on('render-process-gone', (event, webContents, details) => {
  console.error('Render process crashed:', details);
});

let mainWindow;
let syncManager;
let authManager;

function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1200,
    minHeight: 700,
    webPreferences: {
      preload: path.join(__dirname, 'preload.js'),
      nodeIntegration: false,
      contextIsolation: true,
      enableRemoteModule: false,
      sandbox: false
    },
    icon: path.join(__dirname, 'assets', 'icon.png'),
    titleBarStyle: 'default',
    show: false
  });

  mainWindow.loadFile('index.html');

  // Log when page is loaded
  mainWindow.webContents.once('did-finish-load', () => {
    console.log('Page loaded, electronAPI should be available');
  });

  mainWindow.once('ready-to-show', () => {
    mainWindow.show();

    // Initialize managers after window is ready
    initManagers();
  });

  // Handle errors
  mainWindow.webContents.on('did-fail-load', (event, errorCode, errorDescription) => {
    console.error('Failed to load page:', errorCode, errorDescription);
  });

  // Catch console errors from renderer
  mainWindow.webContents.on('console-message', (event, level, message, line, sourceId) => {
    if (level >= 2) { // 0=debug, 1=log, 2=info, 3=warning, 4=error
      console.log(`[Renderer ${level}] ${message} (${sourceId}:${line})`);
    }
  });

  // Catch JavaScript errors in renderer
  mainWindow.webContents.on('preload-error', (event, preloadPath, error) => {
    console.error('Preload script error:', preloadPath, error);
  });

  mainWindow.on('closed', () => {
    mainWindow = null;
  });

  // Open DevTools in development
  if (process.env.NODE_ENV === 'development') {
    mainWindow.webContents.openDevTools();
  }
}

function initManagers() {
  try {
    // Initialize database
    initDatabase();
    console.log('Database initialized');

    // Initialize auth manager (don't create syncManager here - wait for login)
    if (!authManager) {
      authManager = new AuthManager();
      console.log('Auth manager initialized');
    }

    // Don't create syncManager here - it will be created after login
    // This ensures syncManager uses the authenticated authManager
    console.log('Managers initialized (syncManager will be created after login)');
  } catch (error) {
    console.error('Error initializing managers:', error);
  }
}

app.whenReady().then(() => {
  // Initialize managers early to ensure they're available for IPC handlers
  try {
    initDatabase();
    authManager = new AuthManager();
    console.log('Managers initialized early');
  } catch (error) {
    console.error('Early initialization error:', error);
  }

  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    app.quit();
  }
});

// IPC Handlers
ipcMain.handle('auth:login', async (event, credentials) => {
  try {
    // Ensure authManager is initialized
    if (!authManager) {
      console.log('Initializing authManager for login');
      authManager = new AuthManager();
    }

    // Check if we have stored credentials for offline login
    const db = getDatabase();
    const hasInitialData = db.prepare('SELECT value FROM settings WHERE key = ?').get('has_initial_data');

    // Set base URL if provided
    if (credentials.apiUrl) {
      authManager.setBaseURL(credentials.apiUrl);
    }

    // Try online login first
    let result = await authManager.login(credentials.username, credentials.password, false);

    // Ensure token is set in authManager after login
    if (result.success && result.token) {
      console.log('Login successful, token stored:', result.token.substring(0, 10) + '...');
      // Token should already be set in authManager.login(), but verify
      if (!authManager.getToken()) {
        console.warn('Token not set in authManager after login, setting it now');
        authManager.token = result.token;
        authManager.user = result.user;
      }
    }

    // If login successful and online, download initial data if needed
    if (result.success && !result.offline) {
      // Small delay to ensure token is fully saved to database
      await new Promise(resolve => setTimeout(resolve, 100));

      // Always recreate syncManager with the authenticated authManager to ensure token is available
      console.log('Creating syncManager with authenticated authManager');
      syncManager = new SyncManager(authManager);

      // Force reload token in authManager to ensure it's in memory
      authManager.loadStoredAuth();

      // Verify token is available before downloading
      let token = authManager.getToken();
      if (!token) {
        // Try one more time with a direct database read
        console.warn('Token not in memory, reading directly from database...');
        const db = getDatabase();
        const tokenSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
        if (tokenSetting && tokenSetting.value) {
          authManager.token = tokenSetting.value;
          token = authManager.token;
          console.log('Token loaded directly from database');
        }
      }

      if (!token) {
        console.error('Token not available after login, cannot download data');
        console.error('AuthManager token:', authManager.token);
        console.error('Result token:', result.token);
        console.error('Database token check:', db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token'));
      } else {
        console.log('Token verified:', token.substring(0, 10) + '..., proceeding with data download');
      }

      if (!hasInitialData) {
        // First login - download all data
        console.log('First login - downloading initial data...');
        try {
          await syncManager.downloadAllData(db);
          console.log('Initial data downloaded successfully');
        } catch (downloadError) {
          console.error('Error downloading initial data:', downloadError);
          console.error('Download error details:', downloadError.message);
          console.error('Stack:', downloadError.stack);
          // Don't fail login if download fails
        }
      } else {
        // Not first login - just sync updates
        try {
          await syncManager.downloadUpdates(db);
          console.log('Updates synced successfully');
        } catch (syncError) {
          console.error('Error syncing updates:', syncError);
          console.error('Sync error details:', syncError.message);
          console.error('Stack:', syncError.stack);
          // Don't fail login if sync fails
        }
      }
    }

    return result;
  } catch (error) {
    console.error('Login error:', error);
    return { success: false, message: error.message || 'Login failed' };
  }
});

ipcMain.handle('auth:logout', async () => {
  try {
    if (authManager) {
      await authManager.logout();
    }
    return { success: true };
  } catch (error) {
    console.error('Logout error:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('auth:isAuthenticated', () => {
  try {
    if (!authManager) {
      authManager = new AuthManager();
    }
    // Check if token exists
    const hasToken = authManager.isAuthenticated();
    if (!hasToken) {
      // Try to reload from database
      authManager.loadStoredAuth();
      return authManager.isAuthenticated();
    }
    return true;
  } catch (error) {
    console.error('isAuthenticated error:', error);
    return false;
  }
});

ipcMain.handle('auth:getUser', () => {
  try {
    if (!authManager) {
      authManager = new AuthManager();
    }
    // Ensure token is loaded
    if (!authManager.isAuthenticated()) {
      authManager.loadStoredAuth();
    }
    return authManager.getUser();
  } catch (error) {
    console.error('getUser error:', error);
    return null;
  }
});

ipcMain.handle('sync:getStatus', async () => {
  try {
    // Ensure authManager exists and has token loaded
    if (!authManager) {
      authManager = new AuthManager();
      authManager.loadStoredAuth();
    } else {
      authManager.loadStoredAuth();
    }

    // Only create syncManager if authenticated
    if (!syncManager) {
      if (authManager.isAuthenticated()) {
        console.log('Creating syncManager for getStatus with authenticated authManager');
        syncManager = new SyncManager(authManager);
      } else {
        // Not authenticated, return minimal status
        return {
          isOnline: false,
          isSyncing: false,
          pending: { total: 0, sales: 0, stock: 0, customers: 0 },
          lastSync: null
        };
      }
    } else {
      // Ensure syncManager uses the current authenticated authManager
      syncManager.authManager = authManager;
      syncManager.authManager.loadStoredAuth();
    }

    // Use the getStatus method which returns all needed data
    return await syncManager.getStatus();
  } catch (error) {
    console.error('getStatus error:', error);
    // Try to check connection on error
    if (syncManager) {
      await syncManager.checkConnection();
      return {
        isOnline: syncManager.isOnline,
        isSyncing: false,
        pending: { total: 0, sales: 0, stock: 0, customers: 0 },
        lastSync: null
      };
    }
    return {
      isOnline: false,
      pending: { total: 0, sales: 0, stock: 0, customers: 0 },
      isSyncing: false,
      lastSync: null
    };
  }
});

ipcMain.handle('sync:syncNow', async () => {
  try {
    // Ensure authManager exists and has token loaded
    if (!authManager) {
      authManager = new AuthManager();
      authManager.loadStoredAuth();
    } else {
      // Reload token to ensure it's in memory
      authManager.loadStoredAuth();
    }

    // Check if authenticated before creating syncManager
    if (!authManager.isAuthenticated()) {
      return { success: false, message: 'Not authenticated. Please login first.' };
    }

    // Create or update syncManager with authenticated authManager
    if (!syncManager) {
      console.log('Creating syncManager for syncNow with authenticated authManager');
      syncManager = new SyncManager(authManager);
    } else {
      // Ensure syncManager uses the current authenticated authManager
      syncManager.authManager = authManager;
      syncManager.authManager.loadStoredAuth();
    }

    return await syncManager.syncNow();
  } catch (error) {
    console.error('syncNow error:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('sync:isOnline', async () => {
  try {
    // Ensure authManager exists and has token loaded
    if (!authManager) {
      authManager = new AuthManager();
      authManager.loadStoredAuth();
    } else {
      authManager.loadStoredAuth();
    }

    // Only create syncManager if authenticated
    if (!syncManager) {
      if (authManager.isAuthenticated()) {
        console.log('Creating syncManager for isOnline with authenticated authManager');
        syncManager = new SyncManager(authManager);
      } else {
        // Not authenticated, can't check connection properly
        return false;
      }
    } else {
      // Ensure syncManager uses the current authenticated authManager
      syncManager.authManager = authManager;
      syncManager.authManager.loadStoredAuth();
    }

    // Check connection and return status
    await syncManager.checkConnection();
    return syncManager.isOnline;
  } catch (error) {
    console.error('isOnline error:', error);
    // On error, try to check connection again
    if (syncManager) {
      await syncManager.checkConnection();
      return syncManager.isOnline;
    }
    return false;
  }
});

// Database clear operations
ipcMain.handle('db:clearAll', async () => {
  try {
    const result = clearAllData();
    return result;
  } catch (error) {
    console.error('Error clearing all data:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('db:clearSync', async () => {
  try {
    const result = clearSyncData();
    return result;
  } catch (error) {
    console.error('Error clearing sync data:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('db:clearSales', async () => {
  try {
    const result = clearSalesData();
    return result;
  } catch (error) {
    console.error('Error clearing sales data:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('db:clearStock', async () => {
  try {
    const result = clearStockData();
    return result;
  } catch (error) {
    console.error('Error clearing stock data:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('db:clearCustomers', async () => {
  try {
    const result = clearCustomersData();
    return result;
  } catch (error) {
    console.error('Error clearing customers data:', error);
    return { success: false, message: error.message };
  }
});

ipcMain.handle('db:query', async (event, query, params = []) => {
  try {
    // Ensure database is initialized
    initDatabase();
    const database = getDatabase();
    if (!database) {
      return { success: false, error: 'Database not initialized' };
    }
    if (query.trim().toUpperCase().startsWith('SELECT')) {
      return database.prepare(query).all(...params);
    } else {
      const result = database.prepare(query).run(...params);
      return { success: true, lastInsertRowid: result.lastInsertRowid, changes: result.changes };
    }
  } catch (error) {
    console.error('Database query error:', error);
    return { success: false, error: error.message };
  }
});

ipcMain.handle('api:request', async (event, method, endpoint, data = null) => {
  try {
    const token = authManager.getToken();
    if (!token) {
      return { success: false, message: 'Not authenticated' };
    }

    const axios = require('axios');
    const baseURL = authManager.getBaseURL();

    const config = {
      method,
      url: `${baseURL}${endpoint}`,
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    };

    if (data) {
      config.data = data;
    }

    const response = await axios(config);
    return { success: true, data: response.data };
  } catch (error) {
    return {
      success: false,
      message: error.response?.data?.message || error.message,
      status: error.response?.status
    };
  }
});

ipcMain.handle('dialog:showMessageBox', async (event, options) => {
  const result = await dialog.showMessageBox(mainWindow, options);
  return result;
});

