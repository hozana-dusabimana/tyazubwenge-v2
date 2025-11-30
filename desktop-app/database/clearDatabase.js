/**
 * Utility to clear all data from the local SQLite database
 * Use with caution - this will delete ALL local data!
 */

const { getDatabase } = require('./db');

/**
 * Clear all data from all tables (except settings)
 * This will delete:
 * - All sales and sale items
 * - All stock inventory
 * - All customers
 * - All products, categories, brands, suppliers
 * - All sync mappings and queue
 * - All users
 * 
 * Settings table is preserved (keeps auth token, API URL, etc.)
 */
function clearAllData() {
    const db = getDatabase();

    try {
        db.exec('BEGIN TRANSACTION');

        // Delete in order to respect foreign key constraints
        db.exec('DELETE FROM sale_items');
        db.exec('DELETE FROM sales');
        db.exec('DELETE FROM stock_inventory');
        db.exec('DELETE FROM customers');
        db.exec('DELETE FROM products');
        db.exec('DELETE FROM categories');
        db.exec('DELETE FROM brands');
        db.exec('DELETE FROM suppliers');
        db.exec('DELETE FROM users');
        db.exec('DELETE FROM sync_queue');
        db.exec('DELETE FROM sync_mappings');

        // Reset auto-increment counters (SQLite specific)
        // Use single quotes for string literals in SQL
        db.exec(`DELETE FROM sqlite_sequence WHERE name IN ('sales', 'sale_items', 'stock_inventory', 'customers', 'sync_queue', 'sync_mappings')`);

        db.exec('COMMIT');

        console.log('All data cleared successfully');
        return { success: true, message: 'All data cleared successfully' };
    } catch (error) {
        db.exec('ROLLBACK');
        console.error('Error clearing database:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Clear only sync-related data (sales, stock, customers that are pending sync)
 */
function clearSyncData() {
    const db = getDatabase();

    try {
        db.exec('BEGIN TRANSACTION');

        // Delete only pending sync records
        db.exec('DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE sync_status = "pending")');
        db.exec('DELETE FROM sales WHERE sync_status = "pending"');
        db.exec('DELETE FROM stock_inventory WHERE sync_status = "pending"');
        db.exec('DELETE FROM customers WHERE sync_status = "pending"');
        db.exec('DELETE FROM sync_queue');
        db.exec('DELETE FROM sync_mappings');

        db.exec('COMMIT');

        console.log('Sync data cleared successfully');
        return { success: true, message: 'Sync data cleared successfully' };
    } catch (error) {
        db.exec('ROLLBACK');
        console.error('Error clearing sync data:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Clear only sales data
 */
function clearSalesData() {
    const db = getDatabase();

    try {
        db.exec('BEGIN TRANSACTION');

        db.exec('DELETE FROM sale_items');
        db.exec('DELETE FROM sales');

        db.exec('COMMIT');

        console.log('Sales data cleared successfully');
        return { success: true, message: 'Sales data cleared successfully' };
    } catch (error) {
        db.exec('ROLLBACK');
        console.error('Error clearing sales data:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Clear only stock data
 */
function clearStockData() {
    const db = getDatabase();

    try {
        db.exec('BEGIN TRANSACTION');

        db.exec('DELETE FROM stock_inventory');

        db.exec('COMMIT');

        console.log('Stock data cleared successfully');
        return { success: true, message: 'Stock data cleared successfully' };
    } catch (error) {
        db.exec('ROLLBACK');
        console.error('Error clearing stock data:', error);
        return { success: false, message: error.message };
    }
}

/**
 * Clear only customers data
 */
function clearCustomersData() {
    const db = getDatabase();

    try {
        db.exec('BEGIN TRANSACTION');

        // Delete customers (sales that reference customers will have customer_id set to NULL due to foreign key)
        db.exec('DELETE FROM customers');

        db.exec('COMMIT');

        console.log('Customers data cleared successfully');
        return { success: true, message: 'Customers data cleared successfully' };
    } catch (error) {
        db.exec('ROLLBACK');
        console.error('Error clearing customers data:', error);
        return { success: false, message: error.message };
    }
}

module.exports = {
    clearAllData,
    clearSyncData,
    clearSalesData,
    clearStockData,
    clearCustomersData
};

