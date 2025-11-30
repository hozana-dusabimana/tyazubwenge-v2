const ProductsPage = {
    async load() {
        const content = `
            <div class="page-header">
                <h2><i class="bi bi-tags"></i> Products</h2>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div id="productsTable">Loading...</div>
                </div>
            </div>
        `;
        
        document.getElementById('pageContent').innerHTML = content;
        await this.loadProducts();
    },

    async loadProducts() {
        const products = await window.electronAPI.db.query(
            `SELECT * FROM products ORDER BY name`
        );

        const tableDiv = document.getElementById('productsTable');
        
        if (products && products.length > 0) {
            tableDiv.innerHTML = `
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Unit</th>
                            <th>Cost Price</th>
                            <th>Retail Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${products.map(product => `
                            <tr>
                                <td>${product.name}</td>
                                <td>${product.sku || 'N/A'}</td>
                                <td>${product.unit || 'pcs'}</td>
                                <td>${(product.cost_price || 0).toFixed(2)}</td>
                                <td>${(product.retail_price || 0).toFixed(2)}</td>
                                <td>
                                    <span class="badge ${product.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                                        ${product.status || 'active'}
                                    </span>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            tableDiv.innerHTML = '<p class="text-muted">No products found. Sync to download products.</p>';
        }
    }
};

window.ProductsPage = ProductsPage;

