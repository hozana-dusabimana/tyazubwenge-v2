import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';
import 'package:flutter/foundation.dart';
import 'package:sqflite_common_ffi/sqflite_ffi.dart';
import '../database/db_helper.dart';
import 'auth_service.dart';

class SyncService extends ChangeNotifier {
  final AuthService authService;
  bool _isOnline = false;
  bool _isSyncing = false;

  SyncService(this.authService);

  Future<bool> checkConnection() async {
    try {
      final baseURL = authService.getBaseURL();
      final token = authService.getToken();

      await http.get(
        Uri.parse('$baseURL/api/auth.php?token=${token ?? ''}'),
      ).timeout(const Duration(seconds: 5));

      _isOnline = true;
      notifyListeners();
      return true;
    } catch (e) {
      _isOnline = false;
      notifyListeners();
      return false;
    }
  }

  Future<Map<String, dynamic>> syncNow() async {
    if (_isSyncing) {
      return {'success': false, 'message': 'Sync already in progress'};
    }

    _isSyncing = true;
    final results = <String, dynamic>{
      'synced': 0,
      'failed': 0,
      'errors': <String>[],
    };

    try {
      // Sync sales
      final salesResult = await _syncEntity('sales');
      results['synced'] = (results['synced'] as int) + (salesResult['synced'] as int? ?? 0);
      results['failed'] = (results['failed'] as int) + (salesResult['failed'] as int? ?? 0);
      (results['errors'] as List<String>).addAll((salesResult['errors'] as List?)?.cast<String>() ?? []);

      // Sync stock
      final stockResult = await _syncEntity('stock');
      results['synced'] = (results['synced'] as int) + (stockResult['synced'] as int? ?? 0);
      results['failed'] = (results['failed'] as int) + (stockResult['failed'] as int? ?? 0);
      (results['errors'] as List<String>).addAll((stockResult['errors'] as List?)?.cast<String>() ?? []);

      // Sync customers
      final customersResult = await _syncEntity('customers');
      results['synced'] = (results['synced'] as int) + (customersResult['synced'] as int? ?? 0);
      results['failed'] = (results['failed'] as int) + (customersResult['failed'] as int? ?? 0);
      (results['errors'] as List<String>).addAll((customersResult['errors'] as List?)?.cast<String>() ?? []);

      return {'success': true, ...results};
    } catch (e) {
      return {'success': false, 'message': 'Sync error: $e', ...results};
    } finally {
      _isSyncing = false;
      notifyListeners();
    }
  }

