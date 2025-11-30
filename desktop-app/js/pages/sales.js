const SalesPage = {
    async load() {
        const content = `
            <div class="page-header">
                <h2><i class="bi bi-receipt"></i> Sales History</h2>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="salesTable">Loading...</div>
                </div>
            </div>
        `;
        
        document.getElementById('pageContent').innerHTML = content;
        await this.loadSales();
    },

    async loadSales() {
        const sales = await window.electronAPI.db.query(
            `SELECT * FROM sales ORDER BY created_at DESC LIMIT 50`
        );

        const tableDiv = document.getElementById('salesTable');
        
        if (sales && sales.length > 0) {
            tableDiv.innerHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sales.map(sale => `
                            <tr>
                                <td>${sale.invoice_number || 'N/A'}</td>
                                <td>${(sale.final_amount || 0).toFixed(2)}</td>
                                <td>${sale.payment_method || 'N/A'}</td>
                                <td>${new Date(sale.created_at).toLocaleString()}</td>
                                <td>
                                    <span class="badge ${sale.sync_status === 'synced' ? 'bg-success' : 'bg-warning'}">
                                        ${sale.sync_status || 'pending'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            tableDiv.innerHTML = '<p class="text-muted">No sales found</p>';
        }
    }
};

window.SalesPage = SalesPage;

