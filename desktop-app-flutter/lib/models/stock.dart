class StockItem {
  final int? id;
  final int productId;
  final int? branchId;
  final double quantity;
  final String? unit;
  final double? costPrice;
  final String? batchNumber;
  final String? expiryDate;
  final int? supplierId;
  final String? localId;
  final int? serverId;
  final String syncStatus;
  final String? createdAt;
  final String? updatedAt;
  final String? productName;
  final String? productSku;

  StockItem({
    this.id,
    required this.productId,
    this.branchId,
    required this.quantity,
    this.unit,
    this.costPrice,
    this.batchNumber,
    this.expiryDate,
    this.supplierId,
    this.localId,
    this.serverId,
    this.syncStatus = 'pending',
    this.createdAt,
    this.updatedAt,
    this.productName,
    this.productSku,
  });

  Map<String, dynamic> toMap() {
    return {
      'id': id,
      'product_id': productId,
      'branch_id': branchId,
      'quantity': quantity,
      'unit': unit,
      'cost_price': costPrice,
      'batch_number': batchNumber,
      'expiry_date': expiryDate,
      'supplier_id': supplierId,
      'local_id': localId,
      'server_id': serverId,
      'sync_status': syncStatus,
      'created_at': createdAt,
      'updated_at': updatedAt,
    };
  }

  factory StockItem.fromMap(Map<String, dynamic> map) {
    return StockItem(
      id: map['id'] as int?,
      productId: map['product_id'] as int,
      branchId: map['branch_id'] as int?,
      quantity: (map['quantity'] as num).toDouble(),
      unit: map['unit'] as String?,
      costPrice: (map['cost_price'] as num?)?.toDouble(),
      batchNumber: map['batch_number'] as String?,
      expiryDate: map['expiry_date'] as String?,
      supplierId: map['supplier_id'] as int?,
      localId: map['local_id'] as String?,
      serverId: map['server_id'] as int?,
      syncStatus: map['sync_status'] as String? ?? 'pending',
      createdAt: map['created_at'] as String?,
      updatedAt: map['updated_at'] as String?,
      productName: map['product_name'] as String?,
      productSku: map['sku'] as String?,
    );
  }
}


