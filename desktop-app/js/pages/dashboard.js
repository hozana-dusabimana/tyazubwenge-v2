const DashboardPage = {
    async load() {
        if (!window.electronAPI) {
            document.getElementById('pageContent').innerHTML =
                '<div class="alert alert-danger">Electron API not available</div>';
            return;
        }

        const content = `
            <div class="page-header">
                <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="totalSales">0</div>
                    <div class="stat-label">Total Sales (Today)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="totalRevenue">0</div>
                    <div class="stat-label">Revenue (Today)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="lowStock">0</div>
                    <div class="stat-label">Low Stock Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" id="pendingSync">0</div>
                    <div class="stat-label">Pending Sync</div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">Recent Sales</div>
                <div class="card-body">
                    <div id="recentSales">Loading...</div>
                </div>
            </div>
        `;

        document.getElementById('pageContent').innerHTML = content;
        
        // Wait a bit to ensure DOM is updated before accessing elements
        await new Promise(resolve => setTimeout(resolve, 10));
        
        await this.loadData();
    },

    async loadData() {
        // Check if online
        const isOnline = await window.electronAPI.sync.isOnline();
        
        // If online, try to fetch latest data
        if (isOnline) {
            try {
                // Trigger sync to get latest data
                await window.electronAPI.sync.syncNow();
            } catch (error) {
                console.error('Error syncing data:', error);
                // Continue with local data
            }
        }
        
        // Load today's sales from local database
        const today = new Date().toISOString().split('T')[0];
        const sales = await window.electronAPI.db.query(
            `SELECT * FROM sales WHERE DATE(created_at) = ? ORDER BY created_at DESC LIMIT 10`,
            [today]
        );

        let totalSales = 0;
        let totalRevenue = 0;

        if (sales && sales.length > 0) {
            totalSales = sales.length;
            totalRevenue = sales.reduce((sum, sale) => sum + (sale.final_amount || 0), 0);
        }

        // Update elements with null checks
        const totalSalesEl = document.getElementById('totalSales');
        if (totalSalesEl) {
            totalSalesEl.textContent = totalSales;
        }

        const totalRevenueEl = document.getElementById('totalRevenue');
        if (totalRevenueEl) {
            totalRevenueEl.textContent = totalRevenue.toFixed(2);
        }

        // Load low stock
        const lowStock = await window.electronAPI.db.query(
            `SELECT COUNT(*) as count FROM stock_inventory si 
             JOIN products p ON si.product_id = p.id 
             WHERE si.quantity <= p.min_stock_level`
        );
        const lowStockEl = document.getElementById('lowStock');
        if (lowStockEl) {
            lowStockEl.textContent = lowStock?.[0]?.count || 0;
        }

        // Load pending sync
        const syncStatus = await window.electronAPI.sync.getStatus();
        const pendingSyncEl = document.getElementById('pendingSync');
        if (pendingSyncEl) {
            pendingSyncEl.textContent = syncStatus.pending?.total || 0;
        }

        // Display recent sales
        const salesHtml = sales && sales.length > 0
            ? `<table class="table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    ${sales.map(sale => `
                        <tr>
                            <td>${sale.invoice_number || 'N/A'}</td>
                            <td>${(sale.final_amount || 0).toFixed(2)}</td>
                            <td>${new Date(sale.created_at).toLocaleString()}</td>
                            <td>
                                <span class="badge ${sale.sync_status === 'synced' ? 'bg-success' : 'bg-warning'}">
                                    ${sale.sync_status || 'pending'}
                                </span>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>`
            : '<p class="text-muted">No sales today</p>';

        const recentSalesEl = document.getElementById('recentSales');
        if (recentSalesEl) {
            recentSalesEl.innerHTML = salesHtml;
        }
    }
};

window.DashboardPage = DashboardPage;

