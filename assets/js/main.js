// Tyazubwenge Management System - Main JavaScript

const API_BASE = 'api/';

// Utility Functions
function formatCurrency(amount) {
    if (amount === null || amount === undefined) return '0.00';
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(parseFloat(amount));
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function showLoading(element) {
    element.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';
}

// API Functions
async function apiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(API_BASE + endpoint, options);
        
        // Check if response is ok
        if (!response.ok) {
            const errorText = await response.text();
            console.error('API Error Response:', errorText);
            return { success: false, message: `Server error: ${response.status} ${response.statusText}` };
        }
        
        // Check if response has content
        const text = await response.text();
        if (!text || text.trim() === '') {
            console.error('Empty response from API');
            return { success: false, message: 'Empty response from server' };
        }
        
        try {
            const result = JSON.parse(text);
            return result;
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            return { success: false, message: 'Invalid JSON response from server', raw: text };
        }
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network error occurred: ' + error.message };
    }
}

// Pagination Helper with rows per page selector
function displayPagination(pagination, containerId, onPageChange, onLimitChange) {
    if (!pagination || !containerId) return;
    
    const container = document.getElementById(containerId);
    if (!container) return;
    
    const { current_page, total_pages, total_items, per_page } = pagination;
    
    if (total_pages <= 1 && total_items <= per_page) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">';
    
    // Rows per page selector
    html += '<div class="d-flex align-items-center gap-2">';
    html += '<label class="mb-0">Rows per page:</label>';
    html += '<select class="form-select form-select-sm" style="width: auto;" id="rowsPerPage">';
    const options = [5, 10, 20, 50, 100];
    options.forEach(opt => {
        html += `<option value="${opt}" ${per_page == opt ? 'selected' : ''}>${opt}</option>`;
    });
    html += '</select>';
    html += '</div>';
    
    // Page info
    const start = total_items > 0 ? ((current_page - 1) * per_page) + 1 : 0;
    const end = Math.min(current_page * per_page, total_items);
    html += `<div class="text-muted small">Showing ${start}-${end} of ${total_items}</div>`;
    
    // Pagination controls
    html += '<nav><ul class="pagination pagination-sm mb-0">';
    
    // Previous button
    html += `<li class="page-item ${current_page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${current_page - 1}">Previous</a>
    </li>`;
    
    // Page numbers
    for (let i = 1; i <= total_pages; i++) {
        if (i === 1 || i === total_pages || (i >= current_page - 2 && i <= current_page + 2)) {
            html += `<li class="page-item ${i === current_page ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>`;
        } else if (i === current_page - 3 || i === current_page + 3) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Next button
    html += `<li class="page-item ${current_page === total_pages ? 'disabled' : ''}">
        <a class="page-link" href="#" data-page="${current_page + 1}">Next</a>
    </li>`;
    
    html += '</ul></nav>';
    html += '</div>';
    
    container.innerHTML = html;
    
    // Attach event listeners
    container.querySelectorAll('.page-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const page = parseInt(link.dataset.page);
            if (page && page !== current_page && page >= 1 && page <= total_pages) {
                onPageChange(page);
            }
        });
    });
    
    // Rows per page change handler
    const rowsSelect = container.querySelector('#rowsPerPage');
    if (rowsSelect && onLimitChange) {
        rowsSelect.addEventListener('change', (e) => {
            const newLimit = parseInt(e.target.value);
            onLimitChange(newLimit);
        });
    }
}

// Barcode Scanner Support
let barcodeInput = '';
let barcodeTimer = null;

document.addEventListener('keypress', (e) => {
    if (e.target.tagName === 'INPUT' && e.target.type === 'text') {
        return; // Don't interfere with normal typing
    }
    
    if (e.key === 'Enter' && barcodeInput.length > 0) {
        clearTimeout(barcodeTimer);
        handleBarcodeScan(barcodeInput);
        barcodeInput = '';
    } else {
        barcodeInput += e.key;
        clearTimeout(barcodeTimer);
        barcodeTimer = setTimeout(() => {
            barcodeInput = '';
        }, 100);
    }
});

function handleBarcodeScan(barcode) {
    // This will be implemented in POS page
    if (typeof onBarcodeScan === 'function') {
        onBarcodeScan(barcode);
    }
}

// Print Invoice
function printInvoice(invoiceId, size = 'a4') {
    window.open(`invoice.php?id=${invoiceId}&size=${size}`, '_blank');
}

// Export to Excel (CSV)
function exportToCSV(data, filename) {
    if (!data || data.length === 0) {
        showAlert('No data to export', 'warning');
        return;
    }
    
    const headers = Object.keys(data[0]);
    const csv = [
        headers.join(','),
        ...data.map(row => headers.map(header => {
            const value = row[header] || '';
            return `"${String(value).replace(/"/g, '""')}"`;
        }).join(','))
    ].join('\n');
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}

// Sidebar Toggle Function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar && overlay) {
        sidebar.classList.toggle('collapsed');
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        if (mainContent) {
            mainContent.classList.toggle('sidebar-collapsed');
        }
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
}

// Initialize sidebar state from localStorage
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Restore sidebar state
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    const savedState = localStorage.getItem('sidebarCollapsed');
    
    if (savedState === 'true' && window.innerWidth > 768) {
        if (sidebar) {
            sidebar.classList.add('collapsed');
        }
        if (mainContent) {
            mainContent.classList.add('sidebar-collapsed');
        }
    }
    
    // Handle window resize
    window.addEventListener('resize', () => {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (window.innerWidth > 768) {
            // Desktop: remove overlay, handle collapsed state
            if (overlay) {
                overlay.classList.remove('show');
            }
            if (sidebar) {
                sidebar.classList.remove('show');
            }
        } else {
            // Mobile: ensure sidebar is hidden by default
            if (sidebar && !sidebar.classList.contains('show')) {
                sidebar.classList.add('collapsed');
            }
        }
    });
});

