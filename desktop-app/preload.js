const { contextBridge, ipcRenderer } = require('electron');

// Expose protected methods that allow the renderer process to use
// the ipcRenderer without exposing the entire object
try {
    contextBridge.exposeInMainWorld('electronAPI', {
        // Auth
        auth: {
            login: (credentials) => ipcRenderer.invoke('auth:login', credentials),
            logout: () => ipcRenderer.invoke('auth:logout'),
            isAuthenticated: () => ipcRenderer.invoke('auth:isAuthenticated'),
            getUser: () => ipcRenderer.invoke('auth:getUser')
        },

        // Sync
        sync: {
            getStatus: () => ipcRenderer.invoke('sync:getStatus'),
            syncNow: () => ipcRenderer.invoke('sync:syncNow'),
            isOnline: () => ipcRenderer.invoke('sync:isOnline')
        },

        // Database
        db: {
            query: (query, params) => ipcRenderer.invoke('db:query', query, params),
            clearAll: () => ipcRenderer.invoke('db:clearAll'),
            clearSync: () => ipcRenderer.invoke('db:clearSync'),
            clearSales: () => ipcRenderer.invoke('db:clearSales'),
            clearStock: () => ipcRenderer.invoke('db:clearStock'),
            clearCustomers: () => ipcRenderer.invoke('db:clearCustomers')
        },

        // API
        api: {
            request: (method, endpoint, data) => ipcRenderer.invoke('api:request', method, endpoint, data)
        },

        // Dialog
        dialog: {
            showMessageBox: (options) => ipcRenderer.invoke('dialog:showMessageBox', options)
        }
    });

    console.log('electronAPI exposed successfully');
} catch (error) {
    console.error('Error exposing electronAPI:', error);
    // Fallback: expose a minimal API
    window.electronAPI = {
        auth: {
            login: () => Promise.resolve({ success: false, message: 'API not available' }),
            logout: () => Promise.resolve({ success: false }),
            isAuthenticated: () => Promise.resolve(false),
            getUser: () => Promise.resolve(null)
        },
        sync: {
            getStatus: () => Promise.resolve({ isOnline: false, pending: { total: 0 } }),
            syncNow: () => Promise.resolve({ success: false, message: 'API not available' }),
            isOnline: () => Promise.resolve(false)
        },
        db: {
            query: () => Promise.resolve({ success: false, error: 'API not available' })
        },
        api: {
            request: () => Promise.resolve({ success: false, message: 'API not available' })
        },
        dialog: {
            showMessageBox: () => Promise.resolve({ response: 0 })
        }
    };
}

