import 'package:uuid/uuid.dart';
import '../database/db_helper.dart';
import '../models/stock.dart';

class StockService {
  static Future<List<StockItem>> getStockItems() async {
    final db = await DatabaseHelper.database;
    final results = await db.rawQuery('''
      SELECT si.*, p.name as product_name, p.sku, p.unit as product_unit
      FROM stock_inventory si
      JOIN products p ON si.product_id = p.id
      ORDER BY si.updated_at DESC
    ''');
    
    return results.map((map) => StockItem.fromMap(map)).toList();
  }

  static Future<int> addStock(StockItem stock) async {
    final db = await DatabaseHelper.database;
    final stockMap = stock.toMap();
    
    if (stockMap['local_id'] == null) {
      stockMap['local_id'] = const Uuid().v4();
    }
    stockMap['sync_status'] = 'pending';
    stockMap['created_at'] = DateTime.now().toIso8601String();
    stockMap['updated_at'] = DateTime.now().toIso8601String();
    
    return await db.insert('stock_inventory', stockMap);
  }

  static Future<void> updateStock(StockItem stock) async {
    final db = await DatabaseHelper.database;
    final stockMap = stock.toMap();
    stockMap['updated_at'] = DateTime.now().toIso8601String();
    
    await db.update(
      'stock_inventory',
      stockMap,
      where: 'id = ?',
      whereArgs: [stock.id],
    );
  }

  static Future<void> deleteStock(int id) async {
    final db = await DatabaseHelper.database;
    await db.delete(
      'stock_inventory',
      where: 'id = ?',
      whereArgs: [id],
    );
  }
}

