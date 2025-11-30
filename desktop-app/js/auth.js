// Authentication helper functions
const Auth = {
    async checkAuth() {
        return await window.electronAPI.auth.isAuthenticated();
    },

    async getUser() {
        return await window.electronAPI.auth.getUser();
    }
};

window.Auth = Auth;

