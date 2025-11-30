/**
 * Test script to sync stock to the API endpoint
 * Tests the NaOH stock record: sku75, 34 kg, batch 2344445, expiry 2026-02-18
 * 
 * Run with: node test_stock_sync_endpoint.js
 */

const axios = require('axios');

// Configuration
const API_BASE_URL = 'http://localhost/tyazubwenge_v2';
const SYNC_ENDPOINT = `${API_BASE_URL}/api/sync.php`;

console.log('=== Testing Stock Sync Endpoint ===\n');

// Test data - matching the stock record in the app
const testStockRecord = {
    local_id: 'test_stock_' + Date.now(),
    product_id: 1, // NaOH product ID (should match server)
    branch_id: 1,
    quantity: 34,
    unit: 'kg',
    batch_number: '2344445',
    expiry_date: '2026-02-18',
    supplier_id: null
};

const syncPayload = {
    entity: 'stock',
    records: [testStockRecord]
};

console.log('Step 1: Preparing sync payload...');
console.log('Payload:', JSON.stringify(syncPayload, null, 2));
console.log('');

// Function to test sync
async function testStockSync() {
    try {
        console.log('Step 2: Sending sync request...');
        console.log(`Endpoint: ${SYNC_ENDPOINT}\n`);

        const response = await axios.post(
            SYNC_ENDPOINT,
            syncPayload,
            {
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: 30000,
                validateStatus: function (status) {
                    return status < 500; // Don't throw for 4xx errors
                }
            }
        );

        console.log('Step 3: Response received');
        console.log(`HTTP Status: ${response.status}`);
        console.log('Response Data:', JSON.stringify(response.data, null, 2));
        console.log('');

        // Clean HTML warnings from response if present
        let responseData = response.data;
        if (typeof responseData === 'string') {
            // Try to extract JSON from HTML-wrapped response
            const jsonMatch = responseData.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                try {
                    responseData = JSON.parse(jsonMatch[0]);
                } catch (e) {
                    console.log('⚠ Could not parse JSON from response');
                }
            }
        }

        // Check response
        if (responseData && typeof responseData === 'object') {
            if (responseData.success) {
                console.log('✓ Sync successful!');
                console.log(`  - Synced: ${responseData.synced || 0}`);
                console.log(`  - Failed: ${responseData.failed || 0}`);

                if (responseData.errors && responseData.errors.length > 0) {
                    console.log('  - Errors:');
                    responseData.errors.forEach((error, index) => {
                        console.log(`    ${index + 1}. ${error}`);
                    });
                } else {
                    console.log('  - No errors!');
                }
            } else {
                console.log('✗ Sync failed');
                console.log(`  - Message: ${responseData.message || 'Unknown error'}`);
                if (responseData.errors) {
                    console.log('  - Errors:', responseData.errors);
                }
            }
        } else {
            console.log('✗ No valid response data received');
            console.log('Raw response:', response.data);
        }

        // Step 4: Verify on server
        console.log('\nStep 4: Verifying stock on server...');
        await verifyStockOnServer(testStockRecord.product_id);

    } catch (error) {
        console.error('✗ Error during sync:', error.message);

        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', JSON.stringify(error.response.data, null, 2));
        } else if (error.request) {
            console.error('No response received. Server might be offline.');
            console.error('Request:', error.request);
        } else {
            console.error('Error details:', error);
        }
    }
}

// Function to verify stock on server
async function verifyStockOnServer(productId) {
    try {
        // Try to get stock data from download endpoint
        const downloadUrl = `${API_BASE_URL}/api/download.php?entity=stock&last_sync=1970-01-01 00:00:00`;

        const response = await axios.get(downloadUrl, {
            headers: {
                'Content-Type': 'application/json'
            },
            timeout: 10000
        });

        if (response.data && response.data.stock) {
            const stockRecords = response.data.stock;
            const matchingStock = stockRecords.find(s =>
                s.product_id == productId &&
                s.quantity == 34 &&
                s.unit === 'kg' &&
                s.expiry_date === '2026-02-18'
            );

            if (matchingStock) {
                console.log('✓ Stock verified on server:');
                console.log(`  - ID: ${matchingStock.id}`);
                console.log(`  - Quantity: ${matchingStock.quantity} ${matchingStock.unit}`);
                console.log(`  - Expiry: ${matchingStock.expiry_date || 'None'}`);
                console.log(`  - Batch: ${matchingStock.batch_number || 'None'}`);
            } else {
                console.log('⚠ Stock not found with exact match, but may have been added to existing stock');
                const productStock = stockRecords.filter(s => s.product_id == productId);
                if (productStock.length > 0) {
                    console.log(`Found ${productStock.length} stock record(s) for product ${productId}:`);
                    productStock.forEach(s => {
                        console.log(`  - ID: ${s.id}, Qty: ${s.quantity} ${s.unit}, Expiry: ${s.expiry_date || 'None'}`);
                    });
                }
            }
        }
    } catch (error) {
        console.log('⚠ Could not verify stock on server (download endpoint may require auth)');
        console.log('  Error:', error.message);
    }
}

// Run the test
testStockSync()
    .then(() => {
        console.log('\n=== Test Complete ===');
        process.exit(0);
    })
    .catch((error) => {
        console.error('\n=== Test Failed ===');
        console.error(error);
        process.exit(1);
    });

