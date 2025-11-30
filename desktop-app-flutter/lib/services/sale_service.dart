import 'package:uuid/uuid.dart';
import '../database/db_helper.dart';
import '../models/sale.dart';

class SaleService {
  static Future<List<Sale>> getSales({int limit = 50}) async {
    final db = await DatabaseHelper.database;
    final results = await db.query(
      'sales',
      orderBy: 'created_at DESC',
      limit: limit,
    );
    
    final sales = results.map((map) => Sale.fromMap(map)).toList();
    
    // Load items for each sale
    for (var sale in sales) {
      if (sale.id != null) {
        final items = await getSaleItems(sale.id!);
        sales[sales.indexOf(sale)] = Sale(
          id: sale.id,
          localId: sale.localId,
          serverId: sale.serverId,
          invoiceNumber: sale.invoiceNumber,
          customerId: sale.customerId,
          branchId: sale.branchId,
          userId: sale.userId,
          totalAmount: sale.totalAmount,
          discount: sale.discount,
          tax: sale.tax,
          finalAmount: sale.finalAmount,
          paymentMethod: sale.paymentMethod,
          paymentStatus: sale.paymentStatus,
          saleType: sale.saleType,
          notes: sale.notes,
          syncStatus: sale.syncStatus,
          createdAt: sale.createdAt,
          updatedAt: sale.updatedAt,
          items: items,
        );
      }
    }
    
    return sales;
  }

  static Future<List<SaleItem>> getSaleItems(int saleId) async {
    final db = await DatabaseHelper.database;
    final results = await db.query(
      'sale_items',
      where: 'sale_id = ?',
      whereArgs: [saleId],
    );
    
    return results.map((map) => SaleItem.fromMap(map)).toList();
  }

  static Future<int> createSale(Sale sale) async {
    final db = await DatabaseHelper.database;
    final saleMap = sale.toMap();
    
    if (saleMap['local_id'] == null) {
      saleMap['local_id'] = const Uuid().v4();
    }
    saleMap['sync_status'] = 'pending';
    saleMap['created_at'] = DateTime.now().toIso8601String();
    saleMap['updated_at'] = DateTime.now().toIso8601String();
    
    final saleId = await db.insert('sales', saleMap);
    
    // Insert sale items
    if (sale.items != null && sale.items!.isNotEmpty) {
      for (var item in sale.items!) {
        final itemMap = item.toMap();
        itemMap['sale_id'] = saleId;
        await db.insert('sale_items', itemMap);
      }
    }
    
    return saleId;
  }
}

