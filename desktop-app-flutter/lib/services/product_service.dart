import '../database/db_helper.dart';
import '../models/product.dart';

class ProductService {
  static Future<List<Product>> getProducts({String? search}) async {
    final db = await DatabaseHelper.database;
    
    // First check total count
    final countResult = await db.rawQuery('SELECT COUNT(*) as count FROM products');
    final totalCount = countResult.first['count'] as int;
    print('Total products in database: $totalCount');
    
    String query = "SELECT * FROM products WHERE status = 'active'";
    List<dynamic> args = [];
    
    if (search != null && search.isNotEmpty) {
      query += " AND (name LIKE ? OR sku LIKE ?)";
      args.add('%$search%');
      args.add('%$search%');
    }
    
    query += " ORDER BY name";
    
    final results = await db.rawQuery(query, args);
    print('Products query returned ${results.length} results');
    return results.map((map) => Product.fromMap(map)).toList();
  }

  static Future<Product?> getProductById(int id) async {
    final db = await DatabaseHelper.database;
    final results = await db.query(
      'products',
      where: 'id = ?',
      whereArgs: [id],
    );
    
    if (results.isEmpty) return null;
    return Product.fromMap(results.first);
  }
}

