const axios = require('axios');
const { getDatabase } = require('../database/db');
const crypto = require('crypto');

// Simple UUID generator (since we don't want to add uuid package)
function generateUUID() {
    return crypto.randomBytes(16).toString('hex');
}

class SyncManager {
    constructor(authManager) {
        this.authManager = authManager;
        this.isOnline = false;
        this.isSyncing = false;
        this.syncInterval = null;
        this.connectionCheckInterval = null;
        this.checkConnection();
    }

    async checkConnection() {
        try {
            const baseURL = this.authManager.getBaseURL();
            const token = this.authManager.getToken();

            // Try to check connection with token if available
            if (token) {
                try {
                    const response = await axios.get(`${baseURL}/api/auth.php`, {
                        params: { token: token },
                        timeout: 5000,
                        validateStatus: (status) => status < 500 // Accept 401/403 as "online but not authenticated"
                    });
                    // If we get any response (even 401), we're online
                    // But if token is invalid, we should still consider it online (server is reachable)
                    if (response.status === 401) {
                        console.warn('Token invalid or expired, but server is reachable');
                        // Token might be expired, but server is online
                        this.isOnline = true;
                        return true;
                    }
                    this.isOnline = true;
                    return true;
                } catch (error) {
                    // Network errors mean offline
                    if (error.code === 'ECONNREFUSED' || error.code === 'ENOTFOUND' || error.code === 'ETIMEDOUT' || error.message.includes('timeout')) {
                        this.isOnline = false;
                        return false;
                    }
                    // 401/403 errors mean we're online but token is invalid
                    if (error.response && (error.response.status === 401 || error.response.status === 403)) {
                        console.warn('Token invalid or expired, but server is reachable');
                        this.isOnline = true;
                        return true;
                    }
                    // Other errors mean we're online
                    this.isOnline = true;
                    return true;
                }
            } else {
                // No token, try a simple connection test
                try {
                    const response = await axios.get(`${baseURL}/api/auth.php`, {
                        timeout: 5000,
                        validateStatus: (status) => status < 500
                    });
                    this.isOnline = true;
                    return true;
                } catch (error) {
                    // Network errors mean offline
                    if (error.code === 'ECONNREFUSED' || error.code === 'ENOTFOUND' || error.code === 'ETIMEDOUT' || error.message.includes('timeout')) {
                        this.isOnline = false;
                        return false;
                    }
                    // Other errors mean we're online
                    this.isOnline = true;
                    return true;
                }
            }
        } catch (error) {
            // Any unexpected error - assume offline
            console.error('Connection check error:', error.message);
            this.isOnline = false;
            return false;
        }
    }

    startAutoSync(interval = 60000) { // Default: every 60 seconds
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }

        // Check connection periodically (every 30 seconds)
        this.connectionCheckInterval = setInterval(async () => {
            await this.checkConnection();
        }, 30000);

