// Utility functions
const Utils = {
    // Check if electronAPI is available
    checkElectronAPI() {
        if (!window.electronAPI) {
            console.error('electronAPI is not available');
            return false;
        }
        return true;
    },

    // Safe wrapper for electronAPI calls
    async safeElectronCall(apiPath, ...args) {
        if (!this.checkElectronAPI()) {
            throw new Error('Electron API not available');
        }

        const parts = apiPath.split('.');
        let api = window.electronAPI;
        
        for (const part of parts) {
            if (api[part]) {
                api = api[part];
            } else {
                throw new Error(`API path ${apiPath} not found`);
            }
        }

        if (typeof api !== 'function') {
            throw new Error(`${apiPath} is not a function`);
        }

        return await api(...args);
    },

    // Format currency
    formatCurrency(amount) {
        return parseFloat(amount || 0).toFixed(2);
    },

    // Format date
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleString();
    }
};

window.Utils = Utils;

