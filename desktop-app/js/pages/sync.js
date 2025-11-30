const SyncPage = {
    async load() {
        const content = `
            <div class="page-header">
                <h2><i class="bi bi-arrow-repeat"></i> Sync Management</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="pendingSales">0</div>
                    <div class="stat-label">Pending Sales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="pendingStock">0</div>
                    <div class="stat-label">Pending Stock</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="pendingCustomers">0</div>
                    <div class="stat-label">Pending Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="lastSync">Never</div>
                    <div class="stat-label">Last Sync</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Sync Status</div>
                <div class="card-body">
                    <div id="syncInfo">Loading...</div>
                    <button class="btn btn-primary mt-3" id="syncNowBtn">
                        <i class="bi bi-arrow-repeat"></i> Sync Now
                    </button>
                </div>
            </div>
        `;

        document.getElementById('pageContent').innerHTML = content;
        await this.loadStatus();
        this.setupEventListeners();
    },

    async loadStatus() {
        const status = await window.electronAPI.sync.getStatus();

        document.getElementById('pendingSales').textContent = status.pending?.sales || 0;
        document.getElementById('pendingStock').textContent = status.pending?.stock || 0;
        document.getElementById('pendingCustomers').textContent = status.pending?.customers || 0;

        const lastSync = status.lastSync
            ? new Date(status.lastSync).toLocaleString()
            : 'Never';
        document.getElementById('lastSync').textContent = lastSync;

        const infoDiv = document.getElementById('syncInfo');
        infoDiv.innerHTML = `
            <div class="mb-2">
                <strong>Status:</strong> 
                <span class="badge ${status.isOnline ? 'bg-success' : 'bg-danger'}">
                    ${status.isOnline ? 'Online' : 'Offline'}
                </span>
            </div>
            <div class="mb-2">
                <strong>Syncing:</strong> 
                <span class="badge ${status.isSyncing ? 'bg-warning' : 'bg-secondary'}">
                    ${status.isSyncing ? 'In Progress' : 'Idle'}
                </span>
            </div>
            <div>
                <strong>Total Pending:</strong> ${status.pending?.total || 0} records
            </div>
        `;
    },

    setupEventListeners() {
        document.getElementById('syncNowBtn').addEventListener('click', async () => {
            const btn = document.getElementById('syncNowBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-arrow-repeat spin"></i> Syncing...';

            const result = await window.electronAPI.sync.syncNow();

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Sync Now';

            if (result.success) {
                alert(`Sync completed! Synced: ${result.synced}, Failed: ${result.failed}`);
            } else {
                const errorMsg = result.message || 'Unknown error';
                alert('Sync failed: ' + errorMsg);

                // If token expired, suggest logout
                if (errorMsg.includes('token') || errorMsg.includes('Authentication') || errorMsg.includes('Unauthorized')) {
                    if (confirm('Your session has expired. Would you like to logout and login again?')) {
                        await window.electronAPI.auth.logout();
                        window.location.reload();
                    }
                }
            }

            this.loadStatus();
        });
    }
};

window.SyncPage = SyncPage;