        this.syncInterval = setInterval(async () => {
            // Recheck connection before syncing
            await this.checkConnection();
            if (this.isOnline && !this.isSyncing) {
                await this.syncNow();
            }
        }, interval);
    }

    stopAutoSync() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
            this.syncInterval = null;
        }
        if (this.connectionCheckInterval) {
            clearInterval(this.connectionCheckInterval);
            this.connectionCheckInterval = null;
        }
    }

    async syncNow() {
        if (this.isSyncing) {
            return { success: false, message: 'Sync already in progress' };
        }

        // Always reload token from storage before sync to ensure it's available
        this.authManager.loadStoredAuth();

        if (!this.authManager.isAuthenticated()) {
            console.error('Not authenticated in syncNow after reload');
            return { success: false, message: 'Not authenticated. Please login again.' };
        }

        let token = this.authManager.getToken();
        if (!token) {
            console.error('Token still not available in syncNow after reload');
            // Try direct database read as last resort
            try {
                const { getDatabase } = require('../database/db');
                const db = getDatabase();
                const tokenSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
                if (tokenSetting && tokenSetting.value) {
                    this.authManager.token = tokenSetting.value;
                    token = this.authManager.token;
                    console.log('Token loaded directly from database in syncNow');
                }
            } catch (dbError) {
                console.error('Error reading token from database:', dbError);
            }
        }

        if (!token) {
            console.error('No token available after all reload attempts in syncNow');
            return { success: false, message: 'No authentication token. Please login again.' };
        }

        console.log('Token available in syncNow:', token.substring(0, 10) + '...');

        await this.checkConnection();
        if (!this.isOnline) {
            return { success: false, message: 'Offline - cannot sync' };
        }

        this.isSyncing = true;
        const db = getDatabase();
        const results = {
            success: true,
            synced: 0,
            failed: 0,
            errors: []
        };

        try {
            // Sync sales
            const salesResult = await this.syncEntity('sales', db);
            results.synced += salesResult.synced;
            results.failed += salesResult.failed;
            results.errors.push(...salesResult.errors);

            // Sync stock
            const stockResult = await this.syncEntity('stock', db);
            results.synced += stockResult.synced;
            results.failed += stockResult.failed;
            results.errors.push(...stockResult.errors);

            // Sync customers
            const customersResult = await this.syncEntity('customers', db);
            results.synced += customersResult.synced;
            results.failed += customersResult.failed;
            results.errors.push(...customersResult.errors);

            // Download updates
            await this.downloadUpdates(db);

        } catch (error) {
            results.success = false;
            results.errors.push(error.message);
        } finally {
            this.isSyncing = false;
        }

        return results;
    }

    async syncEntity(entityType, db) {
        const results = { synced: 0, failed: 0, errors: [] };

        // Get pending records
        let query, records;

        switch (entityType) {
            case 'sales':
                query = "SELECT * FROM sales WHERE sync_status = 'pending' ORDER BY created_at ASC LIMIT 100";
                records = db.prepare(query).all();
                break;
            case 'stock':
                query = "SELECT * FROM stock_inventory WHERE sync_status = 'pending' ORDER BY created_at ASC LIMIT 100";
                records = db.prepare(query).all();
                break;
            case 'customers':
                query = "SELECT * FROM customers WHERE sync_status = 'pending' ORDER BY created_at ASC LIMIT 100";
                records = db.prepare(query).all();
                break;
            default:
                return results;
        }

        if (records.length === 0) {
            return results;
        }

        // Prepare records for API
        const apiRecords = records.map(record => {
            const apiRecord = { ...record };

            // Add local_id if not present
            if (!apiRecord.local_id) {
                apiRecord.local_id = generateUUID();
                // Update local record with local_id
                // Map entity type to actual table name
                const tableName = entityType === 'stock' ? 'stock_inventory' : entityType;
                const updateQuery = `UPDATE ${tableName} SET local_id = ? WHERE id = ?`;
                db.prepare(updateQuery).run(apiRecord.local_id, record.id);
            }

            // Remove local database fields
            delete apiRecord.id;
            delete apiRecord.server_id;
            delete apiRecord.sync_status;
            delete apiRecord.created_at;
            delete apiRecord.updated_at;
            delete apiRecord.cost_price; // Not used in stock_inventory sync

            // For stock, map product_id to server's product_id
            if (entityType === 'stock' && apiRecord.product_id) {
                // Check if product has a server_id mapping
                const product = db.prepare('SELECT server_id, local_id FROM products WHERE id = ?').get(apiRecord.product_id);
                if (product) {
                    if (product.server_id) {
                        // Use server_id if available
                        console.log(`[SYNC] Mapping local product_id ${apiRecord.product_id} to server_id ${product.server_id}`);
                        apiRecord.product_id = product.server_id;
                    } else if (product.local_id) {
                        // Try to find server_id via sync_mappings
                        const mapping = db.prepare('SELECT server_id FROM sync_mappings WHERE entity_type = ? AND local_id = ?').get('products', product.local_id);
                        if (mapping && mapping.server_id) {
                            console.log(`[SYNC] Mapping product via sync_mappings: ${product.local_id} -> ${mapping.server_id}`);
                            apiRecord.product_id = mapping.server_id;
                        } else {
                            console.warn(`[SYNC] Warning: Product ID ${apiRecord.product_id} has no server mapping. Using as-is (may fail if product doesn't exist on server).`);
                        }
                    }
                } else {
                    console.error(`[SYNC] Error: Product ID ${apiRecord.product_id} not found in local database!`);
                }
            }

            // Handle entity-specific formatting
            if (entityType === 'sales') {
                // Get sale items
                const items = db.prepare('SELECT * FROM sale_items WHERE sale_id = ?').all(record.id);
                apiRecord.items = items.map(item => {
                    const itemCopy = { ...item };
                    delete itemCopy.id;
                    delete itemCopy.sale_id;

                    // Map product_id to server's product_id for sale items
                    if (itemCopy.product_id) {
                        const product = db.prepare('SELECT server_id, local_id FROM products WHERE id = ?').get(itemCopy.product_id);
                        if (product) {
                            if (product.server_id) {
                                console.log(`[SYNC] Mapping sale item product_id ${itemCopy.product_id} to server_id ${product.server_id}`);
                                itemCopy.product_id = product.server_id;
                            } else if (product.local_id) {
                                const mapping = db.prepare('SELECT server_id FROM sync_mappings WHERE entity_type = ? AND local_id = ?').get('products', product.local_id);
                                if (mapping && mapping.server_id) {
                                    console.log(`[SYNC] Mapping sale item product via sync_mappings: ${product.local_id} -> ${mapping.server_id}`);
                                    itemCopy.product_id = mapping.server_id;
                                } else {
                                    console.warn(`[SYNC] Warning: Sale item product ID ${itemCopy.product_id} has no server mapping. Using as-is.`);
                                }
                            }
                        } else {
                            console.error(`[SYNC] Error: Sale item product ID ${itemCopy.product_id} not found in local database!`);
                        }
                    }

                    return itemCopy;
                });
            }

            // For customers, ensure all fields are properly formatted
            if (entityType === 'customers') {
                // Ensure customer_id is null if not set (for new customers)
                if (!apiRecord.customer_id) {
                    delete apiRecord.customer_id;
                }
            }

            // Log the prepared record for debugging
            console.log(`[SYNC] Prepared ${entityType} record:`, JSON.stringify(apiRecord, null, 2));

            return apiRecord;
        });

        try {
            // Token is optional - try to get it but don't fail if not available
            let token = this.authManager.getToken();
            if (!token) {
                // Try to reload token from storage
                console.log('Token not found, reloading from storage...');
                this.authManager.loadStoredAuth();
                token = this.authManager.getToken();
            }

            // Prepare request headers and data
            const requestHeaders = {
                'Content-Type': 'application/json'
            };

            const requestData = {
                entity: entityType,
                records: apiRecords
            };

            // Add token if available (optional)
            if (token) {
                requestHeaders['Authorization'] = `Bearer ${token}`;
                requestData.token = token;
                console.log(`[SYNC] Sending with token for ${entityType}:`);
                console.log(`  - Token length: ${token.length}`);
                console.log(`  - Token preview: ${token.substring(0, 20)}...`);
            } else {
                console.log(`[SYNC] Sending without token for ${entityType} (token optional)`);
            }

            const response = await axios.post(
                `${this.authManager.getBaseURL()}/api/sync.php`,
                requestData,
                {
                    headers: requestHeaders
                }
            );

            // Only check for authentication errors if we sent a token
            if (token && (response.status === 401 || (response.data && !response.data.success && response.data.message && response.data.message.includes('token')))) {
                console.warn('Token authentication failed, but continuing sync (token is optional)');
                // Don't throw - continue processing the response
            }

            if (response.data.success) {
                // Map entity type to actual table name
                const tableName = entityType === 'stock' ? 'stock_inventory' : entityType;

                // Update sync status for synced records
                records.forEach((record, index) => {
                    if (index < response.data.synced) {
                        try {
                            const updateQuery = `UPDATE ${tableName} SET sync_status = 'synced', updated_at = CURRENT_TIMESTAMP WHERE id = ?`;
                            db.prepare(updateQuery).run(record.id);
                            console.log(`[SYNC] ✓ Marked ${entityType} record ${record.id} as synced`);
                        } catch (updateError) {
                            console.error(`[SYNC] ✗ Error updating sync status for ${entityType} record ${record.id}:`, updateError.message);
                        }

                        // Store server_id mapping if available
                        // Note: The API should return server IDs, but for now we'll mark as synced
                    }
                });

                // Log sync results
                console.log(`[SYNC] ${entityType} sync complete: ${response.data.synced} synced, ${response.data.failed} failed`);
                if (response.data.errors && response.data.errors.length > 0) {
                    console.error(`[SYNC] ${entityType} sync errors:`, response.data.errors);
                }

                results.synced = response.data.synced;
                results.failed = response.data.failed;
                results.errors = response.data.errors || [];
            } else {
                results.failed = records.length;
                const errorMsg = response.data.message || 'Sync failed';
                results.errors.push(errorMsg);
                console.error(`[SYNC] ${entityType} sync failed:`, errorMsg);
                if (response.data.errors) {
                    console.error(`[SYNC] ${entityType} sync detailed errors:`, response.data.errors);
                }
            }
        } catch (error) {
            if (error.response) {
                // Server responded with error
                const status = error.response.status;
                const message = error.response.data?.message || 'Sync failed';

                if (status === 401 || message.includes('token') || message.includes('Unauthorized')) {
                    // Token expired or invalid
                    results.success = false;
                    results.failed = records.length;
                    results.errors.push('Authentication failed. Token expired or invalid. Please logout and login again.');
                    // Clear invalid token
                    this.authManager.logout();
                } else {
                    results.success = false;
                    results.failed = records.length;
                    results.errors.push(message);
                }
            } else {
                // Network or other error
                results.success = false;
                results.failed = records.length;
                results.errors.push(error.message || 'Network error');
            }
        }

        return results;
    }

    async downloadAllData(db) {
        // Download all data (for first login)
        return await this.downloadUpdates(db, true);
    }

    async downloadUpdates(db, forceAll = false) {
        try {
            // Get last sync time
            let lastSync = null;
            if (!forceAll) {
                const lastSyncSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('last_sync_time');
                lastSync = lastSyncSetting ? lastSyncSetting.value : null;
            }

            // Always reload token from storage before download to ensure it's available
            this.authManager.loadStoredAuth();

            let token = this.authManager.getToken();
            if (!token) {
                console.error('Token not found in downloadUpdates after reload, trying direct database read...');
                // Try direct database read as last resort
                try {
                    const { getDatabase } = require('../database/db');
                    const db = getDatabase();
                    const tokenSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('auth_token');
                    if (tokenSetting && tokenSetting.value) {
                        this.authManager.token = tokenSetting.value;
                        token = this.authManager.token;
                        console.log('Token loaded directly from database in downloadUpdates');
                    }
                } catch (dbError) {
                    console.error('Error reading token from database:', dbError);
                }
            }

            if (!token) {
                console.error('No token available after all reload attempts in downloadUpdates');
                throw new Error('No authentication token available. Please login again.');
            }

            console.log('Token available in downloadUpdates:', token.substring(0, 10) + '...');

            const response = await axios.get(
                `${this.authManager.getBaseURL()}/api/download.php`,
                {
                    params: {
                        token: token,
                        entity: 'all',
                        last_sync: lastSync
                    },
                    headers: {
                        'Authorization': `Bearer ${token}`
                    }
                }
            );

            // Check for authentication errors
            if (response.status === 401 || (response.data && !response.data.success && response.data.message && response.data.message.includes('token'))) {
                throw new Error('Authentication failed. Token expired or invalid. Please logout and login again.');
            }

            if (response.data.success) {
                const data = response.data.data;

                // Update products
                if (data.products) {
                    data.products.forEach(product => {
                        db.prepare(`
              INSERT OR REPLACE INTO products 
              (id, name, sku, barcode, description, category_id, brand_id, unit, 
               cost_price, retail_price, wholesale_price, min_stock_level, status, 
               server_id, server_synced, updated_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
            `).run(
                            product.id, product.name, product.sku, product.barcode, product.description,
                            product.category_id, product.brand_id, product.unit,
                            product.cost_price, product.retail_price, product.wholesale_price,
                            product.min_stock_level, product.status, product.id
                        );
                    });
                }

                // Update stock
                if (data.stock) {
                    data.stock.forEach(stock => {
                        const existing = db.prepare('SELECT id FROM stock_inventory WHERE server_id = ?').get(stock.id);
                        if (existing) {
                            db.prepare(`
                UPDATE stock_inventory SET 
                  quantity = ?, unit = ?, cost_price = ?, batch_number = ?, 
                  expiry_date = ?, supplier_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE server_id = ?
              `).run(
                                stock.quantity, stock.unit, stock.cost_price, stock.batch_number,
                                stock.expiry_date, stock.supplier_id, stock.id
                            );
                        }
                    });
                }

                // Update customers
                if (data.customers) {
                    data.customers.forEach(customer => {
                        const existing = db.prepare('SELECT id FROM customers WHERE server_id = ?').get(customer.id);
                        if (existing) {
                            db.prepare(`
                UPDATE customers SET 
                  name = ?, email = ?, phone = ?, address = ?, 
                  loyalty_points = ?, credit_limit = ?, updated_at = CURRENT_TIMESTAMP
                WHERE server_id = ?
              `).run(
                                customer.name, customer.email, customer.phone, customer.address,
                                customer.loyalty_points, customer.credit_limit, customer.id
                            );
                        }
                    });
                }

                // Update last sync time
                db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')
                    .run('last_sync_time', response.data.server_time);

                // Mark that we have initial data
                db.prepare('INSERT OR REPLACE INTO settings (key, value) VALUES (?, ?)')
                    .run('has_initial_data', '1');
            }
        } catch (error) {
            console.error('Download updates error:', error);

            // Check if it's an authentication error
            if (error.response && (error.response.status === 401 ||
                (error.response.data && error.response.data.message &&
                    (error.response.data.message.includes('token') || error.response.data.message.includes('Unauthorized'))))) {
                // Token expired or invalid - clear it
                this.authManager.logout();
                throw new Error('Authentication failed. Token expired or invalid. Please logout and login again.');
            }

            throw error;
        }
    }

    async getStatus() {
        // Always check connection when getting status
        await this.checkConnection();
        const db = getDatabase();

        const pendingSales = db.prepare("SELECT COUNT(*) as count FROM sales WHERE sync_status = 'pending'").get();
        const pendingStock = db.prepare("SELECT COUNT(*) as count FROM stock_inventory WHERE sync_status = 'pending'").get();
        const pendingCustomers = db.prepare("SELECT COUNT(*) as count FROM customers WHERE sync_status = 'pending'").get();

        const lastSyncSetting = db.prepare('SELECT value FROM settings WHERE key = ?').get('last_sync_time');

        return {
            isOnline: this.isOnline,
            isSyncing: this.isSyncing,
            pending: {
                sales: pendingSales.count,
                stock: pendingStock.count,
                customers: pendingCustomers.count,
                total: pendingSales.count + pendingStock.count + pendingCustomers.count
            },
            lastSync: lastSyncSetting ? lastSyncSetting.value : null
        };
    }
}

module.exports = SyncManager;

