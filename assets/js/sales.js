let currentPage = 1;
let currentLimit = 5; // Default to 5 rows

async function loadSales(page = 1, limit = null) {
    if (limit !== null) {
        currentLimit = limit;
        currentPage = 1; // Reset to first page when limit changes
    }
    currentPage = page;
    
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    const tbody = document.getElementById('salesTable');
    showLoading(tbody);
    
    let endpoint = `sales.php?page=${currentPage}&limit=${currentLimit}`;
    if (dateFrom) endpoint += `&date_from=${dateFrom}`;
    if (dateTo) endpoint += `&date_to=${dateTo}`;
    
    const result = await apiCall(endpoint);
    
    if (result.success) {
        displaySales(result.data);
        if (result.pagination) {
            displayPagination(result.pagination, 'pagination', loadSales, (newLimit) => {
                loadSales(1, newLimit);
            });
        }
    } else {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-danger">Error loading sales</td></tr>';
    }
}

function displaySales(sales) {
    const tbody = document.getElementById('salesTable');
    
    if (sales.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No sales found</td></tr>';
        return;
    }
    
    tbody.innerHTML = sales.map(sale => `
        <tr>
            <td><strong>${sale.invoice_number}</strong></td>
            <td>${sale.customer_name || 'Walk-in'}</td>
            <td>${sale.sale_type}</td>
            <td><strong>${formatCurrency(sale.final_amount)}</strong></td>
            <td>${sale.payment_method.replace('_', ' ')}</td>
            <td>
                <span class="badge bg-${sale.payment_status === 'paid' ? 'success' : 'warning'}">
                    ${sale.payment_status}
                </span>
            </td>
            <td>${formatDateTime(sale.created_at)}</td>
            <td>
                <button class="btn btn-sm btn-outline-primary" onclick="printInvoice(${sale.id}, 'a4')" title="Print A4">
                    <i class="bi bi-printer"></i>
                </button>
                <button class="btn btn-sm btn-outline-secondary" onclick="printInvoice(${sale.id}, 'thermal')" title="Print Thermal">
                    <i class="bi bi-receipt"></i>
                </button>
            </td>
        </tr>
    `).join('');
}


function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Load summary
async function loadSalesSummary() {
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;
    
    let endpoint = `summary.php?type=sales`;
    if (dateFrom) endpoint += `&date_from=${dateFrom}`;
    if (dateTo) endpoint += `&date_to=${dateTo}`;
    
    const result = await apiCall(endpoint);
    if (result.success) {
        document.getElementById('totalSales').textContent = result.data.total_sales;
        document.getElementById('totalRevenue').textContent = formatCurrency(result.data.total_revenue);
        document.getElementById('pendingAmount').textContent = formatCurrency(result.data.pending_amount);
    }
}

// Initial load
loadSalesSummary();
loadSales();

