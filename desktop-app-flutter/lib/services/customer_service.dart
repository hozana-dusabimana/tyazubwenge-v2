import 'package:uuid/uuid.dart';
import '../database/db_helper.dart';
import '../models/customer.dart';

class CustomerService {
  static Future<List<Customer>> getCustomers() async {
    final db = await DatabaseHelper.database;
    final results = await db.query(
      'customers',
      orderBy: 'name',
    );
    
    return results.map((map) => Customer.fromMap(map)).toList();
  }

  static Future<int> addCustomer(Customer customer) async {
    final db = await DatabaseHelper.database;
    final customerMap = customer.toMap();
    
    if (customerMap['local_id'] == null) {
      customerMap['local_id'] = const Uuid().v4();
    }
    customerMap['sync_status'] = 'pending';
    customerMap['created_at'] = DateTime.now().toIso8601String();
    customerMap['updated_at'] = DateTime.now().toIso8601String();
    
    return await db.insert('customers', customerMap);
  }

  static Future<void> updateCustomer(Customer customer) async {
    final db = await DatabaseHelper.database;
    final customerMap = customer.toMap();
    customerMap['updated_at'] = DateTime.now().toIso8601String();
    
    await db.update(
      'customers',
      customerMap,
      where: 'id = ?',
      whereArgs: [customer.id],
    );
  }

  static Future<void> deleteCustomer(int id) async {
    final db = await DatabaseHelper.database;
    await db.delete(
      'customers',
      where: 'id = ?',
      whereArgs: [id],
    );
  }
}


