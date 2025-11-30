/**
 * Test script to verify stock sync for specific record
 * Product: NaOH, SKU: sku75, Quantity: 34 kg, Expiry: 2026-02-18
 */

const { initDatabase, getDatabase } = require('./database/db');
const axios = require('axios');

console.log('=== Testing Stock Sync ===\n');

// Initialize database
try {
    initDatabase();
    const db = getDatabase();
    console.log('✓ Database initialized\n');

    // First, check if the product exists
    console.log('Step 1: Checking if product exists...');
    const product = db.prepare('SELECT id, name, sku, unit FROM products WHERE sku = ? OR name = ?').get('sku75', 'NaOH');

    if (!product) {
        console.log('✗ Product not found. Creating test product...');
        // Create the product first
        const productId = db.prepare(`
      INSERT INTO products (id, name, sku, unit, retail_price, wholesale_price, cost_price, min_stock_level, status, server_synced)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).run(
            75, // Use the ID from SKU
            'NaOH',
            'sku75',
            'kg',
            100.00,
            80.00,
            50.00,
            10,
            'active',
            1
        ).lastInsertRowid;
        console.log(`✓ Product created with ID: ${productId}\n`);
    } else {
        console.log(`✓ Product found: ID=${product.id}, Name=${product.name}, SKU=${product.sku}, Unit=${product.unit}\n`);
    }

    const productId = product ? product.id : 75;

    // Check if stock record exists
    console.log('Step 2: Checking for existing stock record...');
    const existingStock = db.prepare(`
    SELECT * FROM stock_inventory 
    WHERE product_id = ? AND quantity = 34 AND unit = 'kg' AND expiry_date = '2026-02-18'
  `).get(productId);

    let stockId;
    if (existingStock) {
        console.log(`✓ Stock record found: ID=${existingStock.id}`);
        console.log(`  - Sync Status: ${existingStock.sync_status}`);
        console.log(`  - Local ID: ${existingStock.local_id || 'None'}\n`);
        stockId = existingStock.id;

        // Reset to pending for testing
        if (existingStock.sync_status === 'synced') {
            console.log('Resetting sync_status to pending for testing...');
            db.prepare('UPDATE stock_inventory SET sync_status = ? WHERE id = ?').run('pending', stockId);
            console.log('✓ Reset to pending\n');
        }
    } else {
        console.log('✗ Stock record not found. Creating test stock record...');
        // Create the stock record
        const result = db.prepare(`
      INSERT INTO stock_inventory 
      (product_id, branch_id, quantity, unit, batch_number, expiry_date, supplier_id, sync_status, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    `).run(
            productId,
            1, // branch_id
            34, // quantity
            'kg', // unit
            '2344445', // batch_number (assuming this is the batch number)
            '2026-02-18', // expiry_date
            null, // supplier_id (or could be 2344445 if that's supplier_id)
            'pending' // sync_status
        );
        stockId = result.lastInsertRowid;
        console.log(`✓ Stock record created with ID: ${stockId}\n`);
    }

    // Get the stock record
    const stockRecord = db.prepare('SELECT * FROM stock_inventory WHERE id = ?').get(stockId);
    console.log('Step 3: Stock record details:');
    console.log(JSON.stringify(stockRecord, null, 2));
    console.log('');

    // Check pending stock count
    console.log('Step 4: Checking pending stock count...');
    const pendingCount = db.prepare("SELECT COUNT(*) as count FROM stock_inventory WHERE sync_status = 'pending'").get();
    console.log(`Pending stock records: ${pendingCount.count}\n`);

    // Test the sync by preparing the data as syncManager would
    console.log('Step 5: Preparing data for sync (as syncManager would)...');
    const apiRecord = { ...stockRecord };

    // Add local_id if not present
    if (!apiRecord.local_id) {
        const crypto = require('crypto');
        apiRecord.local_id = crypto.randomBytes(16).toString('hex');
        console.log(`Generated local_id: ${apiRecord.local_id}`);
        db.prepare('UPDATE stock_inventory SET local_id = ? WHERE id = ?').run(apiRecord.local_id, stockRecord.id);
    }

    // Remove local database fields
    delete apiRecord.id;
    delete apiRecord.server_id;
    delete apiRecord.sync_status;
    delete apiRecord.created_at;
    delete apiRecord.updated_at;

    console.log('Prepared API record:');
    console.log(JSON.stringify(apiRecord, null, 2));
    console.log('');

    // Test API endpoint structure
    console.log('Step 6: Testing API endpoint structure...');
    const testPayload = {
        entity: 'stock',
        records: [apiRecord]
    };

    console.log('Payload structure:');
    console.log(JSON.stringify(testPayload, null, 2));
    console.log('');

    // Verify required fields
    console.log('Step 7: Verifying required fields...');
    const requiredFields = ['product_id', 'branch_id', 'quantity', 'unit'];
    const missingFields = requiredFields.filter(field => !(field in apiRecord));

    if (missingFields.length > 0) {
        console.log(`✗ Missing required fields: ${missingFields.join(', ')}\n`);
    } else {
        console.log('✓ All required fields present\n');
    }

    // Check if product_id exists on server (would need API call)
    console.log('Step 8: Summary');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log(`Product ID: ${productId}`);
    console.log(`Stock ID: ${stockId}`);
    console.log(`Local ID: ${apiRecord.local_id}`);
    console.log(`Quantity: ${apiRecord.quantity} ${apiRecord.unit}`);
    console.log(`Expiry Date: ${apiRecord.expiry_date || 'None'}`);
    console.log(`Batch Number: ${apiRecord.batch_number || 'None'}`);
    console.log(`Sync Status: ${stockRecord.sync_status}`);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n');

    console.log('✓ Test preparation complete!');
    console.log('The stock record is ready to sync.');
    console.log('You can now test the sync from the desktop app or use the API directly.\n');

} catch (error) {
    console.error('✗ Error:', error);
    console.error('Stack:', error.stack);
    process.exit(1);
}


