// Main Application Controller
class App {
    constructor() {
        this.currentPage = 'dashboard';
        this.user = null;
        this.waitForElectronAPI().then(() => this.init());
    }

    async waitForElectronAPI() {
        // Wait for electronAPI to be available
        while (!window.electronAPI) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }

    async init() {
        // Check if electronAPI is available
        if (!window.electronAPI) {
            console.error('electronAPI is not available');
            document.getElementById('pageContent').innerHTML =
                '<div class="alert alert-danger">Error: Electron API not available. Please restart the application.</div>';
            return;
        }

        try {
            // Check authentication
            const isAuth = await window.electronAPI.auth.isAuthenticated();
            if (isAuth) {
                // Verify token is still valid
                const user = await window.electronAPI.auth.getUser();
                if (user) {
                    this.user = user;
                    this.showApp();
                } else {
                    // Token might be expired, show login
                    console.warn('User data not available, showing login');
                    this.showLogin();
                }
            } else {
                this.showLogin();
            }

            // Setup event listeners
            this.setupEventListeners();

            // Start sync status monitoring
            this.monitorSyncStatus();
        } catch (error) {
            console.error('Initialization error:', error);
            document.getElementById('pageContent').innerHTML =
                `<div class="alert alert-danger">Error: ${error.message}</div>`;
        }
    }

    setupEventListeners() {
        // Navigation
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const page = e.target.closest('a').dataset.page;
                this.navigateTo(page);
            });
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', () => {
            this.logout();
        });

        // Login form
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin();
        });
    }

    async handleLogin() {
        if (!window.electronAPI) {
            alert('Electron API not available. Please restart the application.');
            return;
        }

        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const apiUrl = document.getElementById('apiUrl').value;

        const errorDiv = document.getElementById('loginError');
        errorDiv.classList.add('d-none');

        // Show loading state
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Logging in...';

        try {
            const result = await window.electronAPI.auth.login({
                username,
                password,
                apiUrl: apiUrl || 'http://localhost/tyazubwenge_v2'
            });

            if (result.success) {
                this.user = result.user;

                // Show message based on login type
                if (result.offline) {
                    console.log('Logged in offline');
                } else {
                    console.log('Logged in online');
                }

                this.showApp();
            } else {
                errorDiv.textContent = result.message || 'Login failed';
                errorDiv.classList.remove('d-none');
            }
        } catch (error) {
            errorDiv.textContent = error.message || 'Login error occurred';
            errorDiv.classList.remove('d-none');
        } finally {
            // Always re-enable the button and restore text
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
            // Re-enable all inputs in case they were disabled
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const apiUrlInput = document.getElementById('apiUrl');
            if (usernameInput) usernameInput.disabled = false;
            if (passwordInput) passwordInput.disabled = false;
            if (apiUrlInput) apiUrlInput.disabled = false;
        }
    }

    async logout() {
        await window.electronAPI.auth.logout();
        this.showLogin();
    }

    showLogin() {
        document.getElementById('loginScreen').classList.remove('d-none');
        document.getElementById('appScreen').classList.add('d-none');

        // Reset login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.reset();
        }

        // Re-enable all form inputs and button
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const apiUrlInput = document.getElementById('apiUrl');
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        const errorDiv = document.getElementById('loginError');

        if (usernameInput) {
            usernameInput.disabled = false;
            usernameInput.value = '';
        }
        if (passwordInput) {
            passwordInput.disabled = false;
            passwordInput.value = '';
        }
        if (apiUrlInput) {
            apiUrlInput.disabled = false;
            if (!apiUrlInput.value) {
                apiUrlInput.value = 'http://localhost/tyazubwenge_v2';
            }
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Login';
        }
        if (errorDiv) {
            errorDiv.classList.add('d-none');
            errorDiv.textContent = '';
        }
    }

    showApp() {
        document.getElementById('loginScreen').classList.add('d-none');
        document.getElementById('appScreen').classList.remove('d-none');

        if (this.user) {
            document.getElementById('userName').textContent = this.user.username || 'User';
        }

        this.navigateTo(this.currentPage);
    }

    navigateTo(page) {
        this.currentPage = page;

        // Update active menu item
        document.querySelectorAll('.sidebar-menu a').forEach(link => {
            link.classList.remove('active');
            if (link.dataset.page === page) {
                link.classList.add('active');
            }
        });

        // Load page content
        const pageContent = document.getElementById('pageContent');
        pageContent.innerHTML = '<div class="loading"><i class="bi bi-arrow-repeat spin"></i> Loading...</div>';

        // Load page module
        setTimeout(() => {
            switch (page) {
                case 'dashboard':
                    DashboardPage.load();
                    break;
                case 'pos':
                    POSPage.load();
                    break;
                case 'stock':
                    StockPage.load();
                    break;
                case 'products':
                    ProductsPage.load();
                    break;
                case 'customers':
                    CustomersPage.load();
                    break;
                case 'sales':
                    SalesPage.load();
                    break;
                case 'sync':
                    SyncPage.load();
                    break;
                default:
                    pageContent.innerHTML = '<div class="alert alert-warning">Page not found</div>';
            }
        }, 100);
    }

    async monitorSyncStatus() {
        if (!window.electronAPI) return;

        // Update immediately
        this.updateSyncStatus();

        // Then update periodically
        setInterval(async () => {
            await this.updateSyncStatus();
        }, 10000); // Check every 10 seconds
    }

    async updateSyncStatus() {
        try {
            // Force connection check by getting status
            await window.electronAPI.sync.getStatus();

            const isOnline = await window.electronAPI.sync.isOnline();
            const statusDiv = document.getElementById('syncStatus');

            if (statusDiv) {
                if (isOnline) {
                    statusDiv.className = 'sync-status online';
                    statusDiv.innerHTML = '<i class="bi bi-wifi"></i> <span>Online</span>';
                } else {
                    statusDiv.className = 'sync-status offline';
                    statusDiv.innerHTML = '<i class="bi bi-wifi-off"></i> <span>Offline</span>';
                }
            }
        } catch (error) {
            console.error('Sync status error:', error);
            // On error, assume offline
            const statusDiv = document.getElementById('syncStatus');
            if (statusDiv) {
                statusDiv.className = 'sync-status offline';
                statusDiv.innerHTML = '<i class="bi bi-wifi-off"></i> <span>Offline</span>';
            }
        }
    }
}

// Initialize app when DOM is ready
let app;
document.addEventListener('DOMContentLoaded', () => {
    // Wait a bit for preload script to execute
    setTimeout(() => {
        app = new App();
    }, 100);
});

// Export for use in other modules
window.App = app;

