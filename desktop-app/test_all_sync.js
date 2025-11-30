/**
 * Test script to sync all entity types (Sales, Stock, Customers) to the API endpoint
 * 
 * Run with: node test_all_sync.js
 */

const axios = require('axios');

// Configuration
const API_BASE_URL = 'http://localhost/tyazubwenge_v2';
const SYNC_ENDPOINT = `${API_BASE_URL}/api/sync.php`;

console.log('=== Testing All Entity Sync Endpoints ===\n');

// Test data for each entity type
const testData = {
    sales: {
        entity: 'sales',
        records: [{
            local_id: 'test_sale_' + Date.now(),
            customer_id: null,
            user_id: 1,
            branch_id: 1,
            total_amount: 100.00,
            discount: 0,
            tax: 0,
            final_amount: 100.00,
            payment_method: 'cash',
            payment_status: 'completed',
            sale_type: 'retail',
            notes: 'Test sale',
            items: [{
                product_id: 1, // NaOH
                quantity: 2,
                unit: 'kg',
                unit_price: 50.00,
                discount: 0,
                subtotal: 100.00
            }],
            created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
        }]
    },
    stock: {
        entity: 'stock',
        records: [{
            local_id: 'test_stock_' + Date.now(),
            product_id: 1, // NaOH
            branch_id: 1,
            quantity: 34,
            unit: 'kg',
            batch_number: '2344445',
            expiry_date: '2026-02-18',
            supplier_id: null
        }]
    },
    customers: {
        entity: 'customers',
        records: [{
            local_id: 'test_customer_' + Date.now(),
            name: 'Test Customer ' + Date.now(),
            email: 'test' + Date.now() + '@example.com',
            phone: '+250788' + Math.floor(Math.random() * 1000000),
            address: 'Test Address',
            loyalty_points: 0,
            credit_limit: 0,
            status: 'active',
            created_at: new Date().toISOString().slice(0, 19).replace('T', ' ')
        }]
    }
};

// Function to test sync for each entity
async function testSync(entityType, payload) {
    console.log(`\n${'='.repeat(50)}`);
    console.log(`Testing ${entityType.toUpperCase()} Sync`);
    console.log('='.repeat(50));

    try {
        console.log(`\nPayload:`, JSON.stringify(payload, null, 2));
        console.log(`\nSending to: ${SYNC_ENDPOINT}`);

        const response = await axios.post(
            SYNC_ENDPOINT,
            payload,
            {
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: 30000,
                validateStatus: function (status) {
                    return status < 500;
                }
            }
        );

        // Clean HTML warnings from response if present
        let responseData = response.data;
        if (typeof responseData === 'string') {
            const jsonMatch = responseData.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
                try {
                    responseData = JSON.parse(jsonMatch[0]);
                } catch (e) {
                    console.log('⚠ Could not parse JSON from response');
                }
            }
        }

        console.log(`\nHTTP Status: ${response.status}`);
        console.log('Response:', JSON.stringify(responseData, null, 2));

        if (responseData && typeof responseData === 'object') {
            if (responseData.success) {
                console.log(`\n✓ ${entityType.toUpperCase()} sync successful!`);
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
                return true;
            } else {
                console.log(`\n✗ ${entityType.toUpperCase()} sync failed`);
                console.log(`  - Message: ${responseData.message || 'Unknown error'}`);
                if (responseData.errors) {
                    console.log('  - Errors:', responseData.errors);
                }
                return false;
            }
        } else {
            console.log(`\n✗ ${entityType.toUpperCase()} - No valid response data`);
            return false;
        }

    } catch (error) {
        console.error(`\n✗ ${entityType.toUpperCase()} sync error:`, error.message);

        if (error.response) {
            console.error('Response status:', error.response.status);
            console.error('Response data:', JSON.stringify(error.response.data, null, 2));
        } else if (error.request) {
            console.error('No response received. Server might be offline.');
        }
        return false;
    }
}

// Run all tests
async function runAllTests() {
    const results = {
        sales: false,
        stock: false,
        customers: false
    };

    // Test Sales
    results.sales = await testSync('sales', testData.sales);

    // Test Stock
    results.stock = await testSync('stock', testData.stock);

    // Test Customers
    results.customers = await testSync('customers', testData.customers);

    // Summary
    console.log(`\n${'='.repeat(50)}`);
    console.log('SUMMARY');
    console.log('='.repeat(50));
    console.log(`Sales:    ${results.sales ? '✓ PASS' : '✗ FAIL'}`);
    console.log(`Stock:    ${results.stock ? '✓ PASS' : '✗ FAIL'}`);
    console.log(`Customers: ${results.customers ? '✓ PASS' : '✗ FAIL'}`);
    console.log('='.repeat(50));

    const allPassed = results.sales && results.stock && results.customers;
    console.log(`\nOverall: ${allPassed ? '✓ ALL TESTS PASSED' : '✗ SOME TESTS FAILED'}\n`);
}

// Run the tests
runAllTests()
    .then(() => {
        console.log('=== All Tests Complete ===');
        process.exit(0);
    })
    .catch((error) => {
        console.error('\n=== Test Suite Failed ===');
        console.error(error);
        process.exit(1);
    });