  Future<Map<String, dynamic>> _syncEntity(String entityType) async {
    final db = await DatabaseHelper.database;
    final results = <String, dynamic>{'synced': 0, 'failed': 0, 'errors': <String>[]};

    String tableName;
    switch (entityType) {
      case 'sales':
        tableName = 'sales';
        break;
      case 'stock':
        tableName = 'stock_inventory';
        break;
      case 'customers':
        tableName = 'customers';
        break;
      default:
        return results;
    }

    // Get pending records
    final records = await db.query(
      tableName,
      where: 'sync_status = ?',
      whereArgs: ['pending'],
      limit: 100,
    );

    if (records.isEmpty) {
      return results;
    }

    // Prepare records for API
    final apiRecords = <Map<String, dynamic>>[];
    for (var record in records) {
      final apiRecord = Map<String, dynamic>.from(record);
      
      // Remove local-only fields
      apiRecord.remove('id');
      apiRecord.remove('server_id');
      apiRecord.remove('sync_status');
      apiRecord.remove('created_at');
      apiRecord.remove('updated_at');

      // Generate local_id if missing
      if (apiRecord['local_id'] == null) {
        apiRecord['local_id'] = const Uuid().v4();
        await db.update(
          tableName,
          {'local_id': apiRecord['local_id']},
          where: 'id = ?',
          whereArgs: [record['id']],
        );
      }

      // Handle entity-specific data
      if (entityType == 'sales') {
        final items = await db.query(
          'sale_items',
          where: 'sale_id = ?',
          whereArgs: [record['id']],
        );
        apiRecord['items'] = items.map((item) {
          final itemCopy = Map<String, dynamic>.from(item);
          itemCopy.remove('id');
          itemCopy.remove('sale_id');
          return itemCopy;
        }).toList();
      }

      apiRecords.add(apiRecord);
    }

    // Send to server
    try {
      final baseURL = authService.getBaseURL();
      final token = authService.getToken();

      final response = await http.post(
        Uri.parse('$baseURL/api/sync.php'),
        headers: {
          'Content-Type': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'entity': entityType,
          'records': apiRecords,
          if (token != null) 'token': token,
        }),
      ).timeout(const Duration(seconds: 30));

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body) as Map<String, dynamic>;
        if (data['success'] == true) {
          final synced = data['synced'] as int? ?? 0;
          
          // Mark synced records
          for (int i = 0; i < synced && i < records.length; i++) {
            await db.update(
              tableName,
              {'sync_status': 'synced', 'updated_at': DateTime.now().toIso8601String()},
              where: 'id = ?',
              whereArgs: [records[i]['id']],
            );
          }

          results['synced'] = synced;
          results['failed'] = data['failed'] as int? ?? 0;
          (results['errors'] as List<String>).addAll((data['errors'] as List?)?.cast<String>() ?? []);
        } else {
        results['failed'] = records.length;
        (results['errors'] as List<String>).add(data['message'] as String? ?? 'Sync failed');
        }
      } else {
        results['failed'] = records.length;
        (results['errors'] as List<String>).add('Server error: ${response.statusCode}');
      }
    } catch (e) {
      results['failed'] = records.length;
      (results['errors'] as List<String>).add('Network error: $e');
    }

    return results;
  }

  Future<Map<String, dynamic>> downloadUpdates() async {
    try {
      final baseURL = authService.getBaseURL();
      final token = authService.getToken();
      final db = await DatabaseHelper.database;

      // Get last sync time - for first download, use a very old date to get all data
      final lastSyncResult = await db.query(
        'settings',
        where: 'key = ?',
        whereArgs: ['last_sync'],
      );
      
      // Check if we have any products - if not, download all data
      final productCheck = await db.rawQuery('SELECT COUNT(*) as count FROM products');
      final hasProducts = (productCheck.first['count'] as int) > 0;
      
      final lastSync = hasProducts && lastSyncResult.isNotEmpty
          ? lastSyncResult.first['value'] as String?
          : '1970-01-01 00:00:00'; // Get all data on first download

      print('Downloading data. Last sync: $lastSync, Has products: $hasProducts');
      
      final url = '$baseURL/api/download.php?last_sync=$lastSync${token != null ? '&token=$token' : ''}';
      print('Download URL: $url');
      
      final response = await http.get(
        Uri.parse(url),
        headers: {
          'Content-Type': 'application/json',
          if (token != null) 'Authorization': 'Bearer $token',
        },
      ).timeout(const Duration(seconds: 30));
      
      print('Response status: ${response.statusCode}');
      print('Response headers: ${response.headers}');

      if (response.statusCode == 200) {
        final responseBody = response.body;
        print('Download response body: ${responseBody.substring(0, responseBody.length > 500 ? 500 : responseBody.length)}');
        
        final responseData = jsonDecode(responseBody) as Map<String, dynamic>;
        print('Download response parsed. Keys: ${responseData.keys.toList()}');
        
        // The API returns data in a nested structure: {success: true, data: {products: [], stock: [], ...}}
        final data = responseData['data'] as Map<String, dynamic>?;
        
        if (data == null) {
          print('WARNING: No data field in response. Response keys: ${responseData.keys}');
          return {'success': false, 'message': 'No data field in response'};
        }
        
        print('Data keys: ${data.keys.toList()}');
        print('Products count: ${(data['products'] as List?)?.length ?? 0}');
        print('Stock count: ${(data['stock'] as List?)?.length ?? 0}');
        print('Customers count: ${(data['customers'] as List?)?.length ?? 0}');
        
        // Process downloaded data
        await _processDownloadedData(data, db);
        
        // Update last sync time
        await db.insert(
          'settings',
          {'key': 'last_sync', 'value': DateTime.now().toIso8601String()},
          conflictAlgorithm: ConflictAlgorithm.replace,
        );

        print('Download completed. Products: ${data['products']?.length ?? 0}, Stock: ${data['stock']?.length ?? 0}, Customers: ${data['customers']?.length ?? 0}');
        return {'success': true, 'data': data};
      } else {
        return {'success': false, 'message': 'Download failed: ${response.statusCode}'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Download error: $e'};
    }
  }

  Future<void> _processDownloadedData(Map<String, dynamic> data, Database db) async {
    // Process products
    if (data['products'] != null) {
      final products = data['products'] as List;
      print('Processing ${products.length} products...');
      for (var product in products) {
        final productMap = product as Map<String, dynamic>;
        // Map column names from API to database
        final mappedProduct = {
          'id': productMap['id'],
          'name': productMap['name'],
          'sku': productMap['sku'],
          'barcode': productMap['barcode'],
          'description': productMap['description'],
          'category_id': productMap['category_id'],
          'brand_id': productMap['brand_id'],
          'unit': productMap['unit'],
          'cost_price': productMap['cost_price'],
          'retail_price': productMap['retail_price'],
          'wholesale_price': productMap['wholesale_price'],
          'min_stock_level': productMap['min_stock_level'],
          'status': productMap['status'] ?? 'active',
          'server_id': productMap['id'], // Store server ID
          'server_synced': 1,
          'updated_at': productMap['updated_at'] ?? DateTime.now().toIso8601String(),
        };
        await db.insert(
          'products',
          mappedProduct,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      print('Products processed successfully');
    }

    // Process stock
    if (data['stock'] != null) {
      final stock = data['stock'] as List;
      print('Processing ${stock.length} stock items...');
      for (var item in stock) {
        final itemMap = item as Map<String, dynamic>;
        final mappedStock = {
          'id': itemMap['id'],
          'product_id': itemMap['product_id'],
          'branch_id': itemMap['branch_id'],
          'quantity': itemMap['quantity'],
          'unit': itemMap['unit'],
          'cost_price': itemMap['cost_price'],
          'batch_number': itemMap['batch_number'],
          'expiry_date': itemMap['expiry_date'],
          'supplier_id': itemMap['supplier_id'],
          'server_id': itemMap['id'],
          'sync_status': 'synced',
          'updated_at': itemMap['updated_at'] ?? DateTime.now().toIso8601String(),
        };
        await db.insert(
          'stock_inventory',
          mappedStock,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      print('Stock items processed successfully');
    }

    // Process customers
    if (data['customers'] != null) {
      final customers = data['customers'] as List;
      print('Processing ${customers.length} customers...');
      for (var customer in customers) {
        final customerMap = customer as Map<String, dynamic>;
        final mappedCustomer = {
          'id': customerMap['id'],
          'name': customerMap['name'],
          'email': customerMap['email'],
          'phone': customerMap['phone'],
          'address': customerMap['address'],
          'loyalty_points': customerMap['loyalty_points'] ?? 0,
          'credit_limit': customerMap['credit_limit'] ?? 0,
          'server_id': customerMap['id'],
          'sync_status': 'synced',
          'updated_at': customerMap['updated_at'] ?? DateTime.now().toIso8601String(),
        };
        await db.insert(
          'customers',
          mappedCustomer,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );
      }
      print('Customers processed successfully');
    }

    // Process sales
    if (data['sales'] != null) {
      final sales = data['sales'] as List;
      for (var sale in sales) {
        final saleMap = sale as Map<String, dynamic>;
        final saleId = await db.insert(
          'sales',
          saleMap,
          conflictAlgorithm: ConflictAlgorithm.replace,
        );

        // Process sale items
        if (saleMap['items'] != null) {
          final items = saleMap['items'] as List;
          for (var item in items) {
            final itemMap = item as Map<String, dynamic>;
            itemMap['sale_id'] = saleId;
            await db.insert(
              'sale_items',
              itemMap,
              conflictAlgorithm: ConflictAlgorithm.replace,
            );
          }
        }
      }
    }
  }

  bool get isOnline => _isOnline;
  bool get isSyncing => _isSyncing;
}

