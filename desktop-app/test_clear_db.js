/**
 * Test script for clearDatabase functions
 * Run this with: node test_clear_db.js
 */

const { initDatabase, getDatabase } = require('./database/db');
const { clearAllData, clearSyncData, clearSalesData, clearStockData } = require('./database/clearDatabase');

console.log('=== Testing Clear Database Functions ===\n');

// Initialize database
try {
    initDatabase();
    const db = getDatabase();
    console.log('✓ Database initialized\n');

    // Test 1: Check current data counts
    console.log('Current data counts:');
    const salesCount = db.prepare('SELECT COUNT(*) as count FROM sales').get();
    const stockCount = db.prepare('SELECT COUNT(*) as count FROM stock_inventory').get();
    const customersCount = db.prepare('SELECT COUNT(*) as count FROM customers').get();
    const productsCount = db.prepare('SELECT COUNT(*) as count FROM products').get();

    console.log(`  Sales: ${salesCount.count}`);
    console.log(`  Stock: ${stockCount.count}`);
    console.log(`  Customers: ${customersCount.count}`);
    console.log(`  Products: ${productsCount.count}\n`);

    // Test 2: Test clearSyncData
    console.log('Test 1: Testing clearSyncData()...');
    const result1 = clearSyncData();
    console.log(`  Result: ${result1.success ? '✓ Success' : '✗ Failed'}`);
    if (!result1.success) {
        console.log(`  Error: ${result1.message}`);
    }
    console.log('');

    // Test 3: Test clearSalesData
    console.log('Test 2: Testing clearSalesData()...');
    const result2 = clearSalesData();
    console.log(`  Result: ${result2.success ? '✓ Success' : '✗ Failed'}`);
    if (!result2.success) {
        console.log(`  Error: ${result2.message}`);
    }
    console.log('');

    // Test 4: Test clearStockData
    console.log('Test 3: Testing clearStockData()...');
    const result3 = clearStockData();
    console.log(`  Result: ${result3.success ? '✓ Success' : '✗ Failed'}`);
    if (!result3.success) {
        console.log(`  Error: ${result3.message}`);
    }
    console.log('');

    // Test 5: Test clearAllData (commented out by default - uncomment to test)
    console.log('Test 4: Testing clearAllData()...');
    console.log('  ⚠️  WARNING: This will delete ALL data!');
    console.log('  Uncomment the code below to test clearAllData()\n');

    // Uncomment to test clearAllData:
    // const result4 = clearAllData();
    // console.log(`  Result: ${result4.success ? '✓ Success' : '✗ Failed'}`);
    // if (!result4.success) {
    //   console.log(`  Error: ${result4.message}`);
    // }

    // Final counts
    console.log('Final data counts:');
    const finalSalesCount = db.prepare('SELECT COUNT(*) as count FROM sales').get();
    const finalStockCount = db.prepare('SELECT COUNT(*) as count FROM stock_inventory').get();
    const finalCustomersCount = db.prepare('SELECT COUNT(*) as count FROM customers').get();
    const finalProductsCount = db.prepare('SELECT COUNT(*) as count FROM products').get();

    console.log(`  Sales: ${finalSalesCount.count}`);
    console.log(`  Stock: ${finalStockCount.count}`);
    console.log(`  Customers: ${finalCustomersCount.count}`);
    console.log(`  Products: ${finalProductsCount.count}\n`);

    console.log('=== Testing Complete ===');

} catch (error) {
    console.error('Error:', error);
    console.error('Stack:', error.stack);
    process.exit(1);
}


