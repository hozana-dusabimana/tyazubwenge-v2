let reportData = [];

async function loadReport() {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const contentDiv = document.getElementById('reportContent');
    showLoading(contentDiv);
    
    let endpoint = `reports.php?type=${reportType}`;
    if (dateFrom) endpoint += `&date_from=${dateFrom}`;
    if (dateTo) endpoint += `&date_to=${dateTo}`;
    
    if (reportType === 'custom') {
        const customType = document.getElementById('customReportType').value;
        endpoint += `&custom_type=${customType}`;
    }
    
    const result = await apiCall(endpoint);
    
    if (result.success) {
        reportData = result.data;
        displayReport(reportType, result.data);
    } else {
        contentDiv.innerHTML = '<div class="alert alert-danger">Error loading report</div>';
    }
}

function displayReport(type, data) {
    const contentDiv = document.getElementById('reportContent');
    const titleDiv = document.getElementById('reportTitle');
    
    // Show/hide custom report options
    document.getElementById('customReportOptions').style.display = type === 'custom' ? 'block' : 'none';
    
    // Show export buttons if data exists
    const hasData = data && (Array.isArray(data) ? data.length > 0 : Object.keys(data).length > 0);
    document.getElementById('pdfExportBtn').style.display = hasData ? 'inline-block' : 'none';
    document.getElementById('csvExportBtn').style.display = hasData ? 'inline-block' : 'none';
    
    if (!hasData) {
        contentDiv.innerHTML = '<div class="alert alert-info">No data available for the selected period</div>';
        return;
    }
    
    switch (type) {
        case 'sales':
            titleDiv.textContent = 'Sales Report';
            displaySalesReport(data);
            break;
        case 'top_products':
            titleDiv.textContent = 'Top Products';
            displayTopProducts(data);
            break;
        case 'stock_valuation':
            titleDiv.textContent = 'Stock Valuation';
            displayStockValuation(data);
            break;
        case 'profit_loss':
            titleDiv.textContent = 'Profit & Loss';
            displayProfitLoss(data);
            break;
        case 'custom':
            const customType = document.getElementById('customReportType').value;
            titleDiv.textContent = 'Custom Report';
            displayCustomReport(customType, data);
            break;
    }
}

function displaySalesReport(data) {
    const contentDiv = document.getElementById('reportContent');
    
    const totalRevenue = data.reduce((sum, item) => sum + parseFloat(item.total_revenue || 0), 0);
    const totalSales = data.reduce((sum, item) => sum + parseInt(item.total_sales || 0), 0);
    
    contentDiv.innerHTML = `
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>Total Sales</h6>
                        <h3>${totalSales}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Total Revenue</h6>
                        <h3>${formatCurrency(totalRevenue)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6>Total Discount</h6>
                        <h3>${formatCurrency(data.reduce((sum, item) => sum + parseFloat(item.total_discount || 0), 0))}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Total Sales</th>
                        <th>Revenue</th>
                        <th>Discount</th>
                        <th>Tax</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map(item => `
                        <tr>
                            <td>${item.period}</td>
                            <td>${item.total_sales}</td>
                            <td>${formatCurrency(item.total_revenue)}</td>
                            <td>${formatCurrency(item.total_discount)}</td>
                            <td>${formatCurrency(item.total_tax)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function displayTopProducts(data) {
    const contentDiv = document.getElementById('reportContent');
    
    contentDiv.innerHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity Sold</th>
                        <th>Revenue</th>
                        <th>Times Sold</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map((item, index) => `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${item.name}</strong></td>
                            <td>${item.sku}</td>
                            <td>${item.total_quantity_sold}</td>
                            <td><strong>${formatCurrency(item.total_revenue)}</strong></td>
                            <td>${item.times_sold}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function displayStockValuation(data) {
    const contentDiv = document.getElementById('reportContent');
    
    const totalValue = data.reduce((sum, item) => sum + parseFloat(item.stock_value || 0), 0);
    
    contentDiv.innerHTML = `
        <div class="alert alert-info">
            <strong>Total Stock Value:</strong> ${formatCurrency(totalValue)}
        </div>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Cost Price</th>
                        <th>Stock Value</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.map(item => `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.sku}</td>
                            <td>${item.quantity}</td>
                            <td>${item.unit}</td>
                            <td>${formatCurrency(item.cost_price)}</td>
                            <td><strong>${formatCurrency(item.stock_value)}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
}

function displayProfitLoss(data) {
    const contentDiv = document.getElementById('reportContent');
    
    contentDiv.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6>Total Revenue</h6>
                        <h3>${formatCurrency(data.total_revenue || 0)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6>Total Cost</h6>
                        <h3>${formatCurrency(data.total_cost || 0)}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6>Profit</h6>
                        <h3>${formatCurrency(data.profit || 0)}</h3>
                        <small>Margin: ${parseFloat(data.profit_margin || 0).toFixed(2)}%</small>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function displayCustomReport(type, data) {
    const contentDiv = document.getElementById('reportContent');
    
    if (type === 'sales_by_customer') {
        contentDiv.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Total Orders</th>
                            <th>Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td><strong>${item.name}</strong></td>
                                <td>${item.phone || '-'}</td>
                                <td>${item.total_orders}</td>
                                <td><strong>${formatCurrency(item.total_spent)}</strong></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } else if (type === 'sales_by_category') {
        contentDiv.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td><strong>${item.name || 'Uncategorized'}</strong></td>
                                <td>${item.total_quantity}</td>
                                <td><strong>${formatCurrency(item.total_revenue)}</strong></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    } else if (type === 'slow_moving') {
        contentDiv.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Stock</th>
                            <th>Quantity Sold (Last 90 Days)</th>
                            <th>Days Since Last Sale</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.map(item => `
                            <tr>
                                <td><strong>${item.name}</strong></td>
                                <td>${item.sku}</td>
                                <td>${item.current_stock}</td>
                                <td>${item.sold_quantity}</td>
                                <td>${item.days_since_last_sale || 'N/A'}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
}

function exportReport(format) {
    const reportType = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    const customType = document.getElementById('customReportType').value;
    
    if (format === 'csv') {
        if (!reportData || (Array.isArray(reportData) && reportData.length === 0)) {
            showAlert('No data to export', 'warning');
            return;
        }
        exportToCSV(reportData, `report_${Date.now()}.csv`);
    } else if (format === 'pdf') {
        // Build PDF URL
        let url = `api/pdf_report.php?type=${reportType}&download=1`;
        if (dateFrom) url += `&date_from=${dateFrom}`;
        if (dateTo) url += `&date_to=${dateTo}`;
        if (reportType === 'custom' && customType) {
            url += `&custom_type=${customType}`;
        }
        
        // Open in new window for print/download
        const printWindow = window.open(url, '_blank');
        if (!printWindow) {
            showAlert('Please allow popups to download PDF', 'warning');
        }
    }
}

// Initial load
loadReport();

